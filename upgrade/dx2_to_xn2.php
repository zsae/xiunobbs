<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来升级 DiscuzX 2.0 到 Xiuno BBS 2.0.0 Release，支持重复升级，断点升级，可以反复升级，不会导致数据错乱。
/*
	流程：
		1. 备份原站点：新建目录: dx2, 将所有文件移动到 dx2 中
		2. 上传 XiunoBBS 2.0.0 upload_me 文件夹下的源代码到根目录，通过 url 访问，安装，安装成功以后进入第3步。
		3. 访问 http://www.domain.com/dx2_to_xn2.php 开始升级
		4. 升级完毕后，删除升级程序：dx2_to_xn2.php
*/


/*
	uid = 1 默认为管理员
	system uid 设置到 conf/conf.php
	转换置顶帖，精华帖。
*/

// 积分范围确定用户组
// 转换那些积分 extcredits

@set_time_limit(0);

define('DEBUG', 0);

define('BBS_PATH', './');

// DX2_PATH 需要配置正确！ linux 下可以用如下命令行： ln -s /data/www/oldbbs.com /data/www/xiunobbs/dx2
define('DX2_PATH', BBS_PATH.'dx2/');

// 以下为默认路径，一般情况不需要修改！
define('DX2_CONF_FILE', DX2_PATH.'config/config_global.php');
define('UCENTER_CONF_FILE', DX2_PATH.'config/config_ucenter.php');
define('DX2_ATTACH_PATH', DX2_PATH.'data/attachment/');
define('DX2_AVATAR_PATH', DX2_PATH.'uc_server/data/avatar/');

// 加载应用的配置文件，唯一的全局变量 $conf
if(!($conf = include BBS_PATH.'conf/conf.php')) {
	message('配置文件不存在，请先安装 Xiuno BBS。');
}

define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';
core::init();
core::ob_start();

// 初始化参数
loading_upgrade_process($step, $start, $start2);
$step = isset($_GET['step']) ? $_GET['step'] : $step;
$start = isset($_GET['start']) ? intval($_GET['start']) : $start;
$start2 = isset($_GET['start2']) ? intval($_GET['start2']) : $start2;

// 输入 dx2 路径！ 检查 xn2 安装。取得 max uid


// 升级配置文件
if(empty($step)) {
	/*
	// 如果没有升级进度，则清空
	$db = get_db();
	$file = $conf['tmp_path'].'upgrade_process.txt';
	if(!is_file($file)) {
		$db = get_db();
		$db->truncate('forum');
		$db->truncate('thread');
		$db->truncate('post');
		$db->truncate('user');
		$db->truncate('mypost');
		$db->truncate('thread_type');
		$db->truncate('friendlink');
		$db->truncate('runtime');
	*/
	upgrade_conf();
} elseif($step == 'upgrade_prepare') {
	upgrade_prepare();
} elseif($step == 'upgrade_forum_policy') {
	upgrade_forum_policy();
} elseif($step == 'upgrade_forum') {
	upgrade_forum();
} elseif($step == 'upgrade_thread') {
	upgrade_thread();
} elseif($step == 'upgrade_thread_type') {
	upgrade_thread_type();
} elseif($step == 'upgrade_post') {
	upgrade_post();
} elseif($step == 'upgrade_attach') {
	upgrade_attach();
} elseif($step == 'upgrade_user') {
	upgrade_user();
} elseif($step == 'upgrade_pm') {
	upgrade_pm();
} elseif($step == 'upgrade_friendlink') {
	upgrade_friendlink();
} elseif($step == 'upgrade_mod') {
	upgrade_mod();
} elseif($step == 'upgrade_stat') {
	upgrade_stat();
} elseif($step == 'upgrade_postpage') {
	upgrade_postpage();
} elseif($step == 'upgrade_forum2') {
	upgrade_forum2();
} elseif($step == 'laststep') {
	laststep();
}

function upgrade_conf() {
	
	global $conf;
	
	//global $old, $conf;
	$dx2 = get_dx2();
	
	// 各种检查
	$dx2_path = DX2_PATH;
	if(!is_dir($dx2_path)) {
		message("路径: $dx2_path 不存在，请将 Discuz!X 2.0 所有文件目录移动到 dx2 目录下。");
	}
	
	// 取老的 setting
	$settinglist = $dx2->index_fetch('common_setting', 'skey', array(), array(), 0, 1000);
	$old = misc::arrlist_key_values($settinglist, 'skey', 'svalue');
	
	$sql = "ALTER TABLE {$dx2->tablepre}forum_post ADD INDEX tidpid(tid, pid);";
	message('修改配置文件成功，接下来一些准备工作... 为了加快您的升级速度，如果您的数据量很大，超出 100w 帖子，请您手工执行如下SQL: <br /><br />'.$sql, '?step=upgrade_prepare');
}

// 一些准备工作
function upgrade_prepare() {

	global $conf;
	
	$db = get_db();
	$dx2 = get_dx2();
	try {
		//$db->index_create('thread', array('tid'=>1));
		$db->query("CREATE TABLE IF NOT EXISTS {$db->tablepre}user_ext (
			  uid int(11) unsigned NOT NULL default '0',	
			  gender tinyint(11) unsigned NOT NULL default '0',	
			  birthyear int(11) unsigned NOT NULL default '0',	
			  birthmonth int(11) unsigned NOT NULL default '0',	
			  birthday int(11) unsigned NOT NULL default '0',	
			  province char(16) NOT NULL default '',
			  city char(16) NOT NULL default '',
			  county char(16) NOT NULL default '',
			  KEY (birthyear, birthmonth),
			  KEY (province),
			  KEY (city),
			  KEY (county),
			  PRIMARY KEY (uid));", NULL);
	} catch (Exception $e){}
	try {
		$db->query("CREATE TABLE {$db->tablepre}friendlink(
			  linkid int(10) unsigned NOT NULL auto_increment,
			  type tinyint(1) NOT NULL default '0',
			  rank tinyint(1) unsigned NOT NULL default '0',
			  sitename char(16) NOT NULL default '',
			  url char(64) NOT NULL default '',
			  logo char(64) NOT NULL default '',
			  PRIMARY KEY (linkid),
			  KEY type (type, rank)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
	} catch (Exception $e){}
	try { $db->query("ALTER TABLE {$db->tablepre}forum ADD COLUMN fup int not null default '0';"); } catch (Exception $e){}
	try { $db->query("ALTER TABLE {$db->tablepre}thread_type ADD column oldtypeid int(11) NOT NULL default '0';"); } catch (Exception $e){}
	try { $db->query("ALTER TABLE {$db->tablepre}thread_type ADD column oldfid int(11) NOT NULL default '0';"); } catch (Exception $e){}
	try { $db->index_create('thread_type', array('oldtypeid'=>1)); } catch (Exception $e){}
	try { $db->index_create('thread_type', array('oldfid'=>1)); } catch (Exception $e){}
	try { $db->index_create('attach', array('aid'=>1)); } catch (Exception $e){}
	try { $db->index_create('post', array('pid'=>1)); } catch (Exception $e){}
	try { $dx2->index_create('forum_post', array('tid'=>1, 'pid'=>1)); } catch (Exception $e){}
	
	//die($e->getMessage());
	
	message('准备完毕，接下来设置升级策略...', '?step=upgrade_forum_policy');
}

// 用户选择版块的升级方法。
function upgrade_forum_policy() {
	global $conf;
	$dx2 = get_dx2();
	$db = get_db();
	$uc = get_uc();
	
	$policy = load_upgrade_policy();
	
	if(isset($_POST['submit'])) {
		$policy['keepfup'] =  core::gpc('keepfup', 'P');
		$policy['fidto'] =  core::gpc('fidto', 'P');
		$policy['threadtypefid'] =  core::gpc('threadtypefid', 'P');
		$policyfile = $conf['upload_path'].'upgrade_policy.txt';
		file_put_contents($policyfile, core::json_encode($policy));
		message('升级策略已经保存，下一步开始升级版块！', '?step=upgrade_forum&start=0');
	}
	
	show_header();
	
	echo '
		<div id="body" style="line-height: 1.8">
		<h1>设置版块升级策略</h1>
		<ul>
			<li>因为 Xiuno 采用的分类方式为 一级版块 +　多维主题分类，所以您需要选择下升级过去后的版块+主题分类的结构：</li>
			<li>一般在您版块比较多的情况下，我们建议采用“保留大区，下属版块作为主题分类”。</li>
			<li>版块比较少的话，建议选择抛弃大区，下属版块作为 Xiuno 一级版块。</li>
			<li><b>注意：</b>只支持二级版块的转换，三级子版块请调整后再进行转换。</li>
			<li><b>注意：</b><span class="red">每个版块下子版块不能超过40个！。</span></li>
		</ul>';
	
	$catelist = $dx2->index_fetch('forum_forum', 'fid', array('fup'=>0), array(), 0, 1000);
	
	echo '<form action="'.$_SERVER['PHP_SELF'].'?step=upgrade_forum_policy" method="post">';
	foreach($catelist as $cate) {
		$fup = $cate['fid'];
		if($cate['status'] == 3) continue;
		if($cate['name'] == '') continue;
		
		$cate['name'] = strip_tags($cate['name']); 
		
		!isset($policy['keepfup'][$fup]) && $policy['keepfup'][$fup] = 0;
		$check1 = $policy['keepfup'][$fup] == 1 ? ' checked="checked"' : '';
		$check2 = $policy['keepfup'][$fup] == 0 ? ' checked="checked"' : '';
		
		$forumlist = $dx2->index_fetch('forum_forum', 'fid', array('fup'=>$fup), array(), 0, 1000);
		
		if(empty($forumlist)) continue;
		
		echo '<div class="catediv" style="margin: 32px;">';
		
		echo "<p>
			<table width=\"700\">
				<tr>
					<td><b>$cate[name]</b> <span class=\"grey\">($cate[fid])</span></td>
					<td width=\"160\" class=\"grey\"><input type=\"radio\" name=\"keepfup[$fup]\" value=\"0\" class=\"grey\" $check2 />升级所属版块为一级</td>
					<td width=\"160\" class=\"grey\"><input type=\"radio\" name=\"keepfup[$fup]\" value=\"1\" class=\"grey\" $check1 />升级大区成一级</td>
					<td width=\"160\" class=\"grey\">&nbsp;</td>
				</tr>
			</table>
			</p>";
	
		foreach($forumlist as $forum) {
			if($cate['status'] == 3) continue;
			if($cate['name'] == '') continue;
		
			$fid = $forum['fid'];
			
			$check1 = $policy['fidto'][$fid] == 'forum' ? ' checked="checked"' : '';
			$check2 = $policy['fidto'][$fid] == 'threadtype' ? ' checked="checked"' : '';
			$check3 = isset($policy['threadtypefid'][$fup]) && $policy['threadtypefid'][$fup] == $fid ? ' checked="checked"' : '';
		
			$forum['name'] = strip_tags($forum['name']); 
			echo "<table width=\"700\">
					<tr>
						<td>&nbsp; &nbsp; &nbsp; &nbsp; $forum[name] <span class=\"grey\">($forum[fid])</span></td>
						<td width=\"160\" class=\"grey\"><input type=\"radio\" name=\"fidto[$fid]\" value=\"forum\" checked=\"checked\" $check1 />升级为 Xiuno 一级版块 &nbsp; </td>
						<td width=\"160\" class=\"grey\"><input type=\"radio\" name=\"fidto[$fid]\" value=\"threadtype\" $check2 />升级为主题分类 &nbsp; </td>
						<td width=\"160\" class=\"grey\"><input type=\"radio\" name=\"threadtypefid[$fup]\" value=\"$fid\" $check3 />该板主题分类为二级分类</td>
					</tr>
				</table>";
		}
		echo '</div>';
	}
	echo "<p><input name=\"submit\" type=\"submit\" value=\"确定升级版块策略，下一步\" /></p>";
	echo '</div>';
	echo "</form>";
	
	echo '
		<script src="../view/js/jquery-1.4.min.js" ></script>
		<script type="text/javascript">
			$("div.catediv").each(function() {
				var _div = this;
				$("input[name^=keepfup]", this).click(function() {
					var _input = this;
					// 保留大区
					if($(this).val() == 1) {
						$("input[value=forum]", _div).attr("checked", "");
						$("input[value=threadtype]", _div).attr("checked", "checked");
						$("input[name^=threadtypefid]", _div).attr("disabled", "");
						$("input[name^=threadtypefid]:first", _div).attr("checked", "checked");
					} else {
						$("input[value=forum]", _div).attr("checked", "checked");
						$("input[value=threadtype]", _div).attr("checked", "");
						$("input[name^=threadtypefid]", _div).attr("disabled", "disabled");
					}
				});
				$("input", this).click(function() {
					var _input = this;
					var _tr = $(this).closest("tr");
					if($("input[value=forum]", _tr).attr("checked") && $("input[name^=threadtypefid]", _tr).attr("checked")) {
						$("input[value=forum]", _tr).attr("checked", "");
						$("input[value=threadtype]", _tr).attr("checked", "checked");
					}
				});
			});
		</script>
	';
	
	show_footer();
}

function load_upgrade_policy() {
	global $conf;
	$dx2 = get_dx2();
	
	// 策略文件。
	$policyfile = $conf['upload_path'].'upgrade_policy.txt';
	if(!is_file($policyfile)) {
		return init_policy();
	}
	$s = file_get_contents($policyfile);
	$policy = (array)core::json_decode($s);
	
	// 初始化。
	if(empty($policy)) {
		$policy = init_policy();
	}
	return $policy;
}

function init_policy() {
	global $conf;
	$dx2 = get_dx2();
	
	$policyfile = $conf['upload_path'].'upgrade_policy.txt';
	if(is_file($policyfile)) {
		throw new Exception('upload/upgrade_policy.txt 已经存在！');
	}
	$forumlist = $dx2->index_fetch('forum_forum', 'fid', array(), array(), 0, 4000);
	$fuparr = array();
	foreach($forumlist as $forum) {
		$policy['keepfup'][$forum['fid']] = 0;
		$policy['fidto'][$forum['fid']] = 'forum';
		$policy['threadtypefid'][$forum['fid']] = 0;
		$policy['fuparr'][$forum['fid']] = $forum['fup'];
	}
	$policyfile = $conf['upload_path'].'upgrade_policy.txt';
	file_put_contents($policyfile, core::json_encode($policy));
	return $policy;
}

// 根据策略，调整 fid
function get_fid_by_policy($fid, $policy) {
	$fup = $policy['fuparr'][$fid];
	
	// 大区
	if($fup == 0) {
		if($policy['keepfup']) {
			return $fid;
		} else {
			return 0;
		}
	// 普通版块
	} else {
		// 移动到上一级
		
		// 子版块 type = sub
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

// 获取fid 所在的位置，0-40，一级主题分类的值范围。
function get_fid_order($policy, $fid) {
	$i = 0;
	$fup = $policy['fuparr'][$fid];
	foreach($policy['fuparr'] as $_fid=>$_fup) {
		$fup == $_fup && $i++;
		if($fid == $_fid) return $i;
	}
	return 0;
}

function upgrade_forum() {
	global $start, $conf;
	$dx2_attach_path = DX2_ATTACH_PATH;
	
	$policy = load_upgrade_policy();
	
	include DX2_CONF_FILE;
	
	$dx2 = get_dx2();
	$db = get_db();
	$uc = get_uc();
	$count = $dx2->index_count('forum_forum');
	$mthread_type = new thread_type($conf);
	$mthread_type_cate = new thread_type_cate($conf);
	$mforum_access = new forum_access($conf);
	$groupids = array(0, 1, 2, 3, 4, 5, 6, 7, 11, 12, 13, 14, 15);
	if($start < $count) {
		$limit = DEBUG ? 10 : 500;	// 每次升级 100
		$arrlist = $dx2->index_fetch_id('forum_forum', 'fid', array(), array('fid'=>1), $start, $limit);
		foreach($arrlist as $key) {
			list($table, $col, $fid) = explode('-', $key);
			$old = $dx2->get("forum_forum-fid-$fid");
			$old2 = $dx2->get("forum_forumfield-fid-$fid");
			$fup = $old['fup'];
			
			// fix dx2 empty forumname
			if(empty($old['name'])) continue;
			
			// 群组，隐藏掉
			if($old['status'] == 3) {
				$old['status'] = 0;
				$old['name'] .= '(群组)';
				continue;	// 群组数据抛弃，thread, post 都应该抛弃，发帖数，回复数都得重新统计！
			}
			
			// 忽略第三级子版块
			if($old['type'] == 'sub') {
				continue;
			}
			
			// a:6:{s:8:"required";b:1;s:8:"listable";b:0;s:6:"prefix";s:1:"0";s:5:"types";a:3:{i:1;s:7:"fenlei1";i:2;s:7:"fenlei2";i:3;s:7:"fenlei3";}s:5:"icons";a:3:{i:1;s:0:"";i:2;s:0:"";i:3;s:0:"";}s:10:"moderators";a:3:{i:1;N;i:2;N;i:3;N;}}
			
			// 四种情况： 大区|版块  *  保留大区|保留大区
			if($old['fup'] == 0) {
				
				// 根据策略忽略版块
				if(empty($policy['keepfup'][$fid])) {
					continue;
				} else {
					
					// 初始化一个空壳版块，
					$arr = array (
						'fid'=> $old['fid'],
						'fup'=> $old['fup'],
						'name'=> strip_tags($old['name']),
						'rank'=> $old['displayorder'],
						'threads'=> 0,
						'posts'=> 0,
						'todayposts'=> 0,
						'lasttid'=> 0,
						'brief'=> '',
						'accesson'=> 0,
						'modids'=> '',
						'modnames'=> '',
						'toptids'=> '',
						'orderby'=> 0,
						'seo_title'=> '',
						'seo_keywords'=> '',
					);
					
					$db->set("forum-fid-$fid", $arr);
				}
			} else {
				
				// ---------> 普通二级版块（非大区）
				
				// 判断大区是否保留，如果保留大区：则当前二级版块为主题分类
				if($policy['keepfup'][$fup]) {
					$savefid = get_fid_by_policy($fid, $policy);
						
					// 这里可能执行多次，但是只会成功一次
					$mthread_type_cate->create(array('fid'=>$fup, 'cateid'=>1, 'catename'=>'分类', 'rank'=>1, 'enable'=>1));
					$newtypeid = get_fid_order($policy, $fid);
					$arr = array(
						'fid'=>$fup,
						'typeid'=>$newtypeid,
						'oldtypeid'=>0,
						'oldfid'=>$fid,
						'typename'=>str_replace(array("\r", "\n"), array('', ''), strip_tags($old['name'])),
						'rank'=>0,
						'enable'=>1,
					);
					$db->set("thread_type-fid-$fup-typeid-$newtypeid", $arr);
					
					// 升级版块下的主题分类
					if(isset($policy['threadtypefid'][$fup]) && $policy['threadtypefid'][$fup] == $fid && $old2['threadtypes']) {
						$mthread_type_cate->create(array('fid'=>$fup, 'cateid'=>2, 'catename'=>'原分类', 'rank'=>2, 'enable'=>1));
						$threadtypes = dx2_unserialize($old2['threadtypes'], $_config['db']['1']['dbcharset']);
						if(!empty($threadtypes)) {
							$threadtype = $threadtypes['types'];
							$newtypeid = 0;
							foreach($threadtype as $typeid=>$typename) {
								$newtypeid++;
								$typeid2 = $mthread_type->map[2][$newtypeid];
								$arr = array(
									'fid'=>$fup,
									'typeid'=>$typeid2,
									'oldtypeid'=>$typeid,
									'oldfid'=>0,
									'typename'=>str_replace(array("\r", "\n"), array('', ''), strip_tags($typename)),
									'rank'=>0,
									'enable'=>1,
								);
								$db->set("thread_type-fid-$fup-typeid-$typeid2", $arr);
							}
						}
					}
					
				// 判断大区是否保留，如果不保留大区：正常升级成版块	
				} else {
					
					// 主题分类
					if($old2['threadtypes']) {
						
						$mthread_type_cate->create(array('fid'=>$fid, 'cateid'=>1, 'catename'=>'分类', 'rank'=>1, 'enable'=>1));
						
						$threadtypes = dx2_unserialize($old2['threadtypes'], $_config['db']['1']['dbcharset']);
						if(!empty($threadtypes)) {
							$threadtype = $threadtypes['types'];
							$newtypeid = 0;
							foreach($threadtype as $typeid=>$typename) {
								$newtypeid++;
								$arr = array(
									'fid'=>$fid,
									'typeid'=>$newtypeid,
									'oldtypeid'=>$typeid,
									'oldfid'=>0,
									'typename'=>str_replace(array("\r", "\n"), array('', ''), strip_tags($typename)),
									'rank'=>0,
									'enable'=>1,
								);
								$db->set("thread_type-fid-$fid-typeid-$typeid", $arr);
							}
						}
					}
					
					//5	subjectxxx	1343525778	star
					if($old['lastpost']) {
						$last = explode("\t", $old['lastpost']);
						$last[0] = intval($last[0]);
						$last[2] = intval($last[2]);
						$last[3] = str_replace('-', '', $last[3]);
						$lastuser = $uc->get("members-username-$last[3]");
						$lastuid = $lastuser['uid'];
					} else {
						$last = array(0, '', 0, '');
						$lastuid = 0;
					}
					
					$arr = array (
						'fid'=> $old['fid'],
						'fup'=> $old['fup'],
						'name'=> strip_tags($old['name']),
						'rank'=> $old['displayorder'],
						'threads'=> $old['threads'], // 最后要重新统计。
						'posts'=> $old['posts'], // 最后要重新统计。
						'todayposts'=> $old['todayposts'],
						'lasttid'=> $last[0],
						'brief'=> strip_tags($old2['description']),
						'accesson'=> 0,
						'modids'=> '',
						'modnames'=> '',
						'toptids'=> '',
						'orderby'=> 0,
						'seo_title'=> $old2['seotitle'],
						'seo_keywords'=> $old2['keywords'],
					);
					
					$db->set("forum-fid-$fid", $arr);
					
					// todo: 如果为隐藏版块，则对 forum_access 增加记录
					if($old['status'] != 1) {
						foreach($groupids as $groupid) {
							$access = array();
							$access['allowread'] = ($groupid == 1 ? 1 : 0);
							$access['allowpost'] = 0;
							$access['allowthread'] = 0;
							$access['allowdown'] = 0;
							$access['allowattach'] = 0;
							$access['allowdown'] = 0;
							$access['fid'] = $fid;
							$access['groupid'] = $groupid;
							$mforum_access->create($access);
						}				
					}
				}
			}
		}
		
		$start += $limit;
		message("正在升级 forum, 一共: $count, 当前: $start...", "?step=upgrade_forum&start=$start", 0);
	} else {
		
		// fid, typeid -> newfid, typeid1, typeid2
		message('升级 forum 完成，接下来升级 thread ...', '?step=upgrade_thread');
	}
}

function upgrade_thread() {
	global $start, $conf;
	$dx2 = get_dx2();
	$db = get_db();
	$uc = get_uc();
	
	$policy = load_upgrade_policy();
	
	$maxtid = intval(core::gpc('maxtid'));
	!isset($_GET['maxtid']) && $maxtid = $dx2->index_maxid('forum_thread-tid');
	
	$forum_types = array();
	$thread_type_data = new thread_type_data($conf);
	$thread_type_count = new thread_type_count($conf);
	$mkv = new kv($conf);
	$mforum = new forum($conf);
	
	// 清空主题分类
	if($maxtid == 0) {
		$thread_type_data->truncate();
		$thread_type_count->truncate();
	}
	
	if($start < $maxtid) {
		$limit = DEBUG ? 10 : 1000;	// 每次升级 100
		$arrlist = $dx2->index_fetch_id('forum_thread', 'tid', array('tid'=>array('>'=>$start)), array('tid'=>1), 0, $limit);
		foreach($arrlist as $key) {
			list($table, $_, $tid) = explode('-', $key);
			$old = $dx2->get("forum_thread-tid-$tid");
			
			$start = $tid;
			
			if(empty($old)) continue;
			$fid = $old['fid'];
			//if($old['status'] == 0) continue;
			if($old['displayorder'] < 0) continue;
			if($old['displayorder'] == 2) $old['displayorder'] = 1;
			if(!isset($policy['fuparr'][$fid]) ) continue; // 版块不存在，则不升级
			$fup = $policy['fuparr'][$fid];			// 大区也不升级
			if($fup == 0) continue;
			if(isset($policy['fuparr'][$fup]) && $policy['fuparr'][$fup] != 0) continue; // type = sub
			//if($old['replies'] > 30000) continue;
			
			$lastuid = 0;
			$lastuser = '';
			
			$newfid = get_fid_by_policy($fid, $policy);
			
			// 主题分类
			$typeid1 = $typeid2 = 0;
			if($fid != $newfid) {
				$threadtypelist = $db->index_fetch('thread_type', 'typeid', array('oldfid'=>$old['fid']), array(), 0, 1);
				if($threadtypelist) {
					$type = array_pop($threadtypelist);
					$typeid1 = $type['typeid'];
				}
			}
			if(!empty($old['typeid'])) {
				if(empty($forum_types[$fid])) {
					$forum_types[$fid] = $db->get('thread_type-oldtypeid-'.$old['typeid']);
				}
				$type = $forum_types[$fid];
				$typeid2 = $type['typeid'];
			}
			if(!$typeid1 && $typeid2) {
				$typeid1 = $typeid2;
				$typeid2 = 0;
			}
			
			$thread_type_data->xcreate($newfid, $tid, $typeid1, $typeid2, 0, 0);
			
			// firstpid
			$pidkeylist = $dx2->index_fetch_id('forum_post', 'pid', array('tid'=>$old['tid'], 'first'=>1), array(), 0, 1);
			if(empty($pidkeylist)) continue;
			list($_, $_, $firstpid) = explode('-', $pidkeylist[0]);
			
			// 置顶主题
			if($old['displayorder'] > 0) {
				if($old['displayorder'] == 3) {
					$toptids = $mkv->get('toptids');
					if(substr_count($toptids, ' ', 0) < 10) {
						$toptids .= trim($toptids)." $newfid-$tid";
						$mkv->set('toptids', $toptids);
					}
				} elseif($old['displayorder'] == 2 || $old['displayorder'] == 1) {
					$forum = $mforum->read($newfid);
					if(substr_count($forum['toptids'], ' ', 0) < 8) {
						$forum['toptids'] = trim($forum['toptids'])." $newfid-$tid";
						$mforum->update($forum);
					} else {
						$old['displayorder'] = 0;
					}
				} else {
					$old['displayorder'] = 0;
				}
			}
			$old['digest'] = misc::mid($old['digest'], 0, 3);
			$old['displayorder'] = misc::mid($old['displayorder'], 0, 3);
			
			$arr = array (
				'fid'=> $newfid,
				'tid'=> $old['tid'],
				'username'=> $old['author'],
				'uid'=> $old['authorid'],
				'subject'=> $old['subject'],
				'dateline'=> $old['dateline'],
				'lastpost'=> $old['lastpost'],
				'lastuid'=> $lastuid,
				'lastusername'=> $old['lastposter'],
				'views'=> $old['views'],
				'posts'=> ($old['replies'] + 1),
				'top'=> $old['displayorder'],
				'typeid1'=> $typeid1,
				'typeid2'=> $typeid2,
				'typeid3'=> 0,
				'typeid4'=> 0,
				'digest'=> $old['digest'],
				'attachnum'=> $old['attachment'],
				'imagenum'=> 0,
				'modnum'=> 0,
				'closed'=> $old['closed'],
				'firstpid'=> $firstpid,
			);
			$db->set("thread-fid-$newfid-tid-$tid", $arr);
			$db->set("thread_views-tid-$tid", array('tid'=>$tid, 'views'=>$old['views']));
			
			// 精华
			$db->set("thread_digest-tid-$tid", array('fid'=>$newfid, 'tid'=>$tid, 'digest'=>$old['digest']));
			
			// mypost
			$arr = array (
				'uid'=>$old['authorid'],
				'fid'=>$newfid,
				'tid'=>$old['tid'],
				'pid'=>$firstpid,
			);
			try {
				$db->set("mypost-uid-$thread[uid]-fid-$newfid-pid-$firstpid", $arr);
			} catch(Exception $e) {
				continue;
			}
		}
		
		message("正在升级 thread, maxtid: $maxtid, 当前: $start...", "?step=upgrade_thread&start=$start&maxtid=$maxtid", 0);
	} else {	
		message('升级 thread 完成，接下来升级 upgrade_attach...', '?step=upgrade_attach&start=0', 5);
	}
}

function upgrade_attach() {
	global $start, $conf;
	$dx2_attach_path = DX2_ATTACH_PATH;
	$dx2 = get_dx2();
	$db = get_db();
	
	$policy = load_upgrade_policy();
	
	$maxaid = intval(core::gpc('maxaid'));
	!isset($_GET['maxaid']) && $maxaid = $dx2->index_maxid('forum_attachment-aid');
	
	if($start < $maxaid) {
		$limit = DEBUG ? 20 : 500;
		$arrlist = $dx2->index_fetch_id('forum_attachment', 'aid', array('aid'=>array('>'=>$start)), array('aid'=>1), 0, $limit);
		foreach($arrlist as $key) {
			list($table, $keyname, $aid) = explode('-', $key);
			$attach = $dx2->get("forum_attachment-aid-$aid");
			$tableid = $attach['tableid'];
			
			$start = $aid;
			
			// fix: dx2 的附件存储到错误的表(127), bug
			try {
				$old = $dx2->get("forum_attachment_$tableid-aid-$aid");
				$old2 = $dx2->get("forum_attachment-aid-$aid");
                                $old['downloads'] = $old2['downloads'];
			} catch(Exception $e) {
				continue;
			}
			
			// 过滤掉关联错误的垃圾附件。
			$tid = $attach['tid'];
			$thread = $dx2->get("forum_thread-tid-$tid");
			if(empty($thread)) continue;
			$fid = $thread['fid'];
			if(!isset($policy['fuparr'][$fid]) ) continue;
			$fup = $policy['fuparr'][$fid];
			if($fup == 0) continue;
			if(isset($policy['fuparr'][$fup]) && $policy['fuparr'][$fup] != 0) continue; // type = sub
			
			$newfid = get_fid_by_policy($fid, $policy);
			if(empty($newfid)) continue;
			
			$oldattach = '';
			is_file($dx2_attach_path.'forum/'.$old['attachment']) && $oldattach = $dx2_attach_path.'forum/'.$old['attachment'];
			is_file(DX2_PATH.$old['attachment']) && $oldattach = DX2_PATH.$old['attachment'];
			if(empty($oldattach)) continue;
			
			$filetype = get_filetype($old['filename']);
			if($filetype == 'image') {
				list($width, $height, $type, $attr) = getimagesize($oldattach);
			} else {
				$height = 0;
			}
			
			// copy
			$ext = strrchr($old['filename'], '.');
			$pathadd = image::set_dir($aid, $conf['upload_path'].'attach/');
			$newfilename = $pathadd.'/'.$aid.$ext;
			$newfile = $conf['upload_path'].'attach/'.$newfilename;
			!is_file($newfile) && copy($oldattach, $newfile);
			$forum = $dx2->get("forum_thread-tid-$old[tid]");
			
			$arr = array (
				'fid'=> intval($newfid),
				'aid'=> intval($old['aid']),
				'pid'=> intval($old['pid']),
				'tid'=> intval($old['tid']),
				'uid'=> intval($old['uid']),
				'filesize'=> intval($old['filesize']),
				'width'=> intval($old['width']),
				'height'=> intval($height),
				'filename'=> $newfilename,
				'orgfilename'=> $old['filename'],
				'filetype'=> $filetype,
				'dateline'=> intval($old['dateline']),
				'comment'=> $old['description'],
				'downloads'=> $old['downloads'],
				'isimage'=> 0,
				'golds'=> 0,
			);
			$db->set("attach-fid-$newfid-aid-$aid", $arr);
			
		}
		
		message("正在升级 attach, maxaid: $maxaid, 当前: $start...", "?step=upgrade_attach&start=$start&maxaid=$maxaid", 0);
	} else {	
		message('升级 attach 完成，接下来升级 post ...', '?step=upgrade_post&start=0', 5);
	}
}

function upgrade_post() {
	global $start, $conf;
	
	$dx2 = get_dx2();
	$db = get_db();
	
	$policy = load_upgrade_policy();
	
	$maxpid = intval(core::gpc('maxpid'));
	!isset($_GET['maxpid']) && $maxpid = $dx2->index_maxid('forum_post-pid');
	
	if($start < $maxpid) {
		$limit = DEBUG ? 20 : 500;	// 每次升级 100
		$arrlist = $dx2->index_fetch_id('forum_post', 'pid', array('pid'=>array('>'=>$start)), array('pid'=>1), 0, $limit);
		foreach($arrlist as $key) {
			list($table, $_, $pid) = explode('-', $key);
			$old = $dx2->get("forum_post-pid-$pid");
			
			$pid > $start && $start = $pid;
			
			// 过滤掉关联错误的 post
			if(empty($old)) continue;
			$fid = $old['fid'];
			if(!isset($policy['fuparr'][$fid]) ) continue;
			$fup = $policy['fuparr'][$fid];
			if($fup == 0) continue;
			$newfid = get_fid_by_policy($fid, $policy);
			if(empty($newfid)) continue;
			$post = $db->get("post-fid-$newfid-pid-$pid");
			if($post) continue;
			if(isset($policy['fuparr'][$fup]) && $policy['fuparr'][$fup] != 0) continue; // type = sub
			
			// 帖子附件
			if($old['attachment']) {
				$attachlist = $db->index_fetch('attach', 'aid', array('fid'=>$newfid, 'pid'=>$pid), array('aid'=>1), array(), 0, 1000);
				if($attachlist) {
					foreach($attachlist as $attach) {
						$attachinsert = '[attach]'.$attach['aid'].'[/attach]';
						if(strpos($old['message'], $attachinsert) !== FALSE) {
							$old['message'] = str_replace($attachinsert, get_attach_html($attach), $old['message']);
						} else {
							$old['message'] .= get_attach_html($attach);
						}
					}
				}
				// 如果没有 aid 不在 message 中，则直接粘贴到内容末尾
			}
			$old['message'] = bbcode2html($old['message']);
			
			//$s = preg_replace('#\[attach\]([^[]*?)\[/attach\]#i', '', $s);
			
			$arr = array (
				'fid'=> intval($newfid),
				'pid'=> intval($old['pid']),
				'tid'=> intval($old['tid']),
				'uid'=> intval($old['authorid']),
				'dateline'=> intval($old['dateline']),
				'userip'=> 0,
				'attachnum'=> intval($old['attachment']),
				'imagenum'=> 0,
				'page'=> 1,
				'username'=> $old['author'],
				'subject'=> $old['subject'],
				'message'=> $old['message'],
			);
			
			$db->set("post-fid-$newfid-pid-$pid", $arr);
			
		}
		
		message("正在升级 post, maxpid: $maxpid, 当前: $start...", "?step=upgrade_post&start=$start&maxpid=$maxpid", 0);
		
	} else {	
		message('升级 post，接下来升级 user...', '?step=upgrade_user&start=0', 5);
	}
}

// 升级头像，一次1000，跳转升级。一百万用户需要跳转1000次。一次大概5秒。5000秒。大概2小时。
function upgrade_user() {
	global $conf, $start;
	$uc_avatar_path = DX2_AVATAR_PATH;
	$conf = include BBS_PATH.'conf/conf.php';
	
	if(!is_dir($uc_avatar_path)) {
		message('头像目录不存在，请将头像目录移动到：'.$uc_avatar_path.' 后，然后刷新本页。');
	}
	
	$uc = get_uc();
	$db = get_db();
	$dx2 = get_dx2();
	
	$start_time = microtime(1);
	
	$maxuid = intval(core::gpc('maxuid'));
	empty($maxuid) && $maxuid = $uc->index_maxid('members-uid');
	
	if($start < $maxuid) {
		$limit = DEBUG ? 20 : 500;	// 每次升级 100
		$arrlist = $uc->index_fetch_id('members', 'uid', array('uid'=>array('>'=>$start)), array('uid'=>1), 0, $limit);
		
		foreach($arrlist as $key) {
			list($table, $col, $uid) = explode('-', $key);
			
			$start = $uid;
			
			// todo: only bt, 不升级没发帖的用户，如果用户数大于3000000
			$old3 = $dx2->get("common_member_count-uid-$uid");
			if($maxuid > 3000000 && $old3['posts'] == 0 && $old3['threads'] == 0) continue;
			
			$user = $db->get("user-uid-$uid");
                        if($user) continue;
                        
			$old1 = $uc->get("members-uid-$uid");
			$old2 = $dx2->get("common_member-uid-$uid");
			$old4 = $dx2->get("common_member_status-uid-$uid");
			$old5 = $dx2->get("common_member_profile-uid-$uid");
			
			if(empty($old2)) {
				$old2 = array('avatarstatus'=>0, 'groupid'=>0, 'adminid'=>0);
			}
			$oldavatarfile = $uc_avatar_path.get_avatar($uid, 'big');
			if($old2['avatarstatus'] && is_file($oldavatarfile) && filesize($oldavatarfile) < 200000) {
				
				$hugepath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_huge.gif';
				$bigpath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_big.gif';
				$middlepath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_middle.gif';
				$smallpath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_small.gif';
				!is_file($hugepath) && image::thumb($oldavatarfile, $hugepath, $conf['avatar_width_huge'], $conf['avatar_width_huge']);
				!is_file($bigpath) && image::thumb($oldavatarfile, $bigpath, $conf['avatar_width_big'], $conf['avatar_width_big']);
				!is_file($middlepath) && image::thumb($oldavatarfile, $middlepath, $conf['avatar_width_middle'], $conf['avatar_width_middle']);
				!is_file($smallpath) && image::thumb($oldavatarfile, $smallpath, $conf['avatar_width_small'], $conf['avatar_width_small']);
			} else {
				$old2['avatarstatus'] = 0;
			}
			
			if($old3['posts'] > 0) {
				$myposts = $db->fetch_first("SELECT COUNT(*) AS num FROM {$db->tablepre}mypost WHERE uid='$uid'");
				$myposts = !empty($myposts) ? intval($myposts['num']) : 0;
			} else {
				$myposts = 0;
			}
			
			// todo:only bt
			$credits = $old3['extcredits2'] * 1 + $old3['extcredits3'] * 4 + $old3['extcredits4'] * 40 + $old3['extcredits5'] * 2;
			//$credits = $old3['threads'] * 2 + $old3['posts'];
			
			// email 为空
			if(empty($old1['email'])) {
				$old1['email'] = $old1['uid'].'@'.$_SERVER['HTTP_HOST'];
			}
			
			// utf8_general_ci
			if(strpos($old1['username'], 'ü') !== FALSE || strpos($old1['username'], 'u') !== FALSE) {
				$srchlist = $db->index_fetch('user', 'uid', array('username'=>$old1['username']), array(), 0, 1);
				if(count($srchlist) > 0) {
					$srchuser = array_pop($srchlist);
					// 对当前用户名改名，改为 uid
					$newname = $old1['uid'].rand(1000, 9999);
					$old1['username'] = $newname;
					log::write("rename $old1[username] to $newname");
				}
			}
			
			$arr = array (
				'uid'=> intval($uid),
				'regip'=> ip2long($old4['regip']),
				'regdate'=> intval($old1['regdate']),
				'username'=> $old1['username'],
				'password'=> $old1['password'],
				'salt'=> $old1['salt'],
				'email'=> $old1['email'],
				'groupid'=> get_groupid($credits, $old2['groupid'], $old2['adminid']),
				'threads'=> intval($old3['threads']),
				'posts'=> intval($old3['posts']),
				'myposts'=> $myposts,	// todo: 后面统计
				'avatar'=> intval($old2['avatarstatus']),
				'credits'=> intval($credits),
				'golds'=> $old3['extcredits1'],
				'follows'=> 0,
				'followeds'=> 0,
				'newpms'=> 0,
				'newfeeds'=> 0,
				'homepage'=> '',
				'accesson'=> 0,
				'onlinetime'=> 0,
				'lastactive'=> $old4['lastactivity'],
			);
			$db->set("user-uid-$uid", $arr);
			
			
			$arr = array(
				'gender'=>$old5['gender'],
				'birthyear'=>$old5['birthyear'],
				'birthmonth'=>$old5['birthmonth'],
				'birthday'=>$old5['birthday'],
				'province'=>$old5['resideprovince'],
				'city'=>$old5['residecity'],
				'county'=>$old5['residedist'],
			);
			$db->set("user_ext-uid-$uid", $arr);
		
		}
		
		message("正在升级 user, maxuid: $maxuid, 当前: $start... ", "?step=upgrade_user&start=$start&maxuid=$maxuid", 0);
		
	} else {
		// 生成系统用户，系统用户名：系统，如果发现重名，则改名。
		//INSERT INTO bbs_user SET uid='2', regip='12345554', regdate=UNIX_TIMESTAMP(), username='系统', password='d14be7f4d15d16de92b7e34e18d0d0f7', salt='99adde', email='system@admin.com', groupid='11', golds='0';
		$maxuid = $db->index_maxid('user-uid') + 1;
		$db->maxid('user-uid', $maxuid);
		$admin = $db->get("user-uid-1");
		$arr = array (
			'uid'=> $maxuid,
			'regip'=> 0,
			'regdate'=> $_SERVER['time'],
			'username'=> '系统',
			'password'=> $admin['password'],
			'salt'=> $admin['salt'],
			'email'=> 'system@admin.com',
			'groupid'=> 11,
			'threads'=> 0,
			'posts'=> 0,
			'myposts'=> 0,	// todo: 后面统计
			'avatar'=> 0,
			'credits'=> 0,
			'golds'=> 0,
			'follows'=> 0,
			'followeds'=> 0,
			'newpms'=> 0,
			'newfeeds'=> 0,
			'homepage'=> '',
			'accesson'=> 0,
			'lastactive'=> 0,
		);
		$db->set("user-uid-$maxuid", $arr);
		
		// 写入配置
		$mkv = new kv($conf);
		$mkv->xset('system_uid', $maxuid);
		$mkv->xset('system_username', '系统');
		$mkv->xsave();
		
		message('升级 user 完成，接下来升级 pm ...', '?step=upgrade_pm&start=0', 5);
	}
}

function upgrade_pm() {
	message('升级 pm 完成，接下来升级 ...', '?step=upgrade_friendlink&start=0');
}

function upgrade_friendlink() {
	global $conf;
	$dx2 = get_dx2();
	$db = get_db();
	$arrlist = $dx2->index_fetch('common_friendlink', 'id', array(), array(), 0, 1000);
	foreach($arrlist as $old) {
		$arr = array (
			'linkid'=> intval($old['id']),
			'type'=> $old['logo'] ? 1 : 0,
			'rank'=> intval($old['displayorder']),
			'sitename'=> $old['name'],
			'url'=> $old['url'],
			'logo'=> $old['logo'],
		);
		$db->set("friendlink-linkid-$old[id]", $arr);
	}
		
	message('升级 friendlink 完成，接下来升级 mod...', '?step=upgrade_mod&start=0');
}

function upgrade_mod() {
	message('升级 mod 完成，接下来升级 stat...', '?step=upgrade_stat&start=0');
}

function upgrade_stat() {
	message('升级 stat 完成，接下来升级 postpage...', '?step=upgrade_postpage&start=0');
}

function upgrade_postpage() {
	global $conf;
	
	include DX2_CONF_FILE;
	$dx2_tablepre = $_config['db'][1]['tablepre'];

	$dx2 = get_dx2();
	$db = get_db();
	
	$policy = load_upgrade_policy();
	
	$starttid = intval(core::gpc('starttid'));
	$startpid = intval(core::gpc('startpid'));
	$floor = intval(core::gpc('floor'));
	$limit = DEBUG ? 10 : 500;	// 每次升级 100
	$limit2 = DEBUG ? 20 : 500;
	$floorlimit = DEBUG ? 20 : 500; // 每次升级的楼层数， 
	$tidkeys = $dx2->index_fetch_id('forum_thread', array('tid'), array('tid'=>array('>'=>$starttid)), array('tid'=>1), 0, $limit);
	// 结束循环
	if(empty($tidkeys)) {
		message('升级 upgrade_postpage 完成，接下来升级 upgrade_postpid ...', '?step=upgrade_postpid&start=0');
	}
	foreach($tidkeys as $key) {
		list($table, $_, $tid) = explode('-', $key);
		$thread = $dx2->get("forum_thread-tid-$tid");
		$fid = $thread['fid'];
		
		// 过滤掉关联错误的 post
		if(empty($thread)) {
			$floor = 0;
			$startpid = 0;
			$starttid = $thread['tid'];
			continue;
		}
		if(!isset($policy['fuparr'][$fid]) ) {
			$floor = 0;
			$startpid = 0;
			$starttid = $thread['tid'];
			continue;
		}
		$fup = $policy['fuparr'][$fid];
		if($fup == 0) {
			$floor = 0;
			$startpid = 0;
			$starttid = $thread['tid'];
			continue;
		}
		$newfid = get_fid_by_policy($fid, $policy);
		if(empty($newfid)) {
			$floor = 0;
			$startpid = 0;
			$starttid = $thread['tid'];
			continue;
		}
		if(isset($policy['fuparr'][$fup]) && $policy['fuparr'][$fup] != 0) {
			$floor = 0;
			$startpid = 0;
			$starttid = $thread['tid'];
			continue;
		}
		
		//$count2 = $dx2->index_count('forum_post', array('tid'=>1));
		
		// 保证一次能取出 $limit2 个 post，$limit2 次用完，则进入下一轮跳转循环。
		$pidkeys = $dx2->index_fetch_id('forum_post', array('pid'), array('tid'=>$tid, 'pid'=>array('>'=>$startpid)), array('dateline'=>1), 0, $limit2);
		$n = count($pidkeys);
		
		// 进入下一轮 tid 循环
		if($n == 0) {
			$floor = 0;
			$startpid = 0;
			$starttid = $thread['tid'];
			continue;
		// 进入下一轮 pid 循环
		} else {
			// 写入 post 表
			$i = 0;
			$pid = 0;
			foreach($pidkeys as $key2) {
				$i++;
				list($table, $_, $pid) = explode('-', $key2);
				$post = $db->get("post-fid-$newfid-pid-$pid");
				/* 提高一点点速度
				$post = $db->fetch_first("SELECT fid,pid,page FROM bbs_post WHERE fid='$newfid' AND pid='$pid'");
				if(empty($post)) continue;
				*/
				$page = max(1, ceil(($floor + $i) / 20));
				$post['page'] = $page;
				if($conf['db']['type'] == 'mysql') {
					// 提高写入速度
					$db->query("UPDATE {$db->tablepre}post SET page='$page' WHERE fid='$newfid' AND pid='$pid'");
				} else {
					$db->set("post-fid-$newfid-pid-$pid", $post);
				}
			}
			
			$floor += $n;
			$startpid = $pid;	   // 不停的增加 startpid
			$floorlimit -= $n; // 多轮循环后 $floorlimit 将会 <= 0
			
			// $floorlimit 用完，中断 tid 大循环，进入下一轮跳转。
			if($floorlimit <= 0) {
				break;
			// 如果 $floortime 没用完，但是已经取完了。
			} else {
				// 进入下一轮 tid 循环，并且将 floor, startpid 重设
				if($n < $limit2) {
					$floor = 0;
					$startpid = 0;
					$starttid = $thread['tid'];
					continue;
				}
			}
		}
	}

	message("正在升级 post.page, 进度 starttid: $starttid, startpid: $startpid...", "?step=upgrade_postpage&startpid=$startpid&starttid=$starttid&floor=$floor", 0);
}

/*
	mysql_insert_id() 返回的为非最大值，这里需要修正。
*/
function upgrade_postpid() {
	global $start, $conf;
	$dx2 = get_dx2();
	$db = get_db();
	$uc = get_uc();
	
	$policy = load_upgrade_policy();
	
	$maxtid = intval(core::gpc('maxtid'));
	!isset($_GET['maxtid']) && $maxtid = $dx2->index_maxid('forum_thread-tid');
	
	$forum_types = array();
	$thread_type_data = new thread_type_data($conf);
	$thread_type_count = new thread_type_count($conf);
	$mkv = new kv($conf);
	$mforum = new forum($conf);
	
	if($start < $maxtid) {
		$limit = DEBUG ? 10 : 50;	// 每次升级 100
		$arrlist = $dx2->index_fetch_id('forum_thread', 'tid', array('tid'=>array('>'=>$start)), array('tid'=>1), 0, $limit);
		foreach($arrlist as $key) {
			list($table, $_, $tid) = explode('-', $key);
			$old = $dx2->get("forum_thread-tid-$tid");
			
			$start = $tid;
			
			if(empty($old)) continue;
			$fid = $old['fid'];
			//if($old['status'] == 0) continue;
			if($old['displayorder'] < 0) continue;
			if($old['displayorder'] == 2) $old['displayorder'] = 1;
			if(!isset($policy['fuparr'][$fid]) ) continue; // 版块不存在，则不升级
			$fup = $policy['fuparr'][$fid];			// 大区也不升级
			if($fup == 0) continue;
			if(isset($policy['fuparr'][$fup]) && $policy['fuparr'][$fup] != 0) continue; // type = sub
			//if($old['replies'] > 30000) continue;
			
			
			
			$newfid = get_fid_by_policy($fid, $policy);
			// 翻页, 8w 主题，翻页4000次。大概16秒, 30秒超时，这种高楼主题只能有2个。
			$posts = $old['replies'];
			$totalpage = ceil($posts / 20);
			for($i=1; $i<=$totalpage; $i++) {
				$postlist = $db->index_fetch('post', array('fid', 'pid'), array('fid'=>$newfid, 'tid'=>$tid, 'page'=>$i), array(), 0, 20);
				// 按照 dateline 排序
				misc::arrlist_multisort($postlist, 'dateline', TRUE);
				// 获取 pid
				$pids = array_keys(misc::arrlist_key_values($postlist, 'pid', 'dateline'));
				sort($pids);
				
				$j = 0;
				foreach($postlist as $post) {
					$j++;
					$newpid = $pids[$k];
					$db->query("UPDATE {$db->tablepre}post SET pid='$newpid' WHERE fid='$post[fid]' AND pid='$post[pid]'");
				}
			}
		}
		
		message("正在升级 upgrade_postpid, maxtid: $maxtid, 当前: $start...", "?step=upgrade_postpid&start=$start&maxtid=$maxtid", 0);
	} else {	
		message('升级 thread 完成，接下来升级 upgrade_postpid...', '?step=upgrade_forum2&start=0', 5);
	}
}

// 第二次升级 forum
function upgrade_forum2() {
	global $conf;
	// 放到最后一步。
	$dx2 = get_dx2();
	$db = get_db();	

	$forumlist = $db->index_fetch('forum', 'fid', array(), array(), 0, 500);
	$mkv = new kv($conf);
	foreach($forumlist as $forum) {
		
		$fid = $forum['fid'];
		$modids = $modnames = '';
		$modlist = $dx2->index_fetch('forum_moderator', array('uid', 'fid'), array('fid'=>$fid, 'inherited'=>0), array(), 0, 12);
		$modlist = array_slice($modlist, 0, 6);
		foreach($modlist as $mod) {
			$user = $dx2->get("common_member-uid-$mod[uid]");
			$modids .= (empty($modids) ? '' : ' ').$mod['uid'];
			$modnames .= (empty($modnames) ? '' : ' ').$user['username'];
		}
		// 重新统计 threads, posts
		$threads = $db->index_count('thread', array('fid'=>$fid));
		$posts = $db->index_count('post', array('fid'=>$fid));
		
		$forum['threads'] = $threads;
		$forum['posts'] = $posts;
		$forum['modids'] = $modids;
		$forum['modnames'] = $modnames;
		$db->update("forum-fid-$fid", $forum);
		
		$mkv->delete("cache_forum_$fid");
	}
	message('更新 forum 完成，接下来升级 laststep ...', '?step=laststep&start=0');
}

function laststep() {
	global $conf;
	clear_tmp('');
	$db = get_db();
	
	$db = get_db();
	
	// copy  from install_mongodb		
	$maxs = array(
		'group'=>'groupid',
		'user'=>'uid',
		'user_access'=>'uid',
		'forum'=>'fid',
		'forum_access'=>'fid',
		'thread_type'=>'typeid',
		'thread'=>'tid',
		'post'=>'pid',
		'attach'=>'aid',
		'friendlink'=>'linkid',
		'pm'=>'pmid',
	);
	
	foreach($maxs as $table=>$maxcol) {
		$m = $db->index_maxid($table.'-'.$maxcol);
		$db->maxid("$table-$maxcol", $m);
		
		$n = $db->index_count($table);
		$db->count($table, $n);
	}
	
	$db->truncate('runtime');
	
	// todo: 清理 thread
	//$db->index_drop('oldtypeid');
	//$db->query("ALTER TABLE {$db->tablepre}thread_type DROP COLUMN oldtypeid;");
	
	// 修改管理员用户组
	message('升级完毕，请<b>删除 upgrade 目录</b>，防止重复升级！！！<a href="../">【进入论坛】</a>');
}

function int_to_string($arr) {
	$s = '';
	foreach($arr as $v) {
		$a = sprintf('%08x', $v);
		$b = '';
		// int 在内存中为逆序存放
		$b .= chr(base_convert(substr($a, 6, 2), 16, 10));
		$b .= chr(base_convert(substr($a, 4, 2), 16, 10));
		$b .= chr(base_convert(substr($a, 2, 2), 16, 10));
		$b .= chr(base_convert(substr($a, 0, 2), 16, 10));
		//echo $a;
		$s .= $b;
	}
	return $s;
}


// dx2 db instance
function get_dx2() {
	include DX2_CONF_FILE;
	$dx2 = new db_mysql(array(
		'master' => array (
			'host' => $_config['db'][1]['dbhost'],
			'user' => $_config['db'][1]['dbuser'],
			'password' => $_config['db'][1]['dbpw'],
			'name' => $_config['db'][1]['dbname'],
			'charset' => 'utf8',	// 要求取出 utf-8 数据 mysql 4.1 以后支持转码
			//'charset' => $_config['db'][1]['dbcharset'],
			'tablepre' => $_config['db'][1]['tablepre'],
			'engine'=>'MyISAM',
		),
		'slaves' => array ()
	));
	// 要求返回的数据为 utf8
	return $dx2;
}

// ucenter db instance
function get_uc() {
	include UCENTER_CONF_FILE;;
	$tablepre = explode('.', UC_DBTABLEPRE);
	$uc = new db_mysql(array(
		'master' => array (
			'host' => UC_DBHOST,
			'user' => UC_DBUSER,
			'password' => UC_DBPW,
			'name' => UC_DBNAME,
			'charset' => 'utf8',	// 要求取出 utf-8 数据 mysql 4.1 以后支持转码
			//'charset' => UC_DBCHARSET,
			'tablepre' => $tablepre[1],
			'engine'=>'MyISAM',
		),
		'slaves' => array ()
	));
	return $uc;
}

// xn2 db instance
function get_db() {
	$conf = include BBS_PATH.'conf/conf.php';
	$db = new db_mysql($conf['db'][$conf['db']['type']]);
	return $db;
}

// 获取升级的进度，保存 step 和 start 到 tmp
function loading_upgrade_process(&$step, &$start, &$start2) {
	$conf = include BBS_PATH.'conf/conf.php';
	$file = $conf['tmp_path'].'upgrade_process.txt';
	if(is_file($file)) {
		$s = file_get_contents($file);
		if($s) {
			$arr = explode(' ', $s);
			$step = $arr[0];
			$start = $arr[1];
			$start2 = $arr[2];
			return;
		}
	}
	$step = '';
	$start = 0;
	$start2 = 0;
	return;
}

function save_upgrade_process() {
	global $start, $start2, $step;
	$conf = include BBS_PATH.'conf/conf.php';
	$file = $conf['tmp_path'].'upgrade_process.txt';
	file_put_contents($file, "$step $start $start2");
}

function show_header() {
	global $conf;
	echo '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Discuz!X 2.0 转 Xiuno BBS 2.0.0 Release 程序 </title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="../view/common.css" />
	</head>
	<body>
	<div id="header" style="overflow: hidden;">
		<h3 style="color: #FFFFFF; line-height: 26px;margin-left: 16px;">Discuz! 2.0 转 Xiuno BBS 2.0.0 Release 程序</h3>
		<p style="color: #BBBBBB; margin-left: 16px;">本程序会记录上次升级的进度，如果需要重头转换，请删除进度记录文件'.$conf['upload_path'].'upgrade_process.txt'.'</p>
	</div>
	<div id="body" style="padding: 16px;">';
}

function show_footer() {
	echo '
		</div>
			<div id="footer"> Powered by Xiuno (c) 2010 </div>
			<div style="color: #888888;">'.(DEBUG ? nl2br(print_r($_SERVER['sqls'], 1)) : '').'</div>
			</body>
			</html>';
}

function message($s, $url = '', $timeout = 2) {
	DEBUG && $timeout = 1000;
	show_header();
	echo $url ? "<h2>$s</h2><p><a href=\"$url\">页面将在<b>$timeout</b>秒后自动跳转，点击这里手工跳转。</a></p>
		<script>
			setTimeout(function() {
				window.location=\"$url\";
				setInterval(function() {
					window.location=\"$url\";
				}, 30000);
			}, ".($timeout * 1000).");
		</script>
	" : "<h2>$s</h2>";
	show_footer();
	save_upgrade_process();
	exit;
}

function clear_tmp($pre) {
	global $conf;
	if(IN_SAE) {
		$kv = new SaeKV();
		$ret = $kv->pkrget($pre, 100);
		foreach($ret as $key=>$val) {
			$kv->delete($key);
		}
	} else {
		$dh = opendir($conf['tmp_path']);
		while(($file = readdir($dh)) !== false ) {
			if($file != "." && $file != ".." ) {
				if(substr($file, 0, strlen($pre)) == $pre) {
					unlink($conf['tmp_path']."$file");
				}
			}
		}
		closedir($dh);
	}
}

/*
function file_line_replace($configfile, $startline, $endline, $replacearr) {
	// 从16行-33行，正则替换
	$arr = file($configfile);
	$arr1 = array_slice($arr, 0, $startline - 1); // 此处: startline - 1 为长度
	$arr2 = array_slice($arr, $startline - 1, $endline - $startline + 1); // 此处: startline - 1 为偏移量
	$arr3 = array_slice($arr, $endline);
	
	$s = implode("", $arr2);
	foreach($replacearr as $k=>$v) { 
		$s = preg_replace('#\''.preg_quote($k).'\'\s*=\>\s*\'?.*?\'?,#ism', "'$k' => '$v',", $s);
	}
	$s = implode("", $arr1).$s.implode("", $arr3);
	file_put_contents($configfile, $s);
}
*/

// uc_server 头像存储规则:
function get_avatar($uid, $size = 'middle', $type = '') {
	$size = in_array($size, array('huge', 'big', 'middle', 'small')) ? $size : 'middle';
	$uid = abs(intval($uid));
	$uid = sprintf("%09d", $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}

function get_filetype($filename) {
	 	
	$filetypes = array (
		'av' => array('av', 'wmv', 'wav', 'wma', 'avi'),
		'real' => array('rm', 'rmvb'),
		'mp3' => array('mp3','mp4'),
		'binary' => array('dat'),
		'flash' => array('swf'),
		'html' => array('html', 'htm'),
		'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
		'office' => array('doc', 'xls', 'ppt'),
		'pdf' => array('pdf'),
		'rar' => array('rar'),
		'text' => array('txt'),
		'bt' => array('bt'),
		'zip' => array('tar','zip', 'gz'),
		'book' => array('chm'),
		'torrent' => array('torrent')
	);
	$ext = strtolower(substr(strrchr($filename, '.'), 1));
	foreach($filetypes as $type=>$arr) {
		if(in_array($ext, $arr)) {
			return $type;
		}
	}
	return 'unknow';
}


function get_attach_html($attach) {
	global $conf;
	if($attach['filetype'] == 'image') {
		return "<li><img src=\"$conf[static_url]upload/attach/$attach[filename]\" width=\"$attach[width]\" height=\"$attach[height]\"/></li>";
	} else {
		$fileicon = "<img src=\"$conf[static_url]view/image/filetype/$attach[filetype].gif\" width=\"16\" height=\"16\" />";
		return "<li><a href=\"$conf[static_url]upload/attach/$attach[filename]\" target=\"_blank\">$fileicon $attach[orgfilename]</a></li>";
	}
}


/*
[hide]...[/hide]
[attach]..[/attach]
分页 checked
catename color stripvtags
{:smile:}{:smile:}
*/

function bbcode2html($s, $parseurl=1) {
	$s = str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $s);
	$s = nl2br($s);
	$s = preg_replace('#(<br\s*/?>\s*){3,999}#', '<br /><br />', $s);
	
	$s = str_replace(array(
		'[b]', '[/b]','[i]', '[i=s]', '[/i]', '[u]', '[/u]', '[/color]', '[/size]', '[/font]', 
		'[p]', '[/p]', '[/align]', '[/list]', '[/td]', '[/tr]', '[/table]', '[td]', '[tr]', '[table]', 
		'[hr]', '[quote]', '[/quote]', '[hide]', '[/hide]', '[/backcolor]'), array(
		'<b>', '</b>', '<i>', '<i>', '</i>', '<u>', '</u>', '</font>', '</font>', '</font>', 
		'<p>', '</p>', '</div>', '</ul>', '</td>', '</tr>', '</table>', '<td>', '<tr>', '<table>', 
		'<hr />', '<div class="quote">', '</div>', '', '', '</span>'), $s);
	$s = preg_replace('#\[em:([0-9]+):\]#i', '', $s);
	$s = preg_replace('#\[quote\]([^[]*?)\[/quote\]#i', '<div class="bg2 border shadow">\\1</div>', $s);
	$s = preg_replace('#\[color=([^]]+)\]#i', '<font color="\\1">', $s);
	$s = preg_replace('#\[backcolor=([^]]+)\]#i', '<span style="background:\\1">', $s);
	$s = preg_replace('#\[size=(\w+)\]#i', '<font size="\\1">', $s);
	$s = preg_replace('#\[font=([^]]+)\]#i', '<font="\\1">', $s);
	$s = preg_replace('#\[align=([^]]+)\]#i', '<div align="\\1">', $s);
	$s = preg_replace('#\[table=([^]]+)\]#i', '<table width="\\1">', $s);
	$s = preg_replace('#\[td=([^]]+)\]#i', '<td width="\\1">', $s);
	$s = preg_replace('#\[tr=([^[]+)\]#i', '<tr>', $s);
	$s = preg_replace('#\[p=([^]]+)\]#i', '<p>', $s);
	$s = preg_replace('#\[list=([^]]+)\]#i', '<ul>', $s);
	$s = preg_replace('#\{:[^}]+:\}#i', '', $s);
	$s = preg_replace('#\[\*\](.*?)\r\n#i', '<li>\\1</li>', $s);
	$s = preg_replace('#\[url\](.*?)\[\/url\]#i', "<a href=\"\\1\" target=\"_blank\">\\1</a>", $s);
	$s = preg_replace('#\[url=([^]]+?)\](.*?)\[\/url\]#i', "<a href=\"\\1\" target=\"_blank\">\\2</a>", $s);
	$s = preg_replace('#\[backcolor=([^]]+?)\]([^[]*?)\[\/backcolor\]#i', "<div style=\"background: \\1\">\\2</div>", $s);
	$s = preg_replace('#\[indent\]([^[]*?)\[\/indent\]#i', "<ul>\\1</ul>", $s);
	$s = preg_replace('#\[img\]([^[]*?)\[/img\]#i', '<img src="\\1" />', $s);
	$s = preg_replace('#\[img=(\d+),(\d+)\]([^[]*?)\[/img\]#i', '<img src="\\3" width="\\1" height="\\2" />', $s);
	
	$s = preg_replace('#\[attach\]([^[]*?)\[/attach\]#i', '', $s);
	
	$s = preg_replace('#\[media=\w+,(\d+),(\d+)\]([^[]*?)\[/media\]#i', '[media=\\1,\\2]\\3[/media]', $s);
	
	$s = preg_replace('#\[flash]([^[]*?)\[/flash\]#i', '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase=" http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="400" height="300">
			<param name="wmode" value="transparent" />
			<param name="quality" value="high" />
			<param name="menu" value="false" />
			<param name="loop" value="false" />
			<param name="AutoStart " value="true" />
			<param name="src" value="\\1" />
			<embed src="\\1" quality="high" AutoStart="true" loop="false" width="400" height="300" name="firefoxhead" allowFullScreen="yes" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" swLiveConnect="true" />
		</object>', $s);
	
	$s = preg_replace('#\[(media|swf|flash)=(\d+),(\d+)\]([^[]*?)\[/\\1\]#i', '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase=" http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="\\2" height="\\3">
		<param name="wmode" value="transparent" />
		<param name="quality" value="high" />
		<param name="menu" value="false" />
		<param name="loop" value="false" />
		<param name="AutoStart " value="true" />
		<param name="src" value="\\4" />
		<embed src="\\4" quality="high" AutoStart="true" loop="false" width="\\2" height="\\3" name="firefoxhead" allowFullScreen="yes" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" swLiveConnect="true" />
	</object>', $s);
	
	
	
	return $s;
}

function get_groupid($credits, $groupid, $adminid) {
	if($adminid == 1) return 1;
	if($adminid == 2) return 2;
	if($adminid == 3) return 3;
	$grouplist = array(11=>array(0, 50), 12=>array(50, 200), 13=>array(200, 1000), 14=>array(1000, 10000), 15=>array(10000, 10000000));
	foreach($grouplist as $groupid=>$group) {
		if($credits >= $group[0] && $credits < $group[1]) {
			return $groupid;
		}
	}
	return 11;
}

function dx2_unserialize($s, $dbcharset) {
	// fix dx2 bug
	if(strtolower($s) == 'array') {
		return array();
	}
	
	if(strtolower($dbcharset) == 'gbk') {
		$s = iconv('UTF-8', 'GBK', $s);
	}
	$arr = unserialize($s);
	if(strtolower($dbcharset) == 'gbk') {
		foreach($arr as &$v) {
			if(is_string($v)) $v = iconv('GBK', 'UTF-8', $v);
			if(is_array($v)) {
				foreach($v as &$v2) {
					if(is_string($v2)) $v2 = iconv('GBK', 'UTF-8', $v2);
						
				}
			}
		}
	}
	
	return $arr;
}

?>