<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

global $ucconf;
$file = $this->conf['plugin_path'].'ucenter/conf.php';
$pconf = $ucconf = include $file;
include $this->conf['plugin_path'].'ucenter/ucenter_simple_client.php';

if(empty($pconf['uc_url']) || empty($pconf['uc_appkey'])) {
	$this->message('请正确设置 UCenter 以后再开启此功能！', 0);
}

$user = uc_get_user(1, 1);
if(!is_array($user)) {
	$this->message('尝试连接 UCenter ，获取 UCenter 管理员账户发生错误，可能设置 UCenter 有误，错误信息：'.htmlspecialchars($user), 0);
}

?>