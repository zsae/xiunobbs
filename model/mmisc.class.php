<?php

/*
 * Copyright (C) xiuno.com
 */

class mmisc extends base_model {

	function __construct(&$conf) {
		parent::__construct($conf);
	}
		
	public function sendmail($username, $email, $subject, $message) {
		//error_reporting(E_ALL);
		//error_reporting(E_STRICT);
		
		//date_default_timezone_set('America/Toronto');
		
		$mailconf = $this->kv->get('mail_conf');
		
		if($mailconf['sendtype'] == 0) {
			
			mail($email, $subject, $message, NULL, NULL);
			
		} elseif($mailconf['sendtype'] == 1) {
			
			$key = array_rand($mailconf['smtplist']);
			
			$smtp = $mailconf['smtplist'][$key];
			
			include FRAMEWORK_PATH.'lib/phpmailer.class.php';
			//include FRAMEWORK_PATH.'lib/phpmailer_smtp.class.php';
			
			$mail             = new PHPMailer();
			
			//$mail->PluginDir = FRAMEWORK_PATH.'lib/';
			$mail->IsSMTP(); // telling the class to use SMTP
			$mail->IsHTML(TRUE);
			$mail->SMTPDebug  = 0;                     // enables SMTP debug information (for testing)
			                                   // 1 = errors and messages
			                                   // 2 = messages only
			$mail->SMTPAuth   = true;                  // enable SMTP authentication
			$mail->Host       = $smtp['host']; // sets the SMTP server
			$mail->Port       = $smtp['port'];                    // set the SMTP port for the GMAIL server
			$mail->Username   = $smtp['user']; // SMTP account username
			$mail->Password   = $smtp['pass'];        // SMTP account password
			$mail->Timeout    = 5;	// 
			$mail->CharSet    = 'UTF-8';
			
			//$fromemail = $this->conf['reg_email_user'].'@'.$this->conf['reg_email_host'];
			
			$mail->SetFrom($smtp['email'], $this->conf['app_name']);
			$mail->AddReplyTo($smtp['email'], $this->conf['app_name']);
			$mail->Subject    = $subject;
			$mail->AltBody    = $message; // optional, comment out and test
			$message          = str_replace("\\",'',$message);
			$mail->MsgHTML($message);
			
			$mail->AddAddress($email, $username);
			
			//$mail->AddAttachment("images/phpmailer.gif");      // attachment
			//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
			
			if(!$mail->Send()) {
				return "Mailer Error: " . $mail->ErrorInfo;
			} else {
				return '';
			}
			
		}
	
	}
	
	public function get_email_site($str) {	
		$email = array('url'=>'', 'name'=>'');		
		switch($str) {
			case '163.com':
				$email['url'] = 'http://mail.163.com/';
				$email['name'] = '163';
				break;
			case '126.com':
				$email['url'] = 'http://mail.163.com/';
				$email['name'] = '163';
				break;
			case 'yeah.net':
				$email['url'] = 'http://mail.163.com/';
				$email['name'] = '163';
				break;
			case 'qq.com':
				$email['url'] = 'http://mail.qq.com/';
				$email['name'] = 'QQ';
				break;
			case 'yahoo.cn':
				$email['url'] = 'http://mail.cn.yahoo.com/';
				$email['name'] = 'Yahoo';
				break;
			case 'yahoo.com.cn':
				$email['url'] = 'http://mail.cn.yahoo.com/';
				$email['name'] = 'Yahoo';
				break;
			case 'sina.com':
				$email['url'] = 'http://mail.sina.com.cn/';
				$email['name'] = 'sina';
				break;
			case 'sina.cn':
				$email['url'] = 'http://mail.sina.com.cn/';
				$email['name'] = 'sina';
				break;
			case 'hotmail.com':
				$email['url'] = 'http://www.hotmail.com/';
				$email['name'] = 'Hotmail';
				break;
			case 'live.cn':
				$email['url'] = 'http://www.hotmail.com/';
				$email['name'] = 'Hotmail';
				break;
			case 'live.com':
				$email['url'] = 'http://www.hotmail.com/';
				$email['name'] = 'Hotmail';
				break;
			case 'gmail.com':
				$email['url'] = 'https://accounts.google.com/ServiceLogin?service=mail';
				$email['name'] = 'Gmail';
				break;
			case 'sohu.com':
				$email['url'] = 'http://mail.sohu.com/';
				$email['name'] = 'sohu';
				break;
			case '21cn.com':
				$email['url'] = 'http://mail.21cn.com/';
				$email['name'] = '21cn';
				break;
			case 'eyou.com':
				$email['url'] = 'http://www.eyou.com/';
				$email['name'] = 'eyou';
				break;
			case '188.com':
				$email['url'] = 'http://www.188.com/';
				$email['name'] = '188';
				break;
			case '263.net':
				$email['url'] = 'http://www.263.net/';
				$email['name'] = '263';
				break;
			case '139.com':
				$email['url'] = 'http://mail.10086.cn/';
				$email['name'] = '139';
				break;
			case 'tom.com':
				$email['url'] = 'http://mail.tom.com/';
				$email['name'] = 'Tom';
				break;
			case 'sogou.com':
				$email['url'] = 'http://mail.sogou.com/';
				$email['name'] = 'sogou';
				break;
			case 'foxmail.com':
				$email['url'] = 'http://www.foxmail.com/';
				$email['name'] = 'foxmail';
				break;
			case 'wo.com.cn':
				$email['url'] = 'http://mail.wo.com.cn/';
				$email['name'] = 'wo';
				break;	
			default: 
				$email['url'] = '';
				$email['name'] = '';
				break;											
		}
		return $email;
	}
	
	public function check_badword(&$s) {
		if($k = $this->replace_badword($s)) {
			return '数据中包含有不允许的关键字('.$k.')。';
		}
		return '';
	}
	
	// 关键词过滤
	public function replace_badword(&$s) {
		if(empty($this->conf['badword_on'])) {
			return '';
		}
		$badword = $this->kv->get('badword');
		if(!empty($badword)) {
			foreach($badword as $k=>$v) {
				if($v == '#' && strpos($s, $k) !== FALSE) {
					return $k;
				}
			}
			$keys = array_keys($badword);
			$values = array_values($badword);
			$s = str_replace($keys, $values, $s);
		}
		return '';
	}
}
?>