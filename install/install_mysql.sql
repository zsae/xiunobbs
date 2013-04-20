#
#	SQL + 注释用来定义自动生成的代码
#

DROP TABLE IF EXISTS bbs_group;
CREATE TABLE bbs_group (				# 字段中文名			# 控件属性					# 字段描述
  groupid smallint(6) unsigned NOT NULL,		#				#						#
  name char(20) NOT NULL default '',			# 用户组名称			# type="text"					#
  creditsfrom int(10) NOT NULL default '0',		# 起始积分			# type="text" default="0"			# 积分范围（从）
  creditsto int(10) NOT NULL default '0',		# 截止积分			# type="text" default="0"			# 积分范围（到）
  maxcredits int(10) NOT NULL default '0',		# 每日最大评价积分
  maxgolds int(10) NOT NULL default '0',		# 每日最大评价金币
  allowread int(10) NOT NULL default '0',		# 允许访问
  allowthread int(10) NOT NULL default '0',		# 允许发主题
  allowpost int(10) NOT NULL default '0',		# 允许回帖
  allowreply int(10) NOT NULL default '0',		# 允许回复
  allowattach int(10) NOT NULL default '0',		# 允许上传文件
  allowdown int(10) NOT NULL default '0',		# 允许下载文件
  allowtop int(10) NOT NULL default '0',		# 允许置顶
  allowdigest int(10) NOT NULL default '0',		# 允许置顶
  allowupdate int(10) NOT NULL default '0',		# 允许编辑
  allowdelete int(10) NOT NULL default '0',		# 允许删除
  allowmove int(10) NOT NULL default '0',		# 允许移动
  allowbanuser int(10) NOT NULL default '0',		# 允许禁止用户
  allowdeleteuser int(10) NOT NULL default '0',		# 允许删除用户
  allowviewip int(10) NOT NULL default '0',		# 允许查看用户敏感信息
  PRIMARY KEY (groupid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
INSERT INTO bbs_group SET groupid='0', name="游客组", creditsfrom='0', creditsto='0', maxcredits='0', maxgolds='0', allowread='1', allowthread='0', allowpost='0', allowreply='0', allowattach='0', allowdown='0', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';

INSERT INTO bbs_group SET groupid='1', name="管理员组", creditsfrom='0', creditsto='0', maxcredits='10000', maxgolds='10000', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='1', allowdigest='1', allowupdate='1', allowdelete='1', allowmove='1', allowbanuser='1', allowdeleteuser='1', allowviewip='1';
INSERT INTO bbs_group SET groupid='2', name="超级版主组", creditsfrom='0', creditsto='0', maxcredits='200', maxgolds='200', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='1', allowdigest='1', allowupdate='1', allowdelete='1', allowmove='1', allowbanuser='1', allowdeleteuser='1', allowviewip='1';
INSERT INTO bbs_group SET groupid='4', name="版主组", creditsfrom='0', creditsto='0', maxcredits='50', maxgolds='50', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='1', allowdigest='1', allowupdate='1', allowdelete='1', allowmove='1', allowbanuser='1', allowdeleteuser='0', allowviewip='1';
INSERT INTO bbs_group SET groupid='5', name="实习版主组", creditsfrom='0', creditsto='0', maxcredits='0', maxgolds='0', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='1', allowdigest='1', allowupdate='1', allowdelete='0', allowmove='1', allowbanuser='0', allowdeleteuser='0', allowviewip='0';

INSERT INTO bbs_group SET groupid='6', name="待验证用户组", creditsfrom='0', creditsto='0', maxcredits='0', maxgolds='0', allowread='1', allowthread='0', allowpost='1', allowreply='0', allowattach='0', allowdown='0', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';
INSERT INTO bbs_group SET groupid='7', name="禁止用户组", creditsfrom='0', creditsto='0', maxcredits='0', maxgolds='0', allowread='0', allowthread='0', allowpost='0', allowreply='0', allowattach='0', allowdown='0', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';

INSERT INTO bbs_group SET groupid='11', name="一级用户组", creditsfrom='0', creditsto='50', maxcredits='0', maxgolds='0', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';
INSERT INTO bbs_group SET groupid='12', name="二级用户组", creditsfrom='50', creditsto='200', maxcredits='0', maxgolds='0', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';
INSERT INTO bbs_group SET groupid='13', name="三级用户组", creditsfrom='200', creditsto='1000', maxcredits='0', maxgolds='0', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';
INSERT INTO bbs_group SET groupid='14', name="四级用户组", creditsfrom='1000', creditsto='10000', maxcredits='0', maxgolds='0', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';
INSERT INTO bbs_group SET groupid='15', name="五级用户组", creditsfrom='10000', creditsto='10000000', maxcredits='0', maxgolds='0', allowread='1', allowthread='1', allowpost='1', allowreply='1', allowattach='1', allowdown='1', allowtop='0', allowdigest='0', allowupdate='0', allowdelete='0', allowmove='0', allowbanuser='0', allowdeleteuser='0', allowviewip='0';

# 用户表，根据 uid 范围进行分区
DROP TABLE IF EXISTS bbs_user;
CREATE TABLE bbs_user (					# 字段中文名			# 控件属性					# 字段描述
  uid int(11) unsigned NOT NULL auto_increment,		# 用户id				#						#
  regip int(11) NOT NULL default '0',			# 注册ip				#						#
  regdate int(11) unsigned NOT NULL default '0',	# 注册日期			# type="time"					#
  username char(16) NOT NULL default '',		# 用户名				# type="text"					#
  password char(32) NOT NULL default '',		# 密码				# type="password"				# md5(md5() + salt)
  salt char(8) NOT NULL default '',			# 随机干扰字符，用来混淆密码	#						#
  email char(40) NOT NULL default '',			# EMAIL				# type="text"					#
  groupid tinyint(3) unsigned NOT NULL default '0',	# 用户组				# type="select"					#
  threads mediumint(8) unsigned NOT NULL default '0',	# 主题数				#						#
  posts int(8) unsigned NOT NULL default '0',		# 回帖数				#						#
  myposts mediumint(8) unsigned NOT NULL default '0',	# 参与过的主题数			#						#
  avatar int(11) unsigned NOT NULL default '0',		# 头像最后更新的时间，0为默认头像	#						#
  credits int(11) unsigned NOT NULL default '0',	# 用户积分，不可以消费		#						#
  golds int(11) unsigned NOT NULL default '0',		# 虚拟金币，可以消费，充值可以增加	#						#
  digests int(11) unsigned NOT NULL default '0',	# 精华数				#						#
  follows smallint(3) unsigned NOT NULL default '0',	# 关注数				#						#
  followeds int(11) unsigned NOT NULL default '0',	# 被关注数			#						#
  newpms int(11) unsigned NOT NULL default '0',		# 新短消息（x人）			#						#
  newfeeds int(11) NOT NULL default '0',		# 新的事件（x条）todo:预留		#						#
  homepage char(40) NOT NULL default '',		# 主页的URL（外链）		# type="text"					#
  accesson tinyint(1) NOT NULL default '0',		# 是否启用了权限控制		#						#
  onlinetime int(1) NOT NULL default '0',		# 在线时间			#						#
  lastactive int(1) NOT NULL default '0',		# 上次活动时间，用来判断在线	#						#
  UNIQUE KEY username(username),
  KEY email(email),
  PRIMARY KEY (uid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
INSERT INTO bbs_user SET uid='1', regip='12345554', regdate=UNIX_TIMESTAMP(), username='admin', password='d14be7f4d15d16de92b7e34e18d0d0f7', salt='99adde', email='admin@admin.com', groupid='1', golds='0', digests='0';
INSERT INTO bbs_user SET uid='2', regip='12345554', regdate=UNIX_TIMESTAMP(), username='系统', password='d14be7f4d15d16de92b7e34e18d0d0f7', salt='99adde', email='system@admin.com', groupid='11', golds='0', digests='0';

# 用户访问权限，全局的。一般用来设置禁止用户。黑名单机制。
DROP TABLE IF EXISTS bbs_user_access;
CREATE TABLE bbs_user_access (				# 字段中文名			# 控件属性					# 字段描述
  uid int(11) unsigned NOT NULL default '0',		# uid				#						#
  allowread tinyint(1) unsigned NOT NULL default '0',	# 允许查看			# type="radio" default="0"			#
  allowthread tinyint(1) unsigned NOT NULL default '0',	# 允许发主题			# type="radio" default="0"			# 允许发主题
  allowpost tinyint(1) unsigned NOT NULL default '0',	# 允许发帖			# type="radio" default="0"			# 允许回帖
  allowreply tinyint(1) unsigned NOT NULL default '0',	# 允许回复			# type="radio" default="0"			# 允许回复
  allowattach tinyint(1) unsigned NOT NULL default '0',	# 允许上传附件			# type="radio" default="0"			#
  allowdown tinyint(1) unsigned NOT NULL default '0',	# 允许下载附件			# type="radio" default="0"			#
  expiry int(10) unsigned NOT NULL default '0',		# 过期时间，0永不过期		# type="text" default="0"			#
  PRIMARY KEY (uid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 板块表，结合 forum_access 控制权限。
DROP TABLE IF EXISTS bbs_forum;
CREATE TABLE bbs_forum (				# 字段中文名			# 控件属性					# 字段描述
  fid int(11) unsigned NOT NULL auto_increment,		# fid				#						#
  name char(16) NOT NULL default '',			# 用户名				# type="text"					#
  rank tinyint(3) unsigned NOT NULL default '0',	# 显示，倒序			# type="text"
  threads mediumint(8) unsigned NOT NULL default '0',	# 主题数	
  posts int(11) unsigned NOT NULL default '0',		# 回帖数				
  digests int(11) unsigned NOT NULL default '0',	# 版块精华数				
  todayposts mediumint(8) unsigned NOT NULL default '0',# 今日发帖，计划任务每日凌晨０点清空为０
  lasttid int(11) NOT NULL default '0',			# 最后发表的tid
  brief text NOT NULL default '',			# 版块简介 允许HTML		# type="text"
  accesson tinyint(1) NOT NULL default '0',		# 是否启用访问规则
  modids char(73) NOT NULL default '',			# 版主 uid，最多6个，逗号隔开
  modnames char(103) NOT NULL default '',		# 版主 username，最多6个，逗号隔开
  toptids char(240) NOT NULL default '',		# 置顶主题，分区可以置顶，板块可以置顶，格式：2-5 2-10 ，全局置顶放在 tmp/top_3.txt 
  orderby tinyint(11) NOT NULL default '0',		# 默认列表排序，0: 顶贴时间 floortime， 1: 发帖时间 dateline
  seo_title char(64) NOT NULL default '',		# SEO 标题，如果设置会代替版块名称
  seo_keywords char(64) NOT NULL default '',		# SEO keyword
  PRIMARY KEY (fid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
INSERT INTO bbs_forum SET fid='1', name='默认版块1', rank='0', threads='0', posts='0', digests='0', todayposts='0', lasttid='0', brief='默认版块1', accesson='0', modids='', modnames='', toptids='', orderby='0', seo_title='', seo_keywords='';
INSERT INTO bbs_forum SET fid='2', name='默认版块2', rank='0', threads='0', posts='0', digests='0', todayposts='0', lasttid='0', brief='默认版块2', accesson='0', modids='', modnames='', toptids='', orderby='0', seo_title='', seo_keywords='';
INSERT INTO bbs_forum SET fid='3', name='默认版块3', rank='0', threads='0', posts='0', digests='0', todayposts='0', lasttid='0', brief='默认版块3', accesson='0', modids='', modnames='', toptids='', orderby='0', seo_title='', seo_keywords='';

# 版块访问规则 fid * groupid
DROP TABLE IF EXISTS bbs_forum_access;
CREATE TABLE bbs_forum_access (				# 字段中文名			# 控件属性					# 字段描述
  fid int(11) unsigned NOT NULL default '0',		# fid				#						#
  groupid int(11) unsigned NOT NULL default '0',	# fid				#						#
  allowread tinyint(1) unsigned NOT NULL default '0',	# 允许查看			# type="radio" default="0"			#
  allowthread tinyint(1) unsigned NOT NULL default '0',	# 允许发主题			# type="radio" default="0"			# 允许发主题
  allowpost tinyint(1) unsigned NOT NULL default '0',	# 允许发帖			# type="radio" default="0"			# 允许发帖
  allowreply tinyint(1) unsigned NOT NULL default '0',	# 允许盖楼			# type="radio" default="0"			# 允许盖楼
  allowattach tinyint(1) unsigned NOT NULL default '0',	# 允许附件			# type="radio" default="0"			# 允许发帖
  allowdown tinyint(1) unsigned NOT NULL default '0',	# 允许下载			# type="radio" default="0"			# 允许下载
  PRIMARY KEY (fid, groupid),
  KEY (fid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 存放大分类，小表，每个版块4个大分类，ID，1,2,3,4
DROP TABLE IF EXISTS bbs_thread_type_cate;
CREATE TABLE bbs_thread_type_cate (
  fid smallint(6) NOT NULL default '0',			# 版块id
  cateid int(11) NOT NULL default '0',			# 主题分类id，取值范围：1,2,3,4
  catename char(16) NOT NULL default '',		# 主题分类
  rank int(11) unsigned NOT NULL default '0',		# 排序，越小越靠前，最大255
  enable tinyint(3) unsigned NOT NULL default '0',	# 是否启用，主要针对大分类
  PRIMARY KEY (fid, cateid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 主题分类，精华主题也算其中一种。
DROP TABLE IF EXISTS bbs_thread_type;
CREATE TABLE bbs_thread_type (
  fid smallint(6) NOT NULL default '0',			# 版块id
  typeid int(11) NOT NULL default '0',			# 主题分类id，为唯一。
  typename char(16) NOT NULL default '',		# 主题分类
  rank int(11) unsigned NOT NULL default '0',		# 排序，越小越靠前，最大255
  enable tinyint(3) unsigned NOT NULL default '0',	# 是否启用，预留
  PRIMARY KEY (fid, typeid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 主题分类求和，用来分页
DROP TABLE IF EXISTS bbs_thread_type_count;
CREATE TABLE bbs_thread_type_count (
  fid smallint(6) NOT NULL default '0',			# 版块id
  typeidsum int(11) unsigned NOT NULL default '0',	# typeid 求和
  threads int(11) NOT NULL default '0',			# 该 typeidsum 下有多少主题数
  PRIMARY KEY (fid, typeidsum)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 主题分类的数据，一个主题属于多少个主题分类，排列组合，typeid求和以后存放。
DROP TABLE IF EXISTS bbs_thread_type_data;
CREATE TABLE bbs_thread_type_data (
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) NOT NULL default '0',			# tid
  typeidsum int(11) unsigned NOT NULL default '0',	# 这个值是一个“和”
  PRIMARY KEY (fid, tid, typeidsum),			# 一个主题属于多个主题分类（最多三个）
  KEY (fid, typeidsum, tid)				# 一个版块下的 typeid，主题列表按照符合条件查询列表
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 论坛主题 fid->tid, 根据fid分区，已经完美解决分区问题
DROP TABLE IF EXISTS bbs_thread;
CREATE TABLE bbs_thread (
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) unsigned NOT NULL auto_increment,		# 主题id
  username char(16) NOT NULL default '',		# 用户名
  uid int(11) unsigned NOT NULL default '0',		# 用户id
  subject char(80) NOT NULL default '',			# 主题
  dateline int(10) unsigned NOT NULL default '0',	# 发帖时间
  lastpost int(10) unsigned NOT NULL default '0',	# 最后回复时间
  views int(10) unsigned NOT NULL default '0',		# 查看次数, 剥离出去，单独的服务，避免 cache 失效
  posts int(11) unsigned NOT NULL default '0',		# 回帖数
  top tinyint(1) NOT NULL default '0',			# 置顶级别: 0: 普通主题, 1-3 置顶的顺序
  typeid1 int(10) unsigned NOT NULL default '0',	# 主题分类id1
  typeid2 int(10) unsigned NOT NULL default '0',	# 主题分类id2
  typeid3 int(10) unsigned NOT NULL default '0',	# 主题分类id3
  typeid4 int(10) unsigned NOT NULL default '0',	# 主题分类id3
  digest tinyint(3) unsigned NOT NULL default '0',	# 精华等级: 0: 普通主题，1-3 精华等级
  attachnum tinyint(3) NOT NULL default '0',		# 附件总数
  imagenum tinyint(3) NOT NULL default '0',		# 附件总数
  modnum tinyint(3) NOT NULL default '0',		# 版主操作次数
  closed tinyint(1) unsigned NOT NULL default '0',	# 是否关闭，关闭以后不能再回帖。
  firstpid int(11) unsigned NOT NULL default '0',	# 首贴pid
  status tinyint(1) NOT NULL default '0',		# 状态 [未使用]
  lastuid int(11) unsigned NOT NULL default '0',	# 最近参与的 uid
  lastusername char(16) NOT NULL default '',		# 最近参与的 username
  PRIMARY KEY (fid, tid),				# 按照发帖时间排序
  KEY (fid, lastpost)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 精华主题，小表代替大索引，bbs_thread 的扩展表
DROP TABLE IF EXISTS bbs_thread_digest;
CREATE TABLE bbs_thread_digest (
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  digest tinyint(3) unsigned NOT NULL default '0',	# 精华等级
  PRIMARY KEY (tid),					# 
  UNIQUE KEY (fid, tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 最新主题 v2.0.2 增加，用来取代 bbs_thread.tid 大索引，在数据量特别大的情况下，会有很好的效果，结合sphinx 可以做到分词。
# 如果此表的总行数超过100条，则只保存3天内的数据。计划任务每天凌晨检查总行数，来决定是否清理此表。Sphinx 则采用增量的方式扫描此表。
# 发主题，删除主题，移动主题，合并版块，删除版块，需要操作此表
DROP TABLE IF EXISTS bbs_thread_new;
CREATE TABLE bbs_thread_new (
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  lastpost int(10) unsigned NOT NULL default '0',	# 最后回复时间
  PRIMARY KEY (tid),					# 
  UNIQUE KEY (fid, tid),				# 
  KEY (lastpost)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 主题数，单独一个表。用来分离 thread 表的写压力
DROP TABLE IF EXISTS bbs_thread_views;
CREATE TABLE bbs_thread_views (
  tid int(11) unsigned NOT NULL auto_increment,		# 主题id
  views int(11) unsigned NOT NULL default '0',		# 点击数
  PRIMARY KEY (tid)
);

# 论坛帖子数据 fid->tid->pid, 根据fid分区
DROP TABLE IF EXISTS bbs_post;
CREATE TABLE bbs_post (
  fid smallint(6) unsigned NOT NULL default '0',	# 版块id
  pid int(10) unsigned NOT NULL auto_increment,		# 帖子id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  uid int(11) unsigned NOT NULL default '0',		# 用户id
  dateline int(10) unsigned NOT NULL default '0',	# 发贴时间
  userip int(11) NOT NULL default '0',			# 发帖时用户ip ip2long()
  attachnum tinyint(3) unsigned NOT NULL default '0',	# 上传的附件数
  imagenum tinyint(3) unsigned NOT NULL default '0',	# 上传的图片数
  rates int(11) unsigned NOT NULL default '0',		# 评分次数
  page smallint(6) unsigned NOT NULL default '0',	# 第几页
  username char(16) NOT NULL default '',		# 用户名
  subject varchar(255) NOT NULL default '',		# 主题，不允许使用html标签
  message longtext NOT NULL default '',			# 内容，存放的过滤后的html内容
  PRIMARY KEY(fid, pid),
  KEY (fid, tid, page)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

#论坛附件表  fid->tid->pid->aid，只能按照从上往下的方式查找和删除！ 此表如果大，可以考虑通过 aid 分区。
DROP TABLE IF EXISTS bbs_attach;
CREATE TABLE bbs_attach (
  fid smallint(6) unsigned NOT NULL default '0',	# fid
  aid int(10) unsigned NOT NULL auto_increment ,	# 附件id
  tid int(11) NOT NULL default '0',			# 主题id
  pid int(11) NOT NULL default '0',			# 帖子id
  uid int(10) NOT NULL default '0',			# 用户id
  filesize mediumint(8) unsigned NOT NULL default '0',	# 文件尺寸，单位字节
  width mediumint(8) unsigned NOT NULL default '0',	# width
  height mediumint(8) unsigned NOT NULL default '0',	# height
  filename char(120) NOT NULL default '',		# 文件名称，会过滤，并且截断，保存后的文件名，不包含URL前缀 upload_url
  orgfilename char(120) NOT NULL default '',		# 上传的原文件名
  filetype char(7) NOT NULL default '',			# 文件类型: image/txt/zip，小图标显示
  dateline int(10) unsigned NOT NULL default '0',	# 文件上传时间 UNIX时间戳
  comment char(100) NOT NULL default '',		# 文件注释 方便于搜索
  downloads int(10) NOT NULL default '0',		# 下载次数
  isimage tinyint(1) NOT NULL default '0',		# 图片|文件，跟 filetype 含义不同，这个主要为了区分是否为可下载的附件。
  golds int(10) NOT NULL default '0',			# 金币
  PRIMARY KEY (fid, aid),
  KEY fidtid (fid, tid),				# 该索引主要为用来移动主题
  KEY fidpid (fid, pid),
  KEY uid (uid, isimage)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 收费附件的下载历史，用来查账，分页算法为上一页，下一页；此表如果大，可以考虑通过 aid 分区。
DROP TABLE IF EXISTS bbs_attach_download;
CREATE TABLE bbs_attach_download (
  fid int(10) unsigned NOT NULL default '0',		# 下载的附件fid
  aid int(10) unsigned NOT NULL default '0',		# 下载的附件id
  uid int(10) NOT NULL default '0',			# 下载的用户id
  uploaduid int(10) NOT NULL default '0',		# 上传人的UID
  dateline int(10) unsigned NOT NULL default '0',	# 下载的时间   
  golds int(10) NOT NULL default '0',			# 下载时支付的金币
  PRIMARY KEY (uid, fid, aid),
  KEY (uploaduid, dateline),
  KEY (fid, aid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 我的发帖，每个主题不管回复多少次，只记录一次。根据pid查询page
DROP TABLE IF EXISTS bbs_mypost;
CREATE TABLE bbs_mypost (
  uid int(11) unsigned NOT NULL default '0',		# uid
  fid int(11) unsigned NOT NULL default '0',		#
  tid int(11) unsigned NOT NULL default '0',		# 用来排除
  pid int(11) unsigned NOT NULL default '0',		# 查询 post 知道所在的 thread, post.page.
  PRIMARY KEY (uid, fid, pid),				# 每一个帖子只能插入一次 unique
  KEY (uid, fid, tid),					# 用户发表的主题，用来查询
  KEY (uid, pid)					# 用户发表的回帖，用来查询，按照 pid 排序
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 在线用户，每隔五分钟插入一次。	$this->count('online'); 查询和记录总数，否则 mysqld 重启会清空该表。
DROP TABLE IF EXISTS bbs_online;
CREATE TABLE bbs_online (
  sid char(16) NOT NULL default '0',			# 随机生成 id 不能重复
  uid int(11) unsigned NOT NULL default '0',		# 用户id 未登录为0
  username char(16) NOT NULL default '',		# 用户名	未登录为空
  ip int(11) NOT NULL default '0',			# 用户ip
  groupid tinyint(3) unsigned NOT NULL default '0',	# 用户组
  url char(100) NOT NULL default '',			# 当前访问 url
  lastvisit int(11) unsigned NOT NULL default '0',	# 上次活动时间
  PRIMARY KEY (sid),
  KEY lastvisit (lastvisit),
  KEY uid (uid)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# -------------------------> 以下为用户相关的表，增加用户互动。

# 消息的记录，记录A给B发，如果很多，可以根据 recvuid 分区，多对多的关系, N*N(N = user.count())，控制在40*N
# 新消息 recvuid=123 AND count>0
# 删除某人关系 recvuid=123, senduid=222 (可能没有这个必要，造成碎片？)，最近联系人。保留最后40个！  一次取1000个，删除掉后面的。
# dateline 为最后更新的时间，可以用来排序。
DROP TABLE IF EXISTS bbs_pmnew;
CREATE TABLE bbs_pmnew (
  recvuid int(11) unsigned NOT NULL default '0',	# 接受者UID，与 user.newpms 配合使用，非唯一主键
  senduid int(11) unsigned NOT NULL default '0',	# 发送者UID
  count int(11) unsigned NOT NULL default '0',		# 新消息的条数
  dateline int(11) unsigned NOT NULL default '0',	# 按照时间顺序排序 php 排序
  PRIMARY KEY (recvuid, senduid),
  KEY (recvuid, count)					# recvuid=123 and count>0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 用户聊天，类似在线QQ，根据 uid 分区, uid1 小，uid2 大，此表记录最大行数 = N*N (N = user.count())
DROP TABLE IF EXISTS bbs_pmcount;
CREATE TABLE bbs_pmcount (
  uid1 int(11) unsigned NOT NULL default '0',		# 用户id small uid
  uid2 int(11) unsigned NOT NULL default '0',		# 用户id big uid
  count int(11) unsigned NOT NULL default '0',		# 两人对话的记录条数，用来判断 pm.page 的最大页数
  dateline int(11) unsigned NOT NULL default '0',	# 按照时间顺序排序
  PRIMARY KEY (uid1, uid2)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 根据 pmid 分区，变长表。没有全表扫描操作。
DROP TABLE IF EXISTS bbs_pm;
CREATE TABLE bbs_pm (
  pmid bigint(11) unsigned NOT NULL auto_increment,	# pmid
  uid1 int(11) unsigned NOT NULL default '0',		# 用户id small uid
  uid2 int(11) unsigned NOT NULL default '0',		# 用户id big uid
  uid int(11) unsigned NOT NULL default '0',		# 由谁发出
  page int(11) unsigned NOT NULL default '0',		# 翻页数据
  username1 char(16) NOT NULL default '',		# 用户名	未登录为空
  username2 char(16) NOT NULL default '',		# 用户名	未登录为空
  dateline int(11) unsigned NOT NULL default '0',	# 时间
  message varchar(255) NOT NULL default '',		# 内容，没有编辑操作。避免碎片产生
  PRIMARY KEY (pmid),
  KEY (uid1, uid2, page)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 最多可关注 50 个。
DROP TABLE IF EXISTS bbs_follow;
CREATE TABLE bbs_follow (
  uid int(11) unsigned NOT NULL default '0',		# uid
  fuid int(11) unsigned NOT NULL default '0',		# uid关注的fuid
  direction int(11) unsigned NOT NULL default '0',	# 0: 保留, 1: 单向, 2: 双向
  PRIMARY KEY (uid, fuid),
  KEY (uid),
  KEY (fuid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 版主操作日志
DROP TABLE IF EXISTS bbs_modlog;
CREATE TABLE bbs_modlog (
  logid bigint(11) unsigned NOT NULL auto_increment,	# logid
  uid int(11) unsigned NOT NULL default '0',		# 版主 uid
  username char(16) NOT NULL default '',		# 版主 用户名
  fid int(11) unsigned NOT NULL default '0',		# 版块id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  pid int(11) unsigned NOT NULL default '0',		# 帖子id
  subject char(32) NOT NULL default '',			# 主题
  comment char(64) NOT NULL default '',			# 版主评价
  credits int(11) NOT NULL default '0',			# 加减积分
  golds int(11) NOT NULL default '0',			# 加减金币
  dateline int(11) unsigned NOT NULL default '0',	# 时间
  action char(16) NOT NULL default '',			# top|delete|untop
  PRIMARY KEY (logid),
  KEY (uid, logid),
  KEY (fid, tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 禁止的IP: IP段： 1,-1 / 1,2,-1  / 1,2,3,-1 / 1,2,3,4
# 如果 count() < 50, 则取出来，挨个比较
# 如果结果集很大，查询四次确定一个IP是否被禁止
DROP TABLE IF EXISTS bbs_banip;
CREATE TABLE bbs_banip (
  banid bigint(11) unsigned NOT NULL auto_increment,	# banid
  ip0 smallint(11) NOT NULL default '0',		# 
  ip1 smallint(11) NOT NULL default '0',		# 
  ip2 smallint(11) NOT NULL default '0',		# 
  ip3 smallint(11) NOT NULL default '0',		# 
  uid int(11) unsigned NOT NULL default '0',		# 添加人
  dateline int(11) unsigned NOT NULL default '0',	# 添加时间
  expiry int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY (banid),
  KEY (ip0, ip1, ip2, ip3)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 版主评分日志，针对每一楼，实际上也可以是任意用户评分
DROP TABLE IF EXISTS bbs_rate;
CREATE TABLE bbs_rate (
  rateid bigint(11) unsigned NOT NULL auto_increment,	# rateid
  uid int(11) unsigned NOT NULL default '0',		# 版主 uid
  username char(16) NOT NULL default '',		# 版主 用户名
  fid int(11) unsigned NOT NULL default '0',		# 版块id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  pid int(11) unsigned NOT NULL default '0',		# 帖子id
  comment char(64) NOT NULL default '',			# 版主评价
  credits int(11) NOT NULL default '0',			# 加减积分
  golds int(11) NOT NULL default '0',			# 加减金币
  dateline int(11) unsigned NOT NULL default '0',	# 时间
  ymd int(11) unsigned NOT NULL default '0',		# 年月日, 20121201
  PRIMARY KEY (rateid),	
  KEY (uid, rateid),					# 版主所有的评分
  KEY (fid, pid),					# 根据帖子查看评分
  KEY (uid, ymd)					# 用户每日的评分，用来统计今日的剩余量
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 统计信息，统计每日的总贴数，总用户数，新增贴数，用户数，每天增加一条记录
DROP TABLE IF EXISTS bbs_stat;
CREATE TABLE bbs_stat (
  year int(11) unsigned NOT NULL default '0',
  month int(11) unsigned NOT NULL default '0',
  day int(11) unsigned NOT NULL default '0',
  threads int(11) unsigned NOT NULL default '0',
  posts int(11) unsigned NOT NULL default '0',
  users int(11) unsigned NOT NULL default '0',
  newthreads int(11) unsigned NOT NULL default '0',
  newposts int(11) unsigned NOT NULL default '0',
  newusers int(11) unsigned NOT NULL default '0',
  PRIMARY KEY(year, month, day)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 持久的 key value 数据存储, ttserver, mysql
DROP TABLE IF EXISTS bbs_kv;
CREATE TABLE bbs_kv (
  k char(32) NOT NULL default '',
  v text NOT NULL default '',
  expiry int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY(k)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 临时的 key value 数据存储，可以放到 memcached 中。
# 频繁更新的数据，运行期间频繁更新的数据，可能多台WEB同步调用：比如: threads, posts, users, todayposts, todayusers, newuser, cron_1_next_time, cron_2_next_time, toptids
# 数据量小，直接用到 select cache, 不必使用 memory 引擎, myisam 够用。
DROP TABLE IF EXISTS bbs_runtime;
CREATE TABLE bbs_runtime (
  k char(32) NOT NULL default '',
  v text NOT NULL default '',
  expiry int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY(k)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;