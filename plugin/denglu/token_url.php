<?php

// 跳转页

define('DEBUG', 2);

define('BBS_PATH', str_replace('\\', '/', substr(__FILE__, 0, -27)));

$conf = include BBS_PATH.'conf/conf.php';

// 获取回调
if(empty($_GET['token'])) {
	exit('token 为空。');
} else {
	$token = $_GET['token'];
}

// 跳转到
$jumpurl = $conf['app_url'].($conf['urlrewrite'] ? '' : '?')."denglu-index-token-".str_replace('-', '%2D', urlencode($token)).".htm";
header("Location:$jumpurl");
exit;
?>