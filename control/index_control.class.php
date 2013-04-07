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
		$this->_title[] = $this->conf['seo_title'] ? $this->conf['seo_title'] : $this->conf['app_name'];
		$this->_seo_keywords = $this->conf['seo_keywords'];
		$this->_seo_description = $this->conf['seo_description'];
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
		
		$pagesize = 30;
		$toplist = array(); // only top 3
		$readtids = '';
		$page = misc::page();
		$threadlist = $this->thread->get_list($page, $pagesize);
		foreach($threadlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			if(!isset($this->conf['forumarr'][$fid])) {
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
		
		$toplist = $page == 1 ? $this->get_toplist() : array();
		$toplist = array_filter($toplist);
		foreach($toplist as $k=>&$thread) {
			$this->thread->format($thread);
                        $readtids .= ','.$thread['tid'];
                }
                
		$readtids = substr($readtids, 1); 
		$click_server = $this->conf['click_server']."?db=tid&r=$readtids";
		
		$pages = misc::pages('?index-index.htm', $this->conf['threads'], $page, $pagesize);

		// 在线会员
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
	
	// 所有版块，考虑权限！
	public function on_list() {
		
		$this->_checked['forum_list'] = ' class="checked"';
		
		$forumarr = $this->conf['forumarr'];
		$threadlists = $this->runtime->get('threadlists');
		if(empty($threadlists)) {
			foreach($forumarr as $fid=>$name) {
				if(!empty($forumarr[$fid])) {
					$access = $this->forum_access->read($fid, $this->_user['groupid']);
					if(!empty($access) && !$access['allowread']) {
						unset($forumarr[$fid]);
						continue;
					}
				}
				$threadlist = $this->thread->get_threadlist_by_fid($fid, 0, 0, 0, 10);
				foreach($threadlist as &$thread) {
					$thread['dateline_fmt'] = misc::minidate($thread['dateline']);
					$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 24);
				}
				$threadlists[$fid] = $threadlist;
			}
			$this->runtime->set('threadlists', $threadlists, 60); // todo:一分钟的缓存时间！这里可以根据负载进行调节。
		}
		$this->view->assign('forumarr', $forumarr);
		$this->view->assign('threadlists', $threadlists);
		$this->view->display('index_list.htm');
	}
	
	public function on_example() {
		$this->view->display('example2.htm');
	}
	
	private function get_toplist($forum = array()) {
		$fidtids = array();
		// 3 级置顶
		$fidtids = $this->get_fidtids($this->conf['toptids']);
		
		// 1 级置顶
		if($forum) {
			$fidtids += $this->get_fidtids($forum['toptids']);
		}
		
		$toplist = $this->thread->mget($fidtids);
		return $toplist;
	}
	
	private function get_fidtids($s) {
		$fidtids = array();
		if($s) {
			$fidtidlist = explode(' ', trim($s));
			foreach($fidtidlist as $fidtid) {
				list($fid, $tid) = explode('-', $fidtid);
				$fidtids["$fid-$tid"] = array($fid, $tid);
			}
		}
		return $fidtids;
	}
	//hook index_control_after.php
}

?>