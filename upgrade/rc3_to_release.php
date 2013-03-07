<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来升级测试版本的 RC3 到 Release
/*
	流程：
		1. 备份文件: conf/conf.php 为 conf.php.bak
		2. 重命名 plugin 为 plugin2, tmp 为 tmp2
		3. 上传 release 程序到网站根目录，覆盖老程序
		4. 还原配置文件： conf/conf.php
		4. 访问 http://www.domain.com/upgrade/rc2_to_rc3.php
		5. 删除升级目录 upgrade!!!
*/

@set_time_limit(0);

define('DEBUG', 0);

define('BBS_PATH', str_replace('\\', '/', substr(dirname(__FILE__), 0, -7)));

// 加载应用的配置文件，唯一的全局变量 $conf
if(!($conf = include BBS_PATH.'conf/conf.php')) {
	message('配置文件不存在。');
}

define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';
core::init();
core::ob_start();
$step = core::gpc('step');
empty($step) && $step = 'upgrade_conf';
$start = intval(core::gpc('start'));

// 升级配置文件
if($step == 'upgrade_conf') {
	upgrade_conf();
} elseif($step == 'alter_table') {
	alter_table();
} elseif($step == 'upgrade_avatar') {
	upgrade_avatar();
} elseif($step == 'upgrade_forum') {
	upgrade_forum();
}

function upgrade_conf() {
	global $conf;
	
	
	message('修改配置成功，接下来升级 alter_table...', '?step=alter_table');
}


function alter_table() {
	global $conf;
	// 2. 修改表结构
	$sql = "
		ALTER TABLE bbs_thread DROP INDEX typeidtid, typeidfloortime, fidtypeid, typeid, typeid_2, tid;

		RENAME TABLE bbs_thread_type TO bbs_thread_type_old;
		ALTER TABLE bbs_thread_type_old ADD column newtypeid int(11) NOT NULL default '0';
				
		# 存放大分类，小表，每个版块三个大分类，ID，1,2,3
		DROP TABLE IF EXISTS bbs_thread_type_cate;
		CREATE TABLE bbs_thread_type_cate (
		  fid smallint(6) NOT NULL default '0',			# 版块id
		  cateid int(11) NOT NULL default '0',			# 主题分类id，取值范围：1,2,3
		  catename char(16) NOT NULL default '',		# 主题分类
		  rank int(11) unsigned NOT NULL default '0',		# 排序，越小越靠前，最大255
		  enable tinyint(3) unsigned NOT NULL default '0',	# 是否启用，主要针对大分类
		  PRIMARY KEY (fid, cateid)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		
		# 主题分类
		DROP TABLE IF EXISTS bbs_thread_type;
		CREATE TABLE bbs_thread_type (
		  fid smallint(6) NOT NULL default '0',			# 版块id
		  typeid int(11) NOT NULL default '0',			# 主题分类id，为唯一。
		  typename char(16) NOT NULL default '',		# 主题分类
		  rank int(11) unsigned NOT NULL default '0',		# 排序，越小越靠前，最大255
		  enable tinyint(3) unsigned NOT NULL default '0',	# 是否启用，预留
		  PRIMARY KEY (fid, typeid)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		
		# 主题分类求和，用来分页
		DROP TABLE IF EXISTS bbs_thread_type_count;
		CREATE TABLE bbs_thread_type_count (
		  fid smallint(6) NOT NULL default '0',			# 版块id
		  typeidsum int(11) unsigned NOT NULL default '0',	# typeid 求和
		  threads int(11) NOT NULL default '0',			# 该 typeidsum 下有多少主题数
		  PRIMARY KEY (fid, typeidsum)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		
		# 主题分类的数据，一个主题属于多少个主题分类，排列组合，typeid求和以后存放。
		DROP TABLE IF EXISTS bbs_thread_type_data;
		CREATE TABLE bbs_thread_type_data (
		  fid smallint(6) NOT NULL default '0',			# 版块id
		  tid int(11) NOT NULL default '0',			# tid
		  typeidsum int(11) unsigned NOT NULL default '0',	# 这个值是一个“和”
		  PRIMARY KEY (fid, tid, typeidsum),			# 一个主题属于多个主题分类（最多三个）
		  KEY (fid, typeidsum, tid)				# 一个版块下的 typeid，主题列表按照符合条件查询列表
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

		ALTER TABLE bbs_thread DROP column typeid, typename, cateids, catenames;
		
		ALTER TABLE bbs_thread 
			ADD column typeid1 int(10) unsigned NOT NULL default '0',
			ADD column typeid2 int(10) unsigned NOT NULL default '0',
			ADD column typeid3 int(10) unsigned NOT NULL default '0',
			
		ALTER TABLE bbs_attach ADD column tid int(11) NOT NULL default '0';
		ALTER TABLE bbs_attach ADD KEY fidtid(fid, tid);
		
		DROP TABLE IF EXISTS bbs_thread_views;
		CREATE TABLE bbs_thread_views (
		  tid int(11) unsigned NOT NULL auto_increment,		# 主题id
		  views int(11) unsigned NOT NULL default '0',		# 点击数
		  PRIMARY KEY (tid)
		);
		
		ALTER TABLE bbs_user ADD column onlinetime int(11) NOT NULL default '0';
		
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
	
	// 修改实习版主
	$mgroup = new group();
	$group = $mgroup->read(5);
	$group['name'] = '实习版主';
	$mgroup->update($group);
	
	// 删除用户组缓存
	for($i=0; $i<30; $i++) {
		$file = $conf['tmp_path']."forum_$i_cache.php";
		is_file($file) && unlink($file);
	}
	
	// 升级用户组
	
	message('升级表结构完成，接下来升级用户头像...', '?step=upgrade_avatar');
}

function upgrade_avatar() {
	global $start;
	global $conf;
	// 2. 修改表结构
	$avatar_path = $conf['upload_path'].'avatar/';
	$db = new db_mysql($conf['db']['mysql']);
	$count = $db->index_count('user');
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;	// 每次升级 100
		$arrlist = $db->index_fetch_id('user', 'uid', array(), array(), $start, $limit);
		foreach($arrlist as $key) {
			list($table, $col, $uid) = explode('-', $key);
			$user = $db->get("user-uid-$uid");
			$bigfile = $avatar_path.get_avatar($uid, 'big');
			$hugefile = $avatar_path.get_avatar($uid, 'huge');
			if(is_file($bigfile)) {
				if(!is_file($hugefile)) {
					copy($bigfile, $hugefile);
					is_file($hugefile) && image::thumb($hugefile, $bigfile, $conf['avatar_width_big'], $conf['avatar_width_big']);
				}
			}
		}
		$start += $limit;
		message("正在升级 user.avatar, 一共: $count, 当前: $start...", "?step=upgrade_avatar&start=$start", 0);
	} else {
		
		// 删除 group_* 的缓存
		clear_tmp('group_');
		message('升级头像完成，接下来升级论坛数据...', '?step=upgrade_forum');
	}
}

// old typeid -> new typeid 对照表！
function upgrade_forum() {
	global $conf;
	
	// 重命名 thread_type 为 thread_type_old
	$mforum = new forum();
	$mthread_type = new thread_type();
	$mthread_type_cate = new thread_type_cate();
	$forumlist = $mforum->get_list();
	foreach($forumlist as $forum) {
		$fid = $forum['fid'];
		$mthread_type->init($fid);
		$mthread_type_cate->init($fid);
		
		$typelist = $db->index_fetch('thread_type_old', 'typeid', array('fid'=>$fid), array(), 1, 100);
		
		// 插入到第一维
		if(!empty($typelist)) {
			// 1 - 40
			$newtypeid = 0;
			foreach($typelist as $type) {
				$newtypeid++;
				if($newtypeid > 40) continue;
				
				// 对照表
				$type['newtypeid'] = $newtypeid;
				$db->set("thread_type_old-typeid-$type[typeid]", $type);
				
				// 创建新的 type
				$arr = array(
					'fid'=>$fid,
					'typeid'=>$newtypeid,
					'typename'=>$type['typename'],
					'rank'=>$newtypeid,
					'enable'=>1
				);
				$db->set("thread_type-typeid-$newtypeid", $arr);
			}
		}
	}
	
	message('升级头像完成，接下来升级论坛数据...', '?step=upgrade_thread_type');
}

// 典型的跳转框架
function upgrade_thread_type() {
	global $start;
	global $conf;
	$db = new db_mysql($conf['db']['mysql']);
	$count = core::gpc('count');
	if(empty($count)) {
		$db->index_create('thread', array('tid'));
		$count = $db->index_count('thread');
	}
	$thread_type_data = new thread_type_data();
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$threadlist = $db->index_fetch('thread', 'tid', array(), array(), $start, $limit);
		foreach($threadlist as $thread) {
			if($thread['typeid'] > 0) {
				$type = $db->get('thread_type_old-typeid-'.$thread['typeid']);
				$thread['typeid1'] = $type['newtypeid'];
				$thread['typeid2'] = 0;
				$thread['typeid3'] = 0;
				$thread_type_data->xcreate($thread['fid'], $thread['tid'], $type['newtypeid'], 0, 0);
			}
		}
		$start += $limit;
		message("正在升级 upgrade_thread_type, 一共: $count, 当前: $start...", "?step=upgrade_thread_type&start=$start&count=$count", 0);
	} else {
		$db->index_drop('thread', array('tid'));
		if($conf['db']['type'] != 'mongodb') {
			$tablepre = $db->tablepre;
			$db->query("ALTER TABLE {$tablepre}thread DROP COLUMN typeid");
		}
		message('升级 thread_type 完成，接下来升级 attach...', '?step=upgrade_attach');
	}
}

// 附件的 tid
function upgrade_attach() {
	global $start;
	global $conf;
	$db = new db_mysql($conf['db']['mysql']);
	$count = core::gpc('count');
	if(empty($count)) {
		$count = $db->index_count('attach');
	}
	$mpost = new post();
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$arrlist = $db->index_fetch('attach', 'aid', array(), array(), $start, $limit);
		foreach($arrlist as $attach) {
			$post = $mpost->read($attach['fid'], $attach['pid']);
			$attach['tid'] = $post['tid'];
			$db->set("attach-aid-$attach[aid]", $attach);
		}
		$start += $limit;
		message("正在升级 upgrade_attach, 一共: $count, 当前: $start...", "?step=upgrade_attach&start=$start&count=$count", 0);
	} else {
		message('<a href="../">升级完毕!</a>');
	}
}

function alter_table() {
	global $conf;
	$db = new db_mysql($conf['db']['mysql']);
	
	// 升级主题分类
	
	
	message('升级表结构完成，接下来升级用户头像...', '?step=upgrade_avatar');
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
		<title>Xiuno BBS 2.0.0 RC3 - Xiuno BBS 2.0.0 Release 升级程序 </title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="../view/common.css" />
	</head>
	<body>
	<div id="header" style="overflow: hidden;">
		<h3 style="color: #FFFFFF; line-height: 26px; margin-left: 16px;">Xiuno BBS 2.0.0 RC3 - Xiuno BBS 2.0.0 Release  升级程序</h3>
		<p style="color: #BBBBBB; margin-left: 16px;">本程序用来升级Xiuno BBS RC3。</p>
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

function get_avatar($uid, $size = 'middle', $type = '') {
	$dir = image::get_dir($uid);
	return $dir.'/'.$uid.'_'.$size.'.gif';
}

function clear_tmp($pre = '') {
	global $conf;
	if(IN_SAE) {
		$kv = new SaeKV();
		$ret = $kv->pkrget($pre, 100);
		foreach($ret as $key=>$val) {
			$kv->delete($key);
		}
	} else {
		$dh = opendir($conf['tmp_path']);
		while(($file = readdir($dh)) !== false ) {
			if($file != "." && $file != ".." ) {
				if(substr($file, 0, strlen($pre)) == $pre) {
					unlink($conf['tmp_path']."$file");
				}
			}
		}
		closedir($dh);
	}
}

?>