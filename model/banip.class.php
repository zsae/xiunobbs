<?php

/*
 * Copyright (C) xiuno.com
 */

class banip extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'banip';
		$this->primarykey = array('banid');
		$this->maxcol = 'banid';
	}
	
	public function is_banip($ip) {
		return $this->get_banip($ip);
	}
	
	public function get_list() {
		$baniplist = $this->index_fetch(array(), array(), 0, 1000);
		foreach($baniplist as &$banip) {
			$banip['dateline_fmt'] = date('Y-n-j', $banip['dateline']);
			$banip['expiry_fmt'] = date('Y-n-j', $banip['expiry']);
		}
		return $baniplist;
	}
	
	// 获取对应的BAN记录，可能是某个IP段
	public function get_banip($ip) {
		$arr = explode('.', $ip);
		if($this->count() < 50) {
			$arrlist = $this->index_fetch(array(), array(), 0, 50);
			foreach($arrlist as $arr) {
				if($arr['ip0'] == $ip[0] && ($arr['ip1'] == -1 || $arr['ip1'] == $arr[1] &&  ($arr['ip2'] == -1 || $arr['ip2'] == $ip[2] &&  ($arr['ip3'] == -1 || $arr['ip3'] == $ip[3])))) {
					return $arr;
				}
			}
		// 查4次
		} else {
			$arrlist = $this->index_fetch(array('ip0'=>$ip[0], 'ip1'=>-1), array(), 0, 1);
			empty($arrlist) && $this->index_fetch(array('ip0'=>$ip[0], 'ip1'=>$ip[1], 'ip2'=>-1), array(), 0, 1);
			empty($arrlist) && $this->index_fetch(array('ip0'=>$ip[0], 'ip1'=>$ip[1], 'ip2'=>$ip[2], 'ip3'=>-1), array(), 0, 1);
			empty($arrlist) && $this->index_fetch(array('ip0'=>$ip[0], 'ip1'=>$ip[1], 'ip2'=>$ip[2], 'ip3'=>$ip[3], 'ip4'=>-1), array(), 0, 1);
			empty($arrlist) && $this->index_fetch(array('ip0'=>$ip[0], 'ip1'=>$ip[1], 'ip2'=>$ip[2], 'ip3'=>$ip[3], 'ip4'=>$ip[4]), array(), 0, 1);
			if($arrlist) return array_pop($arrlist);
		}
		return array();
	}
	
	public function add_banip($ip, $uid, $expiry) {
		$arr = explode('.', $ip);
		$banip = $this->get_banip($ip);
		if($banip) return TRUE;
		foreach($arr as &$v) {
			$v == '*' && $v = -1;
			$v = intval($v);
		}
		$banid = $this->create(array('ip0'=>$arr[0], 'ip1'=>$arr[1], 'ip2'=>$arr[2], 'ip3'=>$arr[3], 'uid'=>$uid, 'datelie'=>$_SERVER['time'], 'expiry'=>$expiry));
		return $banid;
	}
	
	// 获取 groupid=>name
	/*public function get_group_kv() {
		$group_kv = $this->kv->get('group_kv');
		if(empty($group_kv)) {
			$group_kv = misc::arrlist_key_values();
			$this->kv->set('group_kv', core::json_encode($group_kv));
		}
		return $group_kv;
	}*/
	
	public function get_groupid_by_credits($groupid, $credits) {
		// 根据用户组积分范围升级
		if($groupid > 10) {
			$grouplist = $this->get_list();
			foreach($grouplist as $group) {
				if($group['groupid'] < 11) continue;
				if($credits >= $group['creditsfrom'] && $credits < $group['creditsto']) {
					return $group['groupid'];
				}
			}
		}
		return $groupid;
	}
	
	public function check_name(&$name) {
		if(empty($name)) {
			return '用户组名称不能为空。';
		}
		return '';
	}
	
	public function check_creditsfrom(&$creditsfrom) {
		if(empty($creditsfrom)) {
			return '起始积分不能为空。';
		}
		return '';
	}
	
	public function check_creditsto(&$creditsto) {
		if(empty($creditsto)) {
			return '截止积分不能为空。';
		}
		return '';
	}
	
	// 用来显示给用户
	public function format(&$group) {
		// format data here.
	}
}
?>