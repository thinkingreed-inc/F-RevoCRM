# 共有リンクウィジェット（#1963）テスト仕様書

ブランチ `feature/1963-shared-link-widget` で追加した「ダッシュボードの共有リンク」のテストケース集。
仕様は [docs/specs/shared_links_widget.md](../../specs/shared_links_widget.md)。

## 1. 本書の位置づけ

- **目的**: 仕様書のとおりに動くこと、既存の機能を壊していないことを確かめる。
- **作り方**: 仕様書から期待される振る舞いを先に言語化し（各 TS の 1 章）、条件の組み合わせをデシジョン表にし（2 章）、
  表の各列をテストケースにした（3 章）。コードからケースを起こしていない。
- **粒度**: 1 ケース＝観測できる 1 つの振る舞い。ケース ID（`TC-XXX-000`）を自動テストのメソッド名・テスト名に入れてあるので、
  失敗したテストから仕様書を引ける。

## 2. 範囲

| # | 領域 | 対象 | 仕様書 |
|---|---|---|---|
| 1 | 入力の検証（画面・サーバー） | `Settings_SharedLinks_Rules_Model`、`sharedLinkForm.ts` | [TS-01](TS-01_入力の検証.md) |
| 2 | 公開範囲の判定 | `Rules::isVisible`、`Module_Model::getVisibleLinksForCurrentUser` / `getUserContext` | [TS-02](TS-02_公開範囲.md) |
| 3 | 値の解決と GET の URL | `Rules::resolveParams` / `buildGetUrl`、差し込める項目 | [TS-03](TS-03_値の解決とURL.md) |
| 4 | リンクの開き方 | `SharedLinksContents.tpl`、`SharedLinksWidget.js` | [TS-04](TS-04_リンクの開き方.md) |
| 5 | 管理画面の API | `apis/SharedLinkAPI.php` | [TS-05](TS-05_API.md) |
| 6 | 管理画面（React） | `components/SharedLinks/**` | [TS-06](TS-06_管理画面.md) |
| 7 | ウィジェットとマイグレーション | `Home_SharedLinks_Dashboard`、`<shared-links-widget>`、`SharedLinksWidget.js`、`20261106093622_add_shared_links_widget.php` | [TS-07](TS-07_ウィジェットとマイグレーション.md) |
| 8 | ウィジェット（リンクのまとまり。複数作れる） | `Module_Model` のウィジェットの操作、API の `save_set` / `delete_set`、管理画面のタブ、「ウィジェットの追加」メニュー | [TS-08](TS-08_ウィジェット.md) |

## 3. 自動テストの層

| 層 | ファイル | 対象のケース | 実行方法 |
|---|---|---|---|
| Unit（PHPUnit、DB 不要） | `tests/Unit/Settings/SharedLinks/RulesTest.php`、`tests/Unit/Http/ResponseCspTest.php` | TS-01・02・03・08 の判定ロジック、CSP の form-action、TC-API-041 | `./vendor/bin/phpunit tests/Unit/Settings/SharedLinks tests/Unit/Http` |
| Integration（PHPUnit、テスト用 DB） | `tests/Integration/Settings/SharedLinksTest.php` | TS-01（DB を使う検証）・02（実ユーザー）・03・05・07（マイグレーション）・08 | `./vendor/bin/phpunit tests/Integration/Settings` |
| Integration（Smarty まで描画） | `tests/Integration/Settings/SharedLinksWidgetTest.php` | TS-02（HTML に出さない）・04（`<shared-links-widget>` に渡すデータ）・07・08（メニュー・見出し） | 同上 |
| Vitest（jsdom） | `assets/react-web-components/src/components/SharedLinks/**/__tests__/` | TS-01（画面の検証）・04（`openSharedLink.test.ts`・`SharedLinksWidget.test.tsx`）・06・08 | `cd assets/react-web-components && npx vitest --run src/components/SharedLinks` |
| E2E（Playwright、新規インストールした環境） | `e2e/tests/5_管理設定/F-05_共有リンク.spec.ts` | 実ブラウザでの開き方・Referer・送信内容、ユーザーごとの公開範囲、CSRF、管理画面の操作 | `cd e2e && FORCE=1 npm run test:e2e:fresh -- tests/5_管理設定/F-05_共有リンク.spec.ts` |

補足:

- Integration は `tests/Support/SharedLinksTestCase.php` が本体の読み込み・テスト用 DB へのマイグレーション・テスト用のウィジェットの作成・後始末（見出し・タイトルの接頭辞 `SPECTEST_`）を受け持つ。
  テスト用 DB の役割・グループ・ユーザーを一時的に変えるケース（グループの作成・ユーザーの無効化・項目の非表示）は、`finally` で元に戻す。
- `composer test:integration`（スイート全体）は、既存の `tests/Integration/Modules/Calendar/Apis/GetActivitiesApiTest.php` が存在しない基底クラスを参照しているため起動しない（origin/main の時点からの問題）。共有リンクの結合テストはディレクトリを指定して実行する。
- E2E のリンク先は実在しないドメイン（`https://e2e-shared-link.test`）にし、Playwright の `context.route` で横取りして、届いたメソッド・本文・Referer を記録する。外部へは接続しない。
- E2E の公開範囲のケースは、拡充ベースライン（`e2e/fixtures/seed-spec.json`）のロール階層（部長 ⊃ 1課長 ⊃ 1課員、部長 ⊃ 2課長 ⊃ 2課員）・グループ（E2Eクロスグループ）・ユーザーで確かめる。

## 4. 手動でしか確かめられないもの

| 内容 | 理由 |
|---|---|
| 管理画面の見た目（レイアウト・配色・はみ出し） | 自動テストは要素の有無と動作だけを見ている |
| Firefox・Safari での動作（フォームの `rel="noreferrer"` への対応など） | E2E は Chromium のみ |
| ポップアップブロッカーが有効な実ブラウザでの通知 | E2E のブラウザはポップアップを許可している（ブロック時の動きは Vitest で確認） |

## 5. 実行結果（2026-11-06）

### 5.1 件数

| 仕様書 | ケース数 |
|---|---|
| TS-01 入力の検証 | 46 |
| TS-02 公開範囲 | 34 |
| TS-03 値の解決と URL | 31 |
| TS-04 リンクの開き方 | 40 |
| TS-05 API | 29 |
| TS-06 管理画面 | 42 |
| TS-07 ウィジェットとマイグレーション | 15 |
| TS-08 ウィジェット（リンクのまとまり） | 27 |
| **合計** | **264** |

264 件すべてが自動テストに対応している（TC-MIG-004 は新規インストールのスクリプト `e2e/scripts/fresh-install-local.sh` の検証が受け持つ）。

| 層 | 結果 |
|---|---|
| Unit（`RulesTest` / `ResponseCspTest`） | 123 件 成功 |
| Integration（`SharedLinksTest` / `SharedLinksWidgetTest`） | 49 件 成功 |
| Vitest（`components/SharedLinks`） | 127 件 成功（8 回続けて実行し、すべて成功） |
| E2E（`F-05_共有リンク.spec.ts`、新規インストールした環境） | 16 件 成功（2 回続けて実行し、どちらも全件成功） |
| 新規インストール | 完走・未実行のマイグレーション 0 件 |
| 既存のテスト（回帰） | PHP の Unit 全体 289 件・Vitest 全体 665 件 成功。E2E のスモークは新規インストールした環境で成功。`2_CRUD` は CSP を変えた段階で 436 件成功・失敗 0 件（289 件はテスト側の条件で飛ばす設計のもの） |

E2E の POST で送るケース（TC-OPN-004 / 005）は、送り先 `https://e2e-shared-link.test` を `$csp_allowed_domains['form-action']` に登録した環境でだけ実行し、登録していなければ理由を示して飛ばす。
新規インストール直後（未登録）は 2 件が飛ばされ、複製先（`frevocrm_git_fresh`）の `config.customize.php` にだけ登録して全件を実行した。

### 5.2 テストの有効性（わざとバグを入れて落ちるか）

主な分岐ごとに実装へわざとバグを入れ、対応するテストが落ちることを確かめた（確かめた後は元に戻した）。
例: 下位の役割を判定しない・URL のエンコードを変える・ウィンドウの幅の下限を 1 ずらす・削除された項目でも空文字にしない・
グループや役割の経路を求めない・一部削除では警告しない・リファラーの設定を無視する・CSRF トークンを取り除かない・
修飾キーを見ない・opener を外さない・CSP の form-action を足さない・ワイルドカードが親ドメインも許す・送れない POST でも開く・
ウィジェットの削除で配置を消さない・見出しの重なりを見ない・ウィジェットに関係なく全リンクを出す・頭文字を大文字にしない・
`group` クラスに戻す・「ウィジェットの追加」メニューの修正を外す（E2E）、など。すべてテストが落ちることを確認した。

### 5.3 見つかった不具合（修正済み）

| # | 内容 | 見つけたもの | 直し方 |
|---|---|---|---|
| 1 | 管理者では「役割と下位」で上位の役割を指定しても当てはまらず、グループの指定も当てはまらない（管理者の権限ファイルが役割の経路とグループを持たないため） | TC-VIS-026〜027（Integration） | 役割とグループを DB（`GetUserGroups`）から求める |
| 2 | 対象の ID が空で、役割の無いユーザーと照合すると当てはまる | TC-VIS-016（Unit） | 空の ID は誰にも当てはめない |
| 3 | 対象が一部だけ削除されたリンクに警告が出ない（文言は「削除された対象を含んでいます」） | TC-API-032（Integration） | 削除された対象を 1 件でも含めば警告する |
| 4 | POST・別ウィンドウで、空のウィンドウが残ったまま別のタブで送信される（フォームの `rel="noopener"` で名前付きのウィンドウを探さない） | TC-OPN-005（E2E） | 開いた空のウィンドウの文書の中でフォームを送る |
| 5 | POST の送信がコアの CSP（`form-action 'self'`）で止められ、外部へ送れない | TC-OPN-004 / 005（E2E） | `$csp_allowed_domains['form-action']` で送り先を許可できるようにし（コアを変更）、許可されていない送り先は管理画面で警告・ウィジェットで通知する |
| 6 | マイグレーション・ウィジェットの作成で、`vtiger_links_seq` が遅れている環境では ID の重複で失敗する | 開発 DB での実行（TC-MIG-003） | 登録前に採番をそろえる |
| 7 | ウィジェットの一覧が gridster の CSS で崩れ、行が絶対配置になって見えない | TC-WDG-013（E2E） | 中身を `<ul><li>` で組まない（今は `<shared-links-widget>`） |
| 8 | ウィジェットを大きくしても中身の高さが変わらず、スクロールバーが残る | ご指摘 | `Home_SharedLinks_Widget_Js` で中身の高さをウィジェットに合わせる（TC-WDG-016） |
| 9 | ウィジェットの行の余白が大きすぎる（vtiger の共通 CSS の `.group` と Tailwind の `group` がぶつかる） | ご指摘 | Tailwind の名前付きグループ（`group/link`）を使う（TC-WDG-015） |
| 10 | 共有リンクのウィジェットを 1 つ置くと、ほかの共有リンクのウィジェットも「ウィジェットの追加」から消える | ご指摘 | 押した項目以外の共有リンクの項目を表示し直す（TC-SET-040） |

1〜3・6 は PHP のテスト、4・5・7 は実ブラウザ（E2E）で見つかった。4・5・7〜10 は jsdom（Vitest）では再現しない
（名前付きウィンドウの扱い・CSP・CSS のレイアウト・ダッシュボードの JS をブラウザが動かすため）。8〜10 のご指摘の分は、再発を防ぐテストを足した。
