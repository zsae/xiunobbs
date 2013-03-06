<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 该文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;
	$tablepre = $db->tablepre;
	// 留着吧，反正不占太大地方，哪天又开了，数据还都在。
	//$db->query("DROP TABLE IF EXISTS {$tablepre}user_denglu;");
}

?>