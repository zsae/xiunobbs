<?php

/*
 * Copyright (C) xiuno.com
 */

class reply extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'reply';
		$this->primarykey = array('fid', 'replyid');
		$this->maxcol = 'replyid';
		
	}

	public function get_list_by_page($fid, $pid, $page) {
		$start = ($page - 1) * $this->conf['pagesize'];
		$limit = $this->conf['pagesize'];
		$arrlist = $this->index_fetch(array('fid'=>$fid, 'pid'=>$pid), array(), $start, $limit);
		foreach($arrlist as &$arr) {
			$this->format($arr);
		}
		return $arrlist;
	}
	
	// 板块最新的回复，根据replyid 倒排
	public function get_list_by_fid($fid, $page, $pagesize) {
		$start = ($page - 1) * $pagesize;
		$limit = $pagesize;
		$arrlist = $this->index_fetch(array('fid'=>$fid), array('replyid'=>-1), $start, $limit);
		foreach($arrlist as &$arr) {
			$this->format($arr);
		}
		return $arrlist;
	}
	
	public function check_subject(&$subject) {
		if(empty($subject)) {
			return '回复内容不能为空。';
		}
		if(utf8::strlen($subject) > 128) {
			return '回复内容不能超过 128 个字符。';
		}
		return '';
	}
	
	// 用来显示给用户
	public function format(&$reply) {
		isset($reply['dateline']) && $reply['dateline_fmt'] = misc::minidate($reply['dateline']);
		
		// hook reply_model_format_end.php
		
	}
	
	// 删除回帖，非主题帖。相对比较简单，是相对！万恶的删除和缓存啊！不过现在终于可以把它封起来了，稳定了。
	public function xdelete($fid, $replyid) {
		// post, thread, forum ,user -1
		$reply = $this->read($fid, $replyid);
		$post = $this->post->read($fid, $reply['pid']);
		if(empty($post)) return;
		$post['replies']--;
		$this->post->update($post);
		
		$thread = $this->thread->read($fid, $reply['tid']);
		if(empty($thread)) return;
		$thread['replies']--;
		$this->thread->update($thread);
		
		$forum = $this->forum->read($fid);
		if(empty($forum)) return;
		$forum['replies']--;
		$this->forum->update($forum);
		$this->mcache->clear('forum', $fid);
		
		// 更新用户
		$user = $this->user->read($reply['uid']);
		if(empty($user)) return;
		$user['replies']--;
		$user['credits'] -= $this->conf['credits_policy_reply'];
		$user['golds'] -= $this->conf['golds_policy_reply'];
		$groupid = $user['groupid'];
		$user['groupid'] = $this->group->get_groupid_by_credits($user['groupid'], $user['credits']);
		if($groupid != $user['groupid']) {
			$this->user->set_login_cookie($user);
		}
		$this->user->update($user);
		
		$this->delete($fid, $replyid);
		
		// hook reply_model_delete_end.php
	}
	
	// 删除一个帖子下所有的楼层，最多1000。
	public function delete_by_fid_pid($fid, $pid) {
		$r = array();
		$replies = $todayreplies = 0;
		$arrlist = $this->index_fetch(array('fid'=>$fid, 'pid'=>$pid), array(), 0, 1000);
		foreach($arrlist as $arr) {
			!isset($r[$arr['uid']]) && $r[$arr['uid']] = array('credits'=>0, 'golds'=>0, 'replies'=>0);
			$r[$arr['uid']]['credits'] += $this->conf['credits_policy_reply'];
			$r[$arr['uid']]['golds'] += $this->conf['golds_policy_reply'];
			$r[$arr['uid']]['replies'] += 1;
			$this->delete($arr['fid'], $arr['replyid']);
			$replies++;
			$arr['dateline'] > $_SERVER['time_today'] && $todayreplies++;
		}
		$n = array($r, $replies, $todayreplies);
		
		// hook reply_model_delete_by_fid_pid_end.php
		
		return $n;
	}
	
	// hook reply_model_end.php
}
?>