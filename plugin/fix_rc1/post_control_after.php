	public function on_list() {
		$oldurl = str_replace('post-list', 'thread-index', $_SERVER['REQUEST_URI']);
		header('Location: '.$oldurl);
		exit;
	}