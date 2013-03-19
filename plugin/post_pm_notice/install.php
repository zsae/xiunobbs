<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	$db->query("ALTER TABLE {$tablepre}thread ADD column recvpm tinyint(11) unsigned NOT NULL default '0'");
	
	// 初始化账号，强制设置uid=2的账户为系统账户。
	$admin = $db->get("user-uid-1");
	$u = $db->get("user-uid-2");
	$u['groupid'] = 11;
	$u['username'] = '系统';
	$u['salt'] = $admin['salt'];
	$u['password'] = $admin['password'];
	$db->set("user-uid-2", $u);
	
	// 写入配置文件
	$this->kv->xset('system_uid', 2);
	$this->kv->xset('system_username', '系统');
	// $this->kv->xsave();
	
}

?>