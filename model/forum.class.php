<?php

/*
 * Copyright (C) xiuno.com
 */

class forum extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'forum';
		$this->primarykey = array('fid');
		$this->maxcol = 'fid';
	}
	
	// 更新版块的最后发帖
	public function update_last($fid) {
		$forum = $this->read($fid);
		$threadlist = $this->thread->index_fetch(array('fid'=>$fid), array('tid'=>-1), 0, 1);
		if(empty($threadlist)) {
			$forum['lasttid'] = 0;
		} else {
			$thread = array_pop($threadlist);
			$forum['lasttid'] = $thread['tid'];
		}
		$this->update($forum);
	}
	
	// 取板块列表，二级
	public function get_list() {
		$forumlist = $this->index_fetch(array(), array('rank'=>1), 0, 1000);
		misc::arrlist_change_key($forumlist, 'fid');
		return $forumlist;
	}
	
	public function check_name(&$name) {
		if(empty($name)) {
			return '板块名称不能为空。';
		}
		return '';
	}
	
	public function check_rank(&$rank) {
		if(empty($rank)) {
			return '显示倒序不能为空。';
		}
		return '';
	}
	
	public function check_brief(&$brief) {
		if(empty($brief)) {
			return '版块简介不能为空。';
		}
		return '';
	}
	
	public function check_icon(&$icon) {
		if(empty($icon)) {
			return '版块图标不能为空。';
		}
		return '';
	}
	
	public function format(&$forum, $threadtype = FALSE) {
		// 版主
		$forum['modlist'] = array();
		if(!empty($forum['modids'])) {
			$modidarr = explode(' ', $forum['modids']);
			$modnamearr = explode(' ', $forum['modnames']);
			$forum['modlist'] = array_combine($modidarr, $modnamearr);
		}
		
		// hook forum_model_format_end.php
	}
	
	public function format_thread_type(&$forum) {
		
		// 用来在后台管理，已经排序好了。
		$forum['typecatelist'] = $this->thread_type_cate->get_list_by_fid($forum['fid'], FALSE);
		// 排序，前台显示，缓存到 cache。 $cateid = 1, 2, 3
		if($forum['typecatelist']) {
			foreach($forum['typecatelist'] as $cateid=>$v) {
				$forum['typelist'][$cateid] = $this->thread_type->get_list_by_fid_cateid($forum['fid'], $cateid, FALSE);
				$forum['typecates'][$cateid] = $forum['typecatelist'][$cateid]['catename'];
				$forum['types'][$cateid] = empty($forum['typelist'][$cateid]) ? array() : array_diff(misc::arrlist_key_values($forum['typelist'][$cateid], 'typeid', 'typename'), array(''));
			}
		} else {
			$forum['typecates'] = array();
			$forum['typelist'] = array();
			$forum['types'] = array();
		}
	}
	
	// 获取有权限的版块列表，默认第一个
	public function get_options($uid, $groupid, $checkedfid) {
		$forumlist = $this->forum->get_list();
		$s = '';
		foreach($forumlist as $forum) {
			if($groupid == 1 || $groupid == 2 || ($groupid == 4 && strpos(' '.$forum['modids'].' ', ' '.$uid.' ') !== FALSE)) {
				if(!$forum['status'] && $groupid != 1) continue; // 隐藏版块只有管理员能看到。
				$checked = $checkedfid == $forum['fid'] ? ' selected="selected"' : '';
				$s .= '<option value="'.$forum['fid'].'"'.$checked.' style="font-weight: 800;">'.$forum['name'].'</option>';
			}
		}
		return $s;
	}
	
	// 清除某个版块的缓存
	public function clear_cache($fid, $force = FALSE) {
		if($this->conf['site_pv'] <= 100000 || $force) {
			$this->mcache->clear('forum', $fid);
			$this->runtime->xupdate('forumarr');
		}
	}
	
	// hook forum_model_end.php
	
}
?>