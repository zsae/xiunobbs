<?php

/*
 * Copyright (C) xiuno.com
 */

class thread extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'thread';
		$this->primarykey = array('fid', 'tid');
		$this->maxcol = 'tid';
	}
	
	public function get_threadlist_by_fid($fid, $orderby, $start, $limit) {
		$orderby = $orderby == 0 ? array('floortime'=>-1) : array('tid'=>-1);
		$threadlist = $this->index_fetch(array('fid'=>$fid), $orderby, $start, $limit);
		return $threadlist;
	}
	
	// 按照 tid 倒序
	public function get_list($page = 1) {
		$pagesize = 30;
		$start = ($page - 1) * $pagesize;
		$threadlist = $this->index_fetch(array(), array('tid'=>1), $start, $pagesize);
		return $threadlist;
	}
	
	// ------------------> 杂项
	public function check_subject(&$subject) {
		if(empty($subject)) {
			return '标题不能为空。';
		}
		if(utf8::strlen($subject) > 200) {
			return '标题不能超过 200 字，当前长度：'.strlen($subject);
		}
		return '';
	}
	
	// 用来显示给用户
	public function format(&$thread, $forum = array()) {
		if(empty($thread)) return;
		$thread['subject_substr']  = utf8::substr($thread['subject'], 0, 50);
		isset($thread['message']) && $thread['message']  = nl2br(htmlspecialchars($thread['message']));
		$thread['isnew'] = ($this->conf['site_pv'] <= 1000000 ? $_SERVER['time'] - 86400 * 7 : $_SERVER['time_today']) < max($thread['dateline'], $thread['lastpost']);	// 最新贴定义：如果是 pv > 100w 的站点，为今日，否则为7天内的，否则先的太“冷清”了。
		$thread['dateline_fmt'] = misc::minidate($thread['dateline']);
		$thread['posts_fmt'] = max(0, $thread['posts'] - 1);	// 用来前端显示
		$thread['lastpost_fmt'] = misc::minidate($thread['lastpost']);
		empty($thread['lastpost']) && $thread['lastpost'] = $thread['dateline'];
		if($forum) {
			$thread['typename1'] = $thread['typeid1'] && isset($forum['types'][1][$thread['typeid1']]) ? $forum['types'][1][$thread['typeid1']] : '';
			$thread['typename2'] = $thread['typeid2'] && isset($forum['types'][2][$thread['typeid2']]) ? $forum['types'][2][$thread['typeid2']] : '';
			$thread['typename3'] = $thread['typeid3'] && isset($forum['types'][3][$thread['typeid3']]) ? $forum['types'][3][$thread['typeid3']] : '';
		} else {
			$thread['typename1'] = $thread['typename2'] = $thread['typename3'] = '';
		}
		$thread['forumname'] = $this->conf['forumarr'][$thread['tid']];
		// hook thread_model_format_end.php
	}
	
	// 关联删除，清理掉相关数据
	public function xdelete($fid, $tid, $updatestat = TRUE) {
		
		// 加载配置
		if(!isset($this->conf['credits_policy_post'])) {
			$this->conf += $this->kv->xget('conf_ext');
		}
		
		$forum = $this->forum->read($fid);
		$thread = $this->thread->read($fid, $tid);
		$user = $this->user->read($thread['uid']);
		$uid = $thread['uid'];
		
		// 受影响的值。
		$default_user = array('threads'=>0, 'posts'=>0, 'replies'=>0, 'credits'=>0, 'golds'=>0, 'myposts'=>0);
		$default_forum = array('threads'=>0, 'posts'=>0, 'replies'=>0, 'todayposts'=>0, 'todayreplies'=>0);
		$return = array(
			'forum'=> array($fid=>$default_forum),
			'user' => array($uid=>$default_user)
		);
		$rforum = &$return['forum'][$fid];
		$ruser = &$return['user'];
		
		// todo:算出分页，一页一页的删除，可能会超时。
		$pagesize = $this->conf['pagesize'];
		$pagenum = ceil($thread['posts'] / $pagesize);
		$todayposts = $todayreplies = 0;
		for($i = 1; $i <= $pagenum; $i++) {
			$postlist = $this->post->index_fetch(array('fid'=>$fid, 'tid'=>$tid, 'page'=>$i), array(), 0, $pagesize);
			foreach($postlist as $post) {
				!isset($ruser[$post['uid']]) && $ruser[$post['uid']] = $default_user;
				
				// 删除 attach
				$post['attachnum'] && $this->attach->xdelete($post['fid'], $post['pid']);
				
				// 删除 mypost，删除主题一定不会空删除
				$this->mypost->delete($post['uid'], $post['fid'], $post['pid']);
				
				$ruser[$post['uid']]['myposts']++;
				
				$post['dateline'] > $_SERVER['time_today'] && $todayposts++;
				
				// 删除 $post
				$this->post->delete($post['fid'], $post['pid']);
				
				// 删除 reply
				list($r, $replies, $todayreplies2) = $this->reply->delete_by_fid_pid($post['fid'], $post['pid']);
				foreach($r as $_uid=>$arr) {
					!isset($ruser[$_uid]) && $ruser[$_uid] = $default_user;
					$ruser[$_uid]['replies'] += $arr['replies'];
					$ruser[$_uid]['credits'] += $arr['credits'];
					$ruser[$_uid]['golds'] += $arr['golds'];
					$rforum['replies'] += $replies;
					$rforum['todayreplies'] += $todayreplies2;
					$todayreplies += $todayreplies2;
				}
				
				$ruser[$post['uid']]['posts']++;
				$ruser[$post['uid']]['credits'] += $this->conf['credits_policy_post'];
				$ruser[$post['uid']]['golds'] += $this->conf['golds_policy_post'];
			}
		}
		
		// 发表主题的积分策略不同于回帖的策略。
		$ruser[$uid]['credits'] = $ruser[$uid]['credits'] - $this->conf['credits_policy_post'] +  - $this->conf['credits_policy_thread'];
		$ruser[$uid]['golds'] = $ruser[$uid]['golds'] - $this->conf['golds_policy_post'] +  - $this->conf['golds_policy_thread'];
		
		$rforum['threads']++;
		$rforum['posts'] += $thread['posts'];
		$rforum['todayposts'] += $todayposts;
		$rforum['todayreplies'] += $todayreplies;
		
		// 删除置顶
		if($thread['top']) {
			$thread['top'] == 1 && $this->thread_top->delete_top_1($forum, array("$fid-$tid"));
			$thread['top'] == 3 && $this->thread_top->delete_top_3($forum, array("$fid-$tid"));
		}
		
		// 删除主题
		$this->thread->delete($fid, $tid);
		
		// 同时删除 thread_view, 这里为强关联
		$this->thread_views->delete($tid);
		
		// modlog
		$this->modlog->delete_by_fid_tid($fid, $tid);
		
		// 更新 runtime
		$this->runtime->xset('threads', '-1');
		$this->runtime->xset('posts', '-'.$thread['posts']);
		$this->runtime->xset('todayposts', '-'.$todayposts);
		$this->runtime->xset('todayreplies', '-'.$todayreplies);
		
		if($updatestat) {
			$this->xdelete_update($return);
			
			// 更新最后发帖，直接清零
			if($forum['lasttid'] == $tid) {
				$forum['lasttid'] = 0;
				$forum['lastuid'] = 0;
				$forum['lastusername'] = '';
				$forum['lastsubject'] = '';
			}
		}
		
		// 更新主题分类数
		if($thread['typeid1'] > 0 || $thread['typeid2'] > 0 || $thread['typeid3'] > 0) {
			$this->thread_type_data->xdelete($fid, $tid);
		}
		
		// hook thread_model_xdelete_end.php
		
		return $return;
	}
	
	// 合并返回值，用户删除板块时候，合并主题。
	public function xdelete_merge_return(&$return, &$return2) {
		foreach($return2['user'] as $uid=>$arr) {
			if(!$uid) continue;
			if(!isset($return['user'][$uid])) { $return['user'][$uid] = $arr; continue; }
			$return['user'][$uid]['threads'] += $arr['threads'];
			$return['user'][$uid]['posts'] += $arr['posts'];
			$return['user'][$uid]['replies'] += $arr['replies'];
			$return['user'][$uid]['myposts'] += $arr['myposts'];
			$return['user'][$uid]['credits'] += $arr['credits'];
			$return['user'][$uid]['golds'] += $arr['golds'];
		}
		foreach($return2['forum'] as $fid=>$arr) {
			if(!$fid) continue;
			if(!isset($return['forum'][$fid])) { $return['forum'][$fid] = $arr; continue; }
			$return['forum'][$fid]['threads'] += $arr['threads'];
			$return['forum'][$fid]['posts'] += $arr['posts'];
			$return['forum'][$fid]['todayposts'] += $arr['todayposts'];
			$return['forum'][$fid]['replies'] += $arr['replies'];
			$return['forum'][$fid]['todayreplies'] += $arr['todayreplies'];
		}
		
		// hook thread_model_xdelete_merge_return_end.php
	}
	
	// 关联删除后的更新
	public function xdelete_update($return) {
		// 更新回复用户的积分
		if(isset($return['user'])) {
			foreach($return['user'] as $uid=>$arr) {
				if(!$uid) continue;
				$user = $this->user->read($uid);
				$user['threads'] -= $arr['threads'];
				$user['posts'] -= $arr['posts'];
				$user['replies'] -= $arr['replies'];
				$user['myposts'] -= $arr['myposts'];
				$user['credits'] -= $arr['credits'];
				$user['golds'] -= $arr['golds'];
				$this->user->update($user);
			}
		}
		
		if(isset($return['forum'])) {
			foreach($return['forum'] as $fid=>$arr) {
				if(!$fid) continue;
				$forum = $this->forum->read($fid);
				$forum['threads'] -= $arr['threads'];
				$forum['posts'] -= $arr['posts'];
				$forum['todayposts'] -= $arr['todayposts'];
				$forum['replies'] -= $arr['replies'];
				$forum['todayreplies'] -= $arr['todayreplies'];
				$this->forum->update($forum);
				$this->forum->update_last($fid);
				$this->mcache->clear('forum', $fid);
				$this->runtime->xupdate('forumarr');
			}
		}
		
		// hook thread_model_xdelete_update_end.php
	}
	
	// hook thread_model_end.php
}
?>