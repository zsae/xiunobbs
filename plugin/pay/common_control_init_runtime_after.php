<?php

	// 读取 pay_on, pay_rate 注册到全局, kv -> runtime, 提高速度！
	if(!isset($this->conf['pay_on'])) {
		$kvconf = $this->kv->xget('conf');
		if($kvconf && isset($kvconf['pay_on'])) {
			$this->conf += $kvconf;
			$this->runtime->xset('pay_on', $kvconf['pay_on']);
		}
	}
?>