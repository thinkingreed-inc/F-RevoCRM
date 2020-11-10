# Docker環境の立ち上げ方
## Docker環境の構築
### 事前確認
初期インストールを行う場合、`config.inc.php`を削除、またはリネームしておいてください。
```bash
mv config.inc.php config.inc.php.bak
```
### コンテナを立ち上げる
```bash
docker-compose up
```
## F-RevoCRMのインストール
1. ブラウザで `http://localhost` へアクセス
1. 指示に従って必要情報を入力
1. DBへのアクセスは以下の情報を用いる
```
host: db
user: root
password: docker
```
※詳細は`docker-compose.yml`に記載されています。

