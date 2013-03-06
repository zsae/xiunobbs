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

	/*
		$arr = array(
			'uid1'=>1,
			'uid2'=>2,
			'count'=>100,
			'news'=>0,
			'dateline'=>1234567890,
		);
	*/
/*	public function xcreate($arr) {
		$uid1 = &$arr['uid1'];
		$uid2 = &$arr['uid2'];
		if($uid1 > $uid2) {
			$t = $uid1; $uid1 = $uid2; $uid2 = $t;
		}
		return $this->create($arr);
	}*/
	
}
?>