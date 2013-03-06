			
		
			// ------------------> user_logout_after
			
			global $ucconf;
			$ucconf = include $this->conf['plugin_path'].'ucenter/conf.php';
			include $this->conf['plugin_path'].'ucenter/ucenter_simple_client.php';
			$url = uc_user_synlogout();
			$error['sync_url'] = $url;
			
			// ----------------------> end
			
			