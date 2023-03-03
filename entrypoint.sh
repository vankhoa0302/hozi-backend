#!/bin/bash

#source /etc/apache2/envvars
#tail -F /var/log/apache2/* &
#exec apache2 -D FOREGROUND
service ssh start
#service apache2 start
service clamav-freshclam start

/usr/sbin/apache2ctl -DFOREGROUND

tail -f /dev/stdout 



