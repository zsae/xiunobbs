<?php 
 
/**************************************************************************************************
 	【注意】：
 		1. 请不要使用 Windows 的记事本编辑此文件！此文件的编码为UTF-8编码，不带有BOM头！
 		2. 建议使用UEStudio, Notepad++ 类编辑器编辑此文件！
 
 	【多台WEB服务器部署说明】：
 		1. 多WEB部署的时候，后台设置完毕以后，同步此文件！
 		
 	【SAE 平台说明：】
		1. 安装后，手工编辑此文件，粘贴安装程序给出的内容。
		2. $conf['tmp_path'] = 'saekv://';
		3. $conf['upload_path'] = 'saestor://upload/';
	
	【站点搬家】
		1. 站点更换域名、搬家，需要修改此文件中的域名部分
***************************************************************************************************/

return array (

	// 数据库配置， type 为默认的数据库类型，可以支持多种数据库: mysql|pdo_mysql|pdo_oracle|mongodb
	'db' => array (		
		'type' => 'mysql',		
		'mysql' => array (			
			'master' => array (				
				'host' => 'localhost',				
				'user' => 'root',				
				'password' => 'root',				
				'name' => 'test',				
				'charset' => 'utf8',				
				'tablepre' => 'bbs_',				
				'engine'=>'MyISAM',
			),			
			'slaves' => array ()
		),
		'pdo_mysql' => array (
			'master' => array (				
				'host' => 'localhost',				
				'user' => 'root',				
				'password' => 'root',				
				'name' => 'test',				
				'charset' => 'utf8',				
				'tablepre' => 'bbs_',				
				'engine'=>'MyISAM',
			),			
			'slaves' => array ()
		),
		'mongodb' => array(
			'master' => array (
				'host' => '10.0.0.253:27017',
				'user' => '',
				'password' => '',
				'name' => 'bbs',
				'tablepre' => '',
			),
			'slaves' => array (
			)
		),
	),
	
	// 缓存服务器的配置，支持: memcache|ea|apc|redis
	'cache' => array (
		'enable'=>0,
		'type'=>'memcache',
		'memcache'=>array (
			'multi'=>0,
			'host'=>'10.0.0.253',
			'port'=>'11211',
		)
	),
		
	// 唯一识别ID
	'app_id' => 'bbs',
	
	// 应用的绝对路径： 如: http://www.domain.com/bbs/
	'app_url' => 'http://1.axiuno.sinaapp.com/',
	
	// CDN 缓存的静态域名，如 http://static.domain.com/
	'static_url' => 'http://1.axiuno.sinaapp.com/',
	
	// 模板使用的目录，按照顺序搜索，这样可以支持风格切换,结果缓存在 tmp/bbs_xxx.htm.php
	'view_path' => array(BBS_PATH.'view/'), 
	
	// 数据模块的路径，按照数组顺序搜索目录
	'model_path' => array(BBS_PATH.'model/'),
	
	// 自动加载 model 的配置， 在 model_path 中未找到 modelname 的时候尝试扫描此项, modelname=>array(tablename, primarykey, maxcol)
	'model_map' => array(
		//'user'=>array('user', 'uid', 'uid'),
		//'attach'=>array('attach', 'aid', 'aid'),
	),
	
	// 业务控制层的路径，按照数组顺序搜索目录，结果缓存在 tmp/bbs_xxx_control.class.php
	'control_path' => array(BBS_PATH.'control/'),
	
	// 临时目录，需要可写，可以指定为 linux /dev/shm/ 目录提高速度, 支持 file_put_contents() file_get_contents(), 不支持 fseek(),  SAE: saekv://
	'tmp_path' => BBS_PATH.'tmp/',

	// 上传目录，需要可写，保存用户上传数据的目录，支持 fseek(), SAE: saestor://upload/ (建立 upload 域)
	'upload_path' => 'saestor://upload/',
	
	// 模板的URL，用作CDN时请填写绝对路径，需要时，填写绝对路径： 如: http://www.domain.com/bbs/upload/, SAE: http://xxx-upload.stor.sinaapp.com/1.txt  (建立 upload 域，安装的时候需要设置)
	'upload_url' => 'http://axiuno-upload.stor.sinaapp.com/',
	
	// 日志目录，需要可写
	'log_path' => BBS_PATH.'log/',
	
	// 插件目录
	'plugin_path' => BBS_PATH.'plugin/',
	
	// 插件目录对应的URL
	'plugin_url' => 'http://1.axiuno.sinaapp.com/plugin/',
	
	// ------------------> 以下为 BBS 相关:
	
	// 点击服务器
	'click_server' => 'http://1.axiuno.sinaapp.com/clickd/',	// 记录主题点击数，论坛点击数
	
	// 加密KEY，
	'auth_key' => '3ee04f6834eb6725f566bc7b9f9c3f29',
	
	'cookie_pre'=> 'xn_',
	
	'pagesize' => 20,			// 帖子详情页的每页回复数，一旦定下来，不能修改！
	
	'system_uid' => 2,			// 系统uid，用来发送短消息
	'system_username' => '系统',		// 系统用户名，用来发送短消息
	
	'forumicon_width_small' => 16,		// 论坛图标的宽度:小
	'forumicon_width_middle' => 44,		// 论坛图标的宽度:中
	'forumicon_width_big' => 65,		// 论坛图标的宽度:大
	'avatar_width_small' => 16,		// 用户头像宽度:小
	'avatar_width_middle' => 54,		// 用户头像宽度:中
	'avatar_width_big' => 88,		// 用户头像宽度:大
	'avatar_width_huge' => 120,		// 用户头像宽度:更大
	'thread_icon_middle' => 54,		// 主题的缩略图:中
	'upload_image_max_width' => 1210,	// 上传图片最大宽度
	
	'urlrewrite' => 0,			// 手工开启 URL-Rewrite 后，需要清空下 tmp 目录！
	
	'version' => '2.0.0 Release',		// 版本号
	'installed' => 1,			// 是否安装的标志位
);
?>