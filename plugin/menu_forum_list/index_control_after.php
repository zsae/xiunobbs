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
				$threadlist = $this->thread->get_threadlist_by_fid($fid, 0, 0, 10, 0);
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