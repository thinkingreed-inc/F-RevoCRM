# F-RevoCRM 7.3

F-RevoCRM は日本企業に合わせた形で開発された高機能なCRMです。
あらゆる顧客接点を管理するために、キャンペーン・リード管理から顧客・商談管理、販売管理、サポート管理・プロジェクト管理まで幅広い機能を持ち合わせています。

# ライセンス
Vtiger Public License 1.2

## サーバ推奨要件
* 2コア以上、4GB以上のメモリ、40GB以上の空き容量（利用人数・用途によってスペックが大幅に変わる）
* Apache 2.4以上
* PHP 5.6 / 7.2以上（8.0以上は除く）
  * php-imap
  * php-curl
  * php-xml
  * memory_limit = 512M(min. 256MB)
  * max_execution_time = 0 (min. 60 seconds、0は無制限)
  * error_reporting (E_ERROR & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED)
  * display_errors = OFF
  * short_open_tag = OFF
* MySQL 5.6以上
  * storage_engine = InnoDB
  * local_infile = ON (under [mysqld] section)
  * sql_mode = NO_ENGINE_SUBSTITUTION for MySQL 5.6+

## F-RevoCRMへのアクセスについて
本アプリケーションはWebアプリケーションとなりますので、URLへ直接アクセスしてください。  
またリファラーチェックを行っておりますので、もしSharePointなどの社内イントラにリンクを設置する場合は、`rel=noreferrer`属性を追加してください。
```
<a href="https://example.com/{CRM_DIR}/index.php">F-RevoCRM</a>​​​​​​
↓
<a href="https://example.com/{CRM_DIR}/index.php" rel="noreferrer">F-RevoCRM</a>​​​​​​
```

## PCの推奨環境
* Windows 10 Google Chrome最新 / Microsoft Edge(Chronium)最新 / Internet Explorer 11（2022年4月 非推奨に移行予定）
* 最低1366×768以上の解像度、推奨1920×1080以上
* 最低Intel Core iシリーズまたはそれ以上の2コア以上のプロセッサ、推奨4コア以上
* 最低4GB以上のメモリ、推奨8GB以上

## モバイルデバイスの推奨環境
* Android 9.x/8.x Google Chrome（タブレット未確認）
* iPhone iOS 12.x/11.x Safari（iPad未確認）

## インストール方法（概要）
以下、F-RevoCRMのインストール方法になります。

* F-RevoCRM7.3のインストール方法はそのまま読み進めてください。
* F-RevoCRM6.5からのバージョンアップはインストール方法の後に記載があります。
* F-RevoCRM7.3のパッチ適用方法については各パッチ付属のREADMEを参照してください。
* 本レポジトリをDockerで構築する場合は、[docker/README.md](./docker/README.md)を参照してください。

### configファイルを独自に設定する場合
configファイルは`config.inc.php`として、インストール後に生成されます。  
このファイルは、`config.template.php`をベースに、インストーラーが自動生成するファイルとなりますので、もし独自に設定する必要がある場合は`config.template.php`を`config.inc.php`にリネームし、利用してください。

### 前提条件
データベース名などを「frevocrm」としてインストールすることを前提に記載します。

### 1. Apache, PHP, MySQLのインストール
事前にそれぞれをインストールしておいてください。

***注意点1**

MySQLのSTRICT_TRANS_TABLESを無効にしてください。
```
# 下記手順は設定例

vi /etc/my.cnf

# 以下の行を変更
[変更前]
sql_mode=NO_ENGINE_SUBSTITUTION,STRICT_TRANS_TABLES

[変更後]
sql_mode=NO_ENGINE_SUBSTITUTION

# mysqlを再起動
service mysqld restart
```

MySQL8.0以降の場合は、認証モードの変更が必要です。
```
vi /etc/my.cnf

# [mysqld]のセクションの中に以下の1行を追加
default-authentication-plugin=mysql_native_password

# mysqlを再起動
service mysqld restart
```

***注意点2**

php.iniにて以下の設定が必要です。
```
date.timezone = "Asia/Tokyo"
max_input_vars = 100000
post_max_size = 32M
upload_max_filesize = 32M
max_execution_time = 60
```
* 最低要件のため、利用用途等に合わせて数値を大きくしてください。

### 2. F-RevoCRMのZIPファイルを解凍、設置

ApacheのDocumentRoot以下に解凍したディレクトリ毎、あるいはファイルを置いて下さい。
ここでは仮に/var/www/frevocrmに設置したものをとして進めます。

### 3. 初期設定

3.で設置したF-RevoCRMのURLを開きます。
* http://example.com/frevocrm

画面に従って初期設定を完了させてください。


## バージョンアップ方法
F-RevoCRM 6.5 を F-RevoCRM 7.3 にバージョンアップする手順になります。

### 前提条件
* F-RevoCRM 6.5 であること（パッチバージョンは問わない）
* ソースコードの修正やモジュールの追加がされていないこと
* F-RevoCRM 6.5のインストール済み環境があること

### 1. バックアップの取得
F-RevoCRMのデータベース、ファイルを全てバックアップを取得します。

### 2. プログラムファイルの置き換え
1. F-RevoCRMのディレクトリ全体を別名に置き換えます。
```
# コマンド例
mv frevocrm frevocrm.20201001
```
2. F-RevoCRM 7.3 インストール直後のファイルをもともとのF-RevoCRMのディレクトリとしてコピーします。
```
# コマンド例
cp -r frevocrm73 frevocrm
```
3. F-RevoCRMの設定ファイル(config.*, *.properties, *tabdata.php)をコピーします。
```
# コマンド例
cp frevocrm.20201001/config.* frevocrm/
cp frevocrm.20201001/*.properties frevocrm/
cp frevocrm.20201001/*tabdata.php frevocrm/
```
4.F-RevoCRMのドキュメントファイルをコピーします。
```
# コマンド例
cp -r frevocrm.20201001/storage/* frevocrm/storage/
```

### 3. マイグレーションツールの実行
タグとしてv7.3.xが追加されるまで、Migrationは実行されません。  
最新のバージョンで実行したい場合は、`vtigerversion.php`のファイルを編集し、次のバージョンを指定してから以下のマイグレーション用のURLを実行してください。

1. アクセスすると自動でマイグレーションが実行されます。
 * http://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1

2. 動作確認
  F-RevoCRMのログインや業務に関わる動作を確認してください。

3. 作業ディレクトリの削除
```
# コマンド例
rm -r frevocrm.20170118
```

## 開発環境の構築
Dockerで構築する為、[docker/README.md](./docker/README.md)を参照してください。  

### xdebug
xdebug3がインストール済みです。
`docker-compose.yml` の以下の部分を修正してください
```yml
# Xdebugの設定を有効にしたい場合は、mode=debug に変更してください
# XDEBUG_CONFIG: "mode=off client_host=host.docker.internal client_port=9003 start_with_request=yes"
XDEBUG_CONFIG: "mode=debug client_host=host.docker.internal client_port=9003 start_with_request=yes"
```
#### WSL2での利用
WSL2を利用の場合は、以下のように実行してください。
```sh
cp docker-compose.override.yml.exmple docker-compose.override.yml
cp .env.example .env
```
その後、.envの中にWSL2のIPアドレスを入力してください。
```sh
hostname -I
# 172.26.76.74
vim .env
# DOCKER_HOST_IP=172.26.76.74
```
#### VSCodeでの設定
vscodeをご利用の場合は、xdebugのエクステンションをインストール後、以下のように `.vscode/launch.json`を修正してください。
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "F-RevoCRM XDebug:9003",
      "type": "php",
      "request": "launch",
      "port": 9003, 
      "pathMappings": {
        "/var/www/html": "${workspaceRoot}"
      }
    }
  ]
}
```

## 更新履歴
[更新履歴](./CHANGELOG.md)を参照してください。

