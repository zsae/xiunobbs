		$this->_checked['index'] = ' class="checked"';
		
		$pagesize = 50;
		$toplist = array(); // only top 3
		$readtids = '';
		$page = misc::page();
		$page2 = misc::page('page2');
		$threadlist = $this->thread->get_newlist($page, $pagesize);
		$unset1 = 0;
		foreach($threadlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			if(!isset($this->conf['forumarr'][$fid])) {
				unset($threadlist[$k]);
				$unset1++;
				continue;
			}
			
			$readtids .= ','.$thread['tid'];
			$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 26);
		}
		
		$toplist = $page == 1 ? $this->get_toplist() : array();
		$toplist = array_filter($toplist);
		foreach($toplist as $k=>&$thread) {
			$this->thread->format($thread);
                        $readtids .= ','.$thread['tid'];
                }
                
		$pages = misc::simple_pages("?index-index-page2-$page2.htm", count($threadlist) + $unset1, $page, $pagesize, 'page');

		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('toplist', $toplist);
		$this->view->assign('pages', $pages);
		
		// hook index_bbs_after.php
		
		$unset2 = 0;
		$digestlist = $this->thread_digest->get_newlist($page2, $pagesize);
		foreach($digestlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			if(!isset($this->conf['forumarr'][$fid])) {
				unset($digestlist[$k]);
				$unset2++;
				continue;
			}
			
			$readtids .= ','.$thread['tid'];
			$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 26);
		}
		
		$readtids = substr($readtids, 1); 
		$click_server = $this->conf['click_server']."?db=tid&r=$readtids";
		
		$pages2 = misc::simple_pages("?index-index-page-$page.htm", count($digestlist) + $unset2, $page2, $pagesize, 'page2');
		$this->view->assign('digestlist', $digestlist);
		$this->view->assign('pages2', $pages2);
		$this->view->assign('click_server', $click_server);
		$this->view->display('plugin_index_two_column.htm');
		exit;
		
		