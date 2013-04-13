<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class search_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['bbs'] = ' class="checked"';
		
		// search_type, sphinx_host, sphinx_port, sphinx_datasrc, sphinx_deltasrc
		$this->conf += $this->kv->xget('conf_ext');
	}
	
	// 首页
	public function on_index() {
		
		// hook search_index_before.php
		
		$keyword = urldecode(core::gpc('keyword', 'R'));
		$keyword = misc::safe_str($keyword);
		$keyword_url = urlencode($keyword);
		
		preg_match('#(?:http|https)://(.*?)/.*?#', $this->conf['app_url'], $m);
		$site = empty($m[1]) ? '' : $m[1];
		
		$searchtype = $this->conf['search_type'];
		if(empty($searchtype)) {
			$this->message('搜索功能未开启!');
		}
		if($searchtype == 'sphinx' && !function_exists('fsockopen')) {
			$this->message('Sphinx 依赖的 fsockopen() 函数被禁用！请联系系统管理员，配置 php.ini。');
		}
		
		$pagesize = 30; // 搜索结果大小
		$nextpage = 0;
		$page = misc::page();
		if(!empty($keyword)) {
			switch($searchtype) {
				case 'baidu':
					header('Location:'.$this->get_url($keyword, $site, 'baidu')); exit;
				case 'google':
					header('Location:'.$this->get_url($keyword, $site, 'google')); exit;
				case 'bing':
					header('Location:'.$this->get_url($keyword, $site, 'bing')); exit;
				case 'title':
					$threadlist = $this->get_list_by_title($keyword, $page, $pagesize);
					break;
				case 'sphinx':
					try {
						$threadlist = $this->get_list_by_sphinx($keyword, $page, $pagesize);
					} catch(Exception $e) {
						$this->message($e->getMessage());
					}
					break;
			}
			
			// 点击数
			$readtids = implode(',', misc::arrlist_values($threadlist, 'tid'));
			$click_server = $this->conf['click_server']."?r=$readtids";
			$this->view->assign('readtids', $readtids);
			$this->view->assign('click_server', $click_server);
		} else {
			$click_server = '';
			$this->view->assign('click_server', $click_server);
			$threadlist = array();
		}
		
		
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$toplist = array();
		$pages = misc::simple_pages("?search-index-keyword-$keyword_url-page-$page.htm", count($threadlist), $page, $pagesize);
		$this->view->assign('pages', $pages);
		$this->view->assign('keyword', $keyword);
		$this->view->assign('keyword_url', $keyword_url);
		
		$this->view->assign('searchtype', $searchtype);
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('toplist', $toplist);
		$this->view->assign('click_server', $click_server);
		
		// hook search_index_after.php
		$this->view->display('search_list.htm');
	}
	
	private function get_list_by_title($keyword, $page, $pagesize) {
		$threadlist = $this->thread->index_fetch(array('subject'=>array('LIKE'=>$keyword)), array(), ($page - 1) * $pagesize, $pagesize);
		foreach($threadlist as &$thread) {
			$forum = $this->mcache->read('forum', $thread['fid']);
			$this->thread->format($thread, $forum);
			$thread['forumname'] = isset($this->conf['forumarr'][$thread['fid']]) ? $this->conf['forumarr'][$thread['fid']] : '';
			$thread['subject'] = str_replace($keyword, '<span class="red">'.$keyword.'</span>', $thread['subject']);
		}
		return $threadlist;
	}
	
	private function get_list_by_sphinx($keyword, $page, $pagesize) {
                include FRAMEWORK_PATH.'lib/sphinxapi.class.php';
                
                $cl = new SphinxClient();
                $cl->SetServer($this->conf['sphinx_host'], $this->conf['sphinx_port']);
                $cl->SetConnectTimeout(3);
                $cl->SetArrayResult(TRUE);
                $cl->SetWeights(array(100, 10, 1));     	// 标题权重100，内容权重1，作者权重10
                $cl->SetMatchMode(SPH_MATCH_ALL);
                $cl->SetSortMode (SPH_SORT_RELEVANCE);	// 如果不设置，默认按照权重排序！但是TMD是正序！
                //$cl->SetSortMode (SPH_SORT_ATTR_DESC, 'tid');	// 如果不设置，默认按照权重排序！但是TMD是正序！
                
		/*
		$cl->SetMatchMode ( SPH_MATCH_EXTENDED );	//设置模式
		$cl->SetRankingMode ( SPH_RANK_PROXIMITY );	//设置评分模式
		$cl->SetFieldWeights (array('subject'=>100,'message'=>10,'username'=>1));//设置字段的权重，如果area命中，那么权重算2
		$cl->SetSortMode ('SPH_SORT_EXPR','@weight');	//按照权重排序
		*/
		
		// --------------> 优先搜索增量索引
		$deltamarch = array();
		if($page == 1) {
			$pagesize = 100;
			$cl->SetLimits(0, $pagesize, 1000);	// 最大结果集
	                $res = $cl->Query($keyword, $this->conf['sphinx_deltasrc']);
	                if(!empty($cl->_error)) {
	                       throw new Exception('Sphinx 错误：'.$cl->_error);
	                }
	                if(!empty($res) && !empty($res['total'])) {
	                       $deltamarch = $res['matches'];
	                }
		}
		
		
		// --------------> 再搜索主索引
                
                $start = ($page - 1) * $pagesize;
                $cl->SetLimits($start, $pagesize, 1000);	// 最大结果集
                $res = $cl->Query($keyword, $this->conf['sphinx_datasrc']);
                if(!empty($cl->_error)) {
                       throw new Exception('Sphinx 错误：'.$cl->_error);
                }
                if(empty($res) || empty($res['total'])) {
                       $res['matches'] = $deltamarch;
                } else {
                	// 合并两次搜索的结果，增量的放在后面。一般最佳结果不出现在增量里面。
                	$res['matches'] += $deltamarch;
                }

                $threadlist = array();
                foreach($res['matches'] as $v) {
                        if(!$v['attrs']) continue;
                        if(empty($v['attrs']['fid'])) continue;
                        $fid = $v['attrs']['fid'];
                        
                        $thread = $this->thread->read($v['attrs']['fid'], $v['attrs']['tid']);
                        if(empty($thread)) continue;
                        $forum = $this->mcache->read('forum', $fid);
                        $this->thread->format($thread, $forum);
                        $thread['forumname'] = isset($this->conf['forumarr'][$thread['fid']]) ? $this->conf['forumarr'][$thread['fid']] : '';
                        $thread['subject'] = str_replace($keyword, '<span class="red">'.$keyword.'</span>', $thread['subject']);
                        $threadlist[] = $thread;
                }
                return $threadlist;
        }
	
	// LIKE 采用下一页的方式。
	private function get_url($keyword, $site, $srchsite) {
		if($srchsite == 'baidu') {
			return 'http://www.baidu.com/s?wd='.$keyword.'+site%3A'.$site;
		} elseif($srchsite == 'google') {
			return 'http://www.google.com.hk/search?hl=zh-CN&newwindow=1&safe=strict&site=&q='.$keyword.'+site%3A'.$site;
		} elseif($srchsite == 'bing') {
			return 'http://cn.bing.com/search?q='.$keyword.'+site%3A'.$site;
		}
	}
	
	//hook search_control_after.php
}

?>