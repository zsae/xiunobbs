<?php

/*
 * Copyright (C) xiuno.com
 */

/*
	thread_type 功能中的 cate, 主题分类属于的大类，一个板块只有3个大类。
	是个小表，没有统计相关，比较独立，简单。
*/

// 此 model 不需要 maxid, count
class thread_type_cate extends base_model {
	
	// 最多三种主题分类，支持复合查询，1, 2, 3, 1-2, 2-3, 1-3, 1-2-3

	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'thread_type_cate';
		$this->primarykey = array('fid', 'cateid');
	}
	
	public function xread($fid, $cateid, $fillblank = TRUE) {
		$arr = $this->read($fid, $cateid);
		empty($arr) && $fillblank && $arr = array (
			'fid'=>$fid,
			'cateid'=>$cateid,
			'catename'=>'',
			'rank'=>$cateid,
			'enable'=>0,
		);
		return $arr;
	}
	
	// 删除版块的时候清除
	public function delete_by_fid($fid) {
		$arrlist = $this->get_list_by_fid($fid);
		foreach($arrlist as $arr) {
			$this->delete($arr['fid'], $arr['cateid']);
		}
	}
	
	public function get_list_by_fid($fid, $fillblank = TRUE) {
		// 获取大分类
		$arr = array();
		$cate1 = $this->xread($fid, 1, $fillblank);
		$cate2 = $this->xread($fid, 2, $fillblank);
		$cate3 = $this->xread($fid, 3, $fillblank);
		!empty($cate1) && $arr[1] = $cate1;
		!empty($cate2) && $arr[2] = $cate2;
		!empty($cate3) && $arr[3] = $cate3;
		$arr && misc::arrlist_multisort($arr, 'rank', TRUE);	// 关联数组 key 不变，数字 key 会重新索引
		$arr && misc::arrlist_change_key($arr, 'cateid');
		return $arr;
	}
	
	// 初始化
	public function init($fid) {
		for($i = 1; $i <= 3; $i++) {
			$cateid = $i;
			$arr = array (
				'fid'=>$fid,
				'cateid'=>$cateid,
				'catename'=>'',
				'rank'=>$i,
				'enable'=>1,
			);
			$this->create($arr);
		}
	}
	
	// 初始化
	public function destory($fid) {
		for($i = 1; $i <= 3; $i++) {
			$this->delete($fid, $i);
		}
	}
}
?>