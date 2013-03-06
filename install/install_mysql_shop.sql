############################################# SHOP #######################################

# 商品
DROP TABLE IF EXISTS xn_shop;
CREATE TABLE xn_shop (
	shopid int(11) unsigned NOT NULL auto_increment,	# 商品id
	cateid int(11) unsigned NOT NULL default '0', 		# 商品分类	# type="select"
	shopname char(16) NOT NULL default '',			# 商品名称	# type="text"
	dateline int(10) unsigned NOT NULL default '0',		# 添加时间	# type="time"
	price int(10) unsigned NOT NULL default '0',		# 商品价格	# type="text"
	rank int(11) unsigned NOT NULL default '0',		# 排序		# type="text" value="0"
	views int(11) NOT NULL default '0',			# 查看次数
	coverid int(11) NOT NULL default '0',			# 封面图片的id
	brief text NOT NULL default '', 			# 简介		# type="textarea"
	PRIMARY KEY(shopid),
	KEY rank (cateid, rank)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
INSERT INTO xn_shop SET shopid='1', cateid='1', shopname='NIKE运动鞋', dateline='0', price='268', rank='0', views='1', brief='...';

# 商品图片
DROP TABLE IF EXISTS xn_image;
CREATE TABLE xn_image (
	imageid int(11) unsigned NOT NULL auto_increment,	# 图片ID
	shopid int(11) unsigned NOT NULL default '0',		# 商品ID
	path char(16) NOT NULL default '',			# 图片路径，缓存，实际上有影射关系。
	width smallint(3) NOT NULL default '0',			# 图片宽度
	height smallint(3) NOT NULL default '0',		# 图片高度
	PRIMARY KEY(imageid),
	KEY(shopid)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# 商品分类，默认两级别
DROP TABLE IF EXISTS xn_cate;
CREATE TABLE xn_cate (
	cateid int(11) unsigned NOT NULL auto_increment,	# 分类ID
	parentid int(11) unsigned NOT NULL default '0',		# 父分类		# type="select"
	rank int(11) unsigned NOT NULL default '0',		# 排序		# type="text"
	shops int(11) unsigned NOT NULL default '0',		# 商品数		# type="text"
	name char(16) NOT NULL default '',			# 分类名称	# type="text"
	PRIMARY KEY(cateid)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
INSERT INTO xn_cate SET cateid='1', parentid='0', name='默认大分类';
INSERT INTO xn_cate SET cateid='2', parentid='1', name='默认分类1';
INSERT INTO xn_cate SET cateid='3', parentid='1', name='默认分类2';

# 商品订单
DROP TABLE IF EXISTS xn_order;
CREATE TABLE xn_order (
	orderid int(11) unsigned NOT NULL auto_increment,	# 订单ID
	uid int(11) unsigned NOT NULL default '0',		# uid
	username char(16) NOT NULL default '',			# 用户名
	shopid int(11) unsigned NOT NULL default '0',		# 商品id
	dateline int(10) unsigned NOT NULL default '0',		# 添加时间	# type="time"
	status tinyint(3) NOT NULL default '0',			# 状态		# type="select" options={"0":"等待支付", "1":"已支付，等待发货", "2":"已发货，等待收货", "3":"已收货，交易完毕", "4":"无效订单"}
	paymount int(3) NOT NULL default '0',			# 支付金额	# type="text"
	paytype tinyint(3) NOT NULL default '0',		# 支付方式	# type="select" options={"0":"线下付款", "1":"支付宝", "2":"网银"}
	year smallint(6) NOT NULL default '0',			# 年
	month tinyint(6) NOT NULL default '0',			# 月
	day tinyint(3) NOT NULL default '0',			# 日
	
	tel char(16) NOT NULL default '',			# 电话
	mobile char(16) NOT NULL default '',			# 手机
	email char(16) NOT NULL default '',			# Email
	qq char(16) NOT NULL default '',			# qq
	address char(64) NOT NULL default '',			# 地址
	postcode char(64) NOT NULL default '',			# 邮编
	comment char(255) NOT NULL default '',			# 备注
	
	alipay_email char(60) NOT NULL default '',           
	alipay_orderid char(60) NOT NULL default '',         
	alipay_fee int(10) NOT NULL default '0',             
	alipay_receive_name char(10) NOT NULL default '',    
	alipay_receive_phone char(20) NOT NULL default '',   
	alipay_receive_mobile char(10) NOT NULL default '',  
	ebank_orderid char(64) NOT NULL default '',          
	ebank_amount mediumint(9) NOT NULL default '0',      
	epay_amount int(11) NOT NULL default '0',            
	epay_orderid char(64) NOT NULL default '0',     
	PRIMARY KEY(orderid),
	KEY(year, month, day)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;