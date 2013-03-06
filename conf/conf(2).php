
	
	
	
	// -------------------------------> 全局（三大页面）可修改 start，存放于 kv，缓存在 runtime.bbs, 
	// 写入 kvdb, runtime 两份:
	// $this->kv->set('app_name', 'xiuno');		// 持久存储于 kvdb
	// $this->runtime->xset('app_name', 'xiuno');	// 合并写入缓存 runtime.bbs, 方便从 $conf 读取
	// 读取：
	// $app_name = $this->kv->get('app_name');
	// $app_name = $this->conf['app_name'];
	
	// 站点名称
	/*
	'app_name' => 'Xiuno BBS',
	
	// 是否开启 URL-Rewrite
	'urlrewrite' => 0,
	
	'timeoffset' => '+8',
	'forum_index_pagesize' => 20,		// 列表页的 pagesie，可以修改，建议不要超出100。
	'cookie_keeptime' => 86400,
	
	'site_pv' => 100000,			// PV越高CACHE更新的越慢，该值会影响系统的负载能力
	'site_runlevel' => 0,			// 0:所有人均可访问; 1: 仅会员访问; 2:仅版主可访问; 3: 仅管理员
	
	'threadlist_hotviews' => 2,		// 热门主题的阀值，浏览数
	
	// SEO
	'seo_title' => 'Xiuno BBS',		// 论坛首页的 title，如果不设置则为论坛名称
	'seo_keywords' => 'Xiuno BBS',		// 论坛首页的 keyword
	'seo_description' => 'Xiuno BBS',	// 论坛首页的 description
	
	'china_icp' => '',			// icp 备案号，也只有在这神奇的国度有吧。
	'app_copyright' => '© 2008-201 科技有限公司',
	'footer_js' => '<div style="display: none">
<script type="text/javascript">
var _bdhmProtocol = (("https:" == document.location.protocol) ? " https://" : " http://");
document.write(unescape("%3Cscript src=\'" + _bdhmProtocol + "hm.baidu.com/h.js%3F38e521ad5fb62b974841a4e0b774566e\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>

</div>',					// 页脚额外的代码，放用于统计JS之类代码。
		
	'iptable_on' => 0,			// IP 规则，白名单，黑名单
	'badword_on' => 0,			// 关键词过滤
	
	'online_hold_time' => 900,		// 在线时间，15分钟
	*/
	// -------------------------------> 全局可修改 end
	
	
	
	// -------------------------------> 局部可修改 start
	
	// 写入 kvdb:
	// $this->kv->set('app_name', 'xiuno');		// 持久存储于 kvdb
	// 读取：
	// $app_name = $this->kv->get('app_name');
	
	/*
	// 积分策略 conf_credits_policy
	'credits_policy_thread' => 2,		// 发主题增加的积分
	'credits_policy_post' => 0,		
	'credits_policy_reply' => 0,		
	'credits_policy_digest_0' => 0,
	'credits_policy_digest_1' => 10,
	'credits_policy_digest_2' => 15,
	'credits_policy_digest_3' => 20,
	'golds_policy_thread' => 1,		// 发主题增加的金币 golds（积分不能消费，金币可以消费，充值）
	'golds_policy_post' => 1,		
	'golds_policy_reply' => 1,		
	'golds_policy_digest_0' => 0,
	'golds_policy_digest_1' => 1,
	'golds_policy_digest_2' => 1,
	'golds_policy_digest_3' => 1,
	
	// 帖子多长时间后不能修改，默认为86400，一天，0不限制
	'post_update_expiry' => 86400,
	
	// 搜索相关
	'search_type' => 'title',			// title|baidu|google|bing|sphinx
	'sphinx_host' => '',			// 主机
	'sphinx_port' => '',			// 端口
	'sphinx_datasrc' => '',			// 数据源
	'sphinx_deltasrc' => '',		// 增量索引数据源，优先搜索这个
	
	// 注册相关
	'reg_on' => 1,				// 是否开启注册
	'reg_email_on' => 0,			// 是否开启Email激活
	'reg_init_golds' => 10,			// 注册初始化金币
	'resetpw_on' => 0,			// 是否开启密码找回
	*/
	
	
	// -------------------------------> 局部可修改 end