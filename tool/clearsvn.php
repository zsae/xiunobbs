#!/usr/bin/php
<?php

$dir = $argv[1];
$dir = 'D:/AppServ/www/domob/trunk/web/stat';
$dir = str_replace('\\', '/', $dir);

if(!is_dir($dir)) {
	exit($dir.' 不存在。');
}

rmsvn($dir);

function rmsvn($dir) {
	 if (is_dir($dir)) { 
		 $objects = scandir($dir); 
		 foreach ($objects as $object) { 
			 if ($object == "." || $object == "..") { 
				 continue;
			 }
			 if(filetype($dir.'/'.$object) == 'dir') {
				 if ($object == '.svn') { 
				 	echo "rm $dir ...";
					rrmdir($dir.'/'.$object);
					echo " [Done]\n";
				 } else {
			 		rmsvn($dir."/".$object);
				 }
			 }
		 } 
		 reset($objects); 
	 } 
}

function rrmdir($dir) { 
	 if (is_dir($dir)) { 
		 $objects = scandir($dir); 
		 foreach ($objects as $object) { 
			 if ($object != "." && $object != "..") { 
				 if (filetype($dir."/".$object) == "dir") {
				 	rrmdir($dir."/".$object);
				 } else {
				 	unlink($dir."/".$object); 
				 }
			 } 
		 } 
		 reset($objects); 
		 rmdir($dir); 
	 } 
} 