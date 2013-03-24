<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class mod_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->check_login();
		if($this->_user['groupid'] > 5) {
			$this->message('对不起，您没有权限访问此板块。');
		}
		
		// 加载精华积分策略
		$this->conf += $this->kv->xget('conf_ext');
	}
	
	public function on_index() {
		$this->on_setforum();
	}
	
	// 设置置顶 各种置顶最多十个！
	public function on_top() {
		$this->_title[] = '设置置顶';
		$this->_nav[] = '设置置顶';
		
		$this->check_login();
		
		$fid = intval(core::gpc('fid'));
		$tidarr = $this->get_tidarr();
		
		$forum = $this->forum->read($fid);

		$this->check_access($forum, 'top');
		
		if(!$this->form_submit()) {
			
			// 初始化控件状态
			$this->init_view_thread($tidarr, 'top');
			
			$this->view->assign('fid', $fid);
			
			$this->view->display('mod_top_ajax.htm');
		} else {
			$rank = intval(core::gpc('rank', 'P'));
			$systempm = intval(core::gpc('systempm', 'P'));
			$comment = core::gpc('comment', 'P');
			$this->check_comment($comment);
			
			if($this->_user['groupid'] == 3 && $rank != 1) {
				$this->message('您只能对帖子进行板块置顶！', 0);
			}
			if($this->_user['groupid'] == 2 && $rank == 3) {
				$this->message('您不能对帖子进行全站置顶！', 0);
			}
			
			// -------> 统计 top_1 2 3 的总数，是否超过5个。
			$n = count($tidarr);
			if($rank == 1) {
				// 1 级置顶
				$keys = array();
				$this->tidkeys_to_keys($keys, $forum['toptids']);
				if(count($keys) + $n > 8) {
					$this->message('一级置顶的个数不能超过8个。', 0);
				}
			} elseif($rank == 3) {
				$keys = array();
				$this->tidkeys_to_keys($keys, $this->conf['toptids']);
				if(count($keys) + $n > 8) {
					$this->message('三级置顶的个数不能超过8个。', 0);
				}
			}
			// end
			
			// hook mod_top_after.php
			
			// 先去除已有，然后加入
			$this->thread_top->delete_top_1($forum, $tidarr);
			$this->thread_top->delete_top_3($forum, $tidarr);
			
			if($rank == 0) {
				
			} elseif($rank == 1) {
				$this->thread_top->add_top_1($forum, $tidarr);
			} elseif($rank == 3) {
				$this->thread_top->add_top_3($forum, $tidarr);
			}
			
			// 记录到版主操作日志
			foreach($tidarr as &$v) {			// 此处也得用 &
				// 初始化数据
				list($fid, $tid) = explode('-', $v);
				//$fid = intval($fid);
				$tid = intval($tid);
				
				$thread = $this->thread->read($fid, $tid);
				if(empty($thread)) continue;
				$this->modlog->create(array(
					'uid'=>$this->_user['uid'],
					'username'=>$this->_user['username'],
					'fid'=>$fid,
					'dateline'=>$_SERVER['time'],
					'tid'=>$tid,
					'pid'=>0,
					'subject'=>$thread['subject'],
					'credits'=>0,
					'golds'=>0,
					'action'=>$rank == 0 ? 'untop' : 'top',
					'comment'=>$comment,
				));
				
				$this->inc_modnum($fid, $tid);
				
				// 发送系统消息：
				if($systempm) {
					$pmsubject = utf8::substr($thread['subject'], 0, 32);
					$pmmessage = "您的主题<a href=\"?thread-index-fid-$fid-tid-$tid.htm\" target=\"_blank\">【{$pmsubject}】</a>被版主【{$this->_user['username']}】".($rank > 0 ? '置顶' : '取消置顶')."。";
					$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
				}
			}
			
			// hook mod_top_succeed.php
			
			$this->message('操作成功！', 1);
		}
		
	}
	
	public function on_digest() {
		$this->_title[] = '设置精华';
		$this->_nav[] = '设置精华';
		
		$this->check_login();
		
		$fid = intval(core::gpc('fid'));
		$tidarr = $this->get_tidarr();
		
		$forum = $this->forum->read($fid);
		$this->check_forum_exists($forum);
		
		$this->check_access($forum, 'digest');
		
		if(!$this->form_submit()) {
			
			// 第一个元素作为选中状态
			$fid_tid = array_shift($tidarr);
			list($fid, $tid) = explode('-', $fid_tid);
			$thread = $this->thread->read($fid, $tid);
			$this->check_thread_exists($thread);
			
			$this->view->assign('thread', $thread);
			$this->view->assign('fid', $fid);
			$this->view->assign('tid', $tid);
			
			// hook mod_digest_before.php
			$this->view->display('mod_digest_ajax.htm');
		} else {
			// 修改精华等级，分类。
			$rank = intval(core::gpc('rank', 'P'));
			$systempm = intval(core::gpc('systempm', 'P'));
			$comment = core::gpc('comment', 'P');
			$this->check_comment($comment);
			
			$fidarr = $creditarr = $goldarr = $digestarr = array();
			
			// hook mod_digest_after.php
			$tidnum = 0;
			foreach($tidarr as &$v) {			// 此处也得用 &
				// 初始化数据
				list($fid, $tid) = explode('-', $v);
				//$fid = intval($fid);
				$tid = intval($tid);
				$thread = $this->thread->read($fid, $tid);
				if(empty($thread)) continue;
				
				$tidnum++;	// 帖子数，用来统计精华数
				
				// 更新论坛精华数 todo: 准确？ 没啥用
				$forum = $this->forum->read($fid);
				$rank == 0 ? ($thread['digest'] && $forum['digests']--) : (!$thread['digest'] && $forum['digests']++);
				$this->forum->update($forum);
				$fidarr[$fid] = $fid;
				
				// 更新用户精华数，积分
				!isset($creditarr[$thread['uid']]) && $creditarr[$thread['uid']] = 0;
				!isset($goldarr[$thread['uid']]) && $goldarr[$thread['uid']] = 0;
				!isset($digestarr[$thread['uid']]) && $digestarr[$thread['uid']] = 0;
				// 先减去积分，否则会造成重复加分
				if($thread['digest'] > 0) {
					$creditarr[$thread['uid']] -= $this->conf['credits_policy_digest_'.$thread['digest']];
					$goldarr[$thread['uid']] -= $this->conf['golds_policy_digest_'.$thread['digest']];
				}
				if($rank > 0) {
					$creditarr[$thread['uid']] += $this->conf['credits_policy_digest_'.$rank];
					$goldarr[$thread['uid']] += $this->conf['golds_policy_digest_'.$rank];
				}
				if($rank > 0 && $thread['digest'] == 0) {
					$digestarr[$thread['uid']]++;
				} elseif($rank < 0 && $thread['digest'] > 0) {
					$digestarr[$thread['uid']]--;
				}
				
				// 记录到版主操作日志
				$credits2 = $rank == 0 ? 0 - $this->conf['credits_policy_digest_'.$thread['digest']] : $this->conf['credits_policy_digest_'.$rank];
				$golds2 = $rank == 0 ? 0 - $this->conf['golds_policy_digest_'.$thread['digest']] : $this->conf['golds_policy_digest_'.$rank];
				$this->modlog->create(array(
					'uid'=>$this->_user['uid'],
					'username'=>$this->_user['username'],
					'fid'=>$fid,
					'tid'=>$tid,
					'pid'=>0,
					'subject'=>$thread['subject'],
					'credits'=> $credits2,
					'golds'=> $golds2,
					'dateline'=>$_SERVER['time'],
					'action'=>$rank == 0 ? 'undigest' : 'digest',
					'comment'=>$comment,
				));
				
				$thread['digest'] = $rank;
				
				$this->thread->update($thread);
				
				$this->inc_modnum($fid, $tid);
				
				// 发送系统消息：
				if($systempm) {
					$pmsubject = utf8::substr($thread['subject'], 0, 32);
					$pmmessage = "您的主题<a href=\"?thread-index-fid-$fid-tid-$tid.htm\" target=\"_blank\">【{$pmsubject}】</a>被版主【{$this->_user['username']}】".($rank > 0 ? '设置精华' : '取消精华')."。";
					$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
				}
				
				// hook mod_digest_loop_after.php
			}
			
			foreach($fidarr as $fid) {
				$this->forum->clear_cache($fid);
			}
			
			foreach($creditarr as $uid=>$credits) {
				$uid = intval($uid);
				$user = $this->user->read($uid);
				$user['credits'] += $credits;
				$user['golds'] += $goldarr[$uid];
				$user['digests'] += $digestarr[$uid];
				$this->user->update($user);
			}
			
			// hook mod_digest_succeed.php
			$this->message('操作成功！');
		}
	}
	
	// 批量设置主题分类
	public function on_type() {
		$this->_title[] = '设置主题分类';
		$this->_nav[] = '设置主题分类';
		
		$this->check_login();
		
		$fid = intval(core::gpc('fid'));
		$tidarr = $this->get_tidarr();
		
		$forum = $this->mcache->read('forum', $fid);
		
		if(!array_filter($forum['typecates'])) {
			$this->message('当前版块未开启主题分类。', 0);
		}
		
		if(!$this->form_submit()) {
			
			// 初始化控件状态
			$fidtid = array_pop($tidarr);
			list($fid, $tid) = explode('-', $fidtid);
			$thread = $this->thread->read(intval($fid), intval($tid));
			$this->check_thread_exists($thread);
			$typeid1 = $thread['typeid1'];
			$typeid2 = $thread['typeid2'];
			$typeid3 = $thread['typeid3'];
			$typeid4 = $thread['typeid4'];
			
			$this->init_view_thread($tidarr, 'type');
			$this->init_type_select($forum, $typeid1, $typeid2, $typeid3, $typeid4);
			
			$this->view->assign('fid', $fid);
			
			// hook mod_type_before.php
			$this->view->display('mod_type_ajax.htm');
		} else {
			// 修改精华等级，分类。
			$typeid1 = intval(core::gpc('typeid1', 'P'));
			$typeid2 = intval(core::gpc('typeid2', 'P'));
			$typeid3 = intval(core::gpc('typeid3', 'P'));
			$typeid4 = intval(core::gpc('typeid4', 'P'));
			$systempm = intval(core::gpc('systempm', 'P'));
			$comment = core::gpc('comment', 'P');
			$this->check_comment($comment);
			
			// hook mod_type_after.php
			foreach($tidarr as &$v) {			// 此处也得用 &
				// 初始化数据
				list($fid, $tid) = explode('-', $v);
				$fid = intval($fid);
				$tid = intval($tid);
				
				// 过滤非本板块的主题分类
				if($fid != intval(core::gpc('fid'))) continue;
				
				$thread = $this->thread->read($fid, $tid);
				if(empty($thread)) continue;
				
				$this->thread_type_data->xupdate($fid, $tid, $typeid1, $typeid2, $typeid3, $typeid4);
				
				$thread['typeid1'] = $typeid1;
				$thread['typeid2'] = $typeid2;
				$thread['typeid3'] = $typeid3;
				$thread['typeid4'] = $typeid4;
				$this->thread->update($thread);
				
				// 记录到版主操作日志
				$this->modlog->create(array(
					'uid'=>$this->_user['uid'],
					'username'=>$this->_user['username'],
					'fid'=>$fid,
					'tid'=>$tid,
					'pid'=>0,
					'subject'=>$thread['subject'],
					'credits'=>0,
					'golds'=>0,
					'action'=>'type',
					'comment'=>$comment,
				));
				
				$this->inc_modnum($fid, $tid);
				
				// 发送系统消息：
				if($systempm) {
					$pmsubject = utf8::substr($thread['subject'], 0, 32);
					$pmmessage = "您的主题<a href=\"?thread-index-fid-$fid-tid-$tid.htm\" target=\"_blank\">【{$pmsubject}】</a>被版主【{$this->_user['username']}】".($typeid1 > 0 ? "设置主题分类" : "取消主题分类")."。";
					$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
				}
			}
			
			$this->message('操作成功！');
		}
	}
	
	// 所有 fid 相关表都需要更新，板块的统计数也需要更新。
	public function on_move() {
		$this->_title[] = '移动主题';
		$this->_nav[] = '移动主题';
		
		$this->check_login();
		
		$fid = intval(core::gpc('fid'));
		$tidarr = $this->get_tidarr();
		
		$forum = $this->forum->read($fid);

		$this->check_access($forum, 'move');
		
		if(!$this->form_submit()) {
			
			$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
			$this->view->assign('forumoptions', $forumoptions);
			
			$this->view->assign('forum', $forum);
			$this->view->assign('fid', $fid);
			
			// hook mod_move_before.php
			$this->view->display('mod_move_ajax.htm');
		} else {
			
			$systempm = intval(core::gpc('systempm', 'P'));
			$comment = core::gpc('comment', 'P');
			$this->check_comment($comment);
			
			// 目标论坛的发帖权限
			$fid2 = intval(core::gpc('fid2', 'P'));
			$forum2 = $this->forum->read($fid2);
			$this->check_forum_exists($forum2);
			$this->check_access($forum2, 'post');
			if($fid == $fid2) {
				$this->message('请选择其他板块。', 0);
			}
			
			// hook mod_move_after.php
			
			foreach($tidarr as $v) {
				list($fid, $tid) = explode('-', $v);
				$tid = intval($tid);
				$thread = $this->thread->read($fid, $tid);
				if(empty($thread)) continue;
				if($thread['top'] > 0) {
					$this->message('您选择的主题中包含置顶主题，请先取消置顶再进行移动。', 0);
				}
			}
			
			// 查找主题。更新 fid
			$tidnum = $pidnum = 0;
			foreach($tidarr as $v) {
				list($fid, $tid) = explode('-', $v);
				$tid = intval($tid);
				$thread = $this->thread->read($fid, $tid);
				if(empty($thread)) continue;
				$tidnum++;	// 帖子数
				
				// ----------->更新相关数据的 fid start
				
				// 主题分类，从原来的主题分类中清除
				if($thread['typeid1'] > 0 ||$thread['typeid2'] > 0 ||$thread['typeid3'] > 0 ||$thread['typeid4'] > 0) {
					$this->thread_type_data->xdelete($fid, $tid);
				}
				$thread['typeid1'] = 0;
				$thread['typeid2'] = 0;
				$thread['typeid3'] = 0;
				$thread['typeid4'] = 0;
				$this->thread->update($thread);
				
				$this->thread->index_update(array('fid'=>$fid, 'tid'=>$tid), array('fid'=>$fid2));
				$this->post->index_update(array('fid'=>$fid, 'tid'=>$tid), array('fid'=>$fid2));
				$this->attach->index_update(array('fid'=>$fid, 'tid'=>$tid), array('fid'=>$fid2));
				$this->mypost->index_update(array('fid'=>$fid, 'tid'=>$tid), array('fid'=>$fid2));
				$this->modlog->index_update(array('fid'=>$fid, 'tid'=>$tid), array('fid'=>$fid2));
				
				// ----------->更新相关数据的 fid end
				
				$pidnum += $thread['posts'];
				
				// 记录到版主操作日志
				$this->modlog->create(array(
					'uid'=>$this->_user['uid'],
					'username'=>$this->_user['username'],
					'fid'=>$fid2,
					'tid'=>$tid,
					'pid'=>0,
					'subject'=>$thread['subject'],
					'credits'=>0,
					'golds'=>0,
					'action'=>'move',
					'comment'=>$comment,
				));
				
				$this->inc_modnum($fid2, $tid);
				
				// 发送系统消息：
				if($systempm) {
					$pmsubject = utf8::substr($thread['subject'], 0, 32);
					$pmmessage = "您的主题<a href=\"?thread-index-fid-$fid2-tid-$tid.htm\" target=\"_blank\">【{$pmsubject}】</a>被版主【{$this->_user['username']}】移动到了【{$forum2['name']}】。";
					$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
				}
				
				// hook mod_move_loop_after.php
			}
			
			// 更新板块主题数，回复数
			$forum['threads'] -= $tidnum;
			$forum2['threads'] += $tidnum;
			$forum['posts'] -= $pidnum;
			$forum2['posts'] += $pidnum;
			
			$this->forum->update($forum);
			$this->forum->update($forum2);
			
			// 更新缓存
			$this->forum->clear_cache($fid, TRUE);
			$this->forum->clear_cache($fid2, TRUE);
			
			// hook mod_move_succeed.php
			$this->message("操作成功！", 1, '?forum-index-fid-$fid2.htm');
		}
	}
	
	public function on_rate() {
		$this->_title[] = '版主评分';
		$this->_nav[] = '版主评分';
		
		$fid = intval(core::gpc('fid'));
		$pid = intval(core::gpc('pid'));
		$uid = $this->_user['uid'];
		
		// 权限检测
		$forum = $this->forum->read($fid);
		if(!$this->is_mod($forum, $this->_user)) {
			$this->message('您没有权限管理该板块！');
		}
		
		$post = $this->post->read($fid, $pid);
		$this->check_post_exists($post);
		
		$thread = $this->thread->read($fid, $post['tid']);
		$this->check_thread_exists($thread);
		
		$tid = $thread['tid'];
		
		// 剩余积分
		$user = $this->user->read($uid);
		$group = $this->group->read($user['groupid']);
		list($credits, $golds) = $this->rate->get_today_credits_golds($uid);
		$remain_credits = $group['maxcredits'] - $credits;
		$remain_golds = $group['maxgolds'] - $golds;
		
		// 每日一个斑竹只能对一个帖子的评分只记录一条，后面的覆盖前面。
		$rate = $this->rate->get_today_rate_by_fid_pid_uid($fid, $pid, $uid);
		
		if(!$this->form_submit()) {
			
			$this->view->assign('remain_credits', $remain_credits);
			$this->view->assign('remain_golds', $remain_golds);
			$this->view->assign('fid', $fid);
			$this->view->assign('pid', $pid);
			$this->view->assign('rate', $rate);
			$this->view->display('mod_rate_ajax.htm');
		} else {
			
			// 取消评分
			$delete = core::gpc('delete', 'P');
			if($delete) {
				if(!empty($rate)) {
					
					// 更新用户积分！
					$user = $this->user->read($post['uid']);
					// 还原积分
					$user['credits'] -= $rate['credits'];
					$user['golds'] -= $rate['golds'];
					$this->user->update($user);
					
					$post['rates']--;
					$this->post->update($post);
					$this->rate->delete($rate['rateid']);	// 只能删除今日自己对该楼的，不用判断权限。
				
				
					// 发送系统消息：
					$pmsubject = utf8::substr($thread['subject'], 0, 32);
					$credits_html = $rate['credits'] > 0 ? '-'.$rate['credits'] : -$rate['credits'];
					$golds_html = $rate['golds'] > 0 ? '-'.$rate['golds'] : -$rate['golds'];
					$pmmessage = "您的帖子<a href=\"?thread-index-fid-$fid-tid-$tid-page-$post[page].htm\" target=\"_blank\">【{$pmsubject}】</a>被版主【{$this->_user['username']}】取消了评分，积分：{$credits_html}，金币{$golds_html}。";
					$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
				}
				$this->message('取消评分完毕。');
			}
			
			$credits = intval(core::gpc('credits', 'P'));
			$golds = intval(core::gpc('golds', 'P'));
			$comment = core::gpc('comment', 'P');
			$this->check_comment($comment);
			
			// 判断积分是否足够
			if($credits > 0 && $credits > $remain_credits) {
				$this->message("本次评价积分不够！需要积分：$n, 剩余积分：$remain_credits", 0);
			}
			if($golds > 0 && $golds > $remain_golds) {
				$this->message("本次评价金币不够！需要金币：$n, 剩余金币：$remain_golds", 0);
			}
			
			/*
			if(empty($credits) && empty($golds)) {
				$this->message("请选择评价的积分或金币。", 0);
			}*/
			
			// 如果已经评价过，则返回差值，更新记录
			if(!empty($rate)) {
				// 更新用户积分！
				$user = $this->user->read($post['uid']);
				// 先还原积分
				$user['credits'] -= $rate['credits'];
				$user['golds'] -= $rate['golds'];
				
				// 再设置积分
				$user['credits'] += $credits;
				$user['golds'] += $golds;
				$this->user->update($user);
				
				// 积分差额自动返回（剩余金额是计算出来的，所以不存在返还）。
				$rate['credits'] = $credits;
				$rate['golds'] = $golds;
				$rate['comment'] = $comment;
				$this->rate->update($rate);
				
			} else {
				
				# 版主评分日志，针对每一楼，实际上也可以是任意用户评分
				$this->rate->create(array(
					'uid'=>$this->_user['uid'],
					'username'=>$this->_user['username'],
					'fid'=>$fid,
					'tid'=>$post['tid'],
					'pid'=>$pid,
					'credits'=>$credits,
					'dateline'=>$_SERVER['time'],
					'ymd'=>date('Ymd', $_SERVER['time']),
					'comment'=>$comment,
					'comment'=>$comment,
				));

				$post['rates']++;
				$this->post->update($post);
				
				// 更新用户积分！
				$user = $this->user->read($post['uid']);
				$user['credits'] += $credits;
				$user['golds'] += $golds;
				$this->user->update($user);
			}
			
			// 发送系统消息：
			$pmsubject = utf8::substr($thread['subject'], 0, 32);
			$credits_html = $credits > 0 ? '+'.$credits : $credits;
			$golds_html = $golds > 0 ? '+'.$golds : $golds;
			$pmmessage = "您的帖子<a href=\"?thread-index-fid-$fid-tid-$post[tid]-page-$post[page].htm\" target=\"_blank\">【{$pmsubject}】</a>被版主【{$this->_user['username']}】评分，积分：{$credits_html}，金币{$golds_html}。";
			$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
			
			$this->message('操作成功！', 1);
		}
	}
	
	public function on_delete() {
		$this->_title[] = '删除主题';
		$this->_nav[] = '删除主题';
		
		$this->check_login();
		
		$fid = intval(core::gpc('fid'));
		$tidarr = $this->get_tidarr();
		
		$forum = $this->forum->read($fid);
		
		$this->check_access($forum, 'delete');
		
		if(!$this->form_submit()) {
			
			$this->view->assign('fid', $fid);
			
			// hook mod_delete_before.php
			$this->view->display('mod_delete_ajax.htm');
		} else {
			
			$systempm = intval(core::gpc('systempm', 'P'));
			$comment = core::gpc('comment', 'P');
			$this->check_comment($comment);
			
			// hook mod_delete_after.php
			foreach($tidarr as $v) {
				list($fid, $tid) = explode('-', $v);
				//$fid = intval($fid);
				$tid = intval($tid);
				
				// 记录到版主操作日志
				$thread = $this->thread->read($fid, $tid);
				$this->modlog->create(array(
					'uid'=>$this->_user['uid'],
					'username'=>$this->_user['username'],
					'fid'=>$fid,
					'tid'=>$tid,
					'pid'=>0,
					'subject'=>$thread['subject'],
					'credits'=>0,
					'golds'=>0,
					'action'=>'delete',
					'comment'=>$comment,
				));
				
				// hook mod_delete_loop_after.php
				
				// 发送系统消息：
				if($systempm) {
					$pmsubject = utf8::substr($thread['subject'], 0, 32);
					$pmmessage = "您的帖子【{$pmsubject}】被版主【{$this->_user['username']}】删除。";
					$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
				}
				
				$this->thread->xdelete($fid, $tid, TRUE);
			}
			
			// hook mod_delete_succeed.php
			$this->message('操作成功！');
		}
	}
	
	// copy from thread_control.class.php
	private function tidkeys_to_keys(&$keys, $tidkeys) {
		if($tidkeys) {
			$fidtidlist = explode(' ', trim($tidkeys));
			foreach($fidtidlist as $fidtid) {
				list($fid, $tid) = explode('-', $fidtid);
				$tid && $keys[] = "thread-fid-$fid-tid-$tid";
			}
		}
	}
	
	// 截取前几个字符串，分隔符为
	private function substr_by_sep($string, $sep, $n) {
		$arr = explode($sep, $string);
		$arr2 = array_slice($arr, 0, $n);
		return implode($sep, $arr2);
	}
	
	// 传递 tid
	private function get_tidarr() {
		$tidarr = (array)core::gpc('tidarr');
		$tidsurl = '';
		foreach($tidarr as $fid_tid) {$tidsurl .= '&tidarr[]='.$fid_tid;}
		$threads = count($tidarr);
		$this->view->assign('tidsurl', $tidsurl);
		$this->view->assign('threads', $threads);
		return $tidarr;
	}
	
	// 增加版主操作次数
	private function inc_modnum($fid, $tid) {
		$thread = $this->thread->read($fid, $tid);
		$thread['modnum']++;
		$this->thread->update($thread);
	}

	// 初始化控件的初始值。
	private function init_view_thread($tidarr, $action = '') {
		$thread = $modlog = array();
		foreach($tidarr as &$v) {
			list($fid, $tid) = explode('-', $v);
			$tid = intval($tid);
			$thread = $this->thread->read($fid, $tid);
			break;
		}
		$this->view->assign('thread', $thread);
		
		// modlog, 最后一次的操作
		if(!empty($thread)) {
			$modloglist = $this->modlog->get_list_by_fid_tid($thread['fid'], $thread['tid']);
			foreach($modloglist as &$modlog) {
				if($modlog['action'] == $action) {
					break;
				}
			}
			$this->view->assign('modlog', $modlog);
		}
	}
	
	private function check_comment(&$comment) {
		core::htmlspecialchars($comment);
		if(utf8::strlen($comment) > 64) {
			$this->message('评价不能超过64个字符！', 0);
		}
	}
	
	// copy from post_control.class.php
	private function init_type_select($forum, $typeid1 = 0, $typeid2 = 0, $typeid3 = 0) {
		$typearr1 = empty($forum['types'][1]) ? array() : array('0'=>'&gt;'.$forum['typecates'][1]) + (array)$forum['types'][1];
		$typearr2 = empty($forum['types'][2]) ? array() : array('0'=>'&gt;'.$forum['typecates'][2]) + (array)$forum['types'][2];
		$typearr3 = empty($forum['types'][3]) ? array() : array('0'=>'&gt;'.$forum['typecates'][3]) + (array)$forum['types'][3];
		$typearr4 = empty($forum['types'][4]) ? array() : array('0'=>'&gt;'.$forum['typecates'][4]) + (array)$forum['types'][4];
		$typeselect1 = $typearr1 && !empty($forum['typecates'][1]) ? form::get_select('typeid1', $typearr1, $typeid1, '') : '';
		$typeselect2 = $typearr2 && !empty($forum['typecates'][2]) ? form::get_select('typeid2', $typearr2, $typeid2, '') : '';
		$typeselect3 = $typearr3 && !empty($forum['typecates'][3]) ? form::get_select('typeid3', $typearr3, $typeid3, '') : '';
		$typeselect4 = $typearr4 && !empty($forum['typecates'][4]) ? form::get_select('typeid4', $typearr4, $typeid4, '') : '';
		$this->view->assign('typeselect1', $typeselect1);
		$this->view->assign('typeselect2', $typeselect2);
		$this->view->assign('typeselect3', $typeselect3);
		$this->view->assign('typeselect4', $typeselect4);
	}
	
	//hook mod_control_after.php
}

?>