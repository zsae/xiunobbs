<?php

	// 我的金币，支付记录
	public function on_pay() {
		$this->_checked['my_wealth'] = 'class="checked"';
		$this->_checked['pay'] = 'class="checked"';
		
		$this->_title[] = '支付记录';
		$this->_nav[] = '支付记录';
		
		$_user = $this->_user;
		$uid = $_user['uid'];
		
		// hook my_pay_before.php
		$page = misc::page();
		$pagesize = 20;
		$paylist = $this->pay->get_list_by_uid($uid, $page, $pagesize);
		$pages = misc::simple_pages("?my-pay.htm", count($paylist), $page, $pagesize);
		$this->view->assign('pages', $pages);
		$this->view->assign('paylist', $paylist);
		
		// hook my_pay_after.php
		$this->view->display('my_pay.htm');
	}

	// 兑换金币
	public function on_exchange() {
		$this->_checked['my_wealth'] = 'class="checked"';
		$this->_checked['exchange'] = 'class="checked"';
		
		$this->_title[] = '兑换金币';
		$this->_nav[] = '兑换金币';
		
		$_user = $this->_user;
		$uid = $_user['uid'];
		
		$rate = $this->conf['pay_rate'];
		
		// hook my_exchange_before.php
		$error = array();
		if($this->form_submit()) {
			//$golds = abs(intval(core::gpc('golds', 'P')));
			$money = abs(intval(core::gpc('money', 'P')));
			// 判断余额
			if($money > $_user['money']) {
				$error['money'] = '您的金钱余额不足.';
			}
			$golds = $money * $rate;
			// 扣除金钱，增加金币
			$user = $this->user->read($_user['uid']);
			$this->_user['money'] = $user['money'] -= $money;
			$this->_user['golds'] = $user['golds'] += $golds;
			$this->user->update($user);
		}
		$initgolds = $rate * $_user['money']; 
		
		// hook my_exchange_after.php
		$this->view->assign('initgolds', $initgolds);
		$this->view->assign('rate', $rate);
		$this->view->assign('error', $error);
		$this->view->display('my_exchange.htm');
	}
	
?>