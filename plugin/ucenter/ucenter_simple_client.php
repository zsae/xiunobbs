<?php

/*
	功能：精简版本的 ucenter_client，全部走 http 协议，仅支持用户登录注册修改密码等相关基本的接口，一个文件，方便使用。
	其他：可以很方便的转换为其他语言。
	版权：Copyleft GPL, 您可以任意使用和传播甚至修改而不用担心跨省追捕。
	作者：axiuno(a)gmail.com
	支持：http://www.xiuno.com/
	实例：
		<?php
			$_SERVER['HTTP_USER_AGENT'] = 'I am eval Internet Explorer!';
			$ucconf = array (
				'uc_appid' => 2,
				'uc_appkey' => '026fsDv7Oil9sdawt3b3vz1j7cvVQiYWIECUi4CmxlPgvnEZtO6svxwGmk4mimQ6YlDsgfLz9ke91j9PDrEUd3YR3nwYI+CCnTGk0W6dQzOytnuxvk4qYm5tLQ',
				'uc_url' => 'http://xiuno.com/dz7/uc_server/',
				'uc_charset' => 'UTF-8',
			);
			$s = uc_user_register('xxxa', 'xxxa', 'xxxa@gmail.com');
			$arr = uc_user_login('xxxa', 'xxxa');
			$arr = uc_get_user('xxxa');
			$arr = uc_user_synlogin(1);
			print_r($arr);
		?>
*/

function uc_user_login($username, $password) {
	$username = urlencode(uc_charset($username));
	$password = urlencode($password);
	if(strpos($username, '@') === FALSE) {
		$s = uc_http_request('user', 'login', "username=$username&password=$password&isuid=3");// username
	} else {
		$s = uc_http_request('user', 'login', "username=$username&password=$password&isuid=2");// email
	}
	if(strpos($s, 'Access denied for agent changed') !== FALSE) {
		return '可能 APPKEY 没有设置正确。';
	}
	$arr = uc_xml_to_array($s);
	if(is_array($arr)) {
		return array (
			'status'=>$arr[0],
			'username'=>uc_charset($arr[1], 0),
			'password'=>$arr[2],
			'email'=>$arr[3],
			'merge'=>$arr[4]
		);
	} else {
		return $arr;
	}
}

/*
	ucenter 返回：
		<?xml version="1.0" encoding="ISO-8859-1"?>
		<root>
			<item id="0"><![CDATA[4]]></item>
			<item id="1"><![CDATA[abcde]]></item>
			<item id="2"><![CDATA[abcde@gmail.com]]></item>
		</root>
		
	正则替换后返回：
		Array
		(
		    [0] => 4
		    [1] => abcde
		    [2] => abcde@gmail.com
		)
*/
function uc_xml_to_array($s) {
	if(strpos($s, '<root>') !== FALSE) {
		preg_match_all('#<item id="(\w+)"><!\[CDATA\[([^]]*?)\]\]></item>#is', $s, $m);
		if(!empty($m[1])) {
			foreach($m[1] as $k=>$v) {
				$arr[$v] = $m[2][$k];
			}
			return $arr;
		}
	}
	return $s;
}

/*
	同步登录：
	返回一个数组，包含其他应用的 js 路径，格式大致如：
	array (
		0 => 'http://www.domain1.com/api/uc.php?auth=xxxxxx&action=rsynlogin',
		1 => 'http://www.domain2.com/api/uc.php?auth=xxxxxx&action=rsynlogin',
		2 => 'http://www.domain3.com/api/uc.php?auth=xxxxxx&action=rsynlogin',
	)
	接收到后 <script src="http://xxx"></script> 放入HTML页面，使其发送HTTP请求，完成其他应用的 cookie 设置，实现同步登录。同步退出同理。
*/
function uc_user_synlogin($uid) {
	$s = uc_http_request('user', 'synlogin', "uid=$uid");
	preg_match_all('#<script type="text/javascript" src="([^"]+)"#is', $s, $m);
	return isset($m[1]) ? $m[1] : $s;
}

/*
	同步退出：同上
*/
function uc_user_synlogout() {
	$s = uc_http_request('user', 'synlogout', '');
	preg_match_all('#<script type="text/javascript" src="([^"]+)"#is', $s, $m);
	return isset($m[1]) ? $m[1] : $s;
}

// 修改密码
function uc_user_updatepw($username, $newpw) {
	$username = urlencode(uc_charset($username));
	$newpw = urlencode($newpw);
	return uc_http_request('user', 'edit', "username=$username&newpw=$newpw&ignoreoldpw=1");
}

// 删除用户
function uc_user_delete($uid) {
	return uc_http_request('user', 'delete', "uid=$uid");
}

// 注册
function uc_user_register($username, $password, $email) {
	$username = urlencode(uc_charset($username));
	$password = urlencode($password);
	$email = urlencode($email);
	$regip = $_SERVER['REMOTE_ADDR'];
	return uc_http_request('user', 'register', "username=$username&password=$password&email=$email&regip=$regip");
}

// 获取一个用户，根据用户名。
/*
Array
(
    [0] => 5
    [1] => xxxa
    [2] => xxxa@gmail.com
)
*/
function uc_get_user($username, $isuid = 0) {
	$username = urlencode(uc_charset($username));
	$s = uc_http_request('user', 'get_user', "username=$username&isuid=$isuid");
	$arr = uc_xml_to_array($s);
	if(is_array($arr)) {
		return array (
			'uid'=>$arr[0],
			'username'=>uc_charset($arr[1], 0),
			'email'=>$arr[2],
		);
	} else {
		return $s;
	}
}

// UTF-8 与 uc 编码互转函数， $to 控制转换方向。
function uc_charset($s, $to = 1) {
	global $ucconf;
	return $ucconf['uc_charset'] == 'UTF-8' ? $s : iconv($to ? 'UTF-8' : $ucconf['uc_charset'], $to ? $ucconf['uc_charset'] : 'UTF-8', $s);
}

// 修改密码
function uc_http_request($module, $action, $arg='') {
	global $ucconf;
	$input = urlencode(uc_authcode($arg.'&agent='.md5($_SERVER['HTTP_USER_AGENT'])."&time=".time(), 'ENCODE', $ucconf['uc_appkey']));
	substr($ucconf['uc_url'], -1) != '/' && $ucconf['uc_url'] .= '/';
	$url = $ucconf['uc_url'].'index.php?'."m=$module&a=$action&inajax=2&release=20091001&input=$input&appid=".$ucconf['uc_appid'];
	return xn_get_url($url, 5);
}

// 可逆加密，RC4算法
function uc_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;

	$key = md5($key);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

// HTTP REQUEST，不能使用 php 内置的 file_get_contents(), 需要定制 HTTP_USER_AGENT, SAE 兼容 file_get_contents()
function xn_get_url($url, $timeout = 5, $post = '', $cookie = '') {
	if(function_exists('fsockopen')) {
		$limit = 500000;
		$ip = '';
		$return = '';
		$matches = parse_url($url);
		if(empty($matches['host'])) {
			throw new Exception("$url 格式有误。");
		}
		$host = $matches['host'];
		$path = $matches['path'] ? $matches['path'].(!empty($matches['query']) ? '?'.$matches['query'] : '') : '/';
		$port = !empty($matches['port']) ? $matches['port'] : 80;
	
		if(empty($post)) {
			$out = "GET $path HTTP/1.0\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cookie:$cookie\r\n\r\n";
		} else {
			$out = "POST $path HTTP/1.0\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n";
			$out .= 'Content-Length: '.strlen($post)."\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cache-Control: no-cache\r\n";
			$out .= "Cookie:$cookie\r\n\r\n";
			$out .= $post;
		}
		$host == 'localhost' && $ip = '127.0.0.1';
		$fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
		if(!$fp) {
			return '';
		} else {
			stream_set_blocking($fp, TRUE);
			stream_set_timeout($fp, $timeout);
			@fwrite($fp, $out);
			$status = stream_get_meta_data($fp);
			if(!$status['timed_out']) {
				$starttime = time();
				while (!feof($fp)) {
					if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
						break;
					}
				}
	
				$stop = false;
				while(!feof($fp) && !$stop) {
					$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
					$return .= $data;
					if($limit) {
						$limit -= strlen($data);
						$stop = $limit <= 0;
					}
					if(time() - $starttime > $timeout) break;
				}
			}
			@fclose($fp);
			return $return;
		}
	} else {
		return '';
	}
}