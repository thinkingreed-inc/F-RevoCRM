## Google Chrome --Headless mode install

1. Install Docker (If installed, skip this.)
1. Execute buildDockerImage.sh
1. config.customize.phpの編集
```php
$is_headlesschrome = true;// trueの場合：headless chromeを使用。falseの場合：TCPDFを使用。
$chromeurl = "http://172.29.188.232:30080/converthtmltopdf.php";// headlless chromeの場所
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