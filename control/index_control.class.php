<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class index_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['bbs'] = ' class="checked"';
	}
	
	// 给插件预留个位置
	public function on_index() {
		
		// hook index_index_before.php
		
		$this->on_bbs();
	}
	
	// 首页
	public function on_bbs() {
		$this->_checked['index'] = ' class="checked"';
		
		// hook index_bbs_before.php
		$this->_title[] = $this->conf['seo_title'] ? $this->conf['seo_title'] : $this->conf['app_name'];
		$this->_seo_keywords = $this->conf['seo_keywords'];
		$this->_seo_description = $this->conf['seo_description'];
		
		$pagesize = 30;
		$toplist = array(); // only top 3
		$readtids = '';
		$page = misc::page();
		$threadlist = $this->thread->get_list($page, $pagesize);
		foreach($threadlist as $k=>&$thread) {
			$this->thread->format($thread);
			// remove accesson forum
			if(!empty($this->conf['forumaccesson'][$thread['fid']])) {
				unset($threadlist[$k]);
				continue;
			}
			$readtids .= ','.$thread['tid'];
			if($thread['top'] == 3) {
				unset($threadlist[$k]);
				$toplist[] = $thread;
				continue;
			}
		}
		$readtids = substr($readtids, 1); 
		$click_server = $this->conf['click_server']."?db=tid&r=$readtids";
		
		$pages = misc::pages('?index-index.htm', $this->conf['threads'], $page, $pagesize);

		// 在线会员
		$onlinelist = $this->runtime->get('onlinelist');
		if(empty($onlinelist)) {
			$onlinelist = $this->online->get_onlinelist();
			$this->runtime->set('onlinelist', $onlinelist, 60); // todo:一分钟延迟，根据负载调节缓存时间
		}
		$this->view->assign('onlinelist', $onlinelist);
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('toplist', $toplist);
		$this->view->assign('click_server', $click_server);
		$this->view->assign('pages', $pages);
		
		// hook index_bbs_after.php
		
		$this->view->display('index.htm');
	}
	
	public function on_example() {
		$this->view->display('example2.htm');
	}
	//hook index_control_after.php
}

?>