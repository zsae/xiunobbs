<?php

/*
 * Copyright (C) xiuno.com
 */

class thread_digest extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'thread_digest';
		$this->primarykey = array('fid', 'tid');
	}
	
	public function get_list_by_fid($fid, $start, $limit) {
		$threadlist = array();
		$arrlist = $this->index_fetch(array('fid'=>$fid), array('tid'=>-1), $start, $limit);
		foreach($arrlist as $arr) {
			$thread = $this->thread->read($arr['fid'], $arr['tid']);
			$threadlist[] = $thread;
		}
		return $threadlist;
	}
	
	// 按照 tid 倒序，获取最新的列表
	public function get_newlist($start = 0, $limit = 30) {
		$threadlist = array();
		$arrlist = $this->index_fetch(array(), array('tid'=>-1), $start, $limit);
		foreach($arrlist as $arr) {
			$thread = $this->thread->read($arr['fid'], $arr['tid']);
			$threadlist[] = $thread;
		}
		return $threadlist;
	}
	
	// hook thread_digest_model_end.php
}
?>