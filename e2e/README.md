# E2E テスト (Playwright)

F-RevoCRM の画面を Playwright で操作し、各モジュールの新規作成・編集・削除と基本機能を検証する E2E テストです。

## 前提

- 稼働中の F-RevoCRM（テスト対象の URL にアクセスできること）
- ログインできる **admin** ユーザー
- Node.js v22

## セットアップ

リポジトリのルートで実行します。

```bash
# 1. 依存をインストール
npm install

# 2. ブラウザ(chromium)をインストール
npx playwright install chromium

# 3. .env を用意
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

```bash
# 全テスト
npm run test:e2e

# UI モード（ブラウザで対話的に実行）
npm run test:e2e:ui

# 特定モジュールだけ（テスト名でフィルタ）
npm run test:e2e -- -g "Accounts"
```

初回に `setup`（ログイン認証＋参照先モジュールのシード）が走り、認証状態を
`e2e/.auth/` に保存してから各テストを実行します。
