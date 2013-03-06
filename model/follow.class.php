<?php

/*
 * Copyright (C) xiuno.com
 */

class follow extends base_model{
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'follow';
		$this->primarykey = array('uid', 'fuid');
		$this->conf['cache']['enable'] = FALSE;	// 关闭 Memcached，短消息直接走db or service
	}
	
	// xcrate(), 处理关联关系。判断双向
	public function xcreate($uid, $fuid) {
		$user = $this->user->read($uid);
		$fuser = $this->user->read($fuid);
		$user['follows']++;
		$fuser['followeds']++;
		$this->user->update($user);
		$this->user->update($fuser);
		
		// 看是否已经被关注
		$follow = $this->read($fuid, $uid);
		if(!empty($follow) && $follow['direction'] == 1) {
			$follow['direction'] = 2;
			$this->update($follow);	// 更新对方状态
			$direction = 2;
		} else {
			$direction = 1;
		}
		$this->create(array(
			'uid'=>$uid,
			'fuid'=>$fuid,
			'direction'=>$direction,
		));
		// user.follows++
		// fuser.followeds++
	}
	
	// 关注 & 相互关注关系也查到了
	public function get_list_by_uid($uid, $page = 1, $pagesize = 20) {
		$start = ($page - 1) * $pagesize;
		$arrlist = $this->index_fetch(array('uid'=>$uid), array(), $start, $pagesize);
		$uids = misc::arrlist_key_values($arrlist, '', 'fuid');
		$userlist = $this->user->mget($uids);
		foreach($userlist as &$user) {
			$this->user->format($user);
		}
		return $userlist;
	}
	
	// 关注 & 相互关注关系也查到了
	public function get_followedlist_by_uid($uid, $page = 1, $pagesize = 20) {
		$start = ($page - 1) * $pagesize;
		$arrlist = $this->index_fetch(array('fuid'=>$uid), array(), $start, $pagesize);
		$uids = misc::arrlist_key_values($arrlist, '', 'uid');
		$userlist = $this->user->mget($uids);
		foreach($userlist as &$user) {
			$this->user->format($user);
		}
		return $userlist;
	}
	
	// 查看两人的关系, uid1, uid2, 0:互相不关注， 1: uid1 关注 uid2, 2: uid2 关注 uid1, 3:互相关注
	public function check_follow($uid1, $uid2) {
		// 单向关注 & 相互关注
		$r1 = $this->get($uid1, $uid2);
		if(!empty($r1)) {
			return $r1['direction'] == 1 ? 1 : 3;
		}
		// 被关注
		$r2 = $this->get($uid2, $uid1);
		if(!empty($r1)) {
			return 2;
		}
	}
	
	public function xdelete($uid, $fuid) {
		$follow = $this->read($fuid, $uid);
		if(!empty($follow) && $follow['direction'] == 2) {
			$follow['direction'] = 1;
			$this->update($follow);
		}
		$return = $this->delete(array($uid, $fuid));
		if($return) {
			$this->count('-1');
		}
		return $return;
	}
	
}
?>