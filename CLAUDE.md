# CLAUDE.md

F-RevoCRM は vtiger ベースの PHP 製 CRM。`modules/` 配下に機能モジュールが並ぶ MVC 構成。フロントの一部は `assets/react-web-components/`（React + TypeScript）で実装。

## ディレクトリ構成

| ディレクトリ | 役割 |
|---|---|
| `modules/` | 機能モジュール本体。各モジュールが Model / View / Action を持つ |
| `modules/Vtiger/` | 各モジュールの**デフォルト実装＝フォールバック元**（コア相当） |
| `include/` | ユーティリティ・イベントハンドラ定義などの共通処理（カスタマイズ可） |
| `includes/` | ランタイムローダー・基盤ローダー（コア） |
| `vtlib/` | vtiger のモジュール基盤フレームワーク（コア） |
| `layouts/` | テンプレート（Smarty）・JS・CSS |
| `languages/` | 多言語ファイル |
| `cron/` | 定期実行ジョブ |
| `assets/react-web-components/` | React 製 Web コンポーネント（Vite / Vitest / Tailwind） |
| `tests/` | PHP 側テスト（PHPUnit）。詳細は `tests/README.md` |

## 構造・命名規約

各モジュールは `modules/<Module>/` 配下に役割別ディレクトリを持つ vtiger MVC 構造。

| 役割 | 配置 | クラス名 | 基底クラス | 担当 |
|---|---|---|---|---|
| Model | `models/` | `<Module>_<Type>_Model` | `Vtiger_<Type>_Model` | データ・ビジネスロジック |
| View | `views/` | `<Module>_<Name>_View` | `Vtiger_<Name>_View` | 画面表示 |
| Action | `actions/` | `<Module>_<Name>_Action` | `Vtiger_<Name>_Action` | 保存・処理 |
| Handler | `handlers/` | `<Name>Handler` | `VTEventHandler` | イベント処理 |

実例:
- `modules/Accounts/models/Record.php` → `class Accounts_Record_Model extends Vtiger_Record_Model`
- `modules/Contacts/actions/Save.php` → `class Contacts_Save_Action extends Vtiger_Save_Action`
- `modules/Leads/views/ConvertLead.php` → `class Leads_ConvertLead_View extends Vtiger_Index_View`

**オートロード規約**（`includes/Loader.php`）: クラス名のアンダースコア区切りがディレクトリにマップされる。
`Accounts_Record_Model` → `modules/Accounts/models/Record.php`。
モジュール側に実装が無ければ `modules/Vtiger/` の同役割クラスへ**フォールバック**する。
→ 新規実装・カスタマイズは、この命名と MVC パターンに合わせること。

### API エンドポイント（apis 標準）

- **JSON を返すエンドポイントは `modules/<Module>/apis/` に置く**（標準。`modules/Vtiger/apis` が基準）。
- **`apis` は JSON のみを返す。HTML を返すことは許容しない。**
- 一部モジュールに単数形 `api/`（例: `modules/Mobile/api`, `modules/WSAPP/api`）が残るが、新規は `apis/` に寄せる。

### Web コンポーネント（`assets/react-web-components/`）

- React + TypeScript + Vite。テストは **Vitest**、ビルドは `npm run build`（`tsc && vite build`）。
- **JSON は必ず型付けする**: レスポンス／データには `src/types` に型定義を置き、`any` を避ける。
- コミット前に **`npm run lint` / `npm run format`** を通す。

### PHPUnit（PHP 側テスト）

- 設定は `phpunit.xml`、ブートストラップは `tests/bootstrap.php`。実行は `./vendor/bin/phpunit`。
- テストは `tests/Unit/`（DB 不要）と `tests/Integration/`（DB 使用）に分け、本体のディレクトリ構成に合わせて配置する。
- `tests/bootstrap.php` が接続先を **テスト用 DB（既定は `config.inc.php` の `db_name` + `_test`）に強制**する。開発 DB を壊さないための安全装置なので外さないこと。
- 共通スタブ・ヘルパは `tests/Support/` に置く。書き方の詳細は `tests/README.md`。
- 実行は `composer test`（`vendor/bin/phpunit` のエイリアス）でもよい。

### 静的解析・整形（php-cs-fixer / phpstan）

- **対象は新規・変更したファイルだけ**。既存コードは vtiger 由来で PSR-12 差分と型指摘が大量に出るため、一括適用しない（baseline も持たない）。
- 整形: `composer cs-check -- <path>` で差分確認 → `composer cs-fix -- <path>` で適用。設定は `.php-cs-fixer.dist.php`（`@PSR12` ベース）。
- 静的解析: `composer phpstan -- <path>`（level 9）。`phpstan-shim.php` を `auto_prepend_file` で読ませないと `Vtiger_Loader` が `php7_count()` 未定義で落ちるため、素の `vendor/bin/phpstan` ではなく composer script 経由で実行する。
- `strict_param` は**有効化しない**。`in_array` の第 3 引数が強制 true 化され、`Vtiger_Module_Model::isActive()` が常に false を返す事故があった（`.php-cs-fixer.dist.php` のコメント参照）。

### マイグレーション（DB 変更）

- DB のスキーマ・データ変更は手動 SQL でなく `setup/migration/` のコマンドで行う。詳細は `setup/migration/README.md`。

## Do / Don't

### Do
- 既存モジュールの命名・MVC パターンに合わせて実装する
- **コードを書いたら、自分で動作を確認する**（自己レビュー）。E2E（playwright）は現時点で必須ではないが、今後拡充する
- 1 目的 1 コミット。コミット・PR・Issue は日本語で書く

### Don't
- **コア由来を直接改変しない**: `vtlib/` / `includes/` / `modules/Vtiger/`（アップグレードで上書き・フォールバック元のため）
- `config*.php` の秘匿値をコミットしない
- **テスト・ビルド系コマンド（`phpunit` / `vitest` / `npm run build` 等）は、実行前に同じプロセスが動いていないか確認**してから実行する
