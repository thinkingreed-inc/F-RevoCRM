#!/bin/sh

DIR=$( cd "$( dirname "$0" )" && pwd )
cd $DIR

APACHENAME=`ps -ef | egrep '(httpd|apache2|apache)' | grep -v whoami | grep -v root | head -n1 | awk '{print $1}'`
# APACHENAME=ps -ef | egrep '(httpd|apache2|apache)' | grep -v `whoami` | grep -v root | head -n1 | awk '{print $1}'

mkdir /var/www/html2pdf

#Paged.jsを適用すると、ヘッダ固定ができない件の修正対応。
cp RepeatingTableHeaders.js /var/www/html2pdf/

chown -R $APACHENAME.$APACHENAME /var/www/html2pdf

docker build -t headlesschrome .  --no-cache
docker-compose up -d
