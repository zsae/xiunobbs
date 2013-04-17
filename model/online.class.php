<?php

/*
 * Copyright (C) xiuno.com
 */

class online extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'online';
		$this->primarykey = array('sid');
	}
	
	// 重载, 因为 online 表为 Memory 类型，重启后消失，count 不准确。
	public function count($val = FALSE) {
		return $this->index_count();
	}
	
	// 最多400个会员，再多没啥意义了，耗费带宽
	public function get_onlinelist($limit = 400) {
		$onlinelist = $this->index_fetch(array('uid'=>array('>'=>0)), array(), 0, $limit);
		return $onlinelist;
	}
	
	// 用来显示给用户
	public function format(&$online) {
		// format data here.
	}
	
	// cron_1_next_time，每隔5分钟执行一次，首页缓存也会被刷新。
	public function gc() {
		// 默认 15 分钟算离线
		$expiry = $_SERVER['time'] - $this->conf['online_hold_time'];
		
		// 采用暴力的 index_delete() 节省代码。
		$n = $this->index_delete(array('lastvisit'=>array('<'=>$expiry)));
		$this->conf['onlines'] -= $n;
		
		// 修正非法数据：意外
		if($this->conf['onlines'] < 1) {
			$n = $this->online->index_count();
			$this->online->count($n);
			$this->runtime->xset('onlines', $n);
		}
		
		$this->runtime->xset('onlines', $this->conf['onlines']);
	}
}
?>