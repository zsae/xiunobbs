	public function on_list() {
		$oldurl = str_replace('thread-list', 'forum-index', $_SERVER['REQUEST_URI']);
		header('Location: '.$oldurl);
		exit;
	}