<?php

// 加载框架
define('DEBUG', 2);
define('BBS_PATH', '../../../');
$conf = include BBS_PATH.'conf/conf.php';
define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';
core::init();
core::ob_start();

// 加载 uc 依赖
//define('UC_VERSION', '1.0.0');	// 兼容所有版本
define('API_RETURN_SUCCEED', '1');
define('API_RETURN_FAILED', '-1');
define('API_RETURN_FORBIDDEN', '-2');
include $conf['plugin_path'].'ucenter/ucenter_simple_client.php';
$ucconf = include $conf['plugin_path'].'ucenter/conf.php';

$code = core::gpc('code');
parse_str(uc_authcode($code, 'DECODE', $ucconf['uc_appkey']), $get);
if(empty($get)) {
	exit('Invalid Request');
}
if($_SERVER['time'] - $get['time'] > 3600) {
	exit('Authracation has expiried');
}


$action = $get['action'];
if($action == 'test') {

	exit(API_RETURN_SUCCEED);

} elseif($action == 'deleteuser') {

	$uids = $get['ids'];
	$uids = str_replace("'", '', $uids);
	$arr = explode(',', $uids);
	$muser = new user();
	foreach($arr as $uid) {
		$uid = intval($uid);
		$muser->xdelete($uid);
	}

	exit(API_RETURN_SUCCEED);

} elseif($action == 'renameuser') {

	/*
	$uid = $get['uid'];
	$usernameold = $get['oldusername'];
	$usernamenew = $get['newusername'];
	*/
	exit(API_RETURN_SUCCEED);
	
} elseif($action == 'synlogin') {

	$uid = intval($get['uid']);
	
	$muser = new user();
	$userdb = $muser->read($uid);
	$muser->set_login_cookie($userdb);
		
	exit(API_RETURN_SUCCEED);
	
} elseif($action == 'synlogout') {

	header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
	
	misc::setcookie($conf['cookie_pre'].'auth', '', 0, $conf['cookie_path'], $conf['cookie_domain']);

	exit(API_RETURN_SUCCEED);
	
} elseif($action == 'updatepw') {

	$username = $get['username'];
	$password = $get['password'];
	
	$muser = new user();
	$user = $muser->get_user_by_username($username);
	if(empty($user)) {
		log::write('用户不存在：'.$username);
		exit(API_RETURN_FAILED);
	}
	$user['password'] = $muser->md5_md5($password, $user['salt']);
	$this->user->update($user);
	
	// 重新设置 cookie
	header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
	$this->user->set_login_cookie($user);
	
	exit(API_RETURN_SUCCEED);

} else {

	exit(API_RETURN_FORBIDDEN);

}