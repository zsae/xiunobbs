<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	$db->query("DROP TABLE IF EXISTS {$tablepre}pay");
	$db->query("CREATE TABLE {$tablepre}pay(
		   payid int(11) unsigned NOT NULL auto_increment,	# 支付ID/订单ID
		  uid int(11) unsigned NOT NULL default '0',
		  username char(16) NOT NULL default '',
		  dateline int(10) unsigned NOT NULL default '0',	# 支付时间
		  payamount int(3) NOT NULL default '0',		# 支付金额
		  paytype tinyint(3) NOT NULL default '0',		# 支付方式
		  status tinyint(3) NOT NULL default '0',		# 状态	
		  
		  alipay_email char(60) NOT NULL default '',           
		  alipay_orderid char(60) NOT NULL default '',         
		  alipay_fee int(10) NOT NULL default '0',             
		  alipay_receive_name char(10) NOT NULL default '',    
		  alipay_receive_phone char(20) NOT NULL default '',   
		  alipay_receive_mobile char(10) NOT NULL default '',  
		  ebank_orderid char(64) NOT NULL default '',          
		  ebank_amount mediumint(9) NOT NULL default '0',      
		  epay_amount int(11) NOT NULL default '0',            
		  epay_orderid char(64) NOT NULL default '0',     
		  PRIMARY KEY(payid),
		  KEY(uid)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
	$db->query("ALTER TABLE {$tablepre}user ADD COLUMN money int(11) unsigned NOT NULL default '0';");
	$this->kv->xset('pay_on', 0);
	$this->runtime->xset('pay_on', 0);
}

?>