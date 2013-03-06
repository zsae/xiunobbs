#!/bin/sh
# sphinx: Startup script for Sphinx search
#
# chkconfig: 345 86 14
# description:  This is a daemon for high performance full text \
#               search of MySQL and PostgreSQL databases. \
#               See http://www.sphinxsearch.com/ for more info.
#
# processname: searchd
# pidfile: $sphinxlocation/var/log/searchd.pid
 
# Source function library.
. /etc/rc.d/init.d/functions
 
processname=searchd
servicename=sphinx
username=sphinx
sphinxlocation=/usr/local/coreseek
pidfile=$sphinxlocation/var/log/searchd.pid
searchd=$sphinxlocation/bin/searchd
 
RETVAL=0
 
PATH=$PATH:$sphinxlocation/bin
 
start() {
    echo -n $"Starting Sphinx daemon: "
    daemon --user=$username --check $servicename $searchd
    echo daemon --user=$username --check $servicename $searchd
    RETVAL=$?
    echo
    [ $RETVAL -eq 0 ] && touch /var/lock/subsys/$servicename
}
 
stop() {
    echo -n $"Stopping Sphinx daemon: "
 
    $searchd --stop
    #killproc -p $pidfile $servicename -TERM
    RETVAL=$?
    echo
    if [ $RETVAL -eq 0 ]; then
        rm -f /var/lock/subsys/$servicename
        rm -f $pidfile
    fi
}
 
# See how we were called.
case "$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    status)
        status $processname
        RETVAL=$?
        ;;
    restart)
        stop
sleep 3
        start
        ;;
    condrestart)
        if [ -f /var/lock/subsys/$servicename ]; then
            stop
    sleep 3
            start
        fi
        ;;
    *)
        echo $"Usage: $0 {start|stop|status|restart|condrestart}"
        ;;
esac
exit $RETVAL
