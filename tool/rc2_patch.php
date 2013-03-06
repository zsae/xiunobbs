<?php

/*
        功能：补丁生成程序，生成 new 目录新增的和修改的文件。
        使用：
                php patch.php ./old ./new ./patch

        作者：http://www.xiuno.com/
*/

error_reporting(E_ALL);
@set_time_limit(0);
ob_implicit_flush(true);

$time = date('Ymd', time());

// 需要设置为空的几个文件
$emptyfile = array('plugin/ucenter/dz_authcode.php', 'plugin/ucenter/user_login_succeed.php');

if(PHP_OS != 'WINNT') {

        $oldzip = '/xiuno/down/xiuno_bbs_2.0.0.rc2.old.zip';
        $newzip = '/xiuno/down/xiuno_bbs_2.0.0.rc2.zip';

        shell_exec(escapeshellcmd("rm -rf /xiuno/down/xiuno_bbs_2.0.0.rc2.old"));
        shell_exec(escapeshellcmd("rm -rf /xiuno/down/xiuno_bbs_2.0.0.rc2"));
        shell_exec(escapeshellcmd("unzip $oldzip -d /xiuno/down/xiuno_bbs_2.0.0.rc2.old"));
        shell_exec(escapeshellcmd("unzip $newzip -d /xiuno/down/xiuno_bbs_2.0.0.rc2"));


        $old = substr($oldzip, 0, strrpos($oldzip, '.')).'/upload_me/';
        $new = substr($newzip, 0, strrpos($newzip, '.')).'/upload_me/';
} else {
        $old = 'D:/xiuno/xiuno_bbs_2.0.0.rc2(3)/upload_me/';
        $new = 'D:/xiuno/xiuno_bbs_2.0.0.rc2(10)/upload_me/';
}

$patchname = "xiuno_bbs_2.0.0.rc2_patch_$time";
if(PHP_OS != 'WINNT') {
        $patch = "/xiuno/down/$patchname/";
        $patchfile = "/xiuno/down/$patchname.zip";
} else {
        $patch = "d:/xiuno/$patchname/";
        $patchfile = "d:/xiuno/$patchname.zip";
}

if(!is_dir($new)) exit("$new does not exists.");
if(!is_dir($old)) exit("$old does not exists.");
shell_exec("rm -rf $patch"); 
shell_exec("rm -rf $patchfile"); 
if(!is_dir($patch)) mkdir($patch, 0644);
opendir_recursive($new);

// 生成空文件
foreach($emptyfile as $v) {
        is_file($patch.$v) && unlink($patch.$v);
        file_put_contents($patch.$v, '');
}


shell_exec("rm -rf $patch/install");
shell_exec("rm -rf $patch/conf");
shell_exec("rm -rf $patch/plugin/ucenter/conf.php");
shell_exec("rm -rf $patch/plugin/vip_seo/conf.php");
shell_exec("cd $patch ; zip -r $patchfile ./*");
echo "cd $patch && zip -r $patchfile ./*";

echo "[DONE]\n";

function opendir_recursive($dir, $path = '') {
        if(substr($dir, -1) != '/') $dir .= '/';
        if (!is_dir($dir)) return;
        $dh = opendir($dir.$path);
        if(!$dh) return;
        while(($file = readdir($dh)) !== false ) { 
                  if($file == "." || $file == "..")  continue;  
                  if(is_dir($dir.$path.$file)) {   
                        patch_dir($path.$file.'/', $file);
                        opendir_recursive($dir, $path.$file.'/');
                  } else {   
                        patch_file($path, $file);
                  }   
         }
         
         closedir($dh);
}

function patch_dir($path, $file) {
        global $old, $new, $patch;
        if(!is_dir($old.$path)) {
                mkdir($patch.$path);
        }
}

function patch_file($path, $file) {
           global $old, $new, $patch;
           if(!preg_match('#\.(php|js|htm|sql)$#is', $file)) return;
           
           $oldfile = $old.$path.$file;
           $newfile = $new.$path.$file;
           $patchfile = $patch.$path.$file;
           if(!is_file($oldfile)) {
                pmakedir($patchfile);
                copy($newfile, $patchfile);
           } elseif(file_get_contents($oldfile) != file_get_contents($newfile)) {
                pmakedir($patchfile);
                copy($newfile, $patchfile);
           }
}

// 检查目录
function pmakedir($patchfile) {
        $arr = explode('/', $patchfile);
        $dir = $arr[0];
        for($i=1; $i<count($arr) - 1; $i++) {
                $dir .= "/".$arr[$i];
                !is_dir($dir) && mkdir($dir);
        }
}

?>