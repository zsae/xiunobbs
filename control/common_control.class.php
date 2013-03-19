<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

class common_control extends base_control {
	
	// 为了避免与 model 冲突，加下划线分开
	public $_sid = '';		// session id
	public $_user = array();	// 全局 user
	public $_group = array();	// 全局 group, 包含用户权限
	
	// header 相关
	public $_title = array();	// header.htm title
	public $_nav = array();		// header.htm 导航
	public $_seo_keywords = '';	// header.htm keywords
	public $_seo_description = '';	// header.htm description
	public $_checked = array();	// 选中状态
	
	// 计划任务
	protected $_cron_1_run = 0;	// 计划任务1 是否被激活, 15 分钟执行一次
	protected $_cron_2_run = 0;	// 计划任务2 是否被激活, 每天0点执行一次
	
	// hook common_control_before.php
	
	// 初始化 _sid, _user, _title, _nav
	function __construct(&$conf) {
		// hook common_control_construct_before.php
		parent::__construct($conf);
		// hook common_control_construct_after.php
		
		$this->init_conf();
		$this->init_timezone();
		$this->init_view();
		$this->init_sid();
		$this->init_pm();
		$this->init_user();
		$this->check_ip();
		$this->check_domain();
		$this->init_cron();
		$this->init_online();
		
		// hook common_control_init_after.php
	}
	
	// PHP 规定析构函数中不能抛出异常！保证此函数高度可用！这个析构函数最先执行，这里 db 未释放。
	function __destruct() {
		// $db 有可能比这两个 model 实例更早析构！
		if(isset($this->runtime)) {
			$this->runtime->save_changed();
		}
		if(isset($this->kv)) {
			$this->kv->save_changed();
		}
		
		if(DEBUG > 1 && !empty($_SERVER['trace'])) {
			//restore_exception_handler();
			//restore_error_handler();
			$s = "\r\n\r\n\r\n---------------------------------------------------------------------------------\r\n<?php exit;?>\r\n---------------------------------------------------------------------------------\r\n$_SERVER[REQUEST_URI]:\r\nPOST: ".print_r($_POST, 1)."\r\nSQL:".print_r($_SERVER['sqls'], 1)."\r\n";
			$s .= $_SERVER['trace'];
			!is_file($this->conf['log_path'].'trace.php') && file_put_contents($this->conf['log_path'].'trace.php', '');
			log::write($s, 'trace.php');
		}
	}
	
	private function init_conf() {
		$this->conf += $this->runtime->xget();
		// hook common_control_init_runtime_after.php
	}
	
	private function init_timezone() {
		
		// 第一次访问，默认为系统所在时区。以后为用户所在时区。
		if(!isset($_COOKIE['timeoffset'])) {
			$timeoffset = $this->conf['timeoffset'];
		} else {
			$timeoffset = misc::mid(intval($_COOKIE['timeoffset']), -12, 12);
			$timeoffset = sprintf('%+d', $timeoffset);
		}
		$timeoffset2 = $timeoffset;
		$timeoffset2[0] = $timeoffset[0] == '+' ? '-' : '+';
		date_default_timezone_set('Etc/GMT'.$timeoffset2);	// 覆盖掉框架设置的默认值
		$_SERVER['timeoffset'] = $timeoffset;
		
		// 今日凌晨0点的开始时间！
		$_SERVER['time_fmt'] = date('Y-n-d H:i', $_SERVER['time']);			// +8 hours
		$arr = explode(' ', $_SERVER['time_fmt']);
		list($y, $n, $d) = explode('-', $arr[0]);
		$_SERVER['time_today'] = mktime(0, 0, 0, $n, $d, $y);	// -8 hours
		// hook common_control_init_timezone_after.php
	}
	
	private function init_view() {
		$fid = intval(core::gpc('fid'));
		$this->_checked['forum_'.$fid] = ' class="checked"';
		$this->view->assign('conf', $this->conf);
		$this->view->assign('_title', $this->_title);
		$this->view->assign('_nav', $this->_nav);
		$this->view->assign('_seo_keywords', $this->_seo_keywords);
		$this->view->assign('_seo_description', $this->_seo_description);
		$this->view->assign('_checked', $this->_checked);
		$this->view->assign('_cron_1_run', $this->_cron_1_run);
		
		// hook common_control_init_view_after.php
	}
	
	// 初始化 sid
	private function init_sid() {
		$key = $this->conf['cookie_pre'].'sid';
		$sid = core::gpc($key, 'R');
		if(!$sid) {
			$sid = substr(md5($_SERVER['REMOTE_ADDR'].rand(1, 2147483647)), 0, 16); // 兼容32,64位
			misc::setcookie($key, $sid, $_SERVER['time'] + 86400 * 30, $this->conf['cookie_path'], $this->conf['cookie_domain']);
		}
		$this->_sid = $sid;
		$this->view->assign('_sid', $this->_sid);
		
		define('FORM_HASH', misc::form_hash($this->conf['auth_key']));
		
		// hook common_control_init_sid_after.php
	}
	
	private function init_pm() {
		// 设置短消息心跳频率
		if($this->conf['site_pv'] < 100000) { $pm_delay = 1500; }
		elseif($this->conf['site_pv'] < 1000000) { $pm_delay = 2500; }
		elseif($this->conf['site_pv'] < 6000000) { $pm_delay = 3500; }
		elseif($this->conf['site_pv'] >= 6000000) { $pm_delay = 5500; }
		$this->view->assign('pm_delay', $pm_delay);
		
		// hook common_control_init_pm_after.php
	}
	
	// 初始化 _user, 解密 cookie
	private function init_user() {
		$auth = core::gpc($this->conf['cookie_pre'].'auth', 'R');
		$this->view->assign('_auth', $auth);
		
		$this->_user = $this->user->decrypt_auth($auth);
		$this->view->assign('_user', $this->_user);
		
		$this->_group = $this->group->read($this->_user['groupid']);
		$this->view->assign('_group', $this->_group);
		
		// 如果管理员超出一天，IP地址发生变化，则提示重新登录，每天必须强行登陆一次。
		if($this->_user['groupid'] == 1 && $_SERVER['time'] - $this->_user['cookietime'] > 86400 && !$this->_user['ip_right']) {
			misc::setcookie($this->conf['cookie_pre'].'auth', '', 0, $this->conf['cookie_path'], $this->conf['cookie_domain']);
			$auth = '';
			$this->message('尊敬的管理员，系统检测到您的IP发生变化，为了您的安全，请重新登录。<script>setTimeout("window.location.reload()", 2000);</script>', 0);
		}
		
		// 站点访问权限判断 0:所有人均可访问; 1: 仅会员访问; 2:仅版主可访问; 3: 仅管理员
		$get0 = core::gpc(0);
		$get1 = core::gpc(1);
		$skip_action = ($get0 == 'user' && ($get1 == 'login' || $get1 == 'create' || $get1 == 'logout' || $get1 == 'checkname' || $get1 == 'checkemail'));
		if($this->conf['site_runlevel'] == 1 && $this->_user['groupid'] == 0 && !$skip_action) {
			$infoadd = $this->conf['reg_on'] ? '，您可以注册会员。' : '，当前注册已关闭。';
			$this->message('站点当前设置：只有会员能访问'.$infoadd, 0);
		} elseif($this->conf['site_runlevel'] == 2 && $this->_user['groupid'] >= 11 && !$skip_action) {
			$this->message('站点当前设置：版主以上权限才能访问，（'.$this->_user['groupname'].'）不允许。', 0);
		} elseif($this->conf['site_runlevel'] == 3 && $this->_user['groupid'] != 1 && !$skip_action) {
			$this->message('站点当前设置：只有管理员才能访问。', 0);
		}
		
		$this->_user['access'] = $this->_user['uid'] > 0 && !empty($this->_user['accesson']) ? $this->user_access->read($this->_user['uid']) : array();
		
		$this->_user['groupname'] = $this->_group['name'];
		
		// 为了安全，访问前台的时候，清理后台的 cookie，防止跨站造成危害。
		if($this->conf['app_id'] != 'bbsadmin') {
			if($this->_user['groupid'] > 0 && $this->_user['groupid'] < 6 && core::gpc($this->conf['cookie_pre'].'safe_admin_auth', 'C') && core::gpc(0) != 'pm') {
				misc::setcookie($this->conf['cookie_pre'].'admin_auth', '', $_SERVER['time'], $this->conf['cookie_path'], $this->conf['cookie_domain']);
			}
		}
		
		// hook common_control_init_user_after.php
	}
	
	// 检查IP
	private function check_ip() {
		// IP 规则
		if($this->conf['iptable_on']) {
			$arr = $this->kv->get('iptable');
			$blacklist = $arr['blacklist'];
			$whitelist = $arr['whitelist'];
			$ip = $_SERVER['REMOTE_ADDR'];
			if(!empty($blacklist)) {
				foreach($blacklist as $black) {
					if(substr($ip, 0, strlen($black)) == $black) {
						$this->message('对不起，您的IP ['.$ip.'] 已经被禁止，如果有疑问，请联系管理员。', 0);
					}
				}
			}
			if(!empty($whitelist)) {
				$ipaccess = FALSE;
				foreach($whitelist as $white) {
					if(substr($ip, 0, strlen($white)) == $white) {
						$ipaccess = TRUE;
						break;
					}
				}
				if(!$ipaccess) {
					$this->message('对不起，您的IP ['.$ip.'] 不允许访问，如果有疑问，请联系管理员。', 0);
				}
			}
		}
		
		// hook common_control_check_ip_after.php
	}
	
	// 检查域名，如果不在安装域名下，跳转到安装域名。
	private function check_domain() {
		$appurl = $this->conf['app_url'];
		preg_match('#^http://([^/]+)/#', $appurl, $m);
		$installhost = $m[1];
		$host = core::gpc('HTTP_HOST', 'S');
		if($host != $m[1]) {
			$currurl = misc::get_script_uri();
			$newurl = preg_replace('#^http://([^/]+)/#', "http://$installhost/", $currurl);
			header("Location: $newurl");
			exit;
		}
		
		/* 
			/bbs/index.php?user-login.htm
			/bbs/index.php?
			/bbs/index.php
			/index.php
			/
			/?
		 */
		// 兼容 iis
		$pos = strrpos($_SERVER['REQUEST_URI'], '/');
		if(substr($_SERVER['REQUEST_URI'], $pos + 1, 9) == 'index.php') {
			$_SERVER['REQUEST_URI'] = substr_replace($_SERVER['REQUEST_URI'], '', $pos + 1, 9);
		}
		
		// 判断是否开启了 urlrewrite
		if($this->conf['urlrewrite']) {
			// 查找最后一个 /
			if(substr($_SERVER['REQUEST_URI'], $pos + 1, 1) == '?') {
				// 去掉 ?
				$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, $pos + 1).substr($_SERVER['REQUEST_URI'], $pos + 2);
				$newurl = misc::get_script_uri();
				header("Location: $newurl");
				exit;
			}
		} else {
			if($pos + 1 != strlen($_SERVER['REQUEST_URI']) && substr($_SERVER['REQUEST_URI'], $pos + 1, 1) != '?') {
				// 加上 ?
				$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, $pos + 1).'?'.substr($_SERVER['REQUEST_URI'], $pos + 1);
				$newurl = misc::get_script_uri();
				header("Location: $newurl");
				exit;
			}
		}
	}
	
	private function init_cron() {
		$cron_1_next_time = $this->conf['cron_1_next_time'];
		$cron_2_next_time = $this->conf['cron_2_next_time'];
		if($_SERVER['time'] > min($cron_1_next_time, $cron_2_next_time)) {
			if($_SERVER['time'] > $cron_1_next_time) {
				$this->_cron_1_run = TRUE;
			}
			if($_SERVER['time'] > $cron_2_next_time) {
				$this->_cron_2_run = TRUE;
			}
			$this->cron->run();
		}
		
		// hook common_control_init_cron_after.php
	}
	
	private function init_online() {
		// 每隔 5 分钟插入一次！ cookie 控制时间
		$lastonlineupdate = core::gpc($this->conf['cookie_pre'].'lastonlineupdate', 'C');// cookie 中存放的为北京时间
		if(empty($lastonlineupdate) || $lastonlineupdate < $_SERVER['time'] - 300) {
			misc::setcookie($this->conf['cookie_pre'].'lastonlineupdate', $_SERVER['time'], $_SERVER['time'] + 86400, $this->conf['cookie_path'], $this->conf['cookie_domain']);
			$this->update_online();
		}
		// 每天更新一次用户组，发帖，回帖也会更新。
		$lastday = core::gpc($this->conf['cookie_pre'].'lastday', 'C');
		if(empty($lastday) || $lastday < $_SERVER['time'] - 86400) {
			misc::setcookie($this->conf['cookie_pre'].'lastday', $_SERVER['time'], $_SERVER['time'] + 86400, $this->conf['cookie_path'], $this->conf['cookie_domain']);
			
			// 更新用户组
			$user = $this->user->read($this->_user['uid']);
			$this->user->update_group($user, $this->_user['groupid']);
		}
		
		// hook common_control_init_online_after.php
	}
	
	// 初始化在线，
	public function update_online() {
		// 只执行一次。做标记位。
		static $updated = 0;
		if($updated) return;
		
		$online = array(
			'sid'=>$this->_sid,
			'uid'=>$this->_user['uid'],
			'username'=>$this->_user['username'],
			'groupid'=>$this->_user['groupid'],
			'ip'=>ip2long($_SERVER['REMOTE_ADDR']),
			'url'=>$_SERVER['REQUEST_URI'],
			'lastvisit'=>$_SERVER['time'],
		);
		$this->online->xcreate($online);
		
		$updated = 1;
	}
	
	/*
	 * 功  能：
	 * 	提示单条信息
	 *  
	 * 用  法：
		 $this->message('站点维护中，请稍后访问！');
		$this->message('提交成功！', TRUE, '?forum-index-123.htm');
		$this->message('校验错误！', FALSE);
	 */
	public function message($message, $status = 1, $goto = '') {
		
		// hook common_control_message_before.php
		
		if(core::gpc('ajax', 'R')) {
			// 可能为窗口，也可能不为。
			$json = array('servererror'=>'', 'status'=>$status, 'message'=>$message);
			echo core::json_encode($json);
			exit;
		} else {
			$this->view->assign('message', $message);
			$this->view->assign('status', $status);
			$this->view->assign('goto', $goto);
			$this->view->display('message.htm');
			exit;
		}
	}
	
	// relocation
	public function location($url) {
		header("Location: ".$url);
		exit;
	}
	
	public function form_submit() {
		// hook form_submit_after.php
		// hook common_form_hash.php
		return misc::form_submit($this->conf['auth_key']);
	}
	
	// --------------------------> bbs 权限相关和公共的方法
	
	// 权限细化以后，这里已经没啥用了。只能判断一下菜单显示什么的。实际的操作权限走 check_access()
	protected function is_mod($forum, $user) {
		// == 2 超级版主所有版块均有权限
		if($user['groupid'] == 1 || $user['groupid'] == 2) {
			return TRUE;
		} elseif($user['groupid'] == 4) {
			return strpos(' '.$forum['modids'].' ', ' '.$user['uid'].' ') !== FALSE;
		}
		return FALSE;
	}
	
	// 检测用户权限，主要为全局禁止，优先级:1, $expiry 为解除禁止权限的时间。
	protected function check_user_access($user, $action = 'post', &$message) {
		$uid = $user['uid'];
		$actiontext = array('read'=>'阅读帖子', 'thread'=>'发表帖子', 'post'=>'回贴', 'attach'=>'上传附件', 'down'=>'下载附件');
		
		// 跳过检测
		if(!isset($actiontext[$action])) return TRUE;
		if($user['groupid'] == 1) return TRUE;
		
		if(!isset($user['accesson'])) {
			$user = $this->user->read($user['uid']);
		}
		
		if($user['accesson']) {
			$access = $this->user_access->read($uid);
			// 判断过期时间，如果已经过期，则恢复权限。
			if($access['expiry'] < $_SERVER['time']) {
				$this->user_access->reset($uid);
				return TRUE;
			}
			if($access['allow'.$action]) {
				return TRUE;
			} else {
				$expiry = date('Y-n-j', $access['expiry']);
				$message = '您没有['.$actiontext[$action].']的权限，该限制会在['.$expiry.']解除。如果您有疑问，请联系管理员！';
				return FALSE;
			}
		} else {
			return TRUE;
		}
	}
	
	// 检测用户组权限，优先级:2
	protected function check_group_access($group, $action, &$message) {
		$actiontext = array('read'=>'阅读帖子', 'thread'=>'发表帖子', 'post'=>'回帖', 'attach'=>'上传附件', 'down'=>'下载附件', 'top'=>'设置置顶', 'move'=>'移动主题', 'update'=>'编辑主题', 'delete'=>'删除帖子', 'banuser'=>'禁止用户', 'deleteuser'=>'删除用户');
		if(empty($group['allow'.$action])) {
			$message = '您所在的用户组('.$this->_group['name'].')没有('.$actiontext[$action].')的权限。如果您有疑问，请联系管理员！';
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	// 检测版块权限，优先级:3
	protected function check_forum_access($forum, $action = 'post', &$message) {
		$actiontext = array('read'=>'阅读帖子', 'thread'=>'发表帖子', 'post'=>'回贴', 'attach'=>'上传附件', 'down'=>'下载附件');
		
		// 跳过检测
		if(!isset($actiontext[$action])) return TRUE;
		
		// 如果没有权限限制，默认是允许
		if(!$forum['accesson']) return TRUE;
		
		$access = $this->forum_access->read($forum['fid'], $this->_user['groupid']);
		if(empty($access)) return TRUE;
		
		if($access['allow'.$action]) {
			return TRUE;
		} else {
			$loginadd = empty($this->_user['uid']) ? ' <a href="?user-login-ajax-1.htm" class="ajaxdialog" onclick="return false" rel="nofollow">点击登录</a>' : '';
			$message =  '您没有对该板块的(<b>'.$actiontext[$action].'</b>)权限！'.$loginadd;
			return FALSE;
		}
	}
	
	// 综合权限检测：非版主权限的操作。
	protected function check_access($forum, $action) {
		$user = $this->_user;
		$group = $this->_group;
	
		$adminaction = array('top'=>'设置置顶', 'move'=>'移动主题', 'update'=>'编辑主题', 'delete'=>'删除帖子', 'banuser'=>'禁止用户', 'deleteuser'=>'删除用户');
		
		if(!isset($adminaction[$action])) {
			// 判断该用户是否已经被禁止该权限。
			if(!$this->check_user_access($user, $action, $message)) {
				$this->message($message, 0);
			}
			
			// 用户组如果不允许发帖，则不允许
			if(!$this->check_group_access($group, $action, $message)) {
				$this->message($message, 0);
			} else {
				// 如果版块设置了权限，那么还有最后一线希望，看版块是否允许这个用户组的操作
				if(!empty($forum['accesson'])) {
					
					// 如果版块也没有允许此用户组的权限，则提示错误，中断操作！
					if(!$this->check_forum_access($forum, $action, $message)) {
						$this->message($message, 0);
					} 
				}
			}
		} else {
			if(!$this->check_group_access($group, $action, $message)) {
				$this->message("对不起，您所在的用户组($group[name])没有($forum[name])权限。");
			}
			if(!$this->is_mod($forum, $user)) {
				$this->message("对不起，您所在的用户组($group[name])没有权限管理此版块($forum[name])。");
			}
		}
	}
	
	// 检查是否登录
	public function check_login() {
		if(empty($this->_user['uid'])) {
			$this->message('您还没有登录，请先登录。', -1); // .print_r($_COOKIE, 1)
		}
	}
	
	protected function check_user_exists($user) {
		if(empty($user)) {
			$this->message('用户不存在！可能已经被删除。', 0);
		}
	}
	
	protected function check_forum_exists($forum) {
		if(empty($forum)) {
			$this->message('板块不存在！可能被设置了隐藏。', 0);
		}
	}
	
	protected function check_thread_exists($thread) {
		if(empty($thread)) {
			$this->message('主题不存在！可能已经被删除。', 0);
		}
	}
	
	protected function check_post_exists($post) {
		if(empty($post)) {
			$this->message('帖子不存在！可能已经被删除。', 0);
		}
	}
	
	// 检查是否为待验证用户组。
	protected function check_forbidden_group() {
		if($this->_user['groupid'] == 7) {
			$this->message('对不起，您的账户被禁止发帖。', 0);
		}
	}
	
	protected function check_user_delete($user) {
		if(empty($user)) {
			misc::setcookie($this->conf['cookie_pre'].'auth', '', 0, $this->conf['cookie_path'], $this->conf['cookie_domain']);
			$this->message('您的账户已经被删除。', 0);
		}
	}
	
	protected function check_forum_status($forum) {
		if($this->_user['groupid'] == 1) {
			return TRUE;
		}
		if(empty($forum['status'])) {
			$this->message('该板块不存在，或者已经被删除。', 0);
		}
	}
	
	protected function clear_tmp($pre = '') {
		$len = strlen($pre);
		$dh = opendir($this->conf['tmp_path']);
		while(($file = readdir($dh)) !== FALSE ) {
			if($file != "." && $file != ".." && $file[0] != '.') {
				if(empty($pre) || substr($file, 0, $len) == $pre) {
					unlink($this->conf['tmp_path']."$file");
				}
			}
		}
		closedir($dh);
	}
}

// hook common_control_after.php

?>