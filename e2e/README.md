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
| `E2E_BASE_URL` | テスト対象のベース URL（末尾スラッシュ付き）。例: `http://localhost/crm/` |
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

## ベースライン dump の構成（入力 / 出力の分離）

CI / reset / docker-init が使う `e2e/fixtures/e2e_dump.sql` は、
**「素のインストール状態」＋「E2E 拡充シード」** を固めた *生成物* です。
再現可能にするため、入力と出力を 2 ファイルに分けています:

| ファイル | 役割 |
|---|---|
| `e2e/fixtures/e2e_base_install.sql` | **入力（不変）**。完了済みインストール状態そのもの。ここは手で触らない |
| `e2e/fixtures/e2e_dump.sql` | **出力（生成物）**。base + シードを固めたもの。CI/reset/Playwright が使うのはこれ |
| `e2e/fixtures/seed-spec.json` | シードの**単一の真実**。投入件数・ユーザー・プレフィックス・期待可視数を宣言 |

いずれも公開リポの使い捨て CI 用なので、**固定のテスト用認証情報**を焼き込みます
（`run-e2e.sh` のデフォルトと一致させること）: ユーザー名 `admin` / パスワード `Admin1234/` /
アクセスキー `PqllmJsVZ0Kv5ICx`。

### dump の再生成

シード内容（`seed-spec.json`）や素の状態を変えたら、以下で `e2e_dump.sql` を作り直します
（開発用 compose が起動していること。ホストに php/mysql は不要 — すべてコンテナ内実行）:

```bash
# リポジトリルート or e2e から
FORCE=1 ./e2e/scripts/build-e2e-dump.sh
```

内部の流れ:

1. DB を作り直して `e2e_base_install.sql` を投入（過去実行の残骸を排除）
2. `run_migration.php --all`（スキーマ最新化）
3. `setup/scripts/seed_e2e_data.php`（下記のデータ島を冪等に投入）
4. `RecreateUserFiles.php`（tabdata / 権限・共有キャッシュ再生成）
5. `mysqldump` で `e2e_dump.sql` を再生成し、主要件数を検証

**素の入力（`e2e_base_install.sql`）を更新する**必要があるのは、migration で表現できない土台変更
（初期マスタの入れ替え等）が入ったときだけです。手順は「クリーン DB にインストール完走 →
認証情報を上記固定値に設定 → `mysqldump -uroot -p <dbname>`（`CREATE DATABASE`/`USE` を含めない）」。

## 拡充ベースラインのデータ（E2E data islands）

権限/可視範囲・ページング・ソート・検索・絞り込みは「複数ユーザー」「所有者違いのレコード群」
「一定量のデータ」が無いと検証できません。`seed_e2e_data.php` はこれらを **決定論的な
data island** として投入します。テスト側は型付きの `e2e/fixtures/seedSpec.ts` から
件数・プレフィックス・期待可視数を import します（seed と検証で数値の出所を 1 つに保つ）。

| 島 | 内容 | 用途 |
|---|---|---|
| ユーザー/ロール | H2 管理者配下に E2E 営業ツリー（部長 ▸ 1課長/2課長 ▸ 1課員/2課員）+ 各ロール 1 ユーザー（固定パス `Test1234/`）+ 平社員2名のグループ | 権限/可視範囲 |
| `[E2E-PERM]` Leads | Leads を org 共有 **Private** 化し、各ロールユーザー所有で 4 件ずつ | ロール階層による可視範囲 |
| `[E2E-GRP]` Leads | グループ所有で 4 件 | グループ共有の可視範囲 |
| 権限ペルソナ(アクション) | Sales Profile を複製し Accounts の権限だけ書換えた制限プロファイル+専用ロール+ユーザー 3 種（`e2e_p_hidden`=非表示 / `e2e_p_readonly`=閲覧のみ / `e2e_p_nodelete`=削除不可、固定パス `Test1234/`） | プロファイル/役割による アクション権限（作成/編集/削除/表示の可否） |
| 権限ペルソナ(項目) | 複製プロファイルで Accounts の項目だけ制限したユーザー `e2e_p_field`（`phone`=非表示 / `website`=編集不可） | 項目レベル権限（この項目だけ 見えない/編集できない） |
| 権限ペルソナ(出力) | Accounts の Export/Import を許可した `e2e_p_export`（既定拒否の Sales ユーザーと対比） | エクスポート/インポート権限 |
| 共有ルール+観測者 | 何も所有しない `e2e_observer` + カスタム共有ルール `Leads: 1課長(MGRA)ロール → 観測者ロール read-only`（`vtiger_datashare_role2role`） | カスタム共有ルール（datashare）による可視範囲 |
| タグ絞り込み島 | タグ `E2Eタグ絞込`（`vtiger_freetags`）を `[E2E-PAGE]` の先頭 7 件に付与 | サイドバーのタグ絞り込み |
| `[E2E-PAGE]` Accounts | ゼロ埋め連番 250 件（admin 所有） | ページング / 列ソート |
| `[E2E-SRCH]` Accounts | industry 6 種 × 10 件 + 一意トークン 1 件 | 検索 / 絞り込み |

### 並列実行での使い方（重要）

`fullyParallel` 下で安全に使うための不変条件:

- **島データは READ 専用**。編集・削除しない（変更系テストは従来どおり「自分で作って消す」）。
- **必ずプレフィックスで絞ってから件数を assert** する。グローバル件数に依存しない
  （他テストが Accounts/Leads を増減させても部分集合は不変）。
- 期待可視数は `seedSpec.ts`（= `seed-spec.json`）を唯一の出所にする。dump ビルド時に
  アプリの共有エンジンで一致確認済み。

実証スペック: `test/common/common.permission.spec.ts`（可視範囲）/ `common.permission-action.spec.ts`
（アクション権限）/ `common.permission-field.spec.ts`（項目レベル権限）/ `common.permission-export.spec.ts`
（エクスポート/インポート権限）/ `common.sharing-rule.spec.ts`（カスタム共有ルール）/
`common.masstransfer.spec.ts`（所有者変更）/ `common.tagfilter.spec.ts`（タグ絞り込み）/
`common.customview-condition.spec.ts`（CustomView 絞り込み条件）/ `common.paging.spec.ts`（ページング・ソート）/
`common.filter.spec.ts`（検索・絞り込み）。組織共有の状態は設定画面でも `test/admin/admin.C-04.SharingAccess.spec.ts` が確認。
