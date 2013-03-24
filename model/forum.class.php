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
	
	// 简洁格式，存入缓存，前台调用
	public function format_thread_type(&$forum) {
		$fid = $forum['fid'];
		$forum['typecates'] = array();
		$forum['types'] = array();
		for($i=1; $i<=4; $i++) {
			$typecateid = $i;
			$cate = $this->thread_type_cate->xread($fid, $typecateid, FALSE);
			if($cate && $cate['enable']) {
				$forum['typecates'][$typecateid] = $cate['catename'];
				$typelist = $this->thread_type->get_list_by_fid_cateid($fid, $typecateid, FALSE);
				foreach($typelist as $k=>$type) {
					if(empty($type['enable'])) unset($typelist[$k]);
				}
				$typeid_typenames = misc::arrlist_key_values($typelist, 'typeid', 'typename');
				$forum['types'][$typecateid] = $typeid_typenames;
			}
		}
	}
	
	// 详细的格式化，填充, 后台调用
	public function format_thread_type_full(&$forum) {
		$fid = $forum['fid'];
		$forum['typecatelist'] = array();
		$forum['typelist'] = array();
		for($i=1; $i<=4; $i++) {
			$typecateid = $i;
			$forum['typecatelist'][$i] = $this->thread_type_cate->xread($fid, $typecateid, TRUE); // 填充空白
			$forum['typelist'][$i] = $this->thread_type->get_list_by_fid_cateid($fid, $typecateid, TRUE); // 填充空白
		}
	}
	
	// 获取有权限的版块列表，默认第一个，如果有权限限制，则查询用户组权限
	public function get_options($uid, $groupid, $checkedfid, &$defaultfid) {
		$forumlist = $this->forum->get_list();
		$s = '';
		$checkedfid && $defaultfid = $checkedfid;
		foreach($forumlist as $forum) {
			if($groupid == 1 || $groupid == 2 || ($groupid == 4 && strpos(' '.$forum['modids'].' ', ' '.$uid.' ') !== FALSE)) {
				
				// 隐藏权限不足的版块。
				$fid = $forum['fid'];
				if(!isset($this->conf['forumarr'][$fid])) {
					continue;
				}
				
				empty($checkedfid) && empty($defaultfid) && $defaultfid = $fid;
				
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