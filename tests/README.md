# PHPUnit テスト

F-RevoCRM の PHP 側テスト一式。フロント（`assets/react-web-components/`）のテストは Vitest なので対象外。

## セットアップ

```bash
composer install
```

PHPUnit は `require-dev` に入っている。`composer.json` の `config.platform.php` で
解決時の PHP を 8.3 に固定しているため、ローカルの PHP が 8.3〜8.5 のどれでも同じ依存が入る。

## 実行

```bash
composer test                                          # 全件（./vendor/bin/phpunit のエイリアス）
./vendor/bin/phpunit                                   # 全件
./vendor/bin/phpunit --testsuite Unit                  # Unit のみ
./vendor/bin/phpunit tests/Unit/Inventory              # ディレクトリ指定
./vendor/bin/phpunit tests/Unit/Inventory/IndividualTaxTranslationTest.php  # ファイル指定
./vendor/bin/phpunit --filter translateTaxLabels       # メソッド名で絞り込み
```

設定は `phpunit.xml`（bootstrap / testsuite / カバレッジ対象）。

main 向けの Pull Request では GitHub Actions（`.github/workflows/phpunit.yml`）が同じテストを実行する。
`tests/Unit` は DB を使わないため、CI では DB を立てずに `config.template.php` から生成した
`config.inc.php`（接続情報はダミー）で実行している。DB を使う `tests/Integration` を追加する場合は、
CI 側にも DB を用意するステップが必要になる。

## ディレクトリ構成

| パス | 役割 |
|---|---|
| `tests/bootstrap.php` | テスト用ブートストラップ。DB 接続先の切り替えと安全装置 |
| `tests/Unit/` | 単体テスト（DB 不要）。本体のディレクトリ構成に合わせて配置する |
| `tests/Integration/` | DB を使うテスト |
| `tests/Support/` | テスト用のスタブ・ヘルパ（テスト本体ではない） |

## ドキュメントモジュールのテスト

`docs/tests/Documents/` の仕様書に対応する。各テストメソッドのメッセージに
仕様書のケースID（`TC-BD-001` など）を入れてあるので、失敗したら仕様書を引ける。

| パス | 対応する仕様書 |
|---|---|
| `tests/Unit/Documents/JapaneseHolidaysTest.php` | TS-01 4.5 祝日の算出 / 4.6 CSV解析（DB 不要） |
| `tests/Integration/Documents/BusinessDayTest.php` | TS-01 4.1〜4.4 営業日・休祝日 |
| `tests/Integration/Documents/DeadlineTest.php` | TS-03 入力期限 |
| `tests/Integration/Documents/DeleteGuardTest.php` | TS-05 電帳法対象の削除禁止 |
| `tests/Integration/Documents/ComplianceOnSaveTest.php` | TS-05 保存時の適合判定 |

結合テストは `Tests\Support\DocumentsTestCase` を継承する。この基底クラスが
F-RevoCRM 本体の読み込み・実行ユーザーの切り替え・テストデータの後始末を引き受ける。

```php
namespace Tests\Integration\Documents;

use Tests\Support\DocumentsTestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

final class FooTest extends DocumentsTestCase
{
    public function test_TC_XX_001_なにをどう検証するか(): void
    {
        $notesId = $this->createDocument('Foo');
        // ...
    }
}
```

作成したドキュメント・フォルダ・休祝日は接頭辞 `SPECTEST_` を付け、
`tearDown()` でまとめて消す。ドキュメント設定と週休設定はテストごとに退避・復元する。

`tests/bootstrap.php` は `Vtiger_Loader` のオートロードを外すが、結合テストは本体を
まるごと使うため `DocumentsTestCase::setUpBeforeClass()` で登録し直している
（PHPUnit のクラス探索が終わった後なので探索には影響しない）。

vtiger のコア側（`LanguageHandler` / `VTEntityDelta` / log4php など）が出す
PHP 警告・非推奨の通知が大量に出るが、これは移植前からある既存の状態で、
テストの成否には影響しない。

命名は `<対象>Test.php`、namespace は配置に合わせる（例: `tests/Unit/Inventory/` → `namespace Tests\Unit\Inventory;`）。
`composer.json` の `autoload-dev` で `Tests\` → `tests/` を PSR-4 マップしている。

## テスト用 DB

`tests/bootstrap.php` は `config.inc.php` を読み込んだ上で接続先 DB を **テスト用 DB に強制**する。

- 既定のテスト DB 名: `config.inc.php` の `db_name` + `_test`
- 環境変数で上書き可: `FREVOCRM_TEST_DB_NAME` / `FREVOCRM_TEST_DB_HOST` / `FREVOCRM_TEST_DB_USERNAME` / `FREVOCRM_TEST_DB_PASSWORD`

安全装置として、DB 名が `_test` で終わらない場合と、実接続先（`SELECT DATABASE()`）が
`_test` で終わらない場合は起動時に中止する。開発 DB を誤って壊さないための仕掛けなので外さないこと。

Unit テストは DB に触らないため、テスト DB が存在しなくても実行できる（接続失敗は無視される）。
`tests/Integration/` にテストを追加する場合は、あらかじめテスト用 DB を作成し
F-RevoCRM のスキーマを投入しておく。

```bash
mysql -u <user> -p -e "CREATE DATABASE <db_name>_test DEFAULT CHARACTER SET utf8mb4"
```

`tests/Integration/Documents/` は初期データ（ユーザー・役割・グループ・フォルダ・
選択リスト）に依存するため、スキーマだけでは動かない。開発DBを丸ごと複製するのが手早い。

```bash
mysqldump -u <user> -p --single-transaction <db_name> | mysql -u <user> -p <db_name>_test
```

## テストの書き方

`tests/bootstrap.php` は `Vtiger_Loader` のオートロードを **意図的に外す**（PHPUnit のクラス探索で
Vtiger 側の require が走って落ちるため）。そのためテスト側で必要なクラスを明示 `require_once` する。

vtiger の継承チェーンやグローバル関数のスタブは `tests/Support/` にまとまっている。

- `VtigerActionTestSupport.php`: Action / Settings 系の継承チェーン、`Vtiger_Request`、`Vtiger_Session`、`csrf_check()` などのスタブ
- `VtigerViewTestStubs.php`: View 系（`Vtiger_View_Controller` 継承）のロードと `vimport()` スタブ
- `LanguageHandlerStubs.php`: `languages/` の言語ファイルを直接読む `Vtiger_Language_Handler` スタブ

```php
namespace Tests\Unit\Foo;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/VtigerActionTestSupport.php';
require_once dirname(__DIR__, 3) . '/modules/Foo/actions/Bar.php';

final class BarTest extends TestCase
{
    public function test_なにをどう検証するか(): void
    {
        // ...
    }
}
```

`phpunit.xml` は `failOnWarning` / `failOnRisky` / `beStrictAboutOutputDuringTests` を有効にしている。
テスト中の `echo` や未定義メソッドの警告もテスト失敗になる点に注意。
