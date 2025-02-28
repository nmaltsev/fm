#!/bin/sh
host_ip1=$(hostname -i | cut -d' ' -f1)
host_ip2=$(curl -s https://ipinfo.io/ip)
PORT=9000

echo "http://$host_ip1:$PORT/fm.php"
echo "http://$host_ip2:$PORT/fm.php"
php -S 0.0.0.0:$PORT -t ./src
