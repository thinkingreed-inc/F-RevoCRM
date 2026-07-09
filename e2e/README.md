# E2E テスト (Playwright)

F-RevoCRM の画面を Playwright で操作し、各モジュールの新規作成・編集・削除と基本機能を検証する E2E テストです。

## 前提

- 稼働中の F-RevoCRM（テスト対象の URL にアクセスできること）
- ログインできる **admin** ユーザー
- Node.js v22

## セットアップ

Playwright 一式は `e2e/` 配下にあります。依存インストールとブラウザ導入は `e2e/` で行います。

```bash
# 1. 依存をインストール
cd e2e
npm install

# 2. ブラウザ(chromium)をインストール
npx playwright install chromium
```

環境変数はリポジトリルートの `.env` を使います（`e2e/` の npm スクリプトは `../.env` を読みます）。

```bash
# リポジトリルートで
cp .env.example .env
```

`.env` を環境に合わせて編集します。

| 変数 | 説明 |
|---|---|
| `E2E_BASE_URL` | テスト対象のベース URL（末尾スラッシュ付き）。例: `http://localhost/FR_Remicck/` |
| `E2E_USER_NAME` | ログインユーザー名（既定 `admin`） |
| `E2E_USER_PASSWORD` | ログインパスワード |
| `E2E_USER_ACCESSKEY` | Webservice API 用アクセスキー（下記参照） |

### アクセスキーの取得

`E2E_USER_ACCESSKEY` は Webservice API ログインに使う値です。CRM 画面右上のユーザーメニュー →
「マイプリファレンス（設定）」→ **アクセスキー** の値をコピーして `.env` に設定します。

> `.env` は `.gitignore` 済みです。アクセスキー等の秘匿値はコミットしないでください。

## 実行

`e2e/` ディレクトリで実行します。

```bash
cd e2e

# 全テスト
npm run test:e2e

# UI モード（ブラウザで対話的に実行）
npm run test:e2e:ui

# 特定モジュールだけ（テスト名でフィルタ）
npm run test:e2e -- -g "Accounts"
```

初回に `setup`（ログイン認証＋参照先モジュールのシード）が走り、認証状態を
`e2e/.auth/` に保存してから各テストを実行します。

## CI (GitHub Actions)

PR（対象 `main`）と手動実行（`workflow_dispatch`）で `.github/workflows/e2e.yml` が動きます。
CI では稼働中の CRM を用意する代わりに、**「完了済みインストール状態」の DB dump**
（`e2e/fixtures/e2e_dump.sql`）を使います。インストーラは動かしません（遅いため）。

ブートストラップは `e2e/ci/run-e2e.sh` に集約されています:

1. `docker compose -f compose.yaml -f compose.ci.yaml up`（db は dump を初回 init で自動投入）
2. `config.inc.php` を `config.template.php` から生成（CI 用の固定値）
3. `composer install` / web-components ビルド（どちらも成果物は gitignore のため CI で生成）
4. `run_migration.php --all`（dump のスキーマをコード最新へ追従）
5. `tabdata.php` / `user_privileges_*.php` を再生成（DB とキャッシュの整合）
6. `healthcheck.php` が 200 になるまで待機
7. Playwright 実行

> **なぜ migration とキャッシュ再生成が要るか**: dump は「ある時点」で固定されるので、
> コード側に新しい migration が入るとスキーマがずれます。migration で追従し、
> モジュール/タブの増減後は tabdata 等のキャッシュを DB に合わせ直す必要があります
> （合わせないと admin でも権限エラーになる既知の落とし穴があります）。

### ローカルで擬似 CI 実行

```bash
# 既存の DB ボリュームがあると dump が投入されないため、まっさらにしてから実行
docker compose down -v
./e2e/ci/run-e2e.sh
```

## CI 用 dump の作成・更新

CI が使う `e2e/fixtures/e2e_dump.sql` は、**完了済みインストール状態**を固めたものです。
公開リポジトリの使い捨て CI 用なので、**固定のテスト用認証情報**を焼き込みます
（`run-e2e.sh` のデフォルトと一致させること）:

| 項目 | 値 |
|---|---|
| ユーザー名 | `admin` |
| パスワード | `Admin1234/` |
| アクセスキー | `PqllmJsVZ0Kv5ICx` |

作り方:

1. クリーンな DB に F-RevoCRM をインストール完走する
2. admin のパスワード／アクセスキーを上記の固定値に設定する
3. データベース名を指定して dump する（`CREATE DATABASE`/`USE` を含めない）:
   ```bash
   mysqldump -uroot -p <dbname> > e2e/fixtures/e2e_dump.sql
   ```
4. コミットする

**更新が必要になるのは**、migration では表現できない土台の変更（初期マスタデータの入れ替え等）が
入ったときだけです。通常のスキーマ変更は CI 側の `run_migration.php --all` が吸収するので、
dump を作り直す必要はありません。
