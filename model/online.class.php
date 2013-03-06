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
	
	public function xcreate($arr) {
		$sid = $arr['sid'];
		$online = $this->read($sid);
		if(empty($online)) {
			$this->runtime->xset('onlines', '+1');
		}
		return $this->create($arr);
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
		
		// 搜索引擎等无cookie浏览者会导致这种情况，需要处理，否则在线人数会变成负数。
		if($n > $this->conf['onlines']) {
			$n = $this->online->count();
			$this->conf['onlines'] -= $n;
			$this->runtime->xset('onlines', $this->conf['onlines']);
		}
	}
}
?>