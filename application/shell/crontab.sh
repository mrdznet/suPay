#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH
cd /www/wwwroot/suPay

step=10 #间隔的秒数，不能大于60  
for (( i = 0; i < 60; i=(i+step) )); do
   php think HeartBeatline
   php think AutoOrder   
    sleep $step
done
exit 0
