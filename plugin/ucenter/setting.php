<?php

global $ucconf;
$file = $this->conf['plugin_path'].'ucenter/conf.php';
$pconf = $ucconf = include $file;
include $this->conf['plugin_path'].'ucenter/ucenter_simple_client.php';

$error = $input = array();
if($this->form_submit()) {
	$pconf['uc_url'] = core::gpc('uc_url', 'P');
	$pconf['uc_charset'] = core::gpc('uc_charset', 'P');
	$pconf['uc_appid'] = intval(core::gpc('uc_appid', 'P'));
	$pconf['uc_appkey'] = core::gpc('uc_appkey', 'P');
	
	$password = core::gpc('password');
	
	//$this->conf->xset('uc_url', $pconf['uc_url'], $file);
	//$this->conf->xset('uc_charset', $pconf['uc_charset'], $file);
	//$this->conf->xset('uc_appid', $pconf['uc_appid'], $file);
	//$this->conf->xset('uc_appkey', $pconf['uc_appkey'], $file);
	//$this->mconf->xsave($file);
	
	/*// 生成管理员账户，uid 不同步，不解决这个问题。
	$user = uc_get_user('admin');	// 取UCenter 管理员账户，
	if(empty($user)) {
		$this->message('从 UCenter 获取管理员账户发生错误。');
	}
	
	// 修改本地管理员的账号密码
	$this->user->update_password(1, $password);
	$this->user->update_username(1, $user['username']);
	*/
	
	if(empty($pconf['uc_url'])) {
		$this->message('请填写 UCenter URL！', 0);
	}
	
	
	if(empty($pconf['uc_appkey'])) {
		$this->message('请填写 UCenter app_key！', 0);
	}
	
	$user = uc_get_user('admin');
	if(!is_array($user)) {
		$this->message('尝试连接 UCenter 发生错误，可能设置 UCenter APPKEY 或者 URL 设置有误，错误信息：'.htmlspecialchars($user), 0);
	}

}

$input['uc_url'] = form::get_text('uc_url', $pconf['uc_url'], 210);
$input['uc_charset'] = form::get_text('uc_charset', $pconf['uc_charset'], 64);
$input['uc_appid'] = form::get_text('uc_appid', $pconf['uc_appid'], 64);
$input['uc_appkey'] = form::get_text('uc_appkey', $pconf['uc_appkey'], 210);

$this->view->assign('dir', $dir);
$this->view->assign('input', $input);
$this->view->display('ucenter_setting.htm');

?>