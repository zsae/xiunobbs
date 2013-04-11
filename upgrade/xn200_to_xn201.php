<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来升级测试版本的 Xiuno BBS 2.0.0 到 Xiuno BBS 2.0.1
/*
	流程：
		1. 覆盖 control, model, view, admin, xiunophp 这五个目录
		2. 将 upgrade/xn200_to_xn201.php 放置于根目录
		3. 访问 http://www.domain.com/xn200_to_xn201.php
		4. 清空 tmp
		5. 删除文件 xn200_to_xn201.php
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
	message('不支持SAE环境。');
}

core::init();
core::ob_start();
$step = core::gpc('step');
empty($step) && $step = 'alter_table';
$start = intval(core::gpc('start'));

// 升级配置文件
if($step == 'alter_table') {
	alter_table();
} elseif($step == 'upgrade_attach_download') {
	upgrade_attach_download();
} elseif($step == 'complete') {
	complete();
}

function alter_table() {
	global $conf;
	// 2. 修改表结构
	$sql = "
alter table bbs_attach_download add column fid int(10) unsigned NOT NULL default '0';
alter table bbs_attach_download drop key aid;
alter table bbs_attach_download add key fid,aid;
";

	$db = new db_mysql($conf['db']['mysql']);
	$s = $sql;
	$s = str_replace("\r\n", "\n", $s);
	$s = preg_replace('#\n\#[^\n]*?\n#is', "\n", $s);	// 去掉注释行
	$s = preg_replace('#;\s+#is', ";\n", $s);
	$sqlarr = explode(";\n", $s);
	$tablepre = $db->tablepre;
	
	foreach($sqlarr as $sql) {
		if(trim($sql)) {
			$sql = str_replace('bbs_', $tablepre, $sql);
			try {
				$db->query($sql);
			} catch (Exception $e) {
				$error = $e->getMessage();
				// Duplicate column, Table exists, column/key does not exists
				if(mysql_errno() != 1060 && mysql_errno() != 1050 && mysql_errno() != 1091) {
					//echo mysql_errno();
					//message($error);
					//break;
				}
			}
		}
	}
	
	$db->truncate('runtime');
	
	// 升级用户组
	message('升级表结构完成，接下来升级 upgrade_attach_download...', '?step=upgrade_attach_download');
}

// 典型的跳转框架，修正 fid
function upgrade_attach_download() {
	global $start;
	global $conf;
	$db = new db_mysql($conf['db']['mysql']);
	$count = core::gpc('count');
	if(empty($count)) {
		$count = $db->index_count('attach_download');
	}
	$mattachdown = new attach_download($conf);
	$mattach = new attach($conf);
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$arrlist = $mattachdown->index_fetch(array(), array(), $start, $limit);
		foreach($arrlist as $attachdown) {
			$attachlist = $mattach->index_fetch(array('aid'=>$attachdown['aid']), array(), 0, 1);
			$attach = empty($attachlist) ? array() : array_pop($attachlist);
			if(empty($attach)) continue;
			$attachdown['fid'] = $attach['fid'];
			$db->update("attach_download-uid-$attach[uid]-fid-0-aid-$attach[aid]-", $attachdown);
		}
		$start += $limit;
		message("正在升级 upgrade_attach_download, 一共: $count, 当前: $start...", "?step=upgrade_attach_download&start=$start&count=$count", 0);
	} else {
		message('升级 upgrade_attach_download 完成', '?step=complete');
	}
}

function complete() {
	message('升级完毕。', './');
}

function message($s, $url = '', $timeout = 2) {
	DEBUG && $timeout = 1000;
	
	$s = $url ? "<h2>$s</h2><p><a href=\"$url\">页面将在<b>$timeout</b>秒后自动跳转，点击这里手工跳转。</a></p>
		<script>
			setTimeout(function() {
				window.location=\"$url\";
				setInterval(function() {
					window.location=\"$url\";
				}, 30000);
			}, ".($timeout * 1000).");
		</script>
	" : "<h2>$s</h2>";
	echo '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Xiuno BBS 2.0.0 - Xiuno BBS 2.0.1 升级程序 </title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="view/common.css" />
	</head>
	<body>
	<div id="header" style="overflow: hidden;">
		<h3 style="color: #FFFFFF;line-height: 26px;margin-left: 16px;">Xiuno BBS 2.0.0 RC3 - Xiuno BBS 2.0.1  升级程序</h3>
		<p style="color: #BBBBBB;margin-left: 16px;">本程序用来升级Xiuno BBS 2.0.0。</p>
	</div>
	<div id="body" style="padding: 16px;">
		'.$s.'
	</div>
	<div id="footer"> Powered by Xiuno (c) 2010 </div>
	<div style="color: #888888;">'.(DEBUG ? nl2br(print_r($_SERVER['sqls'], 1)) : '').'</div>
	</body>
	</html>';
	exit;
}


?>