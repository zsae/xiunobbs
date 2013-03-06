		define('UC_USER_CHECK_USERNAME_FAILED', -1);
		define('UC_USER_USERNAME_BADWORD', -2);
		define('UC_USER_USERNAME_EXISTS', -3);
		define('UC_USER_EMAIL_FORMAT_ILLEGAL', -4);
		define('UC_USER_EMAIL_ACCESS_ILLEGAL', -5);
		define('UC_USER_EMAIL_EXISTS', -6);

		// --------------------------> user_create_after start

		if(!array_filter($error)) {
			global $ucconf;
			$ucconf = include $this->conf['plugin_path'].'ucenter/conf.php';
			include $this->conf['plugin_path'].'ucenter/ucenter_simple_client.php';
			$result = uc_user_register($username, $password, $email);
			if($result > 0) {
				$user['uid'] = intval($result);
			} elseif($result == UC_USER_CHECK_USERNAME_FAILED) {
				$error['username'] = '用户名格式不对';
				$this->message($error);
			} elseif($result == UC_USER_USERNAME_BADWORD) {
				$error['username'] = '用户名包含敏感字符';
				$this->message($error);
			} elseif($result == UC_USER_USERNAME_EXISTS) {
				$error['username'] = '用户名已经存在';
				$this->message($error);
			} elseif($result == UC_USER_EMAIL_FORMAT_ILLEGAL) {
				$error['email'] = 'Email 格式不正确';
				$this->message($error);
			} elseif($result == UC_USER_EMAIL_EXISTS) {
				$error['email'] = 'Email 已经存在';
				$this->message($error);
			} else {
				log::write('注册到 UCenter 失败，错误代码：'.$result);
				$this->message('注册到 UCenter 失败，错误代码：'.$result, 0);
			}
		}

		//--------------------------> end
		
	