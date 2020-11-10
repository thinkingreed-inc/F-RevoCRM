@echo off

set DB_USER=root
set DB_PASS=
set DB_NAME=bewith

echo "***************************"
echo "DB_USER: %DB_USER%"
echo "DB_PASS: %DB_PASS%"
echo "DB_NAME: %DB_NAME%"
echo "***************************"

SET /P ANSWER="実行してもよろしいですか？(y/n):"

if "%ANSWER%"=="y" goto :yes
if "%ANSWER%"=="yes" goto :yes

EXIT

:yes

cd /d %~dp0

echo Recreate database.
if "%DB_PASS%"=="" goto :mysql

:mysqlp
mysql -u%DB_USER% -p%DB_PASS% %DB_NAME% < setup\sql\drop_all_tables.sql
rem mysql -u%DB_USER% -p%DB_PASS% %DB_NAME% < dump_firstinstall.sql
goto :main

:mysql
mysql -u%DB_USER% %DB_NAME% < setup\sql\drop_all_tables.sql
rem mysql -u%DB_USER% %DB_NAME% < dump_firstinstall.sql
goto :main

:main
del config.inc.php
echo Run the setup in your browser.
echo http://[your domain or IP address]/[frevocrm directory]/index.php
rem php setup\Setup.php
