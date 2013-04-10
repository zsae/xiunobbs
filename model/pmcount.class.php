<?php

/*
 * Copyright (C) xiuno.com
 */

class pmcount extends base_model{
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'pmcount';
		$this->primarykey = array('uid1', 'uid2');
		$this->conf['cache']['enable'] = FALSE;	// 关闭 Memcached，短消息直接走MYSQL
	}

	
	function create($arr) {
		$arr['uid1'] = min($arr['uid1'], $arr['uid2']);
		$arr['uid2'] = max($arr['uid1'], $arr['uid2']);
		return parent::create($arr);
	}
	
	function read($uid1, $uid2 = NULL, $arg3 = NULL, $arg4 = NULL) {
		return parent::read(min($uid1, $uid2), max($uid1, $uid2));
	}
	
}
?>