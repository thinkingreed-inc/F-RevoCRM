# fix/#1658 テスト仕様書

ブランチ `fix/#1658`（マージベース `b61270ee6`〜`ff6b25d53`、25コミット）で追加・修正された
機能に対するテストケース集。

## 1. 本書の位置づけ

- **目的**: 本ブランチの変更が意図どおり動作し、既存機能を壊していないことを確認する。
- **対象読者**: 実装者（セルフレビュー）、レビュアー、QA。
- **粒度**: 「テスト仕様書」。1ケース＝1つの観測可能な振る舞い。
  実装の内部構造ではなく **公開インターフェースの振る舞い** を記述する。
- **自動化の扱い**: 各ケースに自動化状況（`自動`/`半自動`/`手動`）を付す。
  `自動` は `test/unit/` 配下の PHP スクリプト、または Vitest / Playwright で実行できるもの。

> 本書のケースはコードから起こしたものではなく、**先に期待される振る舞いを言語化**し、
> それに対して実装が満たすかを確認する形で記述している。
> 実装と期待値が食い違う箇所は「⚠️ 要確認」として明示し、
> 「実装が正」と決めつけずに判断を仰ぐ形にしている。

## 2. 変更のスコープ（影響範囲）

| # | 領域 | 主な追加・変更 | 仕様書 |
|---|---|---|---|
| 1 | 営業日・休祝日の共通基盤 | `include/utils/BusinessDay.php`, `include/utils/JapaneseHolidays.php` | [TS-01](TS-01_営業日・休祝日.md) |
| 2 | 休祝日マスタ（設定画面） | `modules/Settings/Holidays/**`, React `Holidays/**` | [TS-02](TS-02_休祝日マスタ.md) |
| 3 | 入力期限の自動計算 | `Documents_DeadlineCalculator`, cron `UpdateInputDeadlineStatus` | [TS-03](TS-03_入力期限.md) |
| 4 | 電帳法設定画面（入力期限＋**取引レコードの判定**） | `modules/Settings/DocumentsCompliance/**`, React `DocumentsCompliance/**` | [TS-04](TS-04_電帳法設定.md) |
| 5 | 電帳法適合（**書類区分ごとの判定**）・ハッシュ・監査ログ | `ComplianceChecker`, `FileHasher`, `AuditLogger`, `ComplianceAPI` | [TS-05](TS-05_電帳法適合と監査ログ.md) |
| 6 | 分割アップロード（〜2GB） | `ChunkUploadStore`, `apis/ChunkUpload`, `actions/Save`, React `chunkUpload.ts` | [TS-06](TS-06_分割アップロード.md) |
| 7 | アップロード検証・失敗時の挙動 | `Documents_Record_Model::validateUploadedFile`, `Documents::save_module`, `QuickUpload` | [TS-07](TS-07_アップロード検証.md) |
| 8 | ドキュメント API（一覧・詳細・フォルダ・スター） | `apis/ListAPI`, `apis/DetailAPI`, `apis/FolderAPI`, `apis/StarAPI` | [TS-08](TS-08_ドキュメントAPI.md) |
| 9 | テキスト抽出・プレビュー・ダウンロード | `TextExtractor`, `PreviewContent`, `DownloadVersion`, `streamFile` | [TS-09](TS-09_テキスト抽出とプレビュー.md) |
| 10 | フロントエンド（React 画面） | `assets/react-web-components/src/components/Documents/**` ほか | [TS-10](TS-10_フロントエンド.md) |
| 11 | マイグレーション | `setup/migration/scripts/2026*` 13本 | [TS-11](TS-11_マイグレーション.md) |
| 12 | 既存機能への影響（回帰） | Web ドキュメント廃止、共有設定、関連リスト、E2E ユーティリティ、翻訳 | [TS-12](TS-12_回帰テスト.md) |
| 13 | 既存ドキュメントの紐づけ | `apis/RelationAPI`, `ListAPI`（候補絞り込み）, React `DocumentSelectModal` | [TS-13](TS-13_ドキュメント関連付け.md) |

## 3. テスト環境と前提

### 3.1 共通前提

| 項目 | 値 |
|---|---|
| DB | MySQL 8.0 / 8.4（`utf8mb4_unicode_ci`） |
| PHP | 7.4 以上（`mbstring` / `zip` / `curl` 有効） |
| マイグレーション | `setup/migration/` を最新まで適用済み |
| ロケール | 既定 `ja_jp`。多言語ケースのみ `en_us` に切替 |
| タイムゾーン | `Asia/Tokyo` 固定 |
| 実行ユーザー | 明記が無い限りシステム管理者（`id=1`） |

### 3.2 非決定要素の固定

日付・時刻・乱数に依存するケースは以下で固定する。固定できない場合はケース内に明記する。

| 要素 | 固定方法 |
|---|---|
| 「今日」 | `calculateStatus($deadline, $today)` のように基準日を引数で渡す。<br>引数を取らない経路は、受領日を「今日」からの相対日で組み立てる |
| 休祝日マスタ | テスト用に **2035年**（初期シードの範囲外）を使い、テスト前後で全削除する |
| 週休設定 | テスト開始時に既定（土日 = `0,6`）へ戻す |
| アップロード ID | `random_bytes` 由来のため値は検証せず、**形式**（`^[0-9a-f]{32}$`）のみ検証する |
| 外部通信 | 内閣府 CSV は固定文字列 or ローカルファイルを使用。実 URL へは接続しない |

### 3.3 テストデータの独立性

- ドキュメントはタイトル接頭辞（例: `DEADLINETEST_`）で作成し、ケース終了時に削除する。
- 休祝日は `holiday_name LIKE 'HOLIDAYTEST%'` および対象年で削除する。
- 一時ファイルは `storage/chunk_uploads/` 配下のみを対象に、テスト後に削除する。
- 他テストが作ったデータ・本番データに依存しない。

## 4. 自動テストと手動テストの振り分け

各ケースには自動化状況（`自動` / `半自動` / `手動`）を付している。
分類の一覧・実行方法・実行結果・自動化していない理由は
**[自動テストと手動テストの振り分け](自動テストと手動テストの振り分け.md)** にまとめた。

自動テストは `test/unit/documents/spec/` に実装済みで、**407件が全件成功**している。
テストが実際に機能することは、実装にわざとバグを入れて落ちることを確認済み（同ドキュメント §1.4）。

> **実行には PHP 8.2 以上が必要**。`composer.lock` の更新で `vendor/` が PHP >= 8.2 を
> 要求するようになったため、PHP 7.4 では起動しない（同ドキュメント §4）。

## 5. 既存の自動テストとの対応

| スクリプト | 主な対応仕様書 |
|---|---|
| `test/unit/settings/test_holidays.php` | TS-01 / TS-02 |
| `test/unit/documents/test_deadline.php` | TS-03 / TS-04 |
| `test/unit/documents/compliance/test_compliance.php` | TS-05 |
| `test/unit/documents/compliance/test_audit_log.php` | TS-05 |
| `test/unit/documents/test_compliance_modules.php` | TS-04 / TS-05（書類区分ごとの判定） |
| `test/unit/documents/test_relation.php` | TS-13 |
| `test/unit/documents/compliance/test_folder_permissions.php`<br>`test_folder_permissions_v2.php`<br>`test/unit/documents/test_folder_permission_access.php` | TS-08 |
| `test/unit/documents/test_chunk_upload.php` | TS-06 |
| `test/unit/documents/test_upload_error.php` | TS-07 |
| `test/unit/documents/test_documents_crud.php` | TS-07 / TS-08 |
| `test/unit/documents/test_i18n.php` | TS-12 |
| `e2e/test/**` | TS-12 |
| `test/unit/documents/spec/**`（本書のために新規作成） | TS-01 / TS-03 / TS-04 / TS-05 / TS-06 / TS-08 / TS-09 / TS-13 |

**未カバー領域**（自動化を推奨するもの）

- React コンポーネント（Vitest。現状 Documents 配下にテストなし）
- HTTP 経由の認可（未認証・権限なしユーザー）
- マイグレーションの再実行冪等性（使い捨て DB が必要）

## 6. 実行方法

```bash
# 本書のテストケースに対応する自動テスト（PHP 8.2 以上・DB を書き換えるため開発環境で実行）
cd /path/to/frevocrm
php8.3 test/unit/documents/spec/run_all.php

# 既存の個別スクリプト
php8.3 test/unit/settings/test_holidays.php
php8.3 test/unit/documents/test_deadline.php
php8.3 test/unit/documents/compliance/test_compliance.php
php8.3 test/unit/documents/compliance/test_audit_log.php
php8.3 test/unit/documents/test_chunk_upload.php
php8.3 test/unit/documents/test_upload_error.php
php8.3 test/unit/documents/test_compliance_modules.php
php8.3 test/unit/documents/test_relation.php

# フロントエンド（実行前に同じプロセスが動いていないか確認すること）
cd assets/react-web-components
npm run lint && npm run build
npx vitest run

# E2E
cd e2e && npx playwright test
```

## 7. 要確認事項の一覧（⚠️）と対応状況

テストケースを設計する過程で見つかった「実装と一般的な期待が食い違う可能性がある箇所」と、
その後の判断・対応の記録。

凡例: **修正済** = コードを修正し本書に反映 / **仕様** = 意図した挙動として本書に明記 /
**対応不要** = 現状維持と判断 / **確認済** = 調査の結果、問題なしと確認

現時点で判断待ちの項目は無い（Q-01〜Q-32 すべて対応済み）。

| # | 箇所 | 内容 | 対応 | 参照 |
|---|---|---|---|---|
| Q-01 | `FR_BusinessDay::isHoliday()` | 空文字・不正日付が `false` → `isBusinessDay()` が `true` になる | **修正済**: 空は `false`、不正日付は `InvalidArgumentException` | TS-01 DT-1 |
| Q-02 | `FR_BusinessDay::normalize()` | `2026-02-30` を弾かず `2026-03-02` に繰り上げる | **修正済**: `checkdate` で実在確認し例外 | TS-01 BV-3 |
| Q-03 | `FR_BusinessDay::countBusinessDays()` | 3651日を超える期間が黙って打ち切られる | **修正済**: 週休から算術で求め、上限を撤廃 | TS-01 BV-4 |
| Q-04 | `Settings_Holidays_Record_Model::save()` | `2035-02-30` が日付検証を通過し、DATE カラムへ渡る | **修正済**: `checkdate` を追加 | TS-02 BV-1 |
| Q-05 | 同上 | `holiday_name` の長さ検証が無い（`VARCHAR(200)`） | **仕様**: 他モジュールと同様に DB へ委ねる | TS-02 S-02b |
| Q-06 | `HolidayAPI mode=delete` | 存在しない ID でも `deleted: true` を返す | **仕様**: 冪等な削除 | TS-02 S-02c |
| Q-07 | `DeadlineCalculator::calculateStatus()` | 残り0営業日でも `warning`（`overdue` にならない） | **仕様**: 期限日当日は超過にしない | TS-03 S-07b |
| Q-08 | `ComplianceChecker::check()` | `scan_resolution_dpi = 0` / 非数値が解像度チェックを素通りする | **修正済**: 空は0扱いで不適合、非数値は例外 | TS-05 BV-1 |
| Q-09 | 同上 | `document_category = ''` が `batchCheck()` で対象に含まれ `non_compliant` に計上される | **修正済**: 抽出条件を共通化し空文字を対象外に。対象外は `skipped` へ計上 | TS-05 S-01/S-02 |
| Q-10 | `ComplianceAPI` / `RelationAPI` | フォルダ権限の検証が無く、参照できないドキュメントを更新・紐づけできる（IDOR） | **修正済**: `Documents_FolderPermission` で検証 | TS-05 TC-CA-032<br>TS-13 TC-RL-037 |
| Q-11 | `ComplianceAPI mode=batch_verify_hash` | `notesids: []` が例外になる（0件は正常応答が自然） | **修正済**: 0件は正常応答 | TS-05 TC-FH-017 |
| Q-12 | `Documents_Module_Model::parseIniSize()` | `2.5M` が 2MB に切り捨てられる | **仕様**: 整数＋単位のみ解釈（切り捨て） | TS-06 S-01b |
| Q-13 | `getChunkSizeInBytes()` | 実効上限が 64KB のときチャンクサイズが上限と同値になる | **修正済**: 必ず上限未満になるよう変更 | TS-06 BV-4 |
| Q-14 | `ListAPI` | `pageLimit=101` が 100 ではなく既定 20 に落ちる | **修正済**: 上限に丸める | TS-08 BV-1 |
| Q-15 | `ListAPI` | 検索キーワードの `%` / `_` を LIKE 用にエスケープしていない | **修正済**: `ESCAPE '!'` でエスケープ | TS-08 BV-3 |
| Q-16 | `ListAPI` | `folder_id=0` が `empty()` 判定で全フォルダ扱いになる | **仕様**: 0 は「指定なし」 | TS-08 S-14 |
| Q-17 | `DetailAPI` | `starred` が常に `false` 固定（一覧 API と不整合） | **修正済**: 実行ユーザーの値を返す | TS-08 TC-AP-040d |
| Q-18 | `FolderAPI mode=tree` | `starredCount` が常に 0 固定 | **修正済**: 実数を返す | TS-08 TC-AP-049b |
| Q-19 | `FolderAPI mode=save` | 親フォルダの循環・自己参照を検証していない | **修正済**: 自己参照・子孫・不存在を拒否 | TS-08 BV-5 |
| Q-20 | `DetailAPI::getFolderPath()` | 11階層以上でパンくずが黙って打ち切られる | **修正済**: 深さ制限を撤廃（循環は検出して停止） | TS-08 BV-5 |
| Q-21 | `TextExtractor` / `PreviewContent` | シート・スライドの走査が `break` のため途中打ち切りになる | **修正済**: ZIP 内の実在項目を列挙 | TS-09 BV-2 |
| Q-22 | `PreviewContent` | シート数・行数の上限に達しても利用者に伝わらない | **修正済**: 省略した旨を画面に表示 | TS-09 S-15 |
| Q-23 | `Documents_Record_Model::streamFile()` | `Content-Disposition` のファイル名をエスケープしていない | **修正済**: 改行・引用符を除去し `filename*` を併記 | TS-09 BV-5 |
| Q-24 | マイグレーション M-03 / M-04 | `CREATE TABLE` に存在チェックが無い | **修正済**: `checkTableExists()` を追加 | TS-11 TC-MG-002 |
| Q-25 | `composer.json` / `composer.lock` | `smalot/pdfparser` の追加が未コミット | **修正済**: コミット `bea107977`（※ lock に他パッケージ更新も含む。TS-12 R-11 参照） | TS-12 R-11 |
| Q-26 | `modules/Vtiger/models/RelationListView.php` | コア相当ファイルを変更している | **対応不要**（現状維持と判断） | TS-12 R-02 |
| Q-27 | `saveCategoryModules()` | 送信されたキーだけで設定を置き換えるため、送らなかった区分が既定値に戻る | **修正済**: 現在の設定にマージする方式へ変更 | TS-04 S-14b |
| Q-28 | 同上 | `{}`（空オブジェクト）が例外にならず、全区分が既定値に戻る | **修正済**: 空の指定はエラー（何も変更しない） | TS-04 S-14c |
| Q-29 | `RelationAPI::getRecordIds()` | 201件目以降を黙って切り捨てる | **修正済**: 件数の上限を撤廃し全件処理 | TS-13 S-05 |
| Q-30 | 判定基準の変更と既存データ | 設定を変えても既存の `compliance_status` は変わらない | **仕様**: 運用手順（再判定の実行）を明記 | TS-05 1.1.1 |
| Q-31 | マイグレーション M-13 | 全件 `batchCheck()` で所要時間が伸びる | **対応不要**（現状維持と判断） | TS-11 TC-MG-130g |
| Q-32 | `DocumentSelectModal` | ページ移動時に選択状態が保持されるか未確認 | **確認済**: 保持される（`isOpen` 変化時のみリセット）。ケースを追加 | TS-13 TC-RL-072 |

### 7.1 判断の記録（Q-09 / Q-12 / Q-27 / Q-28 / Q-29 / Q-30）

判断が必要だった項目の背景と、決定した内容。**すべて対応済み**（コード修正または仕様として明記）。

| # | 決定 | 反映先 |
|---|---|---|
| Q-09 | 空文字も NULL と同じ「対象外」とし、抽出条件を `TARGET_SQL_CONDITION` に集約。対象外は `skipped` に計上 | `ComplianceChecker` / `ComplianceAPI` / `ListAPI` |
| Q-12 | 小数表記の切り捨ては仕様。実効上限を小さく見積もる方向のため実害なし | TS-06 S-01b |
| Q-27 | 指定された書類区分だけを更新するマージ方式へ。空にしたい区分は空配列を明示指定 | `Settings_DocumentsCompliance_Module_Model` |
| Q-28 | `{}` / 空配列はエラー（誤送信を黙って受け入れない） | 同上 |
| Q-29 | 件数の上限を撤廃し、指定された全件を処理 | `RelationAPI` |
| Q-30 | 自動再判定はせず、**運用手順**として「設定変更後に再判定を実行する」を明記 | TS-05 1.1.1 |

以下は判断に至った経緯の記録。

#### Q-09: `document_category = ''` が一括チェックの対象に入る

- **現象**: `batchCheck()` の抽出条件は `document_category IS NOT NULL` のため、空文字（`''`）の行も対象になる。
  一方 `isComplianceTarget()` は `empty()` 判定のため空文字を「電帳法対象外」と見なし、`check()` は `status = null` を返す。
  `batchCheck()` は `status === 'compliant'` 以外をすべて `else` で数えるので、**対象外のはずの行が「不適合」に計上される**。
- **起きること**: 設定画面の「適合状態を再判定」や電帳法レポートの件数が、実際より不適合寄りに膨らむ。
  管理者が「不適合が減らない」と誤認し、対象外のドキュメントを探し続ける。
- **発生条件**: `document_category` に空文字が入っている行があること。
  画面からの登録では NULL になるが、CSV インポート・旧データ・API 経由で空文字が入りうる。
- **決定**: (a)(b) の両方を実施。抽出条件を `Documents_ComplianceChecker::TARGET_SQL_CONDITION`
  （`IS NOT NULL AND != ''`）に集約し、`batchCheck()` は `status === null` を `skipped` に計上する。
  `checked == compliant + non_compliant` が常に成立するようになった（TC-CC-021b）。

#### Q-12: `parseIniSize('2.5M')` が 2MB になる

- **現象**: `(int)'2.5'` = 2 として計算するため、`upload_max_filesize = 2.5M`（2,621,440 バイト）が
  2,097,152 バイトとして扱われる。
- **起きること**: 実効上限を**実際より小さく**見積もる方向の誤差なので、アップロードが失敗する側には倒れない。
  影響は (1) 画面に表示する上限（`2.5 MB` ではなく `2 MB`）がずれる、
  (2) チャンクサイズがやや小さくなり分割回数が増える、の2点。
- **発生条件**: php.ini に小数付きのサイズ表記を書いた場合。
  PHP のマニュアルでは整数＋単位を推奨しており、実運用ではまれ。
- **決定**: **仕様とする**。整数＋単位の表記のみを解釈し、小数部は切り捨てる。
  実効上限を小さく見積もる方向のため、アップロードが失敗する側には倒れない（TS-06 S-01b）。

#### Q-27: 送信しなかった書類区分が既定値に戻る

- **現象**: `saveCategoryModules()` は受け取ったキーだけで `$saved` を組み立て、
  `saveCategoryTransactionModules($saved)` が設定値を**丸ごと置き換える**。
  読み出し時に「設定に無い区分は既定値」とマージされるため、
  送信しなかった区分のカスタマイズが失われる。
- **起きること**: 例えば「契約書」を独自設定にした後、別の画面や API から「請求書」だけを送信して保存すると、
  契約書の設定が既定値へ戻る。**利用者には何も通知されない**ため、
  後日「契約書が不適合になった」という形で表面化する（原因を追いにくい）。
- **発生条件**: 現状の設定画面は常に全区分を送るため発生しない。
  API を直接呼ぶ場合、画面を部分更新に変えた場合、複数タブで同時に編集した場合に発生する。
- **決定**: (a) マージ方式に変更。現在の設定を引き継ぎ、指定された区分だけを上書きする。
  「モジュールを全部外す」は**空配列を明示指定**することで表現する（キーの有無で区別）。

#### Q-28: `{}`（空オブジェクト）で全区分が既定値に戻る

- **現象**: Q-27 の極端なケース。`category_modules={}` は `is_array()` を通るため検証を抜け、
  ループが1回も回らず空の設定が保存される。結果として全区分が既定値に戻る。
- **起きること**: 「設定をすべてクリアしたい」と思って空を送ると、
  クリアではなく**既定値へのリセット**になる。意図と結果が食い違う。
  誤送信（フロントのバグで空になる等）でも黙って全設定が失われる。
- **発生条件**: API に `{}` または空配列を送った場合。
- **決定**: **エラー**にする。マージ方式では `{}` に更新対象が無く、正当な要求になり得ないため。
  区分ごとのクリアは空配列の明示指定で行う（Q-27 と整合）。

#### Q-29: 201件目以降が黙って捨てられる

- **現象**: `getRecordIds()` が `array_slice($recordIds, 0, 200)` で切り詰める。
  戻り値の `linked` / `skipped` / `denied` のどれにも超過分は現れない。
- **起きること**: 300件を指定すると 200件だけ紐づき、レスポンスは `linked: 200` で**成功に見える**。
  呼び出し側は残り100件が処理されなかったことを検知できず、
  電帳法の関連付け漏れ（＝不適合）が静かに残る。
- **発生条件**: 現在の画面は1ページ10件表示で、200件を超える選択は現実的でない。
  API を直接呼ぶ場合や、将来「全件選択」を実装した場合に問題になる。
- **決定**: (c) 上限を撤廃し、指定された全件を処理する。
  1リクエストの件数は PHP の `max_input_vars` / `post_max_size` で自然に制限される。

#### Q-30: 判定基準を変えても既存の適合状態は変わらない

- **現象**: `compliance_status` は `check()` を実行した時点の判定結果を保存している。
  設定（書類区分ごとの取引モジュール）を変更しても、既存ドキュメントは再判定されない。
- **起きること**: 設定画面で基準を緩めても一覧の「不適合」バッジが消えず、
  逆に基準を厳しくしても不適合が増えないため、**画面上の適合状態が実態とずれる**。
  監査対応で「適合しているはずのものが不適合と表示される」状況が起こる。
- **緩和策（実装済み）**: 設定画面の「適合状態を再判定」（`mode=recheck_compliance`）と、
  マイグレーション M-13 の自動再判定で解消できる。
  また関連付けの追加・解除時（`RelationAPI`）と適合情報の保存時は、その場で再判定される。
- **決定**: (a) 手動での再判定を仕様とする。件数が多い環境で保存が重くならないことを優先した。
  運用手順を **TS-05「1.1.1 判定基準を変更したときの再判定」** に明記し、
  設定画面には `LBL_RECHECK_NOTE` で注意を表示する（TC-CC-039c〜039e）。

## 8. 記法

| 記号 | 意味 |
|---|---|
| ✓ / − | デシジョン表の条件成立 / 不成立 |
| `*` | デシジョン表の「値を問わない」 |
| ⚠️ 要確認 | 実装と一般的な期待が食い違う可能性がある箇所。仕様判断が必要 |
| N/A | 検討したうえで対象外とした観点（理由を併記） |

## 9. テストレベルの方針

テストピラミッドに従い、同一のバグを複数層で重複検証しない。

| 層 | 責務 | 本書での主対象 |
|---|---|---|
| ユニット | 純粋なロジック（日付計算・サイズ計算・文字列整形） | TS-01, TS-03 の計算部, TS-06 のサイズ計算 |
| 結合（API） | ステータス・レスポンス形・認可・DB 反映・冪等性 | TS-02, TS-04, TS-05, TS-06, TS-08 |
| コンポーネント | ローディング / 成功 / エラー / 空 / 権限なしの表示、ユーザー操作 | TS-10 |
| E2E | クリティカルパスのみ（登録→一覧→詳細→ダウンロード） | TS-12 |
