<?php

/*
 * Copyright (C) xiuno.com
 */

/*
	这是一个示例代码，用来COPY，替换。 替换 blog -> yourmodel
	
*/

class blog extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'blog';
		$this->primarykey = array('blogid');
		$this->maxcol = 'blogid';
	}
	
	// ------------------> 杂项
	public function check_subject(&$subject) {
		if(empty($subject)) {
			return '标题不能为空。';
		}
		if(utf8::strlen($subject) > 200) {
			return '标题不能超过 200 字，当前长度：'.strlen($subject);
		}
		return '';
	}
	
	public function check_message(&$message) {
		if(empty($message)) {
			return '内容不能为空。';
		}
		if(utf8::strlen($message) > 2000000) {
			return '内容不能超过200万个字符。';
		}
		return '';
	}
	
	// 用来显示给用户
	public function format(&$blog) {
		$blog['subject']  = htmlspecialchars($blog['subject']);
		$blog['message']  = $blog['message'];
		$blog['dateline_fmt'] = date('Y-n-j H:i', $blog['dateline']);
	}
}
?>