<?php

/*
 * Copyright (C) xiuno.com
 */

class forum_access extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'forum_access';
		$this->primarykey = array('fid', 'groupid');
		$this->maxcol = 'fid';
	}
	
	public function delete_by_fid($fid) {
		$accesslist = $this->get_list_by_fid($fid);
		foreach($accesslist as $access) {
			$this->delete($access['fid'], $access['groupid']);
		}
		return TRUE;
	}
	
	public function get_list_by_fid($fid) {
		$arr = array();
		$accesslist = $this->index_fetch(array('fid' => $fid), array('groupid'=>1), 0, 1000);
		foreach($accesslist as $v) {
			$arr[$v['groupid']] = $v;
		}
		return $arr;
	}
	
	// 将游客调到最后一组
	/*
	public function judge_accesslist(&$accesslist) {
		list($access) = $accesslist;
		if($access['groupid'] == 0) {
			$access = array_shift($accesslist);
			array_push($accesslist, $access);
		}
	}
	*/
	
	public function set_default(&$accesslist, $grouplist) {
		foreach($grouplist as $group) {
			$access = &$accesslist[$group['groupid']];
			// guest groupid
			if($group['groupid'] == 0) {
				$access['allowread'] = 1;
				$access['allowpost'] = 0;
				$access['allowthread'] = 0;
				$access['allowattach'] = 0;	// 游客不允许上传附件！写死了！
				$access['allowdown'] = 1;
			} else {
				$access['allowread'] = 1;
				$access['allowpost'] = 1;
				$access['allowthread'] = 1;
				$access['allowattach'] = 1;
				$access['allowdown'] = 1;
			}
		}
	}
	
	// 用来显示给用户
	public function format(&$forum_access) {
		// format data here.
	}
}
?>