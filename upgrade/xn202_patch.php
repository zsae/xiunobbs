<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序为 XiunoBBS 2.0.2 的补丁程序
/*
	流程：
		1. 拷贝到根目录
		2. 访问 http://www.domain.com/xn202_patch.php
		3. 删除 xn202_patch.php
*/

@set_time_limit(0);

define('DEBUG', 2);

define('BBS_PATH', str_replace('\\', '/', dirname(__FILE__)).'/');

// 加载应用的配置文件，唯一的全局变量 $conf
if(!($conf = include BBS_PATH.'conf/conf.php')) {
	message('配置文件不存在。');
}
define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';

if(IN_SAE) {
	exit('不支持SAE环境。');
}

core::init();
core::ob_start();
$step = core::gpc('step');
empty($step) && $step = 'alter_table';
$start = intval(core::gpc('start'));

$runtime = new runtime($conf);
$kv = new kv($conf);
$runtime->xset('view_path', array());
$kv->xset('view_path', array());
$kv->save_changed();
$runtime->save_changed();

echo '卸载所有风格，修补完毕。';
?>