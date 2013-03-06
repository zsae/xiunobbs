#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

# Check if user is root
if [ $(id -u) != "0" ]; then
    echo "Error: You must be root to run this script, please use root to install coreseek"
    exit 1
fi

clear
echo "========================================================================="
echo "Coreseek for CentOS/RadHat Linux / Xiuno BBS 2.0"
echo "========================================================================="
cur_dir=$(pwd)

if [ "$1" != "--help" ]; then
#Disable SeLinux
if [ -s /etc/selinux/config ]; then
sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config
fi
fi

#chang locale
if [ -s /etc/profile ]; then
echo "LANG=zh_CN.UTF-8" >> /etc/profile
echo "LC_ALL=\"zh_CN.UTF-8\"" >> /etc/profile
fi

for packages in patch make gcc g++ gcc-c++ libtool autoconf automake imake mysql-devel libxml2-devel expat-devel;
do yum -y install $packages; done

echo "============================check files=================================="
if [ -s coreseek-4.1-beta.tar.gz ]; then
  echo "coreseek-4.1-beta.tar.gz [found]"
  else
  echo "Error: coreseek-4.1-beta.tar.gz not found!!!download now......"
  wget -c http://www.coreseek.cn/uploads/csft/4.0/coreseek-4.1-beta.tar.gz
fi

cd $cur_dir
tar zxvf coreseek-4.1-beta.tar.gz
cd coreseek-4.1-beta/
cd mmseg-3.2.14/
./bootstrap
./configure --prefix=/usr/local/mmseg3
make && make install

cd ../
cd csft-4.1/
sh buildconf.sh
sed -i "s/USE_LIBICONV\s*1/USE_LIBICONV 0/g" ./configure
./configure --prefix=/usr/local/coreseek  --without-unixodbc --with-mmseg --with-mmseg-includes=/usr/local/mmseg3/include/mmseg/ --with-mmseg-libs=/usr/local/mmseg3/lib/ --with-mysql
make && make install

groupadd sphinx
useradd sphinx -g sphinx
mkdir /usr/local/coreseek/var/data/delta_thread/
chown -R sphinx:sphinx /usr/local/coreseek

cd $cur_dir
cp csft.conf /usr/local/coreseek/etc/
#cat "/usr/local/coreseek/bin/searchd -c /usr/local/coreseek/etc/csft.conf" >> /etc/rc.local
cp sphinx.sh /etc/init.d/sphinx
chmod a+x /etc/init.d/sphinx

/usr/local/coreseek/bin/indexer -c /usr/local/coreseek/etc/csft.conf --all --rotate

service sphinx start
chkconfig sphinx --level 345 on

cp jobs_sphinx_xiuno.sh /usr/local/sbin/
cp jobs_sphinx_xiuno_delta.sh /usr/local/sbin/
chmod a+x /usr/local/sbin/jobs_sphinx_xiuno.sh
chmod a+x /usr/local/sbin/jobs_sphinx_xiuno_delta.sh
crontab jobs.cron

