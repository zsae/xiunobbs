<?php

// nginx
// rewrite "^/forum-(\d+)-(\d+)\.html$" /plugin/discuzx_urlrewrite/rewrite.php;
// rewrite "^/thread-(\d+)-(\d+)-(\d+)\.html$" /plugin/discuzx_urlrewrite/rewrite.php;

define('DEBUG', 2);
define('BBS_PATH', '../../');
define('DX2_PATH', BBS_PATH.'dx2/');
define('DX2_CONF_FILE', DX2_PATH.'config/config_global.php');
$conf = include BBS_PATH.'conf/conf.php';
define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';
core::init();
core::ob_start();

function load_upgrade_policy() {
        global $conf;
        $policyfile = $conf['upload_path'].'upgrade_policy.txt';
        if(!is_file($policyfile)) {
                exit('策略文件不存在。');
        }
        $s = file_get_contents($policyfile);
        $policy = (array)core::json_decode($s);
        return $policy;
}

function get_fid_by_policy($fid, $policy) {
        $fup = $policy['fuparr'][$fid];
        if($fup == 0) {
                if($policy['keepfup']) {
                        return $fid;
                } else {
                        return 0;
                }
        } else {
                if(!isset($policy['fidto'][$fid])){
                        return $fid;
                }
                if($policy['fidto'][$fid] == 'threadtype') {
                        return $fup;
                } else {
                        return $fid;
                }
        }
}

function get_dx2() {
        include DX2_CONF_FILE;
        $dx2 = new db_mysql(array(
                'master' => array (
                        'host' => $_config['db'][1]['dbhost'],
                        'user' => $_config['db'][1]['dbuser'],
                        'password' => $_config['db'][1]['dbpw'],
                        'name' => $_config['db'][1]['dbname'],
                        'charset' => 'utf8',    // 要求取出 utf-8 数据 mysql 4.1 以后支持转码
                        //'charset' => $_config['db'][1]['dbcharset'],
                        'tablepre' => $_config['db'][1]['tablepre'],
                        'engine'=>'MyISAM',
                ),
                'slaves' => array ()
        ));
        // 要求返回的数据为 utf8
        return $dx2;
}

// forum-123-1.html
$s = $_SERVER['REQUEST_URI'];
preg_match('#/forum-(\d+)-(\d+)\.html$#', $s, $m1);
preg_match('#thread-(\d+)-(\d+)-(\d+)\.html$#', $s, $m2);

if(!empty($m1[1])) {

        $fid = intval($m1[1]);
        $page = intval($m1[2]);
        if($fid) {
                $policy = load_upgrade_policy();
                $newfid = get_fid_by_policy($fid, $policy);

                $newurl = $conf['app_url'].($conf['urlrewrite'] ? '' : '?')."forum-index-fid-$newfid-page-$page.htm";
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $newurl");
        }

        exit;
} elseif(!empty($m2[1])) {

        $tid = intval($m2[1]);
        $page = intval($m2[3]);
        if($tid) {

                $dx2 = get_dx2();
                $thread = $dx2->get("forum_thread-tid-$tid");
                if(empty($thread)) {
                        exit("主题不存在：tid=$tid");
                }
                $fid = $thread['fid'];
                $policy = load_upgrade_policy();
                $newfid = get_fid_by_policy($fid, $policy);

                $newurl = $conf['app_url'].($conf['urlrewrite'] ? '' : '?')."thread-index-fid-$newfid-tid-$tid-page-$page.htm";
		 header("HTTP/1.1 301 Moved Permanently");
                header("Location: $newurl");
        }

        exit;
}