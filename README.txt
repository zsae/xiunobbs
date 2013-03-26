【关于 Xiuno BBS 2.0.0 正式版】
Xiuno BBS 是一款面向大数据量高负载开发的论坛，最早用于国内某知名论坛，用于解决论坛运行效率问题，经过一次彻底的重构（陆续经历了一年多的时间）诞生了 Xiuno BBS 2.0 这个令作者都感到激动的版本。
Xiuno BBS 2.0 是经过实际高负载论坛考验过的，除了个别第三方类库，全部代码由作者一人完成，从设计到实现高度统一，它是非常安全稳定的。
压缩后，它只有800k，解压后只有 2M，一共406个文件。但是你知道它有多强大吗？
	在程序方面：
	1. 支持多种数据库存储： MySQL, Mongodb, pdo_mysql, pdo_*，而且有效的规避了慢查询，无索引查询，利用到了MYSQL强大的分区功能，支持海量存储，支持DB主从架构（自动分离读写SQL）。
	2. 开启 Memcached, TTServer, Redis 等 key-value 缓存，可以大大减轻DB的读写压力，而且是透明的，可以随时开启和关闭。
	3. 模板语法非常的简单，前端工程师只需要简单的学习几个标签即可开始工作。 
		{if $uid == 1}...{/if}
		{loop $threadlist $thread} ...{/loop}
		{include footer.htm}
	4. 插件机制是真正的AOP机制，插件代码插入到主程序后运行，不影响主程序执行效率。一个插件一个目录，一个文件名对应一个插入点，打包后即可发布和传播，风格也是使用插件的方式实现。
	5. 基于自行研发的 PHP Framework (XiunoPHP) 开发，MVC 层次清楚，便于扩展。
	6. 支持分布式环境的部署，支持SAE云平台。
	7. 不管是在小数据量还是在大数据量下，它的速度远远领先于同类产品，注意，是远远。
	
	在功能方面：
	1. 支持传统的 BBS 的用户组、版块、附件、斑竹、置顶、精华、权限等概念。
	2. 单板论坛的国内先行者，可能是下一代BBS的原型。
	3. 支持大数据量的复合条件进行组合查询(最多四种条件任意组合）。

【关于 XiunoPHP 1.0】
在开发Xiuno BBS 的过程中开发了 XiunoPHP。
它只有340K，34个文件。
最早目的是用来分离公共模块，后来独立成了产品，它良好的封装了各种DB, CACHE，对上层只提供了12个方法，只需要掌握这12个方法，开发者就可以任意操作各种DB,CACHE。
它设计为以行为最小存储单位，这样大大的简化和统一了 DB,CACHE 的接口，并且它引入了单点分发自增ID，让应用不再依赖于DB的 count(), max()，函数，便于分布式程序的设计。

	// ------------ 操作 db ---------- //
	
	您可以像这样读取db中的数据:
	$user = $db->get("user-uid-123");
	
	更新用户数据:
	$db->set("user-uid-123", $user);
	
	删除一条记录：
	$db->delete("user-uid-123");
	
	统计数据:
	$n = $db->count('user');
	
	// ------------ 操作 cache ---------- //
	
	读取 Cache 中的数据：
	$user = $cache->get("user-uid-123");
	
	更新 Cache 中的数据：
	$cache->set("user-uid-123", 123);
	
	删除一条记录：
	$cache->delete("user-uid-123");
	
	统计:
	$n = $cache->count('user');

看起来是不是太简单了，确实是太简单了，从此不必再记忆各种SQL语法，这样顺便还消灭了LEFT JOIN等容易产生性能问题的SQL语句产生的机会。
高负载，安全性，分布式增加了程序的复杂性，而这个框架就是通过一些约定来消除这些复杂性，我们强烈向开发者推荐这个框架。
文档地址：http://www.xiuno.com/doc/xiunophp/

【如何安装？】
1. 上传 upload 目录下所有文件
2. 设置如下目录和文件为可写
	./upload
	./tmp
	./log
	./conf
3. 访问 http://www.domain.com/install/, 根据提示安装(http://www.domain.com/ 为您的网址)。


【如何修改风格？】
1. view 目录下存放着风格相关的文件, .htm .css .js，如果您不考虑到升级和发布，可以直接修改它们。
2. 风格是插件的一种，您可以在官方论坛下载网友做好的风格插件，也可以根据文档制作，过程非常简单，一个插件一个目录，存放在 ./upload/plugin 下。
3. 更多风格相关信息，请访问：http://www.xiuno.com/thread-index-fid-34-tid-9.htm


【如何二次开发？】
1. 程序采用MVC方式组织代码，插件使用了AOP机制，开发过程简单，不受主程序升级影响，一次写好，终生使用（这么说有点像JAVA当年的吹嘘~ 但是确实是这样）。
2. 详细的二次开发文档，请访问：http://www.xiuno.com/thread-index-fid-35-tid-10.htm


【如何优化Xiuno？】
1. Xiuno BBS 可以使用 Memcached, MongoDB, /dev/shm, clickd, Sphinx, 等来加速您的论坛，默认是关闭状态，您可以通过后台和配置文件来开启这些功能。
2. 配置文件 conf/conf.php 可能是是您进一步优化需要了解的文件。
3. 详情参见：http://www.xiuno.com/thread-index-fid-3-tid-11.htm


【关于分布式WEB服务器部署】
一般说来，跑 XiunoBBS 的单台服务器足够抗住百万级别的PV了，如果到了千万才需要考虑分布式部署，中间过渡的时候可以考虑DB,WEB分离。
写入的目录：
conf: 配置文件，需要手工同步到各台WEB服务器上，主要为了方便维护和开发（安装设置完一台以后再copy部署到其他web服务器）。
log: 日志文件，每台Web写各自的磁盘，也可以通过NFS将 log 挂载到一台服务器上。（SAE 版本禁用了此功能）
upload: 用户上传文件目录，通过NFS挂在到一台（文件）服务器上，SAE 平台下为： saestor://upload (建立 upload 域，并且设置好bbs配置文件中的 upload_url, upload_path）。
tmp: 临时文件夹，用来存放编译模板内容，SAE 平台下为 saekv://


【我需要授权吗？】
如果您是用于学习、非盈利性公益事业、个人站点用途可以免费使用。
商业用途需要获得官方授权，具体授权方式，请访问：http://www.xiuno.com/thread-index-fid-2-tid-14.htm


作者：axiuno#gmail.com
时间：2012/10/26
