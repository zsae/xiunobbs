<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class reply_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['bbs'] = ' class="checked"';
		
		// post_update_expiry
		$this->conf += $this->kv->xget('conf_ext');
	}
	
	// ajax 翻页, 检查看帖权限
	public function on_list() {
		$fid = intval(core::gpc('fid'));
		$page = misc::page();
		
		// 板块权限检查
		$forum = $this->mcache->read('forum', $fid);
		$this->check_forum_exists($forum);
		$this->check_forum_status($forum);
		$this->check_access($forum, 'read');
		
		// 多个 pid 逗号分开
		$pidarr = core::gpc('pidarr');
		$replyarr = core::gpc('replyarr');	// 此处通过传参，省去查询
		$pidarr = explode('_', $pidarr);
		$replyarr = explode('_', $replyarr);
		$replylists = array();
		foreach($pidarr as $k=>$pid) {
			$pid = intval($pid);
			$replies = intval($replyarr[$k]);
			$totalpage = max(1, ceil($replies / $this->conf['pagesize']));
			!isset($_GET['page']) && $page = $totalpage;	// 默认为最后一页
			$replylists[$pid] = $this->reply->get_list_by_page($fid, $pid, $page);
		}
		
		// $pages = misc::pages("?reply-list-fid-$fid-pid-$pid.htm", $post['replies'], $page, $this->conf['pagesize']);
		
		// 版主
		$ismod = $this->is_mod($forum, $this->_user);
		
		$this->view->assign('fid', $fid);
		$this->view->assign('pid', $pid);
		$this->view->assign('ismod', $ismod);
		$this->view->assign('replylists', $replylists);
		$this->view->display('reply_list_ajax.htm');
	}
	
	// create ajax
	public function on_create() {
		$fid = intval(core::gpc('fid'));
		$pid = intval(core::gpc('pid'));
		$toreplyid = intval(core::gpc('toreplyid'));
		
		$this->check_login();
		$this->check_forbidden_group();
		
		$uid = $this->_user['uid'];
		$username = $this->_user['username'];
		$user = $this->user->read($uid);
		$this->check_user_delete($user);
		
		$group = $this->group->read($user['groupid']);
		
		// 帖子存在检查
		$post = $this->post->read($fid, $pid);
		$this->check_post_exists($post);
		
		// 帖子回复数不能超过 10000
		if($post['replies'] > 1000) {
			$this->message('该帖子回复数已经达到1000，不能再回复了，再起话题吧！', 0);
		}
		
		$tid = $post['tid'];
		$thread = $this->thread->read($fid, $tid);
		
		// 板块权限检查
		$forum = $this->mcache->read('forum', $fid);
		$this->check_forum_exists($forum);
		$this->check_forum_status($forum);
		$this->check_access($forum, 'reply');
		
		$touid = 0;
		$tousername = '';
		if(!empty($toreplyid)) {
			$toreply = $this->reply->read($fid, $toreplyid);
			if(!empty($toreply)) {
				$touid = intval($toreply['uid']);
				$tousername = $toreply['username'];
			}
		}
		$this->view->assign('touid', $touid);
		$this->view->assign('tousername', $tousername);
		
		if(!$this->form_submit()) {
			
			$post['totalpage'] = $post['replies'] > 0 ? ceil($post['replies'] / $this->conf['pagesize']) : 0;
			$this->view->assign('fid', $fid);
			$this->view->assign('tid', $tid);
			$this->view->assign('pid', $pid);
			$this->view->assign('toreplyid', $toreplyid);
			$this->view->assign('post', $post);
			$this->view->assign('thread', $thread);
			$this->view->assign('forum', $forum);
			// hook reply_create_before.php
			$this->view->display('reply_create_ajax.htm');
		} else {
			$error = array();
			$subject = htmlspecialchars(core::gpc('subject', 'P'));
			$subject = misc::html_space($subject);
			
			// -----------> 添加到 reply
			$page = ceil(($post['replies'] + 1) / $this->conf['pagesize']);
			$reply = array (
				'fid'=>$fid,
				'tid'=>$post['tid'],
				'pid'=>$pid,
				'uid'=>$uid,
				'username'=>$username,
				'touid'=>$touid,
				'tousername'=>$tousername,
				'username'=>$username,
				'dateline'=>$_SERVER['time'],
				'userip'=>ip2long($_SERVER['ip']),
				'page'=>$page,
				'subject'=>$subject,
			);

			$error['subject'] = $this->reply->check_subject($subject);
			empty($error['subject']) && $error['subject'] = $this->mmisc->check_badword($subject);
			
			// hook reply_create_after.php
			if(!array_filter($error)) {
				$error = array();
				$error['page'] = $page;
				
				// hook reply_create_post_create_before.php
				$replyid = $this->reply->create($reply);
				$reply['replyid'] = $replyid;
				// hook reply_create_post_create_after.php
				
				// 更新 $post
				$post['replies']++;
				$this->post->update($post);
				
				// 更新 $user 用户发帖数，积分
				$user = $this->user->read($uid);
				$user['replies']++;
				$user['credits'] += $this->conf['credits_policy_reply'];
				$user['golds'] += $this->conf['golds_policy_reply'];
				$groupid = $user['groupid'];
				$user['groupid'] = $this->group->get_groupid_by_credits($user['groupid'], $user['credits']);
				// 更新 cookie 如果用户组发生改变，更新用户的 cookie
				if($groupid != $user['groupid']) {
					$this->user->set_login_cookie($user);
				}
				// 更新 $user 
				$this->user->update($user);
				
				// mypost 参与过的主题。
				if(!$this->mypost->have_tid($uid, $fid, $tid) && !$forum['accesson']) {
					$this->mypost->create(array('uid'=>$uid, 'fid'=>$fid, 'tid'=>$tid, 'pid'=>$pid));
					$user['myposts']++;
				}
				
				// 更新 $thread
				$thread['replies']++;
				$this->thread->update($thread);
				
				// 更新 forum
				$forum = $this->forum->read($fid);
				$forum['replies']++;
				$forum['todayreplies']++;
				$this->forum->update($forum);
				$this->mcache->clear('forum', $fid);
				
				// 今日总的发帖数
				$this->runtime->xset('replies', '+1');
				$this->runtime->xset('todayreplies', '+1');
				
				// hook reply_create_succeed.php
				$this->reply->format($reply);
				$replylists = array($pid=>array($reply));
				$ismod = 1;
				$this->view->assign('ismod', $ismod);
				$this->view->assign('replylists', $replylists);
				$this->view->display('reply_list_ajax.htm');
			
			} else {
				$this->message($error);
			}
			
			//$this->message($error);
		}
	}
	
	// 修改 ajax
	public function on_update() {
		$this->_title[] = '修改回复';
		$this->_nav[] = '修改回复';
		
		$this->check_login();
		$this->check_forbidden_group();
		
		$fid = intval(core::gpc('fid'));
		$replyid = intval(core::gpc('replyid'));
		
		$uid = $this->_user['uid'];
		$username = $this->_user['username'];
		$user = $this->user->read($uid);
		$this->check_user_exists($user);
		
		// 板块权限检查
		$forum = $this->mcache->read('forum', $fid);
		$this->check_forum_exists($forum);
		$this->check_forum_status($forum);
		$this->check_access($forum, 'reply');
		
		$reply = $this->reply->read($fid, $replyid);
		$this->check_reply_exists($reply);
		$pid = intval($reply['pid']);
		$tid = intval($reply['tid']);

		$post = $this->post->read($fid, $pid);
		$this->check_post_exists($post);
		$tid = intval($post['tid']);
		
		$thread = $this->thread->read($fid, $tid);
		$this->check_thread_exists($thread);
		
		$ismod = $this->is_mod($forum, $this->_user);
		// 编辑权限检查：管理员，版主，可以编辑
		if($reply['uid'] != $this->_user['uid']) {
			$this->check_access($forum, 'update');
		}
		
		if(!$ismod && $this->conf['post_update_expiry'] && $_SERVER['time'] - $reply['dateline'] > $this->conf['post_update_expiry']) {
			$time = ceil($this->conf['post_update_expiry'] / 60);
			$this->message('您不能再继续修改该回复，已经超出了最大修改时间: (<b>'.$time.'分钟</b>)。', 0);
		}
		
		$error = array();
		if(!$this->form_submit()) {
			$reply['subject'] = str_replace(array('<br>', '<br/>', '<br />'), "\r\n", $reply['subject']);
			$this->view->assign('fid', $fid);
			$this->view->assign('tid', $tid);
			$this->view->assign('pid', $pid);
			$this->view->assign('replyid', $replyid);
			$this->view->assign('reply', $reply);
			$this->view->assign('post', $post);
			$this->view->assign('thread', $thread);
			$this->view->assign('forum', $forum);
			$this->view->assign('error', $error);
			// hook reply_update_before.php
			$this->view->display('reply_update_ajax.htm');
			//$this->view->display('__post_update.htm');
		} else {
			
			$subject = misc::html_space(htmlspecialchars(core::gpc('subject', 'P')));
			
			$error['subject'] = $this->reply->check_subject($subject);
			empty($error['subject']) && $error['subject'] = $this->mmisc->check_badword($subject);
			
			// hook reply_update_after.php
			
			// 如果检测没有错误，则更新
			if(!array_filter($error)) {
				$error = array();
				$reply['subject'] = $subject;
				$this->reply->update($reply);
				
				// hook reply_update_succeed.php
				$error['subject_post'] = $subject;
				$this->message($error);
			}
			$this->message($error);
		}
	}
	
	// tpdo: 删除帖子，删除主题, todayposts 未更新
	public function on_delete() {
		$this->_title[] = '删除回复';
		$this->_nav[] = '删除回复';
		
		// copy from on_update()
		$this->check_login();
		$this->check_forbidden_group();
		
		$fid = intval(core::gpc('fid'));
		$replyid = intval(core::gpc('replyid'));
		
		$uid = $this->_user['uid'];
		$username = $this->_user['username'];
		$user = $this->user->read($uid);
		$this->check_user_exists($user);
		
		// 板块权限检查
		$forum = $this->mcache->read('forum', $fid);
		$this->check_forum_exists($forum);
		$this->check_forum_status($forum);
		$this->check_access($forum, 'reply');
		
		$reply = $this->reply->read($fid, $replyid);
		$this->check_reply_exists($reply);
		$pid = intval($reply['pid']);
		$tid = intval($reply['tid']);

		$post = $this->post->read($fid, $pid);
		$this->check_post_exists($post);
		$tid = intval($post['tid']);
		
		$thread = $this->thread->read($fid, $tid);
		$this->check_thread_exists($thread);
		
		$ismod = $this->is_mod($forum, $this->_user);
		// 编辑权限检查：管理员，版主，可以编辑
		if($reply['uid'] != $this->_user['uid']) {
			$this->check_access($forum, 'update');
		}
		
		// 过期不能编辑
		if(!$ismod && $this->conf['post_update_expiry'] && $_SERVER['time'] - $reply['dateline'] > $this->conf['post_update_expiry']) {
			$time = ceil($this->conf['post_update_expiry'] / 60);
			$this->message('您不能再继续修改该回复，已经超出了最大修改时间: (<b>'.$time.'分钟</b>)。', 0);
		}
		// copy end
		
		if(!$this->form_submit()) {
			$this->view->assign('fid', $fid);
			$this->view->assign('tid', $tid);
			$this->view->assign('pid', $pid);
			$this->view->assign('replyid', $replyid);
			$this->view->assign('reply', $reply);
			$this->view->assign('post', $post);
			$this->view->assign('thread', $thread);
			$this->view->assign('forum', $forum);
			$this->view->assign('error', $error);
			$this->view->display('reply_delete_ajax.htm');
		} else {
			// hook reply_delete_before.php
			$this->reply->xdelete($fid, $replyid);
			// hook reply_delete_after.php
			
			$this->message('删除成功！');
		}
		
	}

	//hook reply_control_after.php
}

?>