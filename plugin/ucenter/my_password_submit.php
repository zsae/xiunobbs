
			// --------------------------> my_password_after start
			
			// 修改完密码，通知其他应用。直接 http 协议。
			
			$result = uc_user_updatepw($this->_user['username'], $newpassword);
			if($result < 0) {
				log::write(' UCenter 返回错误，错误代码：'.$result);
				$this->message(' UCenter 返回错误，错误代码：'.$result);
			}
			
			//--------------------------> end