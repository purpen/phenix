#/bin/bash
BASE=`pwd`
BIN=$BASE/bin
WORKER=$BIN/cron_service.php
VAR=$BASE/var
LOCK=$VAR/cron_service.lock
LOGFILE=$BASE/log/cron_service.log

if [[ ! -e $WORKER ]]; then
    echo "not found worker script $WORKER "
    exit 2;
fi

if [[ ! -d $VAR ]]; then
    mkdir $VAR;
fi

if [[ -e $LOCK ]]; then
    echo "worker started already. PID:".`cat $LOCK`
    exit 2;
fi

echo  $$ > $LOCK
while true; do
    echo "run data worker ..." > $LOGFILE
    $WORKER >>$LOGFILE 2>&1
    echo "sleep ..." >>$LOGFILE
    sleep 30
    if [[ ! -e $LOCK ]]; then
        echo "LOCK:$LOCK missing, shutdown"
        break;
    fi
done

rm $LOCK