# F-RevoCRM 7.3

F-RevoCRM は日本企業に合わせた形で開発された高機能なCRMです。
あらゆる顧客接点を管理するために、キャンペーン・リード管理から顧客・商談管理、販売管理、サポート管理・プロジェクト管理まで幅広い機能を持ち合わせています。

# ライセンス
Vtiger Public License 1.2

## サーバ推奨要件
* 2コア以上、4GB以上のメモリ、40GB以上の空き容量（利用人数・用途によってスペックが大幅に変わる）
* Apache 2.4以上
* PHP 5.6 / 7.2以上（8.0以上は除く）
* MySQL 5.6以上

## PCの推奨環境
* Windows 10 Google Chrome最新 / Microsoft Edge(Chronium)最新 / Internet Explorer 11（非推奨に移行予定）
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
* http://xxx.xxx.xxx.xxx/frevocrm

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
2. F-RevoCRM 6.5 インストール直後のファイルをもともとのF-RevoCRMのディレクトリとしてコピーします。
```
# コマンド例
cp -r frevocrm65 frevocrm
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
 * http://xxx.xxx.xxx.xxx/frevocrm/index.php?module=Migration&view=Index&mode=step1

2. 動作確認
  F-RevoCRMのログインや業務に関わる動作を確認してください。

3. 作業ディレクトリの削除
```
# コマンド例
rm -r frevocrm.20170118
```

## F-RevoCRM7.3の主な変更点

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

