
【脚本功能】：自动安装 sphinx for CentOS/RedHat / Xiuno BBS 2.0


【安装步骤】：

1. 解压压缩包：
    unzip xiuno_sphinx.tar.gz -d xiuno_sphinx
    cd xiuno_sphinx

2. 修改 csft.conf 中的如下参数:  vim csft.conf
    type                     = mysql
    sql_host                 = 127.0.0.1
    sql_user                 = root
    sql_pass                 = root
    sql_db                   = test
    sql_port                 = 3306
2.1 修改 jobs.cron

3. 执行 ./install.sh

4. 执行 netstat -anpt, 查看是否有 9312 端口

5. 进入 bbs 后台管理 -> 设置 -> 基本设置 -> 搜索设置：
    搜索方法：Sphinx
    Sphinx 主机：127.0.0.1 (改为您的sphinx服务器IP)
    Sphinx 端口：9312
    Sphinx 数据源：bbs_thread

【Sphinx 维护】：

/etc/init.d/sphinx.sh start 启动 Sphinx 搜索服务
/etc/init.d/sphinx.sh start 停止 Sphinx 搜索服务

/usr/local/coreseek/ 为安装后的程序目录
/usr/local/coreseek/var 为运行时的数据，比如索引数据

crontab -l 会有两条计划任务:
一个全部索引（每天执行一次）
一个为增量索引（每隔十分钟执行一次）


						Powered by xiuno.com
						           2014/4/29


