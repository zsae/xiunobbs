
			// --------------------------> my_password_before start
			
			global $ucconf;
			$ucconf = include $this->conf['plugin_path'].'ucenter/conf.php';
			include $this->conf['plugin_path'].'ucenter/ucenter_simple_client.php';
			$r = uc_user_login($this->_user['username'], $password);
			if(is_array($r)) {
				if($r['status'] == -2) {
					$error['password'] = 'Ucenter 校验密码错误!';
				} elseif($r['status'] > 0){
					$error['password'] = '';
				} else {
					$error['password'] = 'UCenter 返回错误：'.$r['status'] ;
				}
			} else {
				$error['password'] = 'UCenter 返回错误：'.$r;
			}
			
			//--------------------------> end