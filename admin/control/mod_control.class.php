<?php

/*
 * Copyright (C) xiuno.com
 */

/*
	此模块可以供版主管理，其他模块一律不允许版主访问！
*/

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'admin/control/admin_control.class.php';

class mod_control extends admin_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
	}
	
	public function on_index() {
		$this->on_setforum();
	}
	
	// 设置版规，简介
	public function on_setforum() {
		$this->_checked['mod_setforum'] = 'class="checked"';
		
		$this->_title[] = '基本资料';
		$this->_nav[] = '基本资料';
		
		$fid = intval(core::gpc('fid'));
		
		$error = array();
		if($this->form_submit()) {
			$brief = core::gpc('brief', 'P');
			
			// 检查权限
			$forum = $this->forum->read($fid);
			if(!$this->is_mod($forum, $this->_user)) {
				$this->message('您没有权限管理此版块。');
			}
			
			$brief = htmlspecialchars(strip_tags($brief));
			
			$error['brief'] = $this->forum->check_brief($brief);
			
			if(!array_filter($error)) {
				$forum = $this->forum->read($fid);
				$forum['brief'] = $brief;
				$this->forum->xupdate($forum);
				$error = array();
			}
		}
		$this->view->assign('error', $error);
		
		$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
		$fid = $defaultfid;
		$forum = $this->forum->read($fid);
		
		$this->view->assign('forumoptions', $forumoptions);
		$this->view->assign('fid', $fid);
		
		$forum = $this->forum->read($fid);
		$this->view->assign('forum', $forum);
		
		$this->view->display('mod_setforum.htm');
	}
	
	// 列出版主管理日志
	public function on_listlog() {
		$this->_checked['mod_listlog'] = 'class="checked"';
		
		$this->_title[] = '操作日志';
		$this->_nav[] = '操作日志';
		
		$uid = $this->_user['uid'];
		
		$fid = intval(core::gpc('fid'));
		$oldfid = $fid;
		$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
		$this->view->assign('forumoptions', $forumoptions);
		$this->view->assign('fid', $defaultfid);
		
		if(empty($forumoptions)) {
			$this->message('您没有可管理的板块。');
		}
		
		// 检查权限
		$forum = $this->forum->read($defaultfid);
		if(!$this->is_mod($forum, $this->_user)) {
			$this->message('您没有权限管理此版块。');
		}
		
		$page = misc::page();
		$pagesize = 20;
		$loglist = $this->modlog->get_list_by_uid($uid, $page, $pagesize);
		$pages = misc::simple_pages("?mod-listlog-fid-$fid.htm", count($loglist), $page, $pagesize);
		$this->view->assign('pages', $pages);
		$this->view->assign('loglist', $loglist);
		
		$this->view->display('mod_listlog.htm');
	}
	
	// 列出版主评分日志
	public function on_ratelog() {
		$this->_checked['mod_ratelog'] = 'class="checked"';
		
		$this->_title[] = '评分日志';
		$this->_nav[] = '评分日志';
		
		$uid = $this->_user['uid'];
		
		$fid = intval(core::gpc('fid'));
		
		$oldfid = $fid;
		$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
		$this->view->assign('forumoptions', $forumoptions);
		$this->view->assign('fid', $defaultfid);
		
		if(empty($forumoptions)) {
			$this->message('您没有可管理的板块。');
		}
		
		// 检查权限
		$forum = $this->forum->read($defaultfid);
		if(!$this->is_mod($forum, $this->_user)) {
			$this->message('您没有权限管理此版块。');
		}
		
		$page = misc::page();
		$pagesize = 20;
		$loglist = $this->rate->get_list_by_uid($uid, $page, $pagesize);
		$pages = misc::simple_pages("?mod-ratelog.htm", count($loglist), $page, $pagesize);
		$this->view->assign('pages', $pages);
		$this->view->assign('loglist', $loglist);
		
		$this->view->display('mod_ratelog.htm');
	}
	
	// 禁止用户 / 删除用户
	public function on_manageuser() {
		// 搜索
		$this->_title[] = '用户管理';
		$this->_nav[] = '用户管理';
		$this->_checked['mod_manageuser'] = 'class="checked"';
		
		// 搜索用户
		$do = core::gpc('do');
		if(empty($do)) {
			$user = $error = array();
			if($this->form_submit()) {
				$email = trim(core::gpc('email', 'P'));
				if(empty($email)) {
					$error['email'] = '请输入用户名。';
				} else {
					$user = $this->user->get_user_by_email($email);
					empty($user) && $user = $this->user->get_user_by_username($email);
					empty($user) && $user = $this->user->read($email);
					if(empty($user)) {
						$error['email'] = '用户名不存在。';
					}
					$this->user->format($user);
				}
			}
			$this->view->assign('user', $user);
			$this->view->assign('email', $email);
			$this->view->assign('error', $error);
			$this->view->display('mod_manageuser.htm');
		} elseif($do == 'banuser') {
			if(!$this->_group['allowbanuser']) {
				$this->message('对不起，您没有禁止用户的权限', 0);
			}
			
			$uid = intval(core::gpc('uid'));
			$user = $this->user->read($uid);
			$this->check_user_exists($user);
			
			if($this->_group['groupid'] >= $user['groupid']) {
				$this->message('对不起，您没有权限管理此用户', 0);
			}
			
			$access = $this->user_access->read($uid);
			if(empty($access)) {
				$access = array(
					'uid'=>$uid,
					'allowread'=>1,
					'allowthread'=>1,
					'allowpost'=>1,
					'allowattach'=>1,
					'allowdown'=>1,
					'expiry'=>$_SERVER['time'] + 86400 * 365
				);
			}
			
			$input = $error = array();
			if($this->form_submit()) {
				$post = array('uid'=>$uid);
				$post['allowpost'] = intval(!core::gpc('allowpost', 'P'));
				$post['allowthread'] = intval(!core::gpc('allowthread', 'P'));
				$post['allowattach'] = intval(!core::gpc('allowattach', 'P'));
				$post['allowdown'] = intval(!core::gpc('allowdown', 'P'));
				$post['allowread'] = intval(!core::gpc('allowread', 'P'));
				$post['expiry'] = strtotime(core::gpc('expiry', 'P'));	// 时差问题？
				
				if(!array_filter($error)) {
					$error = array();
					$access = array_merge($access, $post);
					$this->user_access->xupdate($access);
				}
			}
			
			$input['allowpost'] = form::get_checkbox_yes_no('allowpost', empty($access['allowpost']));
			$input['allowthread'] = form::get_checkbox_yes_no('allowthread', empty($access['allowthread']));
			$input['allowattach'] = form::get_checkbox_yes_no('allowattach', empty($access['allowattach']));
			$input['allowdown'] = form::get_checkbox_yes_no('allowdown', empty($access['allowdown']));
			$input['allowread'] = form::get_checkbox_yes_no('allowread', empty($access['allowread']));
			$input['expiry'] = form::get_text('expiry', date('Y-n-j', $access['expiry']), 150);
			
			$this->view->assign('do', $do);
			$this->view->assign('input', $input);
			$this->view->assign('user', $user);
			$this->view->assign('error', $error);
			
			// hook admin_user_access_view_before.php
			
			$this->view->display('mod_manageuser.htm');
		} elseif($do == 'deleteuser') {
			if(!$this->_group['allowdeleteuser']) {
				$this->message('对不起，您没有删除用户的权限', 0);
			}
			
			$uid = intval(core::gpc('uid'));
			$user = $this->user->read($uid);
			$this->check_user_exists($user);
			
			if($uid == 1) {
				$this->message('NO.1 管理员不能删除！', 0);
			} elseif($uid == $this->conf['system_uid']) {
				$this->message('系统账户不能删除！', 0);
			} elseif($user['groupid'] <= 5) {
				$this->message('管理用户组不能删除！', 0);
			} else {
				$r = $this->user->xdelete($uid);
				if($r) {
					// hook admin_user_delete_after.php
					$this->message('删除用户成功！清理后可能会使前台出现空白主题的情况，您可以尝试清理下主题列表缓存。', 1, '?mod-manageuser.htm');
				} else {
					$this->message('正在删除用户，请稍候...', 1, "?mod-manageuser-do-deleteuser-uid-$uid.htm");
				}
			}
		}
	}
	
	//hook admin_mod_control_after.php
}

?>