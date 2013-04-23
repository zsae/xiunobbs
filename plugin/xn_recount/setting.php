<?php

$error = $input = array();
if(!$this->form_submit()) {
	$this->view->assign('dir', $dir);
	$this->view->display('plugin_rebuild_count.htm');
} else {
	$username = core::gpc('username', 'P');
	$newusername = core::gpc('newusername', 'P');
	
	if(empty($username)) {
		$this->message('请填写需要修改的用户名。');
	}
	if(empty($newusername)) {
		$this->message('请填写修改成的用户名。');
	}
	if($username == $newusername) {
		$this->message('用户名不能相等。');
	}
	
	$error = $this->user->check_username($username);
	$error && $this->message($error);
	$user = $this->user->get_user_by_username($username);
	$this->check_user_exists($user);
	
	$uid = $user['uid'];
	
	$error = $this->user->check_username($newusername);
	$error && $this->message($error);
	$error = $this->user->check_username_exists($newusername);
	$error && $this->message($error);
	
	$username = $user['username'];
	
	$this->user->index_update(array('uid'=>$uid), array('username'=>$newusername), TRUE);
	$this->online->index_update(array('uid'=>$uid), array('username'=>$newusername), TRUE);
	$this->modlog->index_update(array('uid'=>$uid), array('username'=>$newusername), TRUE);
	$this->rate->index_update(array('uid'=>$uid), array('username'=>$newusername), TRUE);
	$this->pm->index_update(array('uid1'=>$uid), array('username1'=>$newusername), TRUE);
	$this->pm->index_update(array('uid2'=>$uid), array('username2'=>$newusername), TRUE);
	if($user['posts'] > 0) {
		$this->post->index_update(array('uid'=>$uid), array('username'=>$newusername), TRUE);
	}
	if($user['threads'] > 0) {
		$this->thread->index_update(array('uid'=>$uid), array('username'=>$newusername), TRUE);
		$this->thread->index_update(array('lastusername'=>$username), array('lastusername'=>$newusername), TRUE);
	}
	
	$this->message('恭喜，修改成功。', 1, "?plugin-setting-dir-$dir.htm");
}



?>