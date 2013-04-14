<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来升级测试版本的 Xiuno BBS 2.0.0 RC3 到 Xiuno BBS 2.0.0 Release
/*
	流程：
		1. 新建 rc3 目录，将所有文件目录移动到 rc3/ 下。
		2. 上传 upload_me 下的文件到网站根目录
		3. 拷贝 upgrade/rc3_release.php 到网站根目录
		3. 访问 http://www.domain.com/rc3_to_release.php
		5. 删除升级程序 rc3_release.php
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
} elseif($step == 'upgrade_thread_type') {
	upgrade_thread_type();
} elseif($step == 'upgrade_attach') {
	upgrade_attach();
} elseif($step == 'upgrade_thread_views') {
	upgrade_thread_views();
}

function upgrade_conf() {
	global $conf;

	$old = include BBS_PATH.'rc3/conf/conf.php';
	$configfile = BBS_PATH.'conf/conf.php';
	
	$s = file_get_contents($configfile);
	$master = $old['db']['mysql']['master'];
	$s = preg_replace("#'mysql'\s*=>\s*array\s*\(\s*'master'\s*=>\s*array\s*\([^)]*?\)#is", "'mysql'=>array(\r\n'master'=>\r\n".var_export($master, 1), $s);
	file_put_contents($configfile, $s);
	
	
	$mconf = new xn_conf($configfile);
	$mconf->set('app_url', $old['app_url']);
	$mconf->set('static_url', $old['static_url']);
	$mconf->set('model_map', var_export(array('thread_views'=>array('thread_views', 'tid', 'tid')), 1));
	$mconf->set('upload_url', $old['upload_url']);
	$mconf->set('upload_path', BBS_PATH.'upload/');
	$mconf->set('plugin_url', $old['app_url'].'plugin/');
	$mconf->set('click_server', $old['app_url'].'service/clickd/');
	$mconf->set('auth_key', $old['public_key']);
	$mconf->set('siteid', md5(rand(1, 9999999999)));
	$mconf->set('cookie_pre', isset($old['cookie_pre']) ? $old['cookie_pre'] : 'xn_');
	$mconf->set('cookie_domain', isset($old['cookie_domain']) ? $old['cookie_domain'] : '');
	$mconf->set('cookie_path', isset($old['cookie_path']) ? $old['cookie_path'] : '');
	$mconf->set('urlrewrite', $old['urlrewrite']);
	$mconf->set('installed', 1);
	$mconf->save();
	
	
	$conf = include BBS_PATH.'conf/conf.php';
	$mkv = new kv($conf);
	$mkv->xset('app_name', $old['app_name']);
	$mkv->xset('app_copyright', $old['app_copyright']);
	$mkv->xset('timeoffset', $old['timeoffset']);
	$mkv->xset('forum_index_pagesize', $old['forum_index_pagesize']);
	$mkv->xset('site_pv', $old['site_pv']);
	$mkv->xset('site_runlevel', $old['site_runlevel']);
	$mkv->xset('threadlist_hotviews', $old['threadlist_hotviews']);
	$mkv->xset('seo_title', $old['seo_title']);
	$mkv->xset('seo_keywords', $old['seo_keywords']);
	$mkv->xset('seo_description', $old['seo_description']);
	$mkv->xset('search_type', $old['search_type']);
	$mkv->xset('china_icp', $old['china_icp']);
	$mkv->xset('footer_js', $old['footer_js']);
	$mkv->xset('iptable_on', $old['iptable_on']);
	$mkv->xset('badword_on', $old['badword_on']);
	$mkv->xset('online_hold_time', $old['online_hold_time']);
	$mkv->xsave();
					
	// 生成局部配置
	$kvconf = array (
		'credits_policy_thread' => 2,		// 发主题增加的积分
		'credits_policy_post' => 0,		
		'credits_policy_digest_1' => 1,	
		'credits_policy_digest_2' => 2,		
		'credits_policy_digest_3' => 4,		
		'golds_policy_digest_1' => 1,		
		'golds_policy_digest_2' => 2,		
		'golds_policy_digest_3' => 4,		
		'golds_policy_thread' => 1,		// 发主题增加的金币 golds（积分不能消费，金币可以消费，充值）
		'golds_policy_post' => 1,		
		'post_update_expiry' => 86400 * 7,		// 帖子多长时间后不能修改，默认为86400，一天，0不限制
		'sphinx_host' => '',			// 主机
		'sphinx_port' => '',			// 端口
		'sphinx_datasrc' => '',			// 数据源
		'sphinx_deltasrc' => '',		// 增量索引数据源，优先搜索这个
		'reg_on' => 1,				// 是否开启注册
		'reg_email_on' => 0,			// 是否开启Email激活
		'reg_init_golds' => 10,			// 注册初始化金币
		'resetpw_on' => 0,			// 是否开启密码找回
	);
	$mkv->set('conf_ext', $kvconf);
	
	message('修改配置成功，接下来升级 alter_table...', '?step=alter_table');
}


function alter_table() {
	global $conf;
	// 2. 修改表结构
	$sql = "
	
alter table bbs_attach add unique fid (fid, aid);
alter table bbs_attach drop PRIMARY key;
alter table bbs_attach
	add column tid int(11) NOT NULL  DEFAULT '0' after aid, 
	change pid pid int(11) NOT NULL  DEFAULT '0' after tid, 
	add KEY fidtid (fid,tid), COMMENT='';

drop table bbs_digest;

drop table bbs_digestcate;

alter table bbs_forum 
	change name name char(16) NOT NULL  after fid, 
	change digests digests int(11) unsigned NOT NULL  DEFAULT '0' after posts, 
	change lasttid lasttid int(11) NOT NULL  DEFAULT '0' after todayposts, 
	change brief brief text NOT NULL  after lasttid, 
	change accesson accesson tinyint(1) NOT NULL  DEFAULT '0' after brief, 
	change toptids toptids char(240) NOT NULL  after typecates, 
	change orderby orderby tinyint(11) NOT NULL  DEFAULT '0' after toptids, 
	change seo_title seo_title char(64) NOT NULL  after orderby, 
	drop column fup, 
	drop column replies, 
	drop column todayreplies, 
	drop column tops, 
	drop column lastpost, 
	drop column lastsubject, 
	drop column lastuid, 
	drop column lastusername, 
	drop column rule, 
	drop column icon, 
	drop column lastcachetime, 
	drop column status, 
	drop column listtype, 
	drop column indexforums, 
	drop key fup, COMMENT='';

drop table bbs_friendlink;

alter table bbs_group 
	change maxcredits maxcredits int(10) NOT NULL  DEFAULT '0' after creditsto, 
	drop column upfloors, 
	drop column color, COMMENT='';

alter table bbs_kv 
	change k k char(32) NOT NULL  first, 
	change expiry expiry int(11) unsigned NOT NULL  DEFAULT '0' after v, COMMENT='';

drop table bbs_pay;

alter table bbs_post 
	change rates rates int(11) unsigned NOT NULL  DEFAULT '0' after imagenum, 
	drop column replies, COMMENT='';

alter table bbs_runtime 
	change k k char(32) NOT NULL  first, 
	change v v text NOT NULL  after k, 
	add column expiry int(11) unsigned NOT NULL  DEFAULT '0' after v,  Engine=MyISAM, COMMENT='';

alter table bbs_stat 
	change users users int(11) unsigned NOT NULL  DEFAULT '0' after posts, 
	change newusers newusers int(11) unsigned NOT NULL  DEFAULT '0' after newposts, 
	drop column replies, 
	drop column newreplies, COMMENT='';

alter table bbs_thread 
	change floortime floortime int(10) unsigned NOT NULL  DEFAULT '0' after lastpost, 
	change top top tinyint(1) NOT NULL  DEFAULT '0' after posts, 
	add column typeid1 int(10) unsigned NOT NULL  DEFAULT '0' after top, 
	add column typeid2 int(10) unsigned NOT NULL  DEFAULT '0' after typeid1, 
	add column typeid3 int(10) unsigned NOT NULL  DEFAULT '0' after typeid2, 
	add column typeid4 int(10) unsigned NOT NULL  DEFAULT '0' after typeid3, 
	change digest digest tinyint(3) unsigned NOT NULL  DEFAULT '0' after typeid4, 
	change attachnum attachnum tinyint(3) NOT NULL  DEFAULT '0' after digest, 
	change lastuid lastuid int(11) unsigned NOT NULL  DEFAULT '0' after status, 
	drop column replies, 
	drop column typename, 
	drop column cateids, 
	drop column catenames, 
	drop column seo_keywords, 
	drop column pids, 
	drop column coverimg, 
	drop column brief, 
	drop key fid, add KEY fid (fid,lastpost), 
	add KEY fid_2 (fid,digest,tid);
	
		alter table bbs_thread drop key typeid;
		alter table bbs_thread drop key typeid_2;
		
		DROP TABLE IF EXISTS bbs_thread_type_old;
		CREATE TABLE bbs_thread_type_old (
		  typeid int(11) unsigned NOT NULL auto_increment,	
		  fid smallint(6) NOT NULL default '0',			
		  newtypeid smallint(11) NOT NULL default '0',		 
		  threads int(11) NOT NULL default '0',			
		  typename char(16) NOT NULL default '',		
		  rank tinyint(3) unsigned NOT NULL default '0',	
		  PRIMARY KEY (typeid),
		  KEY (fid)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		
INSERT INTO  bbs_thread_type_old (typeid, fid, newtypeid, threads, typename, rank) SELECT typeid, fid, 0, threads, typename, rank FROM bbs_thread_type;

DROP TABLE IF EXISTS bbs_thread_type;
CREATE TABLE bbs_thread_type(                      
                   `fid` smallint(6) NOT NULL default '0',             
                   `typeid` int(11) NOT NULL default '0',              
                   `typename` char(16) NOT NULL default '',            
                   `rank` int(11) unsigned NOT NULL default '0',       
                   `enable` tinyint(3) unsigned NOT NULL default '0',  
                   PRIMARY KEY  (`fid`,`typeid`)                       
                 ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS bbs_thread_type_cate;
create table bbs_thread_type_cate ( 
	fid smallint(6) NOT NULL  DEFAULT '0'  , 
	cateid int(11) NOT NULL  DEFAULT '0'  , 
	catename char(16) NOT NULL   , 
	rank int(11) unsigned NOT NULL  DEFAULT '0'  , 
	enable tinyint(3) unsigned NOT NULL  DEFAULT '0'  , 
	PRIMARY KEY (fid,cateid) 
)Engine=MyISAM;

DROP TABLE IF EXISTS bbs_thread_type_count;
create table bbs_thread_type_count ( 
	fid smallint(6) NOT NULL  DEFAULT '0'  , 
	typeidsum int(11) unsigned NOT NULL  DEFAULT '0'  , 
	threads int(11) NOT NULL  DEFAULT '0'  , 
	PRIMARY KEY (fid,typeidsum) 
)Engine=MyISAM;

DROP TABLE IF EXISTS bbs_thread_type_data;
create table bbs_thread_type_data ( 
	fid smallint(6) NOT NULL  DEFAULT '0'  , 
	tid int(11) NOT NULL  DEFAULT '0'  , 
	typeidsum int(11) unsigned NOT NULL  DEFAULT '0'  , 
	PRIMARY KEY (fid,tid,typeidsum) , 
	KEY fid (fid,typeidsum,tid) 
)Engine=MyISAM;

DROP TABLE IF EXISTS bbs_thread_views;
create table bbs_thread_views ( 
	tid int(11) unsigned NOT NULL  auto_increment  , 
	views int(11) unsigned NOT NULL  DEFAULT '0'  , 
	PRIMARY KEY (tid) 
)Engine=MyISAM;

alter table bbs_user 
	change myposts myposts mediumint(8) unsigned NOT NULL  DEFAULT '0' after posts, 
	change avatar avatar int(11) unsigned NOT NULL  DEFAULT '0' after myposts, 
	change digests digests int(11) unsigned NOT NULL  DEFAULT '0' after golds, 
	change follows follows smallint(3) unsigned NOT NULL  DEFAULT '0' after digests, 
	change accesson accesson tinyint(1) NOT NULL  DEFAULT '0' after homepage, 
	add column onlinetime int(1) NOT NULL  DEFAULT '0' after accesson, 
	change lastactive lastactive int(1) NOT NULL  DEFAULT '0' after onlinetime, 
	drop column replies, 
	drop column money, 
	drop column signature, 
	drop key email, add KEY email (email), COMMENT='';
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
	$mgroup = new group($conf);
	$group = $mgroup->read(5);
	$group['name'] = '实习版主';
	$mgroup->update($group);
	$mgroup->delete(3);
	
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
		message('升级头像完成，接下来升级论坛数据...', '?step=upgrade_forum');
	}
}

// old typeid -> new typeid 对照表！
function upgrade_forum() {
	global $conf;
	
	// 重命名 thread_type 为 thread_type_old
	$db = new db_mysql($conf['db']['mysql']);
	
	$mforum = new forum($conf);
	$mthread_type = new thread_type($conf);
	$mthread_type_cate = new thread_type_cate($conf);
	$forumlist = $mforum->get_list();
	foreach($forumlist as $forum) {
		$fid = $forum['fid'];
		
		$typelist = $db->index_fetch('thread_type_old', 'typeid', array('fid'=>$fid), array(), 0, 100);
		
		// 插入到第一维
		if(!empty($typelist)) {
			// 1 - 40
			$db->set("thread_type_cate-fid-$fid-cateid-1", array('fid'=>$fid, 'cateid'=>1, 'rank'=>1, 'catename'=>'分类', 'enable'=>1));
			
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
	
	message('升级头像完成，接下来升级主题分类数据...', '?step=upgrade_thread_type');
}

// 典型的跳转框架
function upgrade_thread_type() {
	global $start;
	global $conf;
	$db = new db_mysql($conf['db']['mysql']);
	$count = core::gpc('count');
	if(empty($count)) {
		$count = $db->index_count('thread');
	}
	$thread_type_data = new thread_type_data($conf);
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$threadlist = $db->index_fetch('thread', 'tid', array(), array(), $start, $limit);
		foreach($threadlist as $thread) {
			if($thread['typeid'] > 0) {
				$type = $db->get('thread_type_old-typeid-'.$thread['typeid']);
				$thread['typeid1'] = $type['newtypeid'];
				$thread['typeid2'] = 0;
				$thread['typeid3'] = 0;
				$thread['typeid4'] = 0;
				$thread_type_data->xcreate($thread['fid'], $thread['tid'], $type['newtypeid'], 0, 0);
			}
		}
		$start += $limit;
		message("正在升级 upgrade_thread_type, 一共: $count, 当前: $start...", "?step=upgrade_thread_type&start=$start&count=$count", 0);
	} else {
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
	$mpost = new post($conf);
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$arrlist = $db->index_fetch('attach', 'aid', array(), array(), $start, $limit);
		foreach($arrlist as $attach) {
			$post = $mpost->read($attach['fid'], $attach['pid']);
			$attach['tid'] = $post['tid'];
			$db->set("attach-fid-$attach[fid]-aid-$attach[aid]", $attach);
		}
		$start += $limit;
		message("正在升级 upgrade_attach, 一共: $count, 当前: $start...", "?step=upgrade_attach&start=$start&count=$count", 0);
	} else {
		message('升级 thread_type 完成，接下来升级 upgrade_thread_views...', '?step=upgrade_thread_views');
	}
}

// 典型的跳转框架
function upgrade_thread_views() {
	global $start;
	global $conf;
	$db = new db_mysql($conf['db']['mysql']);
	$count = core::gpc('count');
	if(empty($count)) {
		$count = $db->index_count('thread');
	}
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$mthread_view = core::model($conf, 'thread_views', 'tid', 'tid');
		$threadlist = $db->index_fetch('thread', 'tid', array(), array(), $start, $limit);
		$tmpfile = BBS_PATH.'rc3/upload/click_server.data';
		$fp = fopen($tmpfile, 'rb');
		foreach($threadlist as $thread) {
			$tid = $thread['tid'];
			fseek($fp, $tid * 4);
			$data = fread($fp, 4);
			$arr = unpack("L*", $data);	// unpack 出来的数组，下标从1开始。
			$views = isset($arr[1]) ? $arr[1] : 0;
			$mthread_view->create(array('tid'=>$thread['tid'], 'views'=>$views));
		}
		fclose($fp);
		$start += $limit;
		message("正在升级 upgrade_thread_views, 一共: $count, 当前: $start...", "?step=upgrade_thread_views&start=$start&count=$count", 0);
	} else {
		message('升级完成，请拷贝 upload 目录覆盖到当前目录。', 'index.php');
	}
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
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="view/common.css" />
	</head>
	<body>
	<div id="header" style="overflow: hidden;">
		<h3 style="color: #FFFFFF;line-height: 26px;margin-left: 16px;">Xiuno BBS 2.0.0 RC3 - Xiuno BBS 2.0.0 Release  升级程序</h3>
		<p style="color: #BBBBBB;margin-left: 16px;">本程序用来升级Xiuno BBS RC3。</p>
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

?>