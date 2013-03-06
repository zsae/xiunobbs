		//$this->_seo_keywords = $thread['seo_keywords'] ? $thread['seo_keywords'] : $thread['subject'];
		
		$this->_title = array();
		if($thread['seo_keywords']) {
			$this->_title[] = $thread['seo_keywords'];
			$this->_seo_keywords = $thread['subject'];
		} else {
			$this->_title[] = $thread['subject'];
			$this->_seo_keywords = $thread['subject'];
		}
		
		