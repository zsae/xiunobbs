<?php

/*
 * Copyright (C) xiuno.com
 */

// 调试模式: 1 打开，0 关闭
define('DEBUG', 2);

// 站点根目录
define('BBS_PATH', str_replace('\\', '/', substr(dirname(__FILE__), 0, -7)));

include BBS_PATH.'install/function.php';

// 框架的物理路径
$conf = include BBS_PATH.'conf/conf.php';
if(empty($conf)) {
	message('<h3>读取配置文件失败，请检查配置文件是否存在并且有可读权限：'.BBS_PATH.'conf/conf.php'.'</h3>');
}

// PHP 版本判断
if(version_compare(PHP_VERSION, '5.0.0') == -1) {
	message('<h3>非常抱歉，您的PHP版本太低 ('.PHP_VERSION.')，达不到最低安装要求 (5.0.0)</h3>');
}

if(!DEBUG && is_file($conf['upload_path'].'install.lock')) {
	message('已经安装过，如果需要重新安装，请删除 upload/install.lock 文件。');
}

define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';
core::init();

$step = isset($_GET['step']) ? $_GET['step'] : '';

if(empty($step) || $step == 'checklicense') {
	include './header.inc.php';	
	include './license.inc.php';	
	include './footer.inc.php';
	exit;
} elseif($step == 'checkenv') {
	$env = $write = $saewrite = array();
	get_env($env, $write);
	if(IN_SAE) {
		$saestorage = new SaeStorage();
		$saestorage->getDomainCapacity('upload');
		$saewrite['upload_path'] = $saestorage->errno() != 7;
		
		$kv = new SaeKV();
		$saewrite['tmp_path'] = 1;
	}
	include './header.inc.php';	
	include './check_env.inc.php';	
	include './footer.inc.php';
	exit;

} elseif($step == 'checkdb') {
	
	// 检测 mysql_connnect, mongodb, pdo 扩展情况
	$mysql_support = function_exists('mysql_connect');
	$mongodb_support = extension_loaded('Mongo');
	$pdo_mysql_support = extension_loaded('pdo_mysql');
	
	$type = core::gpc('type', 'G');
	empty($type) && $type = 'mysql';
	
	$timezones = array(
		'+0' => '+0 ',
		'+1' => '+1 ',
		'+2' => '+2 ',
		'+3' => '+3 ',
		'+4' => '+4 ',
		'+5' => '+5 ',
		'+6' => '+6 ',
		'+7' => '+7 ',
		'+8' => '+8 北京时间',
		'+9' => '+9 ',
		'+10' => '+10 ',
		'+11' => '+11 ',
		'+12' => '+12 ',
		'+13' => '+13 ',
		'+14' => '+14 ',
		'+15' => '+15 ',
		'+16' => '+16 ',
		'+17' => '+17 ',
		'+18' => '+18 ',
		'+19' => '+19 ',
		'+20' => '+20 ',
		'+21' => '+21 ',
		'+22' => '+22 ',
		'+23' => '+23 ',
	);
	
	$timeoffset = '+8';
	
	if(core::gpc('FORM_HASH', 'P')) {
		$host = core::gpc('host', 'P');
		$user = core::gpc('user', 'P');
		$pass = core::gpc('pass', 'P');
		$name = core::gpc('name', 'P');
		$tablepre = core::gpc('tablepre', 'P');
		$adminuser = core::gpc('adminuser', 'P');
		$adminpass = core::gpc('adminpass', 'P');
		$adminpass2 = core::gpc('adminpass2', 'P');
		$timeoffset = core::gpc('timeoffset', 'P');
		$error = '';
		if($type == 'mysql') {
			$link = @mysql_connect($host, $user, $pass, TRUE);
			if(!$link) {
				$error = 'MySQL 账号密码可能有误：<span class="small">'.mysql_error().'</span>';
			} else {
				$r = mysql_select_db($name);
				if(mysql_errno() == 1049) {
					mysql_query("CREATE DATABASE $name");
					$r = mysql_select_db($name);
				}
				if(!$r) {
					$error = 'MySQL 账户权限可能受限：<span class="small">'.mysql_error().mysql_errno().'</span>';
				} else {
					$conf['db']['mysql'] = array(
						// 主 MySQL Server
						'master' => array (
								'host' => $host,
								'user' => $user,
								'password' => $pass,
								'name' => $name,
								'charset' => 'utf8',
								'tablepre' => $tablepre,
								'engine'=>'MyISAM',
						),
						// 从 MySQL Server
						'slaves' => array (
						)
					);
					$db = new db_mysql($conf['db']['mysql']);
					
					$s = file_get_contents(BBS_PATH.'install/install_mysql.sql');
					
					$s = str_replace("\r\n", "\n", $s);
					$s = preg_replace('#\n\#[^\n]*?\n#is', "\n", $s);	// 去掉注释行
					$sqlarr = explode(";\n", $s);
					
					foreach($sqlarr as $sql) {
						if(trim($sql)) {
							$sql = str_replace('bbs_', $tablepre, $sql);
							try {
								$db->query($sql);
							} catch (Exception $e) {
								$error = $e->getMessage();
								break;
							}
						}
					}
					
					$db->truncate('framework_count');
					$db->truncate('framework_maxid');
				}
			}
		} elseif($type == 'mongodb') {
			include './install_mongodb.php';
			
			$conf['db']['mongodb'] = array(
				'master' => array (
					'host' => $host,
					'user' => $user,
					'password' => $pass,
					'name' => $name,
					'charset' => 'utf8',
				),
				'slaves' => array (
				)
			);
			
			// 主要是建立索引
			try {
				$db = new db_mongodb($conf['db']['mongodb']);
			} catch(Exception $e) {
				$error = $e->getMessage();
			}
			if(!$error) {
				
				// 清空表
				foreach($db_index as $table=>$indexes) {
					$db->truncate($table);
				}
				
				// 插入初始化数据
				foreach($db_data as $table=>$arrlist) {
					$db->truncate($table);
					$primarykey = $db_index[$table][0];
					foreach($arrlist as $arr) {
						$key = get_key_add($primarykey, $arr);
						$keystring = $table.$key;
						$db->set($keystring, $arr);
					}
				}
				
				// 主要是建立索引
				foreach($db_index as $table=>$indexes) {
					foreach($indexes as $index) {
						$db->index_create($table, $index);
					}
				}
				
				$db->truncate('framework_count');
				$db->truncate('framework_maxid');
			}
		} elseif($type == 'pdo_mysql') {
			$conf['db']['pdo_mysql'] = array(
				'master' => array (
						'host' => $host,
						'user' => $user,
						'password' => $pass,
						'name' => $name,
						'charset' => 'utf8',
						'tablepre' => $tablepre,
						'engine'=>'MyISAM',
				),
				'slaves' => array (
				)
			);
			try {
				$db = new db_pdo_mysql($conf['db']['pdo_mysql']);
				$db->connect($host, $user, $pass, $name, 'utf8');
				$db->query("SELECT VERSION()");
			} catch (Exception $e) {
				$error = $e->getMessage();
			}
			if($error) {
				if(strpos($error, 'Unknown MySQL server host') !== FALSE) {
					$error = 'MySQL Host 连接不上：<span class="small">'.$error.'</span>';
				} elseif(strpos($error, 'Access denied') !== FALSE) {
					$error = 'MySQL 账号密码可能有误：<span class="small">'.$error.'</span>';
				} elseif(strpos($error, 'Unknown database') !== FALSE) {
					$error = "Database $name 不存在：<span class=\"small\">$error</span>";
				} else {
					$error = 'MySQL 错误：<span class="small">'.$error.'</span>';
				}
			} else {
			
				$s = file_get_contents(BBS_PATH.'install/install_mysql.sql');
				
				$s = str_replace("\r\n", "\n", $s);
				$s = preg_replace('#\n\#[^\n]*?\n#is', "\n", $s);	// 去掉注释行
				$sqlarr = explode(";\n", $s);
				
				foreach($sqlarr as $sql) {
					if(trim($sql)) {
						$sql = str_replace('bbs_', $tablepre, $sql);
						try {
							$db->query($sql);
						} catch (Exception $e) {
							$error = $e->getMessage();
							break;
						}
					}
				}
				$db->truncate('framework_count');
				$db->truncate('framework_maxid');
			}
		} elseif($type == 'pdo_oracle') {
			
		} elseif($type == 'pdo_sqlite') {
			
		}
		
		if(!$error) {
			
			// 预设 count maxid
			$db->count('group', 16);
			$db->maxid('group-groupid', 15);
			$db->count('user', 2);
			$db->maxid('user-uid', 10);	// 内置10个账户，方便扩展
			$db->count('forum', 3);
			$db->maxid('forum-fid', 3);
			$db->truncate('kv');
			$db->truncate('runtime');
			
			// db 写入配置文件
			$configfile = BBS_PATH.'conf/conf.php';
			$replacearr = array('user'=>$user, 'host'=>$host, 'password'=>$pass, 'name'=>$name, 'tablepre'=>$tablepre);
			$s = file_get_contents($configfile);
			if($type == 'mysql') {
				$s = str_line_replace($s, 27, 34, $replacearr);
			} elseif($type == 'pdo_mysql') {
				$s = str_line_replace($s, 40, 47, $replacearr);
			} elseif($type == 'mongodb') {
				$s = str_line_replace($s, 53, 58, $replacearr);
			}
			$typearr = array('type'=>$type);
			$s = str_line_replace($s, 24, 25, $typearr);
			$url = misc::get_url_path();
			$auth_key = md5(rand(1, 10000000).$_SERVER['ip']);
			$appurl = substr($url, 0, -8); // 带 /
			
			
			$plugin_path = "BBS_PATH.'plugin/',";
			$plugin_url = "{$appurl}plugin/";
			$conf['plugin_path'] = BBS_PATH.'plugin/';
			$tmp_path = "BBS_PATH.'tmp/',";
			$conf['tmp_path'] = BBS_PATH.'tmp/';
			if(!IN_SAE) {
				$upload_path = "BBS_PATH.'upload/',";
				$upload_url = "{$appurl}upload/";
				$conf['upload_path'] = BBS_PATH.'upload/';
			} else {
				$saestorage = new saestorage();
				$upload_path = "'saestor://upload/',";
				$upload_url = $saestorage->geturl('upload', '');
				$conf['upload_path'] = 'saestor://upload/';
			}
			$siteid = md5($auth_key.$appurl);
			$s = preg_replace('#\'app_url\'\s*=\>\s*\'?.*?\'?,#is', "'app_url' => '$appurl',", $s);
			$s = preg_replace('#\'static_url\'\s*=\>\s*\'?.*?\'?,#is', "'static_url' => '$appurl',", $s);
			$s = preg_replace('#\'upload_url\'\s*=\>\s*\'?.*?\'?,#is', "'upload_url' => '$upload_url',", $s);
			$s = preg_replace('#\'upload_path\'\s*=\>\s*\'?.*?\'?,#is', "'upload_path' => ".$upload_path, $s);
			$s = preg_replace('#\'plugin_path\'\s*=\>\s*\'?.*?\'?,#is', "'plugin_path' => ".$plugin_path, $s);
			$s = preg_replace('#\'plugin_url\'\s*=\>\s*\'?.*?\'?,#is', "'plugin_url' => '$plugin_url',", $s);
			$s = preg_replace('#\'cookie_pre\'\s*=\>\s*\'?.*?\'?,#is', "'cookie_pre' => 'bbs_',", $s);
			$s = preg_replace('#\'cookie_path\'\s*=\>\s*\'?.*?\'?,#is', "'cookie_path' => '/',", $s);
			$s = preg_replace('#\'cookie_domain\'\s*=\>\s*\'?.*?\'?,#is', "'cookie_domain' => '',", $s);
			$s = preg_replace('#\'tmp_path\'\s*=\>\s*\'?.*?\'?,#is', "'tmp_path' => $tmp_path", $s);
			$s = preg_replace('#\'click_server\'\s*=\>\s*\'?.*?\'?,#is', "'click_server' => '{$appurl}service/clickd/',", $s);
			$s = preg_replace('#\'auth_key\'\s*=\>\s*\'?.*?\'?,#is', "'auth_key' => '$auth_key',", $s);
			$s = preg_replace('#\'siteid\'\s*=\>\s*\'?.*?\'?,#is', "'siteid' => '$siteid',", $s);
			$s = preg_replace('#\'timeoffset\'\s*=\>\s*\'?.*?\'?,#is', "'timeoffset' => '$timeoffset',", $s);
			$s = preg_replace('#\'installed\'\s*=\>\s*\'?.*?\'?,#is', "'installed' => 1,", $s);
			
			// 修改密码
			$muser = core::model($conf, 'user');
			$admin = $db->get("user-uid-1");
			$admin['username'] = $adminuser;
			$admin['salt'] = rand(100000, 999999);
			$admin['password'] = $muser->md5_md5($adminpass, $admin['salt']);
			$db->set("user-uid-1", $admin);
			$u = $db->get("user-uid-2");
			$u['groupid'] = 11;
			$u['salt'] = $admin['salt'];
			$u['password'] = $admin['password'];
			$db->set("user-uid-2", $u);

			// 初始化 upload 目录
			if(!IN_SAE) {
				!is_dir($conf['upload_path'].'forum') && mkdir($conf['upload_path'].'forum', 0777);
				!is_dir($conf['upload_path'].'avatar') && mkdir($conf['upload_path'].'avatar', 0777);
				!is_dir($conf['upload_path'].'friendlink') && mkdir($conf['upload_path'].'friendlink', 0777);
				!is_dir($conf['upload_path'].'attach') && mkdir($conf['upload_path'].'attach', 0777);
				!is_dir($conf['upload_path'].'plugin') && mkdir($conf['upload_path'].'plugin', 0777);
			}
			
			// 清理
			!IN_SAE && clear_tmp('', $conf['tmp_path']);
			
			// 生成全局配置
			$kv = core::model($conf, 'kv');
			$kvconf = array(
				'app_name' => 'Xiuno BBS',		// 站点名称
				'urlrewrite' => 0,			// 是否开启 URL-Rewrite
				'timeoffset' => '+8',
				'forum_index_pagesize' => 20,		// 列表页的 pagesie，可以修改，建议不要超出100。
				'cookie_keeptime' => 86400,
				'site_pv' => 100000,			// PV越高CACHE更新的越慢，该值会影响系统的负载能力
				'site_runlevel' => 0,			// 0:所有人均可访问; 1: 仅会员访问; 2:仅版主可访问; 3: 仅管理员
				'threadlist_hotviews' => 2,		// 热门主题的阀值，浏览数
				'seo_title' => 'Xiuno BBS',		// 论坛首页的 title，如果不设置则为论坛名称
				'seo_keywords' => 'Xiuno BBS',		// 论坛首页的 keyword
				'seo_description' => 'Xiuno BBS',	// 论坛首页的 description
				'search_type' => 'title',		// title|baidu|google|bing|sphinx
				'china_icp' => '',			// icp 备案号，也只有在这神奇的国度有吧。
				'app_copyright' => '© 2008-201 科技有限公司',
				'footer_js' => '',			// 页脚额外的代码，放用于统计JS之类代码。
				'iptable_on' => 0,			// IP 规则，白名单，黑名单
				'badword_on' => 0,			// 关键词过滤
				'online_hold_time' => 900,		// 在线时间，15分钟
			);
			$kv->set('conf', $kvconf);
			
			// 生成局部配置
			$kvconf = array(
				'credits_policy_thread' => 2,		// 发主题增加的积分
				'credits_policy_post' => 0,		
				'golds_policy_thread' => 1,		// 发主题增加的金币 golds（积分不能消费，金币可以消费，充值）
				'golds_policy_post' => 1,		
				'post_update_expiry' => 86400,		// 帖子多长时间后不能修改，默认为86400，一天，0不限制
				'sphinx_host' => '',			// 主机
				'sphinx_port' => '',			// 端口
				'sphinx_datasrc' => '',			// 数据源
				'sphinx_deltasrc' => '',		// 增量索引数据源，优先搜索这个
				'reg_on' => 1,				// 是否开启注册
				'reg_email_on' => 0,			// 是否开启Email激活
				'reg_init_golds' => 10,			// 注册初始化金币
				'resetpw_on' => 0,			// 是否开启密码找回
				'credits_policy_digest_1' => 10,		
				'credits_policy_digest_2' => 20,		
				'credits_policy_digest_3' => 30,		
				'golds_policy_digest_1' => 1,		
				'golds_policy_digest_2' => 2,		
				'golds_policy_digest_3' => 3,		
			);
			$kv->set('conf_ext', $kvconf);
			
			$runtime = core::model($conf, 'runtime');
			$runtime->xupdate('forumarr');
			$runtime->xupdate('grouparr');
			
			if(!misc::is_writable($configfile)) {
				include './header.inc.php';
				if(IN_SAE) {
					echo '<h3>SAE 环境下安装需要手工编辑 conf/conf.php，复制以下代码，粘贴到 conf/conf.php：</h3>';
				} else {
					echo '<h3>当前的配置文件不可写，需要手工编辑 conf/conf.php，复制以下代码，粘贴到 conf/conf.php：</h3>';
				}
				echo '<div><textarea style="width: 700px; height: 400px">'.$s.'</textarea></div>';
				echo '<div>【注意】 需要使用UTF-8编辑器，请不要使用WINDOWS 记事本！</div>';
				echo '<div><input type="submit" value=" 下一步" name="formsubmit" onclick="window.location=\'index.php?step=plugin\'" /></div>';
				include './footer.inc.php';
				exit;
			} else {
				file_put_contents($configfile, $s);
			}
		}
	}
	
	$timeoffset_select = form::get_select('timeoffset', $timezones, $timeoffset);
		
	$master = $conf['db'][$type]['master'];
	if(IN_SAE && empty($_POST)) {
		$master['host'] = SAE_MYSQL_HOST_M.(SAE_MYSQL_PORT == 3306 ? '' : ':'.SAE_MYSQL_PORT);// SAE_MYSQL_HOST_S
		$master['user'] = SAE_MYSQL_USER;
		$master['password'] = SAE_MYSQL_PASS;
		$master['name'] = SAE_MYSQL_DB;
	}
	include './header.inc.php';	
	include './check_db.inc.php';	
	include './footer.inc.php';
	exit;
	
} elseif($step == 'complete') {
	// 检查 tmp 目录是否为空
	if(IN_SAE && !is_file($conf['tmp_path'].'_runtime.php')) {
		message('SAE 环境下需要手工上传 tmp 文件夹，请返回上一步，下载 tmp.zip ，解压后上传到服务器。');
	}
	
	// 设置 cookie
	header('Content-Type: text/html; charset=UTF-8');
	include './header.inc.php';	
	echo '<h1>安装完成，点击<a href="../">【跳转到首页】</a>！</h1>';
	echo '<h3>安装完成，为了安全请删除 install 目录</h3><script>setTimeout(function() {window.location="../";}, 3000);</script>';
	include './footer.inc.php';
	file_put_contents($conf['upload_path'].'install.lock', '');
	exit;
}
