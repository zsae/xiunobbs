<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来升级测试版本的 Xiuno BBS 2.0.* 到 Xiuno BBS 2.0.2
/*
	流程：
		1. 覆盖 control, model, view, admin, xiunophp 这五个目录
		2. 将 upgrade/xn201_to_xn202.php 放置于根目录
		3. 访问 http://www.domain.com/xn201_to_xn202.php
		4. 清空 tmp
		5. 删除文件 xn201_to_xn202.php
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
} elseif($step == 'upgrade_digest') {
	upgrade_digest();
} elseif($step == 'complete') {
	complete();
}

function alter_table() {
	global $conf;
	
	// 修改配置文件
	$conffile = BBS_PATH.'conf/conf.php';
	$s = file_get_contents($conffile);
	if(strpos($s, "'thread_new'=>array('thread_new'") === FALSE) {
		$s = str_replace("'thread_views'=>array('thread_views', 'tid', 'tid')", "'thread_views'=>array('thread_views', 'tid', 'tid'),\n\t\t'thread_new'=>array('thread_new', 'tid')", $s);
		$s = str_replace("'version' => '2.0.0 Release'", "'version' => '2.0.2'", $s);
		$s = str_replace("'version' => '2.0.1'", "'version' => '2.0.2'", $s);
		file_put_contents($conffile, $s);
	}
	
	// 2. 修改表结构
	$sql = "
alter table bbs_attach_download add column fid int(10) unsigned NOT NULL default '0';
alter table bbs_attach_download drop key aid;
alter table bbs_attach_download add key fidaid(fid,aid);
alter table bbs_thread drop key tid;
alter table bbs_thread drop key fid_2;
CREATE TABLE IF NOT EXISTS bbs_thread_new (
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  lastpost int(10) unsigned NOT NULL default '0',	# 最后回复时间
  PRIMARY KEY (tid),					# 
  UNIQUE KEY (fid, tid),				# 
  KEY (lastpost)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS bbs_thread_digest (
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  digest tinyint(3) unsigned NOT NULL default '0',	# 精华等级
  PRIMARY KEY (tid),					# 
  UNIQUE KEY (fid, tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS bbs_banip;
CREATE TABLE bbs_banip (
  banid bigint(11) unsigned NOT NULL auto_increment,	# banid
  ip0 smallint(11) NOT NULL default '0',		# 
  ip1 smallint(11) NOT NULL default '0',		# 
  ip2 smallint(11) NOT NULL default '0',		# 
  ip3 smallint(11) NOT NULL default '0',		# 
  uid int(11) unsigned NOT NULL default '0',		# 添加人
  dateline int(11) unsigned NOT NULL default '0',	# 添加时间
  expiry int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY (banid),
  KEY (ip0, ip1, ip2, ip3)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

UPDATE bbs_thread_type SET rank=0;
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
				echo "<p>此错误可以忽略：".$error."</p>";
			}
		}
	}
	
	$db->truncate('runtime');
	
	if($conf['version'] == '2.0.0') {
		message('升级表结构完成，接下来升级 upgrade_attach_download...', '?step=upgrade_attach_download');
	} else {
		message('升级即将完成', '?step=upgrade_digest');
	}
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


// 典型的跳转框架
function upgrade_digest() {
	global $start;
	global $conf;
	$db = new db_mysql($conf['db']['mysql']);
	$count = core::gpc('count');
	if(empty($count)) {
		$count = $db->index_count('thread');
	}
	$mthread = new thread($conf);
	$mdigest = new thread_digest($conf);
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$arrlist = $mthread->index_fetch(array(), array(), $start, $limit);
		foreach($arrlist as $arr) {
			if(empty($arr['digest'])) continue;
			$mdigest->create(array('fid'=>$arr['fid'], 'tid'=>$arr['tid'], 'digest'=>$arr['digest']));
		}
		$start += $limit;
		message("正在升级 upgrade_digest, 一共: $count, 当前: $start...", "?step=upgrade_digest&start=$start&count=$count", 0);
	} else {
		message('升级 upgrade_digest 完成', '?step=complete');
	}
}


function complete() {
	global $conf;
	$mthread = new thread($conf);
	$mthreadnew = core::model($conf, 'thread_new');
	$newlist = $mthread->index_fetch(array('lastpost' => array('>'=>$_SERVER['time'] - 86400 * 2)), array(), 0, 10000);
	foreach($newlist as $new) {
		$mthreadnew->create(array('fid'=>$new['fid'], 'tid'=>$new['tid'], 'lastpost'=>$new['lastpost']));
	}
	unset($newlist);
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
		<title>Xiuno BBS 2.0.* - Xiuno BBS 2.0.2 升级程序 </title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="view/common.css" />
	</head>
	<body>
	<div id="header" style="overflow: hidden;">
		<h3 style="color: #FFFFFF;line-height: 26px;margin-left: 16px;">Xiuno BBS 2.0.* - Xiuno BBS 2.0.2  升级程序</h3>
		<p style="color: #BBBBBB;margin-left: 16px;">本程序用来升级Xiuno BBS 2.0.1。</p>
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