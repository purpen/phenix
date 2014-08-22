#/bin/bash
BASE=`pwd`
BIN=$BASE/bin
SERVER=$BIN/cron_service_guard.sh
VAR=$BASE/var
LOCK=$VAR/cron_service.lock
LOGFILE=$BASE/log/cron_service.log

if [[ ! -e $SERVER ]]; then
    echo "not found service guard script: $SERVER "
    exit 2;
fi

start() {
    if [[ -e $LOCK ]]; then
        echo "found lockfile :$LOCK , maybe the service is started! Pleaase check PID:".`cat $LOCK`
        exit 0;
    fi
    echo  'start server ..'
    nohup $SERVER &
    sleep 1;
    echo "done."
    echo "server running as:".`cat $LOCK`
}

stop() {
    if [[ -e $LOCK ]]; then
        echo "service PID:".`cat $LOCK`
        echo "delete lock file removed, service should shutdown shortly."
        rm -f $LOCK
    else
        echo "service lock file not found, maybe you not started yet?"
    fi
}

case $1 in
    start )
        start
        ;;
    stop )
        stop
        ;;
    * )
        echo "$0 { start|stop}"
esac