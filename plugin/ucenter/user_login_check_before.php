		global $ucconf;
		$ucconf = include $this->conf['plugin_path'].'ucenter/conf.php';
		include $this->conf['plugin_path'].'ucenter/ucenter_simple_client.php';
		
		$r = uc_user_login($email, $password);
		if(is_array($r)) {
			if($r['status'] > 0) {
				$uc_user = uc_get_user($r['username']);
				$uid = $uc_user['uid'];
				$username = $uc_user['username'];
				$email = $uc_user['email'];
				$userdb = $this->user->read($uid);
				if(empty($userdb)) {
					// 创造一个新用户
					$groupid = $this->conf['reg_email_on'] ? 6 : 11;
					$salt = rand(100000, 999999);
					$user = array(
						'uid'=>$uid,
						'username'=>$username,
						'email'=>$email,
						'password'=>$this->user->md5_md5($password, $salt),
						'groupid'=>$groupid,
						'salt'=>$salt,
					);
					$this->user->xcreate($user);
				}
				
				$error = array();
				$error['user']['username'] =  $r['username'];
				$error['user']['auth'] =  $this->user->get_xn_auth($userdb);
				$error['user']['groupid'] =  $userdb['groupid'];
				
				// hook user_login_succeed.php
				$this->user->set_login_cookie($userdb);
				
				// 更新在线列表
				$this->update_online();
				
				$url = uc_user_synlogin($uid);
				$error['sync_url'] = $url;
				
				$this->message($error);
			} elseif($r['status'] == -1) {
				$error['email'] = '用户名/Email 不存在';
				log::write('EMAIL不存在:'.$email, 'login.php');
				$this->message($error);
			} elseif($r['status'] == -2) {
				$error['password'] = '密码错误!';
				$log_password = '******'.substr($password, 6);
				log::write("密码错误：$email - $log_password", 'login.php');
				$this->message($error);
			}	
		}
		$error['email'] = 'Ucenter 返回未知错误:'.$r;
		log::write("UCenter 返回未知错误：$r", 'login.php');
		$this->message($error);