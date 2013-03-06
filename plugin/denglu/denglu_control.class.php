<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class denglu_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['bbs'] = ' class="checked"';
		
		$this->conf += $this->kv->xget('conf_ext');
	}
	
	// 列表
	public function on_index() {
		
		// 如果已经登录 ?
		
		// 加载插件配置
		$pconf = include $this->conf['plugin_path'].'denglu/conf.php';
		
		
		// 获取回调
		$token = core::urldecode(core::gpc('token'));
		
		if(empty($token)) {
			$this->message('token 为空。');
		}
		
		$appid = $pconf['denglu_appid'];
		$appkey = $pconf['denglu_appkey'];
				
		
		$post = $this->create_post_body($token, $appid, $appkey);
		
		$s = misc::fetch_url("http://open.denglu.cc/api/v4/user_info", 5, $post);
		//$s = '{"mediaID":13,"createTime":"2012-08-09 17:04:14","friendsCount":0,"location":null,"favouritesCount":0,"screenName":"黄","profileImageUrl":"http:\/\/qzapp.qlogo.cn\/qzapp\/100232596\/EFDDF6C09915842D19F6D267297BC81C\/100","mediaUserID":19599303,"url":null,"homepage":null,"city":null,"email":null,"createdAt":"","verified":0,"description":null,"name":"黄","province":null,"domain":null,"followersCount":0,"gender":0,"statusesCount":0,"personID":18462738}';
		$arr = json_decode($s, true);
		if(empty($arr)) {
			log::write('灯鹭返回数据为空。');
			$this->message('灯鹭返回数据为空。');
		}
		
		if(!empty($arr['errorCode'])) {
			log::write('灯鹭 errorCode:'.$arr['errorCode']);
			$this->message('灯鹭 errorCode:'.$arr['errorCode']);
		} else {
			$mediaid = intval($arr['mediaID']);		// qzone = 13
			$muid = intval($arr['mediaUserID']);		// 用户唯一id，可以用来生成一个id
			$username = !empty($arr['name']) ? $arr['name'] : $arr['screenName'];
			$avatarurl = $arr['profileImageUrl'];
			
			$muser = new user();
			$user = $this->user_denglu->read($muid);
			if(empty($user)) {
				
				// 通过 token 获取资料成功，给出修改资料页面！自带 verify
				$time = substr($_SERVER['time'], 0, -4); // 超时过期，三小时
				$verify = md5($time.$muid.$mediaid.$username.$avatarurl.md5($this->conf['auth_key']));
				$username_url = core::urlencode($username);
				$avatarurl_url = core::urlencode($avatarurl);
				$this->location("?denglu-profile-muid-$muid-mediaid-$mediaid-username-$username_url-avatarurl-$avatarurl_url-verify-$verify.htm");
				
			} else {
				$user = $this->user->read($user['uid']);
				$this->user->set_login_cookie($user);
			}
			
			$this->message($user['username'].'，您好，登录成功，<a href="../../"><b>点击进入首页</b></a>', 1, './');
			
			exit;
		}

	}
	
	public function on_profile() {
		
		$conf = $this->conf;
		
		// GET 传参，如何防止伪造？
		$mediaid = intval(core::gpc('mediaid'));		//
		$muid = intval(core::gpc('muid'));			// 
		$username_url = core::gpc('username');
		$username = urldecode($username_url);
		$avatarurl_url = core::gpc('avatarurl');
		$avatarurl = urldecode($avatarurl_url);
		$verify = core::gpc('verify');
		$time = substr($_SERVER['time'], 0, -4);
		$verify2 = md5($time.$muid.$mediaid.$username.$avatarurl.md5($this->conf['auth_key']));
		if($verify != $verify2) {
			$this->message('校验码不一致，可能已经过期！');
		}
		
		$this->view->assign('muid', $muid);
		$this->view->assign('mediaid', $mediaid);
		$this->view->assign('username', $username);
		$this->view->assign('username_url', $username_url);
		$this->view->assign('avatarurl', $avatarurl);
		$this->view->assign('avatarurl_url', $avatarurl_url);
		$this->view->assign('verify', $verify);
		
		$error = array();
		if($this->form_submit()) {
			
			$username = core::gpc('username', 'P');
			$email = core::gpc('email', 'P');
			$password = core::gpc('password', 'P');
			$password2 = core::gpc('password2', 'P');
			
			// copy from user_control.class.php
			$error['email'] = $this->user->check_email($email);
			$error['email_exists'] = $this->user->check_email_exists($email);
			
			// 如果email存在
			if($error['email_exists']) {
				// 如果该Email一天内没激活，则删除掉，防止被坏蛋“占坑”。
				$uid = $this->user->get_uid_by_email($email);
				$_user = $this->user->read($uid);
				if($_user['groupid'] == 6 && $_SERVER['time'] - $_user['regdate'] > 86400) {
					$this->user->delete($uid);
					$error['email_exists'] = '';
				}
				$error['email'] = $error['email_exists'];
				unset($error['email_exists']);
			}
			
			$error['username'] = $this->user->check_username($username);
			empty($error['username']) && $error['username'] = $this->user->check_username_exists($username);
			$error['password'] = $this->user->check_password($password);
			$error['password2'] = $this->user->check_password2($password, $password2);
			
			$groupid = $this->conf['reg_email_on'] ? 6 : 11;
			$salt = rand(100000, 999999);
			$user = array(
				'username'=>$username,
				'email'=>$email,
				'password'=>$this->user->md5_md5($password, $salt),
				'groupid'=>$groupid,
				'salt'=>$salt,
			);
			
			// hook user_create_after.php
			
			// copy end
			
			// 判断结果
			if(!array_filter($error)) {
				$error = array();
				$uid = $this->user->xcreate($user);
				if($uid) {
					$userdb = $this->user->read($uid);
					$this->user->set_login_cookie($userdb);
					
					$this->runtime->xset('users', '+1');
					$this->runtime->xset('todayusers', '+1');
					$this->runtime->xset('newuid', $uid);
					$this->runtime->xset('newusername', $userdb['username']);
					
					// hook user_create_succeed.php
					
					// 更新头像
					if($avatarurl) {
						$dir = image::get_dir($uid);
						$smallfile = $conf['upload_path']."avatar/$dir/{$uid}_small.gif";
						$middlefile = $conf['upload_path']."avatar/$dir/{$uid}_middle.gif";
						$bigfile = $conf['upload_path']."avatar/$dir/{$uid}_big.gif";
						
						try {
							$s = misc::fetch_url($avatarurl, 5);
							file_put_contents($bigfile, $s);
							
							image::thumb($bigfile, $smallfile, $conf['avatar_width_small'], $conf['avatar_width_small']);
							image::thumb($bigfile, $middlefile, $conf['avatar_width_middle'], $conf['avatar_width_middle']);
							image::thumb($bigfile, $bigfile, $conf['avatar_width_big'], $conf['avatar_width_big']);
							$user['avatar'] = $_SERVER['time'];
							
						} catch (Exception $e) {
							$userdb['avatar'] = 0;
						}
						
						$this->user->update($userdb);
					}
					
					$this->user_denglu->create(array('muid'=>$muid, 'uid'=>$uid));
				}
			}
		}
		
		$this->view->assign('error', $error);
		$this->view->assign('username', $username);
		$this->view->assign('email', $email);
		$this->view->display('denglu_profile.htm');
	}
		
		
	private function create_post_body($token, $appid, $appkey) {
		$arr = array();
		$arr['appid'] = $appid;
		$arr['sign_type'] = 'MD5';
		$arr['timestamp'] = time().'000';
		$arr['token'] = $token;
		
		ksort($arr);
		$sig = '';
		foreach($arr as $k=>$v) {
			$sig .= "$k=$v";
		}
		$sig .= $appkey;
		$arr['sign'] = md5($sig);
		
		$r = '';
		foreach($arr as $k=>$v) {
			$r .= "&$k=".core::urlencode($v);
		}
		$r = substr($r, 1);
		return $r;
	}

}

?>