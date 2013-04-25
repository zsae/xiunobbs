<?php

$error = $input = array();
if(!$this->form_submit() && isset($_GET['count'])) {
	$this->view->assign('dir', $dir);
	$this->view->display('plugin_rebuild_count.htm');
} else {
	
	$user = core::gpc('user', 'R');
	$forum = core::gpc('forum', 'R');
	$threadtype = core::gpc('threadtype', 'R');
	
	// 锁住
	$this->runtime->xset('site_runlevel', 4, 'runtime');
	$this->kv->xset('site_runlevel', 4, 'conf');
	
	$start = core::start('start');
	$conf = $this->conf;
	
	if(empty($start)) {
		$this->mthread->index_create('index');
	}
	// 建立索引
	
	$db = new db_mysql($conf['db']['mysql']);
	$count = core::gpc('count');
	if(empty($count)) {
		$count = $db->index_count('thread');
	}
	$mthread = new thread($conf);
	$mdigest = new thread_digest($conf);
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$arrlist = $mthread->index_fetch(array(), array(), $start, $limit);
		foreach($arrlist as $arr) {
			if(empty($arr['digest'])) continue;
			$mdigest->create(array('fid'=>$arr['fid'], 'tid'=>$arr['tid'], 'digest'=>$arr['digest']));
		}
		$start += $limit;
		$this->message("正在升级 upgrade_digest, 一共: $count, 当前: $start...", "?plugin-setting-dir-$dir-user-$user-forum-$forum-threadtype-$threadtype-start-$start-count-$count.htm", 0);
	} else {
		$this->message('升级 upgrade_digest 完成', '?step=complete');
	}	
	
	$this->message('恭喜，修改成功。', 1, "?plugin-setting-dir-$dir.htm");
}



?>