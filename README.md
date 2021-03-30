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

vscodeをご利用の場合は、以下のように `.vscode/launch.json`を修正してください。
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

### F-RevoCRM7.3.2
#### パッチ適用方法
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

#### 主な変更点

* 機能改善
  - ユーザーの初回ログイン時に、共有カレンダーのマイグループに所属するユーザーを、自身のみになるように改善
  - 製品モジュールのエクスポート画面の日本語和訳を追加(#31)
  - 項目編集の｢項目を表示｣の日本語和訳を追加(#43)
  - ドキュメントモジュールで「内部」としてURLを保存した際に、新しいタブでページが開くように修正
  - その他、ドキュメントでURLを指定したときの動作を改善
  - 見積、受注、発注、請求モジュールの編集画面にて、手数料が数値以外の場合は0円を設定するように修正
  - カレンダー表示画面にて、マウスオーバー時に表示される活動画面に「活動コピー」機能を追加

* 不具合修正
  - README.mdのアップデート手順に誤りがあったため修正
  - 概要欄や関連から活動を追加する際に、招待者が正常に登録されない不具合を修正
  - スマートフォン表示時に詳細画面で項目タイトルが右寄せになる不具合の修正(#2)
  - ドキュメントの詳細画面でURLを内部で保存するとハイパーリンクにならない問題を修正
  - 見積、受注、発注、請求モジュールの編集画面にて、課税対象地域を変更すると金額がNaNとなる不具合を修正
  - リストの関連リンクから別モジュールへ遷移した場合、左サイドバーのメニューがMARKETINGのものになる不具合の修正(#38)
  - 編集画面においてシステム管理者でないユーザーの場合、詳細情報の配置が崩れる問題を修正
  - Docker環境にてドキュメント保存用フォルダが無いことによってファイルアップロードができない不具合の修正(#5)
  - Docker環境にてJpegファイルがアップロードできない不具合の修正(#24) @zeroadster

* その他修正
  - README.mdに記載されているサンプルURLをexample.comに置換
  - インストーラーにて、環境変数を見て自動でDB設定が入るように修正

### F-RevoCRM7.3.1
#### パッチ適用方法
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

#### 主な変更点
* 機能改善
  - 日本語翻訳を複数追加
  - サイドバーアイコン部分の表示を改善
  - 個人アイコンを設定した際のアスペクト比を維持するように改善
  - チケットモジュール、FAQモジュールにて、画像を挿入した場合の表示を改善
  - チケットモジュール・FAQモジュールにて、画像が保存できないケースを改善
  - チケットモジュール・FAQモジュールにて、「イメージ」ボタンのプレビュー欄の表示を改善
  - 日報モジュールの「コメント」項目の文字数制限が250文字だった為、TEXT型に変更
  - コメント欄にて、PDFファイルをアップロードした時のプレビューの挙動を改善
  - プロジェクトモジュールの「チャート」画面にて、タスク名表示を改善
  - 活動登録時に時間の選択肢をキーボードで選択した場合の動作を改善
  - モバイル：日報モジュールの概要画面の更新履歴エリアにて、更新日時の表示を改善

* 不具合修正
  - 各モジュールの概要画面に表示される活動の曜日が全て木曜日となる不具合の修正
  - 一覧画面にて、最終更新者のユーザを選択できない不具合の修正
  - メール送信可能な一覧からメール送信対象をチェックを付けてメール送信を行った際に、ランダムに1通のみメールが送信される不具合の修正
  - リード画面にて、関連メニューのメール部分に件数が表示されない不具合の修正
  - 一般ユーザーで、レポート表示時にエラーが発生する不具合の修正
  - 管理機能「企業の詳細」画面にて、画像がアップロードできないケースがある不具合の修正
  - 初期セットアップ時に日報が追加されない不具合の修正 ※本パッチ適用時に日報モジュールが追加されます

### F-RevoCRM7.3
#### 主な変更点

* 機能追加
  - 見積、受注、請求、発注のPDFテンプレートを作成・編集できる機能を追加
  - システム設定に文言変更機能を追加
  - プロジェクトタスクのガントチャートを追加
  - グラフのダッシュボード表示を追加
  - デフォルトのダッシュボードを追加
  - ユーザ一覧に検索機能を追加
  - ユーザのCSVインポート機能を追加
  - 関連データに対しての簡易検索機能を追加
  - Webフォーム取り込みに添付ファイルの取り込み機能を追加
  - 一覧画面にクイック表示の機能を追加
  - 案件からプロジェクトにコンバートできる機能を追加
  - メールコンバーターのメール自動紐付け機能を追加
  - フォロー機能を追加
  - RSS、ブックマーク機能を追加（復活）

* 機能改善
  - 画面デザインを刷新
  - 各文言を一般的な用語に変更
  - ユーザーのパスワードを8文字以上、アルファベット英数記号を含めるように制限
  - 一覧のスクロール時にヘッダ行が固定されるように改善（一覧画面のみ）
  - チケット（旧サポート依頼）、FAQ（旧回答事例）のテキストエリアの入力欄をリッチテキストに変更
  - 項目の種類に「関連」を追加（他モジュールを紐付ける項目）
  - リスト（旧フィルタ）の複製できるように改善
  - 共有リスト（旧フィルタ）の共有先の設定できるように改善
  - 「登録/編集」権限を「登録」と「編集」の権限に分離
  - 活動のCSVインポート機能を追加
  - 複数のダッシュボード管理に対応
  - ダッシュボードのウェジェットの表示サイズ変更を追加
  - 主要項目（旧概要）と関連一覧の表示設定の柔軟性を強化
  - 課税計算の設定を強化
  - タグ機能（旧タグクラウド）を強化
  - 関連するコメントをすべて表示できるように改善
  - 各レコードの入力元の表示を追加
  - 活動の繰り返し登録をした際に一括で削除や変更ができるように改善
  - ワークフローのレコードの登録、レコードの更新のアクションを強化
  - 初期表示のカレンダー表示（個人、共有、リスト）が選択できるように改善

以上

