<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'admin/control/admin_control.class.php';

class plugin_control extends admin_control {
	
	private $cates = array(0=>'未分类', 1=>'风格模板', 2=>'小型插件', 3=>'大型插件', 4=>'接口整合');
	private $styles = array(0=>'未分类', 1=>'红', 2=>'橙', 3=>'黄', 4=>'绿', 5=>'青', 6=>'蓝', 7=>'紫', 8=>'黑白', 9=>'古典', 10=>'现代', 11=>'商务', 12=>'科技', 13=>'中国风');
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->check_admin_group();
	}
	
	// 本地插件列表，如果有pluginid, 则从官方获取更新
	public function on_index() {
		
		$installlist = $disablelist = $unstalllist = array();
		
		// 获取本地插件列表，获取更新
		$pluginlist = core::get_plugins($this->conf);
		$dirs = array();
		foreach($pluginlist as $plugin) {
			$plugin['pluginid'] && $dirs[] = $plugin['dir'];
		}
		// 如果全部为第三方插件，这里则不会请求服务器
		$officiallist = $dirs ? $this->get_official_by_dirs($dirs) : array();
		foreach($pluginlist as $dir=>&$pconf) {
			// 用官方数据覆盖当前插件数据
			if(!empty($officiallist[$dir])) {
				$oconf = $officiallist[$dir];
				$pconf['official_version'] = $oconf['version'];
				$pconf['have_new_version'] = version_compare($oconf['version'], $pconf['version']);	// 如果有新版本，提示下载，更新
				$pconf['stars'] = intval($oconf['stars']);
				$pconf['name'] = $oconf['name'];
				$pconf['brief'] = $oconf['brief'];
			} else {
				$pconf['have_new_version'] = 0;
				$pconf['stars'] = 0;
			}
			$pconf['have_setting'] = is_file($this->conf['plugin_path'].$dir.'setting.php');
			$pconf['icon_url'] = is_file($this->conf['plugin_path'].$dir."icon.png") ? $this->conf['plugin_url'].$dir."icon.png" : "../view/image/plugin_icon.png";
			if($pconf['installed'] == 1) {
				if($pconf['enable'] == 1) {
					$installlist[$dir] = $pconf;	// 已启用的插件
				} else {
					$disablelist[$dir] = $pconf;	// 未启用的插件
				}
			} else {
				$unstalllist[$dir] = $pconf;		// 未安装的插件
			}
		}
		
		$this->view->assign('pluginlist', $pluginlist);
		$this->view->assign('installlist', $installlist);
		$this->view->assign('disablelist', $disablelist);
		$this->view->assign('unstalllist', $unstalllist);
		
		$this->view->display('plugin_index.htm');
	}
	
	// 线上插件列表，过滤条件：
	public function on_list() {
		$cateid = intval(core::gpc('cateid'));
		$styleid = intval(core::gpc('styleid'));
		$orderby = core::gpc('orderby');
		!in_array($orderby, array('price', 'installs', 'stars', 'user_stars')) && $orderby = 'stars';
		$page = misc::page();
		$pagesize = 20;
		
		// 从官方获取最新的版本
		$pluginlist = $this->get_official_list($cateid, $styleid, $orderby, $page, $pagesize);
		
		// 获取本地插件列表
		$locallist = core::get_plugins($this->conf);
		
		// 合并
		foreach($pluginlist as $dir=>&$pconf) {
			if(isset($locallist[$dir])) {
				$lconf = $locallist[$dir];
				empty($lconf['version']) && $lconf['version'] = 0;
				$pconf['have_setting'] = is_file($this->conf['plugin_path'].$dir.'setting.php');
				$pconf['local_version'] = $lconf['version'];
				$pconf['have_new_version'] = version_compare($lconf['version'], $pconf['version']);	// 如果有新版本，提示下载，更新
				$pconf += $lconf; // 追加, installed, enable
			} else {
				$pconf['have_setting'] = 0;
				$pconf['local_version'] = 0;
				$pconf['have_new_version'] = 0;
			}
		}
		
		$this->_checked['cateid_'.$cateid] = ' class="checked"';
		$this->_checked['styleid_'.$styleid] = ' class="checked"';
		$this->_checked['orderby_'.$orderby] = ' class="checked"';
		
		$pages = misc::simple_pages("?plugin-list-cateid-$cateid-styleid-$styleid-orderby-$orderby.htm", count($pluginlist), $page, $pagesize);
		
		$this->view->assign('pluginlist', $pluginlist);
		$this->view->assign('cateid', $cateid);
		$this->view->assign('styleid', $styleid);
		$this->view->assign('orderby', $orderby);
		$this->view->assign('pages', $pages);
		$this->view->assign('page', $page);
		$this->view->assign('cates', $this->cates);
		$this->view->assign('styles', $this->styles);
		
		$this->view->display('plugin_list.htm');
	}
	
	// 插件详情，本地，官方插件 
	public function on_read() {
		$dir = core::gpc('dir');
		$pluginid = intval(core::gpc('pluginid'));
		$local = $dir ? $this->get_local_plugin($dir) : array();
		$official = $pluginid ? $this->get_official_plugin($pluginid) : array();
		
		if(!$local && !$official) {
			$this->message('请指定插件 dir 或 pluginid。');
		}
		
		// 本地插件
		if($local) {
			$local['icon_url'] = is_file($this->conf['plugin_path']."$dir/icon.png") ? $this->conf['plugin_url']."$dir/icon.png" : "../view/image/plugin_icon.png";
			$local['img1_url'] = is_file($this->conf['plugin_path']."$dir/img1.jpg") ? $this->conf['plugin_url']."$dir/img1.jpg" : '../view/image/nopic.gif';
			$local['img2_url'] = is_file($this->conf['plugin_path']."$dir/img2.jpg") ? $this->conf['plugin_url']."$dir/img2.jpg" : '../view/image/nopic.gif';
			$local['img3_url'] = is_file($this->conf['plugin_path']."$dir/img3.jpg") ? $this->conf['plugin_url']."$dir/img3.jpg" : '../view/image/nopic.gif';
			$local['img4_url'] = is_file($this->conf['plugin_path']."$dir/img4.jpg") ? $this->conf['plugin_url']."$dir/img4.jpg" : '../view/image/nopic.gif';
			
			$local['have_setting'] = is_file($this->conf['plugin_path'].$dir.'setting.php');
		}
		if($official) {
			$official['icon_url'] = $official['icon'] ? "http://plugin.xiuno.net/upload/plugin/$pluginid/icon.png" : "http://plugin.xiuno.net/view/image/plugin_icon.png";
			$official['img1_url'] = $official['img1'] ? "http://plugin.xiuno.net/upload/plugin/$pluginid/img1.jpg" : '../view/image/nopic.gif';
			$official['img2_url'] = $official['img2'] ? "http://plugin.xiuno.net/upload/plugin/$pluginid/img2.jpg" : '../view/image/nopic.gif';
			$official['img3_url'] = $official['img3'] ? "http://plugin.xiuno.net/upload/plugin/$pluginid/img3.jpg" : '../view/image/nopic.gif';
			$official['img4_url'] = $official['img4'] ? "http://plugin.xiuno.net/upload/plugin/$pluginid/img4.jpg" : '../view/image/nopic.gif';
			
			$official['lastupdate_fmt'] = misc::humandate($official['lastupdate']);
		}
		
		$plugin = array_merge($local + $official);
		
		// 检查 MD5
		if($local && $official) {
			$plugin['cateid'] = $official['cateid'];
			$plugin['username'] = $official['username'];
			$plugin['email'] = $official['email'];
			
			$plugin['md5_ok'] = $this->dir_md5($this->conf['plugin_path'].$dir.'/') == $official['file_md5'];
			$plugin['have_new_version'] = version_compare($official['version'], $local['version']);	// 如果有新版本，提示下载，更新
		}
		
		$this->view->assign('dir', $dir);
		$this->view->assign('pluginid', $pluginid);
		$this->view->assign('plugin', $plugin);
		$this->view->assign('local', $local);
		$this->view->assign('official', $official);
		$this->view->display('plugin_read.htm');
	}
	
	// 安装，接受参数 dir
	public function on_install() {
		
		// 判断插件类型
		$dir = trim(core::gpc('dir'));
		!preg_match('#^\w+$#', $dir) && $this->message('dir 不合法。');

		// 如果本地目录不存在，则下载。
		if(!is_dir($this->conf['plugin_path'].$dir)) {
			$siteid =  md5($this->conf['app_url'].$this->conf['auth_key']);
			$app_url = core::urlencode($this->conf['app_url']);
			$url = "http://plugin.xiuno.net/?plugin-down-dir-$dir-siteid-$siteid-app_url-$app_url-ajax-1.htm";
			if(IN_SAE) {
				// 提示下载
				$next = "?plugin-install-dir-$dir.htm";
				$this->message("SAE 环境安装，需要手工<a href=\"$url\" target=\"_blank\"><b>【下载压缩包】</b></a>，然后解压后，上传到 <b>plugin/$dir</b> 目录，再进行<a href=\"$next\"><b>【下一步】</b></a>安装。");
			} else {
				// 服务端开始下载
				$s = misc::fetch_url($url, 20);
				if(empty($s) || substr($s, 0, 2) != 'PK') {
					$arr = core::json_decode($s);
					empty($arr['message']) && $arr['message'] = '';
					$this->message('服务端返回数据错误：'.$arr['message']);
				}
				$zipfile = $this->conf['tmp_path'].$dir.'.zip';
				$destpath = $this->conf['plugin_path']."$dir/";
				file_put_contents($zipfile, $s);
				xn_zip::unzip($zipfile, $destpath);
				unlink($zipfile);
			}
		}
		
		if(!is_dir($this->conf['plugin_path'].$dir)) {
			$this->message('插件可能安装失败，目录不存在:'.$this->conf['plugin_path'].$dir, 0);
		}
		
		$local = $this->get_local_plugin($dir);
		empty($local) && $this->message('插件不存在。', 0);
		
		$install = $this->conf['plugin_path'].$dir.'/install.php';
		if(is_file($install)) {
			try {
				include $install;
			} catch(Exception $e) {
				log::write("安装插件 $dir 可能发生错误:".$e->getMessage());
			}
		}
		
		// 设置 installed 标记, 民间插件可能不包含 pluginid
		empty($local['pluginid']) && $local['pluginid'] = 0;
		$this->set_setting($dir, array('enable'=>1, 'installed'=>1, 'pluginid'=>$local['pluginid']));
		
		// 如果为风格插件，则需要设置 view_path
		if(substr($dir, 0, 4) == 'view') {
			$viewpath = array($this->conf['plugin_path'].$dir, BBS_PATH.'view/');
			$this->kv->xset('view_path', $viewpath);
			$this->runtime->xset('view_path', $viewpath);
			
			// 卸载其他 view，只允许一个风格插件启用。
			$locallist = core::get_plugins($this->conf);
			foreach($locallist as $_dir=>$plugin) {
				if(substr($_dir, 0, 4) == 'view' && $_dir != $dir) {
					$this->set_setting($_dir, array('enable'=>0));
				}
			}
		}
		
		$referer = core::gpc('HTTP_REFERER', 'S') OR $referer = '?plugin-index.htm';
		if(IN_SAE) {
			$this->make_tmp($this->conf);
			$url = "?plugin-saetmp.htm";
			$this->message("SAE 环境安装，需要<a href=\"$url\" target=\"\"><b>【下载压缩包】</b></a>，解压后，将文件上传到 tmp 目录，完成安装。", 1, $referer);
		} else {
			$this->clear_tmp();
			$this->message('安装成功。', 1, $referer);
		}
	}
	
	// 升级，直接跳转到安装?
	public function on_upgrade() {
		$dir = core::gpc('dir');
		$this->is_right_dir($dir) && $this->message('dir 格式不对', 0);
		
		$siteid =  md5($this->conf['app_url'].$this->conf['priavte_key']);
		$app_url = core::urlencode($this->conf['app_url']);
		$url = "http://plugin.xiuno.net/?plugin-down-dir-$dir-siteid-$siteid-app_url-$app_url.htm";
		$referer = core::gpc('HTTP_REFERER', 'S') OR $referer = '?plugin-index.htm';
		if(IN_SAE) {
			$pluginpath = $this->conf['plugin_path'].$dir;
			$pluginzip = $this->conf['tmp_path']."$dir.zip";
			// $conf['upload_url'].'tmp.zip'
			$saetmp = '?plugin-saetmp.htm';
			$this->message('SAE 环境升级，此步需要手操作以下步骤:<ul>
				<li>FTP 删除线上的 plugin/'.$dir.'目录，</li>
				<li>下载插件压缩包<a href="'.$pluginzip.'" target="_blank"><b>【'.$pluginzip.'】</b></a></li>
				<li>解压本地的'.$pluginzip.'后，将得到的目录'.$dir.'上传到 plugin/ 下</li>
				<li>点击下载<a href="'.$saetmp.'" target="_blank"><b>【tmp.zip】</b></a></li>
				<li>解压 tmp.zip 得到 tmp 目录，FTP 上传到线上 tmp/ 目录下</li>
				</ul>', 1, $referer);
		} else {
			// 下载最新版本
			$s = misc::fetch_url($url, 20);
			empty($s) && $this->message('获取失败', 0);
			substr($s, 0, 2) != 'PK' && $this->message('获取插件包失败，提示信息：'.$s, 0);
			
			$pluginzip = $this->conf['tmp_path']."$dir.zip";
			file_put_contents($pluginzip, $s);
			misc::rmdir($this->conf['plugin_path'].$dir);
			xn_zip::unzip($pluginzip, $this->conf['plugin_path'].$dir);
			$this->clear_tmp();
			$this->message('升级完毕。', 1, $referer);
		}
	}
	
	// sae 下载 tmp 包。
	public function on_saetmp() {
		$filename = $this->conf['upload_path']."tmp.zip";
		if(!is_file($filename)) {
			$this->make_tmp($this->conf);
		}
		if(!is_file($filename)) {
			$this->message('生成 tmp 目录文件失败。', 0);
		}
		
		$timefmt = date('D, d M Y H:i:s', $_SERVER['time']).' GMT';
		header('Date: '.$timefmt);
                header('Last-Modified: '.$timefmt);
		header('Expires: '.$timefmt);
               // header('Cache-control: max-age=0, must-revalidate, post-check=0, pre-check=0');
                header('Cache-control: max-age=86400');
		header('Content-Transfer-Encoding: binary');
		header("Pragma: public");
		header('Content-Disposition: attachment; filename=tmp.zip');
		header('Content-Type: application/octet-stream');
		readfile($filename);
		unlink($filename);
		exit;
	}
	
	// 卸载
	public function on_unstall() {
		
		// 判断插件类型
		$dir = trim(core::gpc('dir'));
		$plugin = $this->get_local_plugin($dir);
		empty($plugin) && $this->message('该插件不存在。');
		
		$isview = substr($dir, 0, 4) == 'view';
		
		// 开始寻找 install，这里非常的危险！需要过滤一下，只允许字母数字下划线的目录名
		$unstall = $this->conf['plugin_path'].$dir.'/unstall.php';
		if(is_file($unstall)) {
			try {
				include $unstall;
			} catch(Exception $e) {
				log::write("卸载插件 $dir 可能发生错误:".$e->getMessage());
			}
		}
		
		// 设置 installed 标记
		$this->set_setting($dir, array('enable'=>0, 'installed'=>0));
		
		// 如果为风格插件，则需要设置 view_path
		$conffile = BBS_PATH.'conf/conf.php';
		if($isview) {
			$viewpath = array(BBS_PATH.'view/');
			$this->kv->xset('view_path', $viewpath);
			$this->runtime->xset('view_path', $viewpath);
		}
		
		// 清空 tmp 目录下的 bbs_* bbsadmin_*
		$referer = core::gpc('HTTP_REFERER', 'S') OR $referer = '?plugin-index.htm';
		if(IN_SAE) {
			$this->make_tmp($this->conf);
			$url = "?plugin-saetmp.htm";
			$this->message('SAE 环境需要手工执行以下步骤：<ul>
				<li>删除<b>'.$this->conf['plugin_path'].$dir.'</b></li>
				<li>下载<a href="'.$saetmp.'" target="_blank"><b>tmp.zip</b></a></li>
				<li>解压 tmp.zip 得到 tmp 目录，FTP 上传到线上 tmp/ 目录下</li>
				', 1, $referer);
		} else {
			//  删除目录
			misc::rmdir($this->conf['plugin_path'].$dir);
			$this->clear_tmp();
			if($isview) {
				$this->message('卸载该风格 $dir 成功，已经还原为系统默认风格。', 1, $referer);
			} else {
				$this->message("卸载插件 $dir 成功。", 1, $referer);
			}
		}
	}
	
	// 启用
	public function on_enable() {
		
		// 判断插件类型
		$dir = trim(core::gpc('dir'));
		!preg_match('#^\w+$#', $dir) && $this->message('dir 不合法。');
			
		$local = $this->get_local_plugin($dir);
		if(empty($local)) {
			$this->message('插件不存在。', 0);
		}
		
		// 设置 installed 标记, 民间插件可能不包含 pluginid
		$this->set_setting($dir, array('enable'=>1));
		
		// 如果为风格插件，则需要设置 view_path
		if(substr($dir, 0, 4) == 'view') {
			$viewpath = array($this->conf['plugin_path'].$dir, BBS_PATH.'view/');
			$this->kv->xset('view_path', $viewpath);
			$this->runtime->xset('view_path', $viewpath);
			
			// 卸载其他 view，只允许一个风格插件启用。
			$locallist = core::get_plugins($this->conf);
			foreach($locallist as $_dir=>$plugin) {
				if(substr($_dir, 0, 4) == 'view' && $_dir != $dir) {
					$this->set_setting($_dir, array('enable'=>0));
				}
			}
		}
		
		$referer = core::gpc('HTTP_REFERER', 'S') OR $referer = '?plugin-index.htm';
		if(IN_SAE) {
			$this->make_tmp($this->conf);
			$url = "?plugin-saetmp.htm";
			$this->message("请<a href=\"$url\" target=\"\"><b>下载压缩包</b></a>，解压后，将文件上传到 tmp 目录，完成启用。", 1, $referer);
		} else {
			$this->clear_tmp();
			$this->message('启用成功。', 1, $referer);
		}
	}
	
	// 禁用
	public function on_disable() {
		// 判断插件类型
		$dir = trim(core::gpc('dir'));
		$plugin = $this->get_local_plugin($dir);
		empty($plugin) && $this->message('该插件不存在。');
		
		$isview = substr($dir, 0, 4) == 'view';
		
		// 设置 installed 标记
		$this->set_setting($dir, array('enable'=>0));
		
		// 如果为风格插件，则需要设置 view_path
		$conffile = BBS_PATH.'conf/conf.php';
		if($isview) {
			$viewpath = array(BBS_PATH.'view/');
			$this->kv->xset('view_path', $viewpath);
			$this->runtime->xset('view_path', $viewpath);
		}
		
		// 清空 tmp 目录下的 bbs_* bbsadmin_*
		$referer = core::gpc('HTTP_REFERER', 'S') OR $referer = '?plugin-index.htm';
		if(IN_SAE) {
			$this->make_tmp($this->conf);
			$url = "?plugin-saetmp.htm";
			$this->message("请<a href=\"$url\" target=\"\"><b>下载压缩包</b></a>，解压后，将文件上传到 tmp 目录，完成禁用。", 1, $referer);
		} else {
			$this->clear_tmp();
			if($isview) {
				$this->message('禁用该风格成功，已经还原为系统默认风格。');
			} else {
				$this->message('禁用插件成功。', 1, $referer);
			}
		}
	}
	
	
	// 插件的设置，一般是修改配置文件。
	public function on_setting() {
		// 判断插件类型
		$dir = trim(core::gpc('dir'));
		$this->check_dir($dir);
		$is_view = $this->is_view($dir);
		$this->conf['view_path'][] = $this->conf['plugin_path'].$dir.'/';	// 增加 view 目录
		$this->view->assign('dir', $dir);
		
		// 开始寻找 install，这里非常的危险！需要过滤一下，只允许字母数字下划线的目录名
		$setting = $this->conf['plugin_path'].$dir.'/setting.php';
		if(is_file($setting)) {
			try {
				include $setting;
			} catch(Exception $e) {
				log::write("设置插件 $dir 可能发生错误:".$e->getMessage());
				echo $e->getMessage();
			}
		} else {
			echo " $setting 文件不存在。";
		}
	}
	
	// 是否为风格插件
	private function is_view($dir) {
		return substr($dir, 0, 4) == 'view';
	}
	
	private function check_writable($path) {
		if(!is_writable($path)) {
			$this->message("错误：$path 不可写！您可以通过FTP或者命令行设置 $path 为可写权限。");
		}
	}
	
	// 检查是否为合法的 dir
	private function check_dir($dir) {
		$r = preg_match('#^\w+$#', $dir) && is_dir($this->conf['plugin_path'].$dir);
		if(!$r) {
			$dir = htmlspecialchars($dir);
			$this->message("插件 $dir 不存在。");
		}
	}
	
	// 是否为合法的 dir
	private function is_right_dir($dir) {
		return preg_match('#^\w+$#', $dir);
	}
	
	private function set_setting($plugindir, $setting) {
		$settingfile = $this->conf['upload_path'].'plugin.json';
		!is_file($settingfile) && file_put_contents($settingfile, '');
		$arr = core::json_decode(file_get_contents($settingfile));
		$arr[$plugindir] = empty($arr[$plugindir]) ? $setting : array_merge($arr[$plugindir], $setting);
		file_put_contents($settingfile, core::json_encode($arr));
	}
	
	// 从官方获取最新的 plugin
	private function get_official_list($cateid, $styleid, $orderby, $page = 1, $pagesize = 20) {
		$pluginlist = array();
		$url = "http://plugin.xiuno.net/?plugin-list-cateid-$cateid-styleid-$styleid-orderby-$orderby-page-$page-pagesize-$pagesize-ajax-1.htm";
		$s = misc::fetch_url($url, 5);
		if(empty($s)) throw new Exception('从官方获取更新失败。');
		$pluginlist = (array)core::json_decode($s);
		return $pluginlist;
	}
	
	private function get_official_by_dirs($dirs) {
		$dirsurl = implode(',', (array)$dirs);
		$url = "http://plugin.xiuno.net/?plugin-update-dirs-$dirsurl-ajax-1.htm";
		$s = misc::fetch_url($url, 5);
		if(empty($s)) throw new Exception('从官方获取更新失败。');
		$pluginlist = core::json_decode($s);
		return $pluginlist;
	}
	
	private function get_official_plugin($pluginid) {
		$url = "http://plugin.xiuno.net/?plugin-read-pluginid-$$pluginid-ajax-1.htm";
		$s = misc::fetch_url($url, 5);
		if(empty($s)) throw new Exception("从官方获取插件 pluginid=$pluginid 失败。");
		$plugin = core::json_decode($s);
		return $plugin;
	}
	
	private function get_local_plugin($dir) {
		$locallist = core::get_plugins($this->conf, 1);
		return empty($locallist[$dir]) ? array() : $locallist[$dir];
	}
	
	// 生成 tmp 缓存，仅仅在SAE下需要。
	private function make_tmp($conf) {
		
		//$tmppath = IN_SAE ? FRAMEWORK_TMP_TMP_PATH.'tmp/' : FRAMEWORK_TMP_PATH;	// 这样比较保险，但是目前看来没有必要。
		$tmppath = FRAMEWORK_TMP_TMP_PATH;
		
		$runtimefile = $tmppath.'_runtime.php';
		if (!is_file($runtimefile)) {
			$content = '';
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'core/core.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'core/misc.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'core/base_control.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'core/base_model.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'lib/log.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'lib/xn_exception.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'lib/encrypt.func.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'lib/template.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'db/db.interface.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'db/db_mysql.class.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'cache/cache.interface.php');
			$content .= php_strip_whitespace(FRAMEWORK_PATH.'cache/cache_memcache.class.php');
			file_put_contents($runtimefile, $content);
			unset($content);
		}
		
		// 获取插件目录
		$pluginpaths = $conf['disable_plugin'] ? array() : core::get_paths($conf['plugin_path'], TRUE);
		
		// 遍历 control
		foreach(($conf['control_path'] + $pluginpaths) as $path) {
			
			// 如果有相关的 app path, 这只读取该目录
			if(is_dir($path.$conf['app_id'])) {
				$path = $path.$conf['app_id'];
			}
			foreach((array)glob($path."*_control.class.php") as $file) {
				if(!is_file($file)) continue;
				$filename = substr(strrchr($file, '/'), 1);
				$objfile = $tmppath.$conf['app_id']."_control_$filename";
				
				$s = file_get_contents($file);
				core::process_include($conf, $s);
				$s = preg_replace('#\t*\/\/\s*hook\s+([^\s]+)#ies', "core::process_hook(\$conf, '\\1')", $s);
				core::process_urlrewrite($conf, $s);
				file_put_contents($objfile, $s);
				unset($s);
			}
		}
		
		// 遍历 view，插入点的 .htm 编译是多余的，不过不碍事。
		$view = new template($conf);
		foreach(($conf['view_path'] + $pluginpaths) as $path) {
			// 如果有相关的 app path, 这只读取该目录
			if(is_dir($path.$conf['app_id'])) {
				$path = $path.$conf['app_id'];
			}
			foreach((array)glob($path."*.htm") as $file) {
				if(!is_file($file)) continue;
				$filename = substr(strrchr($file, '/'), 1);
				$objfile = $tmppath.$conf['app_id']."_view_$filename.php";
				$s = $view->complie($file);
				file_put_contents($objfile, $s);
			}
		}
		unset($view);
		
		// 遍历 model，公共
		foreach(($conf['model_path'] + $pluginpaths) as $path) {
			foreach((array)glob($path."*.class.php") as $file) {
				if(!is_file($file)) continue;
				$filename = substr(strrchr($file, '/'), 1);
				$objfile = $tmppath."model_$filename";
				$s = file_get_contents($file);
				$s = preg_replace('#\t*\/\/\s*hook\s+([^\s]+)#ies', "core::process_hook(\$conf, '\\1')", $s);
				core::process_urlrewrite($conf, $s);
				file_put_contents($objfile, $s);
				unset($s);
			}
		}
		
		// --------> bbsadmin start
		
		$conf2 = $conf;
		$adminconf = include BBS_PATH.'admin/conf/conf.php';
		$adminconf += $conf;
		$conf = $adminconf;
		
		// 遍历 bbsadmin control
		foreach(($conf['control_path'] + $pluginpaths) as $path) {
			
			// 如果有相关的 app path, 这只读取该目录
			if(is_dir($path.$conf['app_id'])) {
				$path = $path.$conf['app_id'];
			}
			foreach((array)glob($path."*_control.class.php") as $file) {
				if(!is_file($file)) continue;
				$filename = substr(strrchr($file, '/'), 1);
				$objfile = $tmppath.$conf['app_id']."_control_$filename";
				
				$s = file_get_contents($file);
				core::process_include($conf, $s);
				$s = preg_replace('#\t*\/\/\s*hook\s+([^\s]+)#ies', "core::process_hook(\$conf, '\\1')", $s);
				core::process_urlrewrite($conf, $s);
				file_put_contents($objfile, $s);
				unset($s);
			}
		}
		
		// 遍历 bbsadmin view
		$view = new template($conf);
		foreach(($conf['view_path'] + $pluginpaths) as $path) {
			// 如果有相关的 app path, 则只读取该目录
			if(is_dir($path.$conf['app_id'])) {
				$path = $path.$conf['app_id'];
			}
			foreach((array)glob($path."*.htm") as $file) {
				if(!is_file($file)) continue;
				$filename = substr(strrchr($file, '/'), 1);
				$objfile = $tmppath.$conf['app_id']."_view_$filename.php";
				$s = $view->complie($file);
				file_put_contents($objfile, $s);
			}
		}
		unset($view);
		
		$conf = $conf2;
		
		// --------> bbsadmin end
		
		// 打包
		if(IN_SAE) {
			xn_zip::zip($tmppath.'tmp.zip', $tmppath);
			copy($tmppath.'tmp.zip', 'saestor://upload/tmp.zip');
		}
	}
	
	private function dir_md5($path) {
		$df = opendir($path);
		$s = '';
		while($file = readdir($df)) {
			$ext = strrchr($file, '.');
			// 校验一下文件的 md5
			if(in_array($ext, array('.htm', '.php', '.js', '.json'))) {
				$s .= md5(file_get_contents($path.$file));
			}
		}
		closedir($df);
		return md5($s);
	}
	
	//hook admin_plugin_control_after.php
	
}

?>