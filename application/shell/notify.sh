#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH
cd /www/wwwroot/suPay

step=1 #间隔的秒数，不能大于60  
for (( i = 0; i < 60; i=(i+step) )); do
   curl -L https://a.tzpay.xyz/api/Ordernotify/getSms #调用链接 
    sleep $step
done
exit 0
