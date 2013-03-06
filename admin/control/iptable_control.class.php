<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'admin/control/admin_control.class.php';

class iptable_control extends admin_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->check_admin_group();
	}
	
	public function on_index() {
		$this->on_black();
	}
	
	public function on_black() {
		$this->_checked['black'] = ' class="checked"';
		
		$input = $error = array();
		$arr = $this->kv->get('iptable');
		$blacklist = &$arr['blacklist'];
		$whitelist = &$arr['whitelist'];
		
		$banip = core::gpc('banip');// GET 接受参数
		if(!empty($banip)) {
			array_push($blacklist, $banip);
		}
		
		if($this->form_submit()) {
			$this->conf['iptable_on'] = intval(core::gpc('iptable_on', 'P'));
			
			$blacklist = core::gpc('ip', 'R');
			$blacklist = array_diff($blacklist, array(''));
			$blacklist = array_unique($blacklist);
			
			foreach($blacklist as $k=>$v) {
				$blacklist[$k] = trim($blacklist[$k]);
				if(!$this->is_ip($v)) unset($blacklist[$k]);
			}
			
			// iptable_on 保存在全局
			$this->kv->set('iptable', $arr);
			$this->kv->xset('iptable_on', $this->conf['iptable_on']);
			$this->runtime->xset('iptable_on', $this->conf['iptable_on']);
		}
		
		$input['iptable_on'] = form::get_radio_yes_no('iptable_on', $this->conf['iptable_on']);
		
		$this->view->assign('whitelist', $whitelist);
		$this->view->assign('blacklist', $blacklist);
		$this->view->assign('input', $input);
		$this->view->assign('error', $error);
		
		// hook admin_iptable_black_view_before.php
		
		$this->view->display('iptable.htm');
	}
	
	public function on_white() {
		$this->_checked['white'] = ' class="checked"';
		
		$input = $error = array();
		$arr = $this->kv->get('iptable');
		$whitelist = &$arr['whitelist'];
		$blacklist = &$arr['blacklist'];
		
		if($this->form_submit()) {
			$this->conf['iptable_on'] = intval(core::gpc('iptable_on', 'P'));
			
			$whitelist = core::gpc('ip', 'P');
			$whitelist = array_diff($whitelist, array(''));
			$whitelist = array_unique($whitelist);
			
			foreach($whitelist as $k=>$v) {
				$whitelist[$k] = trim($whitelist[$k]);
				if(!$this->is_ip($v)) unset($whitelist[$k]);
			}
			
			$this->kv->set('iptable', $arr);
			$this->runtime->xset('iptable_on', $this->conf['iptable_on']);
		}
		
		$input['iptable_on'] = form::get_radio_yes_no('iptable_on', $this->conf['iptable_on']);
		
		$this->view->assign('whitelist', $whitelist);
		$this->view->assign('blacklist', $blacklist);
		$this->view->assign('input', $input);
		$this->view->assign('error', $error);
		
		// hook admin_iptable_white_view_before.php
		
		$this->view->display('iptable.htm');
	}
	
	private function is_ip($ip) {
		return preg_match('#^\d+\.\d+\.\d+\.\d+$#', $ip) || preg_match('#^\d+\.\d+\.\d+$#', $ip) || preg_match('#^\d+\.\d+$#', $ip);
	}
	
	//hook admin_iptable_control_after.php
}

?>