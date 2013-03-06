<?php

/*
 * Copyright (C) xiuno.com
 */

class pay extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'pay';
		$this->primarykey = array('payid');
		$this->maxcol = 'payid';
	}
	
	public function get_list_by_uid($uid, $page, $pagesize) {
		$start = ($page - 1) * $pagesize;
		$paylist = $this->index_fetch(array('uid'=>$uid), array(), $start, $pagesize);
		foreach($paylist as &$pay) {
			$this->format($pay);
		}
		misc::arrlist_multisort($paylist, 'payid', TRUE);
		return $paylist;
	}
	
	// 用来显示给用户
	public function format(&$pay) {
		$paytypes = array('线下支付', '支付宝', '网银');
		$pay['dateline_fmt'] = date('Y-n-j H:i', $pay['dateline']);
		$pay['paytype_fmt'] = $paytypes[$pay['paytype']];
		$pay['status_fmt'] = $pay['status'] ? '支付成功' : '未支付';
	}
	
}
?>