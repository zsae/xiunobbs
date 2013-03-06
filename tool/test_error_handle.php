<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_error', 'ON');
ini_set('default_charset', 'UTF-8');

function error_handle($errno, $errstr, $errfile, $errline) {
	$errortype = array (
		E_ERROR              => 'Error',
		E_WARNING            => 'Warning',
		E_PARSE              => 'Parsing Error',	# uncatchable
		E_NOTICE             => 'Notice',
		E_CORE_ERROR         => 'Core Error',		# uncatchable
		E_CORE_WARNING       => 'Core Warning',		# uncatchable
		E_COMPILE_ERROR      => 'Compile Error',	# uncatchable
		E_COMPILE_WARNING    => 'Compile Warning',	# uncatchable
		E_USER_ERROR         => 'User Error',
		E_USER_WARNING       => 'User Warning',
		E_USER_NOTICE        => 'User Notice',
		E_STRICT             => 'Runtime Notice',
		//E_RECOVERABLE_ERRROR => 'Catchable Fatal Error'
	);
	
	$errnostr = isset($errortype[$errno]) ? $errortype[$errno] : 'Unknonw';

	// 运行时致命错误，直接退出。并且 debug_backtrace()
	$s = "[$errnostr] : $errstr in File $errfile, Line: $errline";
	
	// 抛出异常，记录到日志
	//echo $errstr;
	$s = preg_replace('#[\\x80-\\xff]{2}#', '?', $s);// 替换掉GBK
	echo $s;
}

set_error_handler('error_handle');



$host = '123.11.11';
$port = 25;
$tval = 2;
$errno = $errstr = 0;
$s = fsockopen($host,    // the host of the server
         $port,    // the port to use
         $errno,   // error number if any
         $errstr,  // error message if any
         $tval);   // give up after ? secs
         
         
var_dump($s);