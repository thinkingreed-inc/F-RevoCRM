#!/bin/sh

DB_USER=root
DB_PASS=
DB_NAME=frevocrm

echo "***************************"
echo "DB_USER: ${DB_USER}"
echo "DB_PASS: ${DB_PASS}"
echo "DB_NAME: ${DB_NAME}"
echo "***************************"

read -p "実行してもよろしいですか？(y/n):" YN

if [ "${YN}" != "y" ]; then
    exit 1
fi

script_path=$(cd $(dirname $0);pwd)
cd $script_path
cd ..

echo Recreate database.
if [ "$DB_PASS" != "" ]; then
    mysql -u$DB_USER -p$DB_PASS $DB_NAME < setup/sql/drop_all_tables.sql
#    mysql -u$DB_USER -p$DB_PASS $DB_NAME < setup/dump_firstinstall.sql
else
    mysql -u$DB_USER $DB_NAME < setup/sql/drop_all_tables.sql
#    mysql -u$DB_USER $DB_NAME < setup/dump_firstinstall.sql
fi

rm config.inc.php

echo Run the setup in your browser.
echo http://[your domain or IP address]/[frevocrm directory]/index.php
#php setup/Setup.php
