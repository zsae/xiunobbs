<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class pay_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		
		// 追加配置
		$this->conf += $this->kv->xget('pay_conf');
	}
	
	// 选择支付方式
	public function on_select() {
		$this->check_login();
		$maxpayid = $this->pay->maxid();
		$this->view->assign('maxpayid', $maxpayid);
		
		$payid = intval(core::gpc('payid'));
		if($payid) {
			$pay = $this->pay->read($payid);
			$money = $pay['payamount'];
			$golds = $this->conf['pay_rate'] * $money;
		} else {
			$money = 1;
			$golds = $this->conf['pay_rate'] * $money;
		}
		
		// hook pay_select_after.php
		$this->view->assign('money', $money);
		$this->view->assign('golds', $golds);
		$this->view->display('pay_select.htm');
	}
	
	// 调用接口
	public function on_callapi() {
		$this->check_login();
		$uid = $this->_user['uid'];
		$username = $this->_user['username'];
		
		//$payid = 0;//
		$paytype = intval(core::gpc('paytype', 'P'));
		$payamount = intval(core::gpc('money', 'P'));
		if($payamount < 1) {
			$this->message('输入的金额有误。');
		}
		
		$payid = intval(core::gpc('payid'));
		if($payid) {
			$pay = $this->pay->read($payid);
			if(empty($pay)) {
				$this->message('该支付记录不存在。');
			}
			if($pay['uid'] != $uid) {
				$this->message('这不是您的支付记录。');
			}
		} else {
			
			// hook pay_callapi_after.php
			
			$pay = array(
				'uid'=>$uid,
				'username'=>$username,
				'dateline'=>$_SERVER['time'],
				'payamount'=>0,
				'paytype'=>0,
				'status'=>0,
				
				'alipay_email'=>'',
				'alipay_orderid'=>'',
				'alipay_fee'=>0,
				'alipay_receive_name'=>'',
				'alipay_receive_phone'=>'',
				'alipay_receive_mobile'=>'',
				'ebank_orderid'=>'',
				'ebank_amount'=>0,
				'epay_amount'=>0,
				'epay_orderid'=>'',
			);
			$payid = $this->pay->create($pay);
		}
		
		// 支付宝
		if($paytype == 1) {
			
			require BBS_PATH."upload/plugin/pay/alipay/alipay_service.php";
			$kvconf = $this->kv->xget('pay_conf');
			$partner = $kvconf['alipay_partner'];
			$security_code = $kvconf['alipay_security_code'];
			$seller_email = $kvconf['alipay_seller_email'];
			$alipay_on = $kvconf['alipay_on'];
			$_input_charset = "utf-8";
			$sign_type = "MD5";
			$transport= "http";
			$notify_url = "";// 异步返回地址
			$return_url = ""; //同步返回地址
			$show_url   = "";
 
			$appurl = misc::get_url_path();
			
			//$notify_url = $appurl.'pay-alipayrecall-payid-'.$payid.'.htm';	// 蛋疼的支付宝接口不允许传参！
			$notify_url = $appurl.'upload/plugin/pay/alipay/notify_url.php';	// 分布式环境，此处可能有问题。
			$parameter = array (
				"service" => "create_direct_pay_by_user",//create_direct_pay_by_user
				"partner" =>$partner,			//合作商户号
				//"return_url" =>$return_url,  		//同步返回
				"notify_url" =>$notify_url,  		//异步返回
				"_input_charset" => $_input_charset,	//字符集，默认为GBK
				"subject" => '论坛金币充值',		//商品名称，必填
				"body" => '论坛金币充值',			//商品描述，必填
				"out_trade_no" => $payid,		//商品外部交易号，必填,每次测试都须修改
				"total_fee" => "$payamount",		//商品单价，必填
				"payment_type"=>"1",			// 默认为1,不需要修改

				"show_url" => $show_url,		//商品相关网站
				"seller_email" => $seller_email		//卖家邮箱，必填
			);
			$alipay = new alipay_service($parameter, $security_code, $sign_type);
			$link = $alipay->create_url();
			header("Location:$link");
			exit;
			
		// 网银
		} elseif($paytype == 2) {
			
			$appurl = misc::get_url_path();
			
			$kvconf = $this->kv->xget('pay_conf');
			$v_mid = $kvconf['ebank_v_mid'];
			$key = $kvconf['ebank_key'];
			
			$v_moneytype = 0;
			$v_oid 			= date('Ymd', time())."-".$v_mid."-".date('His', time());
			$v_url 			= "?pay-ebankrecall-payid-$payid.htm";//请填写返回url，这里会被编译模板替换为 http://xxx 绝对路径
			$v_amount 		= $payamount;
			$text 			= $payamount.$v_moneytype.$v_oid.$v_mid.$v_url.$key;
			$v_md5info 		= strtoupper(md5($text));
			$style 			= '0';//网关模式0(普通列表)，1(银行列表中带外卡)
			$remark1 		= '';//备注字段1
			$remark2 		= '';//备注字段2
			
			// *************** 以下几项与网上支付货款无关，建议不用 **************
			$v_rcvaddr 		= '';	//收货人地址
			$v_ordername 		= '';	//发货人
			$v_rcvname 		= '';	//收货人
			$v_rcvtel 		= '';	//联系电话
			$v_rcvpost 		= '';	//邮政编码
			$v_orderemail 		= '';//联系email
			
			// 自动提交表单
			$form = <<<EOT
				<div id="body">
					<div class="bg2 border shadow" style="width: 700px; margin: auto; padding: 32px;">
						<h2>正在连接网银在线接口... </h2>
						<p>请注意：在通过网银支付成功后，先不要网银关闭支付成功的页面，等待三秒，弹出跳转对话框后再关闭！否则可能会导致通知我站支付接口失败。</p>
					</div>
					<form method="post" name="ebankform" id="ebankform" action="https://pay.chinabank.com.cn/select_bank">
						<input type="hidden" name="v_mid"    value="$v_mid">
						<input type="hidden" name="v_oid"     value="$v_oid">
						<input type="hidden" name="v_moneytype"  value="$v_moneytype">
						<input type="hidden" name="v_url"  value="$v_url">
						<input type="hidden" name="style"  value="$style">
						<input type="hidden" name="v_md5info"   value="$v_md5info">
						<input type="hidden" name="v_ordername"  value="$v_ordername">
						<input name="remark1" type="hidden" size="18" value="$remark1">
						<input name="v_amount" type="hidden" size="10" maxlength=8 value="$v_amount">
						<input name="remark2" type="hidden" size="10" value="$remark2"></textarea>
						<input name="v_rcvname" type="hidden" size="10" maxlength=10 value="$v_rcvname">
						<input name="v_rcvtel" type="hidden" size="10" maxlength=50 value="$v_rcvtel">
						<input name="v_orderemail" type="hidden" size="18" maxlength=50 value="$v_orderemail">
						<input name="v_rcvaddr" type="hidden" size="18" maxlength=60 value="$v_rcvaddr">
						<input name="v_rcvpost" type="hidden" size="10" maxlength=6 value="$v_rcvpost">
					</form>
					<script type="text/javascript">
						setTimeout(function (){document.getElementById('ebankform').submit();}, 1000);
					</script>
				</div>
EOT;
			$this->view->display('header.htm');
			echo $form;
			$this->view->display('footer.htm');
			exit;
		
		} else {
			$this->message('paytype 参数不合法');
		}
	}
	
	// 获取银行列表
	public function on_banklist() {
		$this->check_login();
		
		$kvconf = $this->kv->xget('pay_conf');
		$banklist_on = $kvconf['banklist_on'];
		
		if(empty($banklist_on)) {
			$this->message('线下支付未开启。');
		}
		
		$banklist = $kvconf['banklist'];
		$this->message($banklist);
	}
	
	public function on_ebankrecall() {
		$kvconf = $this->kv->xget('pay_conf');
		//$v_mid = $kvconf['ebank_v_mid'];	// 此处不需要
		$key = $kvconf['ebank_key'];
		
		$payid = intval(core::gpc('payid'));
		$v_oid = core::gpc('v_oid', 'P');
		$v_pstatus = core::gpc('v_pstatus', 'P');
		$v_amount = core::gpc('v_amount', 'P');
		$v_moneytype = core::gpc('v_moneytype', 'P');
		$v_md5str = core::gpc('v_md5str', 'P');
		$md5string = md5($v_oid.$v_pstatus.$v_amount.$v_moneytype.$key);
		// $v_md5str 坑爹的大写
		if(strtolower($v_md5str) == strtolower($md5string)) {
			if($v_pstatus == '20') {
				$v_amount = intval($v_amount);
				// 支付成功，更新订单标志位
				$pay = $this->pay->read($payid);
				if($pay['status'] == 0) {
					$pay['status'] = 1;
					$pay['payamount'] = $v_amount;
					$pay['paytype'] = 2;
					$pay['dateline'] = $_SERVER['time'];
					$pay['ebank_orderid'] = $v_oid;
					$pay['ebank_amount'] = $v_amount;
					$this->pay->update($pay);
					
					// 更新用户积分
					$uid = $pay['uid'];
					$user = $this->user->read($uid);
					$user['money'] += $v_amount;
					//$user['golds'] += $this->conf['pay_rate'] * $v_amount;
					$this->user->update($uid, $user);
					
					// hook pay_ebankrecall_after.php
					$this->message("支付成功，您当前的金币为：<b>$user[money]</b>", 1, "?my-pay.htm");
				} else {
					$this->message('已经支付成功。');				
				}
			} else {
				log::write('支付失败(ebank): v_pstatus 错误'.$v_pstatus);
				$this->message("支付失败，v_pstatus 错误，请联系管理员。");
			}
		} else {
			log::write('支付失败(ebank): Md5校验码错误，'." $v_md5str, $md5string ".print_r($_POST, 1));
			$this->message('支付过程中，出现问题，原因：Md5校验码错误！如果有疑问，请联系管理员。');
		}
	}
	
	// AJAX 查询支付状态
	public function on_status() {
		$this->check_login();
		// 不允许查询别人的订单
		$uid = $this->_user['uid'];
		$user = $this->user->read($uid);
		$maxpayid = intval(core::gpc('maxpayid'));
		$paylist = $this->pay->index_fetch(array('uid'=>$uid, 'payid'=>array('>='=>$maxpayid)), array(), 0, 100);
		$pay = array_pop($paylist);
		if(empty($pay)) {
			$this->message('没有正在进行的支付记录。', 0);
		}
		if($pay['status'] == 0) {
			$this->message('未支付成功。', 0);
		}
		// 过滤敏感信息
		$pay2 = array('payid'=>$pay['payid'], 'uid'=>$pay['uid'], 'dateline'=>$pay['dateline'], 'payamount'=>$pay['payamount'], 'paytype'=>$pay['paytype'], 'status'=>$pay['status'], 'money'=>$user['money']);
		
		// hook pay_status_after.php
		$this->message($pay2, 1);
	}

	//hook pay_control_after.php
	
}

?>