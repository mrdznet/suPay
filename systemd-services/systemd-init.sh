#!/usr/bin/env zsh

if [[ "$(id -u)" != "0" ]]; then
    sudo $0 $@
    exit $?
fi


CurPath=$(dirname $(readlink -f "$0"))
WWWDir=$(dirname $CurPath)

cat > $CurPath/Websocket.service <<EOF
[Unit]
Description=websocket

[Service]
WorkingDirectory=$WWWDir
ExecStart=/usr/bin/env php think websocket start
ExecStop=/usr/bin/env php think websocket stop
TimeoutStopSec=3s
Type=simple
Restart=always
KillSignal=SIGINT
#BindsTo=
#Requires=

[Install]
WantedBy=multi-user.target
EOF

cat > $CurPath/autodevice.service <<EOF
[Unit]
Description=websocket

[Service]
WorkingDirectory=$WWWDir
ExecStart=/usr/bin/env php think Autodevice
TimeoutStopSec=3s
Type=oneshot
#BindsTo=
#Requires=

[Install]
WantedBy=multi-user.target
EOF

cat > $CurPath/autodevice.timer <<EOF
[Unit]
Description=autodevice

[Timer]
OnCalendar=*:*:0/10
Unit=autodevice.service

[Install]
WantedBy=timers.target

EOF
