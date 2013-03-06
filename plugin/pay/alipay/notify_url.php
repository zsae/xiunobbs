<?php

/* 我只能说支付宝接口真的很蛋疼，notify_url 为什么不能传参数？ return_url 的时候为什么不提示的明显一点？ */

// SAE 版本需要复制此文件到根目录，并且修改 BBS_PATH

define('DEBUG', 0);
define('BBS_PATH', str_replace('\\', '/', substr(dirname(__FILE__)), 0, -25));
$conf = include BBS_PATH.'conf/conf.php';
define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';
core::init();
core::ob_start(0);

require BBS_PATH."api/alipay/alipay_notify.php";

$kvconf = $this->kv->xget('pay_conf');
$partner = $kvconf['alipay_partner'];
$security_code = $kvconf['alipay_security_code'];
$seller_email = $kvconf['alipay_seller_email'];
$alipay_on = $kvconf['alipay_on'];
$_input_charset = "utf-8";
$sign_type = "MD5";
$transport= "http";
$notify_url = "";
$return_url = "";
$show_url   = "";

if(empty($_POST) || !core::gpc('out_trade_no', 'P') || !core::gpc('out_trade_no', 'P') || !core::gpc('total_fee', 'P')) {
	log::write('缺少参数: '.print_r($_POST, 1));
	exit;
}

$alipay = new alipay_notify($partner,$security_code,$sign_type,$_input_charset,$transport);
$verify_result = $alipay->notify_verify();

$mpay = new pay();
$muser = new user();
if($verify_result) {
	 //获取支付宝的反馈参数
	$payid	 	 = intval(core::gpc('out_trade_no', 'P'));	//获取支付宝传递过来的订单号
	$total_fee	 = intval(core::gpc('total_fee', 'P'));		//获取支付宝传递过来的总价格
	$receive_name    = core::gpc('receive_name', 'P');		//获取收货人姓名
	$receive_address = core::gpc('receive_address', 'P');		//获取收货人地址
	$receive_zip     = core::gpc('receive_zip', 'P');		//获取收货人邮编
	$receive_phone   = core::gpc('receive_phone', 'P');		//获取收货人电话
	$receive_mobile  = core::gpc('receive_mobile', 'P');		//获取收货人手机
	$alipay_email    = core::gpc('buyer_email', 'P');
	$trade_status 	 = core::gpc('trade_status', 'P');		//获取支付宝反馈过来的状态,根据不同的状态来更新数据库 WAIT_BUYER_PAY(表示等待买家付款);WAIT_SELLER_SEND_GOODS(表示买家付款成功,等待卖家发货);WAIT_BUYER_CONFIRM_GOODS(卖家已经发货等待买家确认);TRADE_FINISHED(表示交易已经成功结束)

	// 支付成功
	if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS' || $trade_status == 'WAIT_SELLER_SEND_GOODS') {
		
		// 支付成功，更新订单标志位
		$pay = $mpay->read($payid);
		if($pay['status'] == 0) {
			$pay['status'] = 1;
			$pay['payamount'] = $total_fee;
			$pay['paytype'] = 2;
			$pay['alipay_fee'] = $total_fee;
			$pay['alipay_email'] = $alipay_email;
			$pay['alipay_receive_name'] = $receive_name;
			$pay['alipay_receive_phone'] = $receive_phone;
			$pay['alipay_receive_mobile'] = $receive_mobile;
			$pay['alipay_orderid'] = $payid;
			$mpay->update($pay);
			
			// 更新用户积分，重复通知？
			$uid = $pay['uid'];
			$user = $muser->read($uid);
			$user['money'] += $total_fee;
			//$user['golds'] += $conf['pay_rate'] * $total_fee;
			$muser->update($uid, $user);
		}
		
		// hook pay_alipayrecall.php
		echo 'succeed';
		log::write('支付成功: payid :'.$payid);
	// 支付失败
	} else  {
		echo "fail";
		log::write('支付失败: trade_status :'.$trade_status.' '.print_r($_POST, 1));
	}
} else {
	echo "verify_result fail";
	log::write('支付失败: verify_result fail');
}

?>