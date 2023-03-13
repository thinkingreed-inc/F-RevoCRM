1. Install Docker (If installed, skip this.)
1. Execute buildDockerImage.sh
1. config.inc.phpの編集
```php
$is_headlesschrome = true;// trueの場合：headless chromeを使用。falseの場合：TCPDFを使用。
$chromeurl = "headlesschrome/converthtmltopdf.php";// headlless chromeの場所 同じdockerネットワークに属している場合は左記のurlになる
```
#動かない場合は以下を参考にパーミッションを変更する
centosの場合は以下を実行する
#docker内に入る
```bash
docker exec -ti headlesschrome /bin/bash
```
#uid48をwww-dataユーザーに設定する
```bash
systemctl stop apache2
usermod -u 48 www-data
groupmod -g 48 www-data
#/var/www/htmlディレクトリの所有者をwww-dataにする。
chown -R www-data.www-data /var/www/html
systemctl start apache2
```
end