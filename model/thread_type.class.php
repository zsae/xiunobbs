<?php

/*
 * Copyright (C) xiuno.com
 */

// 此 model 不需要 maxid, count
class thread_type extends base_model {
	
	// 最多三种主题分类，支持复合查询，1, 2, 3, 1-2, 2-3, 1-3, 1-2-3
	// 4 为预留：精华，
	private $map = array (
		1 => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40),
		2 => array(41, 82, 123, 164, 205, 246, 287, 328, 369, 410, 451, 492, 533, 574, 615, 656, 697, 738, 779, 820, 861, 902, 943, 984, 1025, 1066, 1107, 1148, 1189, 1230, 1271, 1312, 1353, 1394, 1435, 1476, 1517, 1558, 1599, 1640),
		3 => array(1681, 3362, 5043, 6724, 8405, 10086, 11767, 13448, 15129, 16810, 18491, 20172, 21853, 23534, 25215, 26896, 28577, 30258, 31939, 33620, 35301, 36982, 38663, 40344, 42025, 43706, 45387, 47068, 48749, 50430, 52111, 53792, 55473, 57154, 58835, 60516, 62197, 63878, 65559, 67240),
		//4 => array(134481, 201722, 268963),
	);
	
	/*
		tid = 100
		typeid = 12 205 1681
		
		100 12
		100 205
		100 1681
		100 12+205
		100 205+1681
		100 12+1681
		100 12+205+1681
	*/
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'thread_type';
		$this->primarykey = array('fid', 'typeid');
	}
	
	/*
	// 有了 init() 此方法作废。
	public function xcreate($fid, $cateid, $typename) {
		// 查找空位
		$typeid = $this->find_empty_typeid($fid, $cateid);
		if(empty($typeid)) {
			return FALSE;
		}
		$arr = array(
			'fid'=>$fid,
			'typeid'=>$typeid,
			'typename'=>$typename,
			'rank'=>$typeid,
			'enable'=>1,
		);
		if($this->set(array($fid, $typeid), $arr)) {
			return $typeid;
		} else {
			return FALSE;
		}
	}*/
	
	private function find_empty_typeid($fid, $cateid) {
		// 最多 40 次查询
		foreach($this->map[$cateid] as $id) {
			$r = $this->read($fid, $typeid);
			if(empty($r)) return $r;
		}
		return 0;
	}
	
	// 将 typeid, 插入到 nextid 之前
	public function update_rank($fid, $typeid, $nextid, $typecateid) {
		
		// 初始化 type
		$this->init($fid, $typecateid);
		
		$typecateid = misc::mid($typecateid, 1, 3);
		$arrlist = $this->get_list_by_fid_cateid($fid, $typecateid);
		$type = $arrlist[$typeid];
		$next = $arrlist[$nextid];
		
		// 遍历数组，从 next, 到 type
		$keys = array_keys($arrlist);
		$values = array_values($arrlist);
		$start = array_search($nextid, $keys);
		$end = array_search($typeid, $keys);
		$movelist = array_slice($values, $start, $end - $start + 1);
		foreach($movelist as $k=>$v) {
			$v['rank'] = $v['typeid'] == $typeid ? $next['rank'] : $movelist[$k + 1]['rank'];
			$this->update($v);
		}
	}
	
	// 删除版块的时候清除
	public function delete_by_fid($fid) {
		$arrlist1 = $this->get_list_by_fid_cateid($fid, 1);
		$arrlist2 = $this->get_list_by_fid_cateid($fid, 2);
		$arrlist3 = $this->get_list_by_fid_cateid($fid, 3);
		$arrlist = array_merge($arrlist1, $arrlist2, $arrlist3);
		foreach($arrlist as $arr) {
			$this->delete($arr['fid'], $arr['typeid']);
		}
	}
	
	public function get_typename($fid, $typeid) {
		$arr = $this->get($fid, $typeid);
		return empty($arr) ? '' : $arr['typename'];
	}
	
	// 要么为空，要么为补全数组
	public function get_list() {
		$typelist = $this->index_fetch(array(), array(), 0, 0);
		foreach($typelist as $k=>$v) {
			if(empty($v['typename'])) {
				unset($typelist[$k]);
			}
		}
		return $typelist;
	}
	
	// 返回非空的主题分类, cateid = 1, 2, 3，排序？
	/*
		返回格式，按照 rank 正序，typeid 为 key：
		array(
			3=>array('typeid'=>3, 'rank'=>1, 'enable'=>1, 'typename'=>'主题分类3'),
			2=>array('typeid'=>2, 'rank'=>2, 'enable'=>1, 'typename'=>'主题分类2'),
			1=>array('typeid'=>1, 'rank'=>3, 'enable'=>1, 'typename'=>'主题分类1'),
			4=>array('typeid'=>4, 'rank'=>4, 'enable'=>1, 'typename'=>'主题分类4'),
		)
	*/
	// 要么为空，要么为补全数组
	public function get_list_by_fid_cateid($fid, $cateid, $fillblank = TRUE) {
		$typelist = array();
		foreach($this->map[$cateid] as $typeid) {
			$arr = $this->read($fid, $typeid);
			if(empty($arr)) {
				$fillblank && $arr = array('fid'=>$fid, 'typeid'=>$typeid, 'typename'=>'', 'rank'=>$typeid, 'enable'=>1);
			}
			if($arr) {
				$typelist[$typeid] = $arr;
			}
		}
		if($typelist) {
			misc::arrlist_multisort($typelist, 'rank', TRUE);	// 排序后key 丢失
			misc::arrlist_change_key($typelist, 'typeid');		// 找回 key
		}
		return $typelist;
	}
	
	// ------------------> 杂项
	
	// 用来显示给用户
	public function format(&$type) {
		
	}
	
	public function check_typeid(&$typeid, $cateid) {
		$cateid == 1 && $typeid = $typeid >= 1 && $typeid <= 40 ? $typeid : 0;
		$cateid == 2 && $typeid = $typeid >= 41 && $typeid <= 1640 ? $typeid : 0;
		$cateid == 3 && $typeid = $typeid >= 1681 && $typeid <= 67240 ? $typeid : 0;
	}
	
	// 当启用主题分类的时候，如果发现为空则初始化数据
	public function init($fid) {
		for($cateid = 1; $cateid <= 3; $cateid++) {
			foreach($this->map[$cateid] as $typeid) {
				$type = $this->read($fid, $typeid);
				if(empty($type)) {
					$arr = array(
						'fid'=>$fid,
						'typeid'=>$typeid,
						'typename'=>'',
						'rank'=>$typeid,
						'enable'=>1,
					);
					$this->create($arr);
				}
			}
		}
	}
	
	public function destory($fid) {
		for($cateid = 1; $cateid <= 3; $cateid++) {
			foreach($this->map[$cateid] as $typeid) {
				$this->delete($fid, $typeid);
			}
		}
	}
}
?>