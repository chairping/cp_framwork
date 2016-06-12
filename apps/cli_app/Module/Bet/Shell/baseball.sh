#!/bin/sh

ITEM="$0"
let COUNT=`ps -A --format='%p%P%C%x%a' --width 2048 -w --sort pid|grep "$ITEM"|grep -v grep|grep -v " -c sh "|grep -v "$$" | grep -c sh|awk '{printf("%d",$1)}'`
if [ ${COUNT} -gt 0 ] ; then
    echo "$0 is running"
    exit 1;
fi

while true
do

done
