<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 该文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;
	$tablepre = $db->tablepre;
	$db->query("DROP TABLE IF EXISTS {$tablepre}pay");
}

?>