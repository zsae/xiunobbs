<?php

return 	array (
	'name'=>'灯鹭-第三方登录插件',		// 插件名
	'brief'=>'整合了QQ登录、新浪微薄、MSN、人人、开心等16家网站的登录接口。',
	'version'=>'1.0',		// 插件版本
	'bbs_version'=>'2.*',		// 插件支持的 Xiuno BBS 版本
	
	// 灯鹭 相关 --------->
	'denglu_appid' => '56382denEyWkWOv0C2UzygGueGBpn6',
	'denglu_appkey' => '48337563XHobPFR9U4TmLsYCuCO7o6',
	'denglu_meta' => '<meta property="qc:admins" content="xxxx" />',	// qq 等平台需要添加 <meta 标志作为认证
	'denglu_enable' => array (
		'qzone' => 1,
		'sina' => 1,
		'tencent' => 0,
		'baidu' => 0,
		'alipay' => 0,
		'renren' => 0,
		'sohu' => 0,
		'netease' => 0,
		'kaixin001'=>0,
		'kaixin001open'=>0,
		'douban'=>0,
		'google' => 0,
		'windowslive' => 0,
		'yahoo' => 0,
		'51'=>0,
		'shengda'=>0,
		'tenpay'=>0,
		'taobao' => 0,
		'tianya' => 0,
		'alipayquick'=>0,
		'netease163' => 0,
		'guard360'=>0,
		'tianyi'=>0,
		'facebook' => 0,
		'twitter' => 0,
	),
);

?>