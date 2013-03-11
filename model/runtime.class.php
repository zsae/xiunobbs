<?php

/*
 * Copyright (C) xiuno.com
 */

// runtime 运行产生的数据，如果DB压力大，可以独立成服务，此表暂时只有一条数据。
// 不同于 kv, 它是内存数据，是可以被清空的，可以狭义的理解为 memcached

class runtime extends base_model {
	
	private $data = array();		// 合并存储
	private $changed = array();		// 合并存储改变标志位
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'runtime';
		$this->primarykey = array('k');
		
		//IN_SAE && $this->conf['db']['type'] = 'saekv';
	}
	
	function __destruct() {
		//restore_exception_handler();
		//restore_error_handler();
		foreach($this->changed as $key=>$v) {
			return $this->set($key, $this->data[$key]);
		}
	}
	
	// threads, posts, users, todayposts, todayusers, newuid, newusername, cron_1_next_time, cron_2_next_time, toptids, cronlock
	public function get($k) {
		$arr = parent::get($k);
		return !empty($arr) ? core::json_decode($arr['v']) : FALSE;
	}
	
	public function set($k, $s) {
		$s = core::json_encode($s);
		$arr = parent::get($k);
		$arr['v'] = $s;
		return parent::set($k, $arr);
	}
	
	// 删除一个 key, 
	// $arg2 = FALSE, $arg3 = FALSE, $arg4 = FALSE 仅仅为了兼容 base_model, 没有意义
	public function delete($k, $arg2 = FALSE, $arg3 = FALSE, $arg4 = FALSE) {
		return parent::delete($k);
	}
	
	// 合并读取，一次读取多个，增加效率
	public function xget($key = 'runtime') {
		$s = $this->get($key);
		if(!empty($s)) {
			$this->data[$key] = $s;
		} else {
			// 冗余存储了 toptids, 在 runtime 数据丢失的时候，可以恢复。
			$toptids = $this->kv->get('toptids');
			$this->data[$key] = $this->kv->get('conf');
			$forumlist = $this->forum->get_list();
			$forumarr = misc::arrlist_key_values($forumlist, 'fid', 'name');
			$grouplist = $this->group->get_list();
			$grouparr = misc::arrlist_key_values($grouplist, 'groupid', 'name');
			$typelist = $this->thread_type->get_list();
			$typearr = misc::arrlist_key_values($typelist, 'typeid', 'typename');
			$forumaccesson = $this->forum_access->get_accesson($forumarr);
			$this->data[$key] += array (
				'onlines'=>$this->online->count(),
				'posts'=>$this->post->count(),
				'threads'=>$this->thread->count(),
				'users'=>$this->user->count(),
				'todayposts'=>0,
				'todayusers'=>0,
				'cron_1_next_time'=>0,
				'cron_2_next_time'=>0,
				'newuid'=>0,
				'newusername'=>'',
				'toptids'=>$toptids,
				'forumarr'=>$forumarr,
				'forumaccesson'=>$forumaccesson,
				'grouparr'=>$grouparr,
				'typearr'=>$typearr,
			);
		}
		return $this->data[$key];
	}
	
	public function xset($k, $v, $key = 'runtime') {
		if(empty($this->data[$key])) {
			$this->data[$key] = $this->xget($key);
		}
		if($v && is_string($v) && ($v[0] == '+' || $v[0] == '-')) {
			$v = intval($v);
			if($v != 0) {
				$this->data[$key][$k] += $v;
				$this->changed[$key] = TRUE;
			}
		} else {
			$this->data[$key][$k] = $v;
			$this->changed[$key] = TRUE;
		}
	}
	
	public function xupdate($k) {
		if($k == 'forumarr') {
			$forumlist = $this->forum->get_list();
			$forumarr = misc::arrlist_key_values($forumlist, 'fid', 'name');
			$this->xset('forumarr', $forumarr);
		} elseif($k == 'groupname') {
			$grouplist = $this->group->get_list();
			$grouparr = misc::arrlist_key_values($grouplist, 'fid', 'name');
			$this-->xset('grouparr', $grouparr);
		}
	}
}
?>