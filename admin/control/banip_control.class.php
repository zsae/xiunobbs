<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'admin/control/admin_control.class.php';

class banip_control extends admin_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->check_admin_group();
	}
	
	// 考虑默认过期时间
	public function on_index() {
	
		$this->_checked['black'] = ' class="checked"';
		
		$input = $error = array();
		
		$baniplist = $this->banip->get_list();
		
		if($this->form_submit()) {
			$banips = core::gpc('banip', 'P');
			$delete = core::gpc('delete', 'P');
			foreach($delete as $v) {
				$this->banip->delete($v);
				unset($banips[$v]);
			}
			
			$this->conf['iptable_on'] = intval(core::gpc('iptable_on', 'P'));
			
			foreach($banips as $banid=>$banip) {
				$ip = "$banip[ip0].$banip[ip1].$banip[ip2].$banip[ip3]";
				$this->banip->add_banip($ip, $this->_user['uid'], strtotime($banip['expiry']));
			}
			
			// iptable_on 保存在全局
			$this->kv->xset('iptable_on', $this->conf['iptable_on']);
			$this->runtime->xset('iptable_on', $this->conf['iptable_on']);
		}
		
		$input['iptable_on'] = form::get_radio_yes_no('iptable_on', $this->conf['iptable_on']);
		
		$this->view->assign('baniplist', $baniplist);
		$this->view->assign('input', $input);
		$this->view->assign('error', $error);
		
		// hook admin_banip_view_before.php
		
		$this->view->display('iptable.htm');
	}
	
	private function is_ip($ip) {
		return preg_match('#^\d+\.\d+\.\d+\.\d+$#', $ip) || preg_match('#^\d+\.\d+\.\d+$#', $ip) || preg_match('#^\d+\.\d+$#', $ip);
	}
	
	//hook admin_banip_control_after.php
}

?>