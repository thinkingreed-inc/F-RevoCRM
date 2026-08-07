# TS-08 ドキュメント API（一覧・詳細・フォルダ・スター）とフォルダ権限

対象:

- `modules/Documents/apis/ListAPI.php`
- `modules/Documents/apis/DetailAPI.php`
- `modules/Documents/apis/FolderAPI.php`
- `modules/Documents/apis/StarAPI.php`
- テーブル `vtiger_folder_permissions` / `vtiger_attachmentsfolder.parent_folderid`

## 1. 期待される振る舞い（仕様）

### 1.1 共通

| # | 仕様 |
|---|---|
| S-01 | すべて JSON のみを返す（HTML を返さない） |
| S-02 | Documents の `DetailView` 権限が必要。権限が無ければ拒否する |
| S-03 | 管理者はすべてのフォルダを参照できる |
| S-04 | 一般ユーザーは、フォルダ権限（`everyone` / `user` / `role` / `group`）に合致するフォルダのみ参照できる |
| S-05 | 権限の無いフォルダのドキュメントは、一覧にも詳細にも現れない（存在の有無も分からない） |

### 1.2 一覧 API

| # | 仕様 |
|---|---|
| S-10 | ページングは `page`（1始まり）と `pageLimit`（既定20・最大100）。上限を超える指定は**上限に丸める** |
| S-11 | ソート項目はホワイトリストに限定し、それ以外は更新日時にフォールバックする |
| S-12 | ソート順は `ASC` / `DESC` のみ |
| S-13 | 検索はタイトル・ファイル名・抽出テキスト（`indexed_content`）の部分一致。<br>入力中の `%` `_` は**文字として扱う**（ワイルドカードにしない） |
| S-14 | フォルダ指定は選択フォルダのみ（子フォルダは含めない）。<br>`folder_id` が未指定・`all`・`0` の場合は全フォルダ（**仕様**: 0 は「フォルダ指定なし」を表す） |
| S-15 | 電帳法フィルタ（対象のみ・書類区分・保存区分・適合状態・入力期限状態・未関連のみ）が使える |
| S-16 | カスタムフィールドの値を `dynamic_fields` として返す |
| S-17 | 不適合理由は表示用に翻訳して返す |

### 1.3 詳細 API

| # | 仕様 |
|---|---|
| S-20 | ファイル情報・関連レコード・電帳法情報・監査ログ（直近10件）・ファイルバージョン・フォルダパスを返す |
| S-20b | スターの状態は実行ユーザーの値を返す（一覧 API と同じ） |
| S-21 | 取引系モジュール（受注・請求・発注・見積）の関連レコードには日付・金額のサマリを付ける |
| S-22 | フォルダパスは親をたどってパンくずにする。**階層の深さで打ち切らない**（循環していても停止する） |

### 1.4 フォルダ API

| # | 仕様 |
|---|---|
| S-30 | フォルダはツリー構造（`parent_folderid`）で返す。スター付き件数は実行ユーザーの実数を返す |
| S-31 | フォルダ名は必須。同名のフォルダは作れない |
| S-31b | 親フォルダには、存在するフォルダのみを指定できる。**自分自身・自分の子孫は指定できない**（循環を作らない） |
| S-32 | 新規作成時は既定で「全員・編集可能」の権限を付与する |
| S-33 | サブフォルダまたはドキュメントを含むフォルダは削除できない |
| S-34 | 権限設定の保存は管理者のみ。全件置換方式 |
| S-35 | 権限の付与先候補（有効ユーザー・役割・グループ）を返す |

### 1.5 スター API

| # | 仕様 |
|---|---|
| S-40 | ユーザーごとにスターの ON/OFF を保存する（`vtiger_crmentity_user_field`） |
| S-41 | 存在しない・削除済み・別モジュールのレコードは 404 |

## 2. デシジョン表

### DT-1: 一般ユーザーのフォルダ参照可否

| 条件 | R1 | R2 | R3 | R4 | R5 | R6 |
|---|---|---|---|---|---|---|
| 管理者 | ✓ | − | − | − | − | − |
| `everyone` の権限行がある | * | ✓ | − | − | − | − |
| 自分の `user` 権限行がある | * | * | ✓ | − | − | − |
| 自分の役割の `role` 権限行がある | * | * | * | ✓ | − | − |
| 所属グループの `group` 権限行がある | * | * | * | * | ✓ | − |
| **参照可否** | 可 | 可 | 可 | 可 | 可 | 不可 |
| 対応ケース | TC-AP-060 | TC-AP-061 | TC-AP-062 | TC-AP-063 | TC-AP-064 | TC-AP-065 |

> 一覧・詳細 API の権限判定は `permission_type` を区別せず、`view` / `edit` のいずれかが合致すれば参照できる。
> フォルダツリー API は `view` または `edit` を明示的に確認し、`edit` があれば `can_edit: true` を返す。

### DT-2: `ListAPI` のページング値の正規化

| 条件 | R1 | R2 | R3 | R4 |
|---|---|---|---|---|
| `page` ≥ 1 | − | ✓ | ✓ | ✓ |
| `pageLimit` ≥ 1 | * | − | ✓ | ✓ |
| `pageLimit` ≤ 100 | * | * | − | ✓ |
| **page** | 1 | 指定値 | 指定値 | 指定値 |
| **pageLimit** | 既定20 or 指定値 | 20 | 20 | 指定値 |
| 対応ケース | TC-AP-010 | TC-AP-011 | TC-AP-012 | TC-AP-001 |

### DT-3: `ListAPI` のフィルタ組み合わせ

| 条件 | R1 | R2 | R3 | R4 | R5 |
|---|---|---|---|---|---|
| `folder_id` が空 or `'all'` | ✓ | − | * | * | * |
| `filter_type = 'starred'` | − | − | ✓ | * | * |
| `search_keyword` あり | − | − | − | ✓ | * |
| `has_related_record` が `'false'` / `'0'` | − | − | − | − | ✓ |
| **抽出** | 全フォルダ | 指定フォルダのみ | 自分がスターを付けたもの | タイトル/ファイル名/本文の部分一致 | 電帳法対象かつ関連レコード無し |
| 対応ケース | TC-AP-020 | TC-AP-021 | TC-AP-022 | TC-AP-023 | TC-AP-026 |

複数条件は AND で結合する（TC-AP-027）。
関連付け候補用の `exclude_parent_id` / `active_only` は **TS-13 DT-3** を参照（同じ AND 条件として結合される）。

### DT-4: `FolderAPI mode=delete`

| 条件 | R1 | R2 | R3 |
|---|---|---|---|
| サブフォルダがある | ✓ | − | − |
| ドキュメントがある | * | ✓ | − |
| **結果** | 例外 `LBL_FOLDER_HAS_SUBFOLDERS` | 例外 `LBL_FOLDER_HAS_DOCUMENTS` | 削除 |
| 対応ケース | TC-AP-042 | TC-AP-043 | TC-AP-041 |

### DT-5: `FolderAPI mode=savePermissions` の1エントリの扱い

| 条件 | R1 | R2 | R3 | R4 |
|---|---|---|---|---|
| `permission_type` が `view`/`edit` | − | ✓ | ✓ | ✓ |
| `target_type` が `everyone`/`user`/`role`/`group` | * | − | ✓ | ✓ |
| `target_type == 'everyone'` | * | * | ✓ | − |
| `target_id` が空でない | * | * | * | ✓ |
| **結果** | 無視 | 無視 | `target_id=NULL` で登録 | 登録 |
| 対応ケース | TC-AP-051 | TC-AP-051 | TC-AP-050 | TC-AP-050 |

> `target_type != 'everyone'` かつ `target_id` が空のエントリは無視される（`inserted` に数えない）。

### DT-6: `StarAPI` の `starred` パラメータ

| 入力 | 保存値 |
|---|---|
| `true`（boolean） / `'true'` / `'1'` | `'1'` |
| `false` / `'false'` / `'0'` / `''` / 未指定 / `'TRUE'` | `'0'` |

## 3. 境界値・同値分割

### BV-1: ページング

| 入力 | 期待 |
|---|---|
| `page` 未指定 | 1 |
| `page=0` / `page=-1` / `page='abc'`（→0） | 1 |
| `page=1` | 先頭ページ |
| `page` が総ページ数+1 | `records: []`・`total` は実件数 |
| `pageLimit=0` / `-1` | 20 |
| `pageLimit=1` | 1件 |
| `pageLimit=100` | 100件（上限） |
| `pageLimit=101` / `pageLimit=10000` | 100 に丸める（既定20に戻さない） |

### BV-2: ソート

| `sort_by` | 期待 |
|---|---|
| `title` / `notes_title` / `filename` / `filesize` / `modifiedtime` / `createdtime` / `assigned_user_id` / `filedownloadcount` | 対応カラムでソート |
| `''` / 未指定 / `unknown` | `vtiger_crmentity.modifiedtime` |
| `title; DROP TABLE x` | ホワイトリスト外 → 既定にフォールバック（SQL に連結されない） |
| `sort_order=asc` / `ASC` | `ASC` |
| `sort_order=desc` / 未指定 / 不正値 | `DESC` |

### BV-3: 検索キーワード

| 入力 | 期待 |
|---|---|
| `''` / 未指定 | 検索条件を付けない |
| 1文字 | 部分一致 |
| 該当なし | `records: []`・`total: 0` |
| `%` | `%` を含むタイトルだけが一致（全件一致にならない） |
| `_` | `_` を含むタイトルだけが一致（任意1文字にならない） |
| `!`（エスケープ文字自身） | `!` を含むタイトルだけが一致（二重エスケープされない） |
| `50%_off` | この文字列を含むものだけが一致 |
| `'` を含む | プレースホルダのため SQL エラーにならない |
| マルチバイト | 一致する |
| 抽出テキスト（`indexed_content`）にのみ含まれる語 | 該当ドキュメントが返る |

### BV-4: `folder_id`

| 入力 | 期待 |
|---|---|
| 未指定 / `''` / `'all'` | 全フォルダ |
| `0` | 全フォルダ（**仕様**: 0 は「フォルダ指定なし」。フォルダIDは1始まり） |
| 存在するフォルダID | そのフォルダのみ |
| 存在しないID | `records: []` |
| 子フォルダを持つフォルダID | 子のドキュメントは含まない（S-14） |

### BV-5: フォルダ階層

| 状況 | 期待 |
|---|---|
| ルート（`parent_folderid=0`） | ツリーの最上位 |
| 10階層 | パンくずが10件 |
| 15階層 | パンくずが15件（深さで打ち切らない） |
| 循環したデータ（A→B→A）が既に存在 | 同じフォルダを二度たどらずに停止する（無限ループにしない） |
| 自分自身を親に指定 | 例外 `LBL_FOLDER_PARENT_SELF`。保存されない |
| 自分の子孫を親に指定 | 例外 `LBL_FOLDER_PARENT_CIRCULAR`。保存されない |
| 存在しないフォルダIDを親に指定 | 例外 `LBL_FOLDER_PARENT_NOT_FOUND`。保存されない |
| `parent_folderid=0` を指定 | ルートに移動（検証をスキップ） |

### BV-6: フォルダ名

| 入力 | 期待 |
|---|---|
| `''` | 例外 `LBL_FOLDER_NAME_REQUIRED` |
| 既存と同名 | 例外 `LBL_FOLDER_EXISTS` |
| 既存と同名（大文字小文字違い） | DB の照合順序に依存 ⚠️ 要確認 |
| 200文字 | 保存できる（カラム長を確認） |
| `<script>` を含む | 保存され、表示時にエスケープされる |

## 4. テストケース

### 4.1 一覧 API

| ID | 観点 | 前提 | 手順 | 期待結果 | 自動化 |
|---|---|---|---|---|---|
| TC-AP-001 | 正常系 | ドキュメント25件 | `api=ListAPI&page=1&pageLimit=10` | 10件・`total:25`・`page:1`・`pageLimit:10` | 自動 |
| TC-AP-002 | 正常系 | 同上 | `page=3&pageLimit=10` | 5件 | 自動 |
| TC-AP-003 | 空 | 0件 | 同上 | `records: []`・`total: 0`（エラーにしない） | 自動 |
| TC-AP-004 | 応答形（S-16） | カスタムフィールドあり | 一覧取得 | `dynamic_fields` にカスタム項目が入る | 自動 |
| TC-AP-005 | 応答形（S-17） | 不適合ドキュメント | 一覧取得 | `compliance.compliance_notes` が翻訳済み文字列 | 自動 |
| TC-AP-006 | 応答形 | 電帳法対象外 | 一覧取得 | `compliance: null` | 自動 |
| TC-AP-007 | 応答形 | 内部ファイルあり | 一覧取得 | `download_url` が生成される | 自動 |
| TC-AP-008 | 応答形 | 外部URL | 一覧取得 | `download_url` が空 | 自動 |
| TC-AP-009 | 応答形 | ファイル未添付（`filestatus=0`） | 一覧取得 | `download_url` が空 | 自動 |
| TC-AP-010 | 境界値（BV-1） | − | `page=0` / `page=-1` | 1ページ目 | 自動 |
| TC-AP-011 | 境界値（BV-1） | − | `pageLimit=0` | 20件 | 自動 |
| TC-AP-012 | 境界値（BV-1） | 件数150件 | `pageLimit=101` / `pageLimit=10000` | 100件（上限に丸める） | 自動 |
| TC-AP-013 | 境界値（BV-1） | − | `pageLimit=100` | 100件 | 自動 |
| TC-AP-014 | 境界値（BV-2） | − | `sort_by=filesize&sort_order=asc` | サイズ昇順 | 自動 |
| TC-AP-015 | セキュリティ（BV-2） | − | `sort_by=title;DROP TABLE vtiger_notes--` | SQL エラーにならず既定ソート。テーブルは無事 | 自動 |
| TC-AP-016 | セキュリティ（BV-2） | − | `sort_order=; DELETE FROM x` | `DESC` にフォールバック | 自動 |
| TC-AP-020 | フィルタ（DT-3 R1 / BV-4） | − | `folder_id=all` / 未指定 | 全フォルダ | 自動 |
| TC-AP-021 | フィルタ（DT-3 R2） | フォルダA に2件・B に3件 | `folder_id=<A>` | 2件 | 自動 |
| TC-AP-021b | フィルタ（S-14） | A の子フォルダに1件 | `folder_id=<A>` | 子は含まれない | 自動 |
| TC-AP-022 | フィルタ（DT-3 R3） | 自分がスターを付けたもの1件 | `filter_type=starred` | 1件。**他ユーザーのスターは含まれない** | 自動 |
| TC-AP-023 | フィルタ（DT-3 R4 / S-13） | タイトルに `契約` を含む1件 | `search_keyword=契約` | 1件 | 自動 |
| TC-AP-024 | フィルタ（S-13） | 本文抽出テキストにのみ含まれる語 | 同上 | 該当ドキュメントが返る | 自動 |
| TC-AP-025 | 境界値（BV-3 / S-13） | タイトル `50%OFF案内` と `通常案内` が存在 | `search_keyword=%` | `50%OFF案内` のみ（全件一致にしない） | 自動 |
| TC-AP-025b | 境界値（BV-3） | タイトル `A_B` と `AXB` が存在 | `search_keyword=A_B` | `A_B` のみ（`AXB` は一致しない） | 自動 |
| TC-AP-025c | 境界値（BV-3） | タイトルに `!` を含むものが存在 | `search_keyword=!` | それだけが一致（二重エスケープされない） | 自動 |
| TC-AP-026 | フィルタ（DT-3 R5） | 電帳法対象・関連付けなし1件 | `has_related_record=false` | その1件のみ | 自動 |
| TC-AP-027 | 複合 | − | `folder_id` + `search_keyword` + `compliance_status` | AND で絞り込まれる | 自動 |
| TC-AP-028 | フィルタ | − | `compliance_filter=1` | 書類区分が設定されたものだけ | 自動 |
| TC-AP-029 | フィルタ | − | `document_category` / `preservation_type` / `input_deadline_status` | それぞれ一致するものだけ | 自動 |
| TC-AP-030 | 応答形 | − | `mode=columns` | 表示可能な項目一覧（name/label/field_type/uitype/is_custom/is_mandatory） | 自動 |
| TC-AP-031 | 関連リスト | 取引先に紐づくドキュメント2件 | `parent_id=<取引先ID>` | その2件のみ | 自動 |
| TC-AP-032 | 候補絞り込み | 関連付け候補の取得 | `exclude_parent_id` / `active_only` | **TS-13 TC-RL-040〜048** を参照 | 自動 |

### 4.2 詳細 API

| ID | 観点 | 前提 | 手順 | 期待結果 | 自動化 |
|---|---|---|---|---|---|
| TC-AP-035 | 正常系 | 内部ファイルのドキュメント | `api=DetailAPI&record=<id>` | ファイル情報・`download_url`・`preview_url`・`folder_path` を返す | 自動 |
| TC-AP-036 | 正常系（S-20） | 監査ログ15件 | 同上 | `audit_log` が10件（新しい順） | 自動 |
| TC-AP-037 | 正常系（S-20） | v1〜v3 | 同上 | `file_versions` が3件・降順・各 `download_url` 付き | 自動 |
| TC-AP-038 | 正常系（S-21） | 請求書に関連付け | 同上 | `related_records[].summary` に日付・金額 | 自動 |
| TC-AP-039 | 境界値（S-21） | 取引先（サマリ対象外）に関連付け | 同上 | `summary: null`（エラーにしない） | 自動 |
| TC-AP-040 | 異常系 | − | `record` 未指定 | HTTP 400 | 自動 |
| TC-AP-040b | 異常系 | − | `record=999999` / 削除済み | 例外 `Document not found` | 自動 |
| TC-AP-040c | 境界値（BV-5 / S-22） | 15階層のフォルダ | 詳細取得 | パンくずが15件（打ち切らない） | 自動 |
| TC-AP-040e | 境界値（BV-5） | 循環した親子関係のデータ | 詳細取得 | 無限ループにならず応答が返る | 自動 |
| TC-AP-040d | 応答形（S-20b） | 実行ユーザーがスターを付けたドキュメント | 詳細取得 | `starred: true`（一覧 API と一致） | 自動 |
| TC-AP-040f | 応答形（S-20b） | スターなし／他ユーザーがスターを付けたもの | 詳細取得 | `starred: false` | 自動 |
| TC-AP-040g | 応答形（S-20b） | スターを付け外しした直後 | 詳細を再取得 | 状態が追従する | 自動 |

### 4.3 フォルダ API

| ID | 観点 | 前提 | 手順 | 期待結果 | 自動化 |
|---|---|---|---|---|---|
| TC-AP-041 | 正常系（DT-4 R3） | 空のフォルダ | `mode=delete&folderid=<id>` | 削除される | 自動 |
| TC-AP-042 | 異常系（DT-4 R1） | サブフォルダあり | 同上 | 例外 `LBL_FOLDER_HAS_SUBFOLDERS`。削除されない | 自動 |
| TC-AP-043 | 異常系（DT-4 R2） | ドキュメントあり | 同上 | 例外 `LBL_FOLDER_HAS_DOCUMENTS`。削除されない | 自動 |
| TC-AP-044 | 異常系 | − | `mode=delete` で `folderid` 未指定 | 例外 | 自動 |
| TC-AP-045 | 正常系（S-31） | − | `mode=save&foldername=営業資料` | 作成され `folder.id` を返す | 自動 |
| TC-AP-046 | 異常系（BV-6） | − | `foldername=''` | 例外 `LBL_FOLDER_NAME_REQUIRED` | 自動 |
| TC-AP-047 | 異常系（BV-6） | 同名フォルダあり | `mode=save` | 例外 `LBL_FOLDER_EXISTS` | 自動 |
| TC-AP-048 | 正常系（S-32） | − | 新規作成の直後に `mode=getPermissions` | `everyone` / `edit` が1件登録されている | 自動 |
| TC-AP-048b | 正常系（S-30） | 親フォルダを指定して作成 | `mode=tree` | `parent_id` が設定されて返る | 自動 |
| TC-AP-048c | 異常系（BV-5 / S-31b） | フォルダA | 自分自身を親に指定して `mode=save&savemode=edit` | 例外 `LBL_FOLDER_PARENT_SELF`。`parent_folderid` が変わらない | 自動 |
| TC-AP-048d | 異常系（BV-5 / S-31b） | A → B（子） | A の親に B を指定 | 例外 `LBL_FOLDER_PARENT_CIRCULAR`。保存されない | 自動 |
| TC-AP-048e | 異常系（BV-5 / S-31b） | A → B → C（孫） | A の親に C を指定 | 例外 `LBL_FOLDER_PARENT_CIRCULAR` | 自動 |
| TC-AP-048f | 異常系（BV-5） | − | 存在しないフォルダIDを親に指定 | 例外 `LBL_FOLDER_PARENT_NOT_FOUND` | 自動 |
| TC-AP-048g | 正常系（BV-5） | B → A（親子） | B の親を 0（ルート）に変更 | 成功する（正当な階層変更を妨げない） | 自動 |
| TC-AP-049 | 正常系（S-30） | フォルダ3件・各件数あり | `mode=tree` | `folders[]` に `count`・`can_edit`・`totalCount` を返す | 自動 |
| TC-AP-049b | 応答形（S-30） | 実行ユーザーがスターを2件付けている | `mode=tree` | `starredCount: 2` | 自動 |
| TC-AP-049c | 応答形（S-30） | 他ユーザーだけがスターを付けている | `mode=tree` | `starredCount: 0`（ユーザーごとに数える） | 自動 |
| TC-AP-049d | 応答形（S-30） | スター付きドキュメントを削除（`deleted=1`） | `mode=tree` | 件数に含まれない | 自動 |
| TC-AP-050 | 正常系（DT-5 R3/R4） | 管理者 | `mode=savePermissions` に `everyone/view` と `user/edit`（target_id あり） | `inserted: 2`。既存権限は置換される | 自動 |
| TC-AP-051 | 境界値（DT-5 R1/R2） | 管理者 | 不正な `permission_type` / `target_type` / `target_id` 空のエントリ | 無視され `inserted` に数えない | 自動 |
| TC-AP-052 | 境界値 | 管理者 | `permissions=[]`（空配列） | すべての権限が削除される。一般ユーザーから見えなくなる ⚠️ 要確認（意図した挙動か） | 自動 |
| TC-AP-053 | 異常系 | 管理者 | `permissions` が JSON でない | 例外 `Invalid permissions data`。既存権限は削除されない | 自動 |
| TC-AP-054 | 認可（S-34） | 一般ユーザー | `mode=savePermissions` | 例外 `Admin permission required`。既存権限が変わらない | 自動 |
| TC-AP-055 | 正常系（S-35） | − | `mode=getPermissionTargets` | 有効ユーザー・役割（階層インデント付き）・グループを返す。無効/削除済みユーザーは含まない | 自動 |
| TC-AP-056 | 冪等性 | 同じ権限で2回保存 | `mode=savePermissions` | 権限行が重複しない | 自動 |

### 4.4 フォルダ権限による参照制御

| ID | 観点 | 前提 | 手順 | 期待結果 | 自動化 |
|---|---|---|---|---|---|
| TC-AP-060 | 正常系（DT-1 R1） | 管理者 | 一覧・詳細・ツリー | すべてのフォルダ・ドキュメントが見える | 自動 |
| TC-AP-061 | 正常系（DT-1 R2） | `everyone/view` のみ | 一般ユーザーで一覧 | 当該フォルダのドキュメントが見える | 自動 |
| TC-AP-062 | 正常系（DT-1 R3） | 自分の `user` 権限のみ | 同上 | 見える | 自動 |
| TC-AP-063 | 正常系（DT-1 R4） | 自分の役割の `role` 権限のみ | 同上 | 見える | 自動 |
| TC-AP-064 | 正常系（DT-1 R5） | 所属グループの `group` 権限のみ | 同上 | 見える | 自動 |
| TC-AP-065 | 認可（DT-1 R6 / S-05） | 権限行なしのフォルダ | 一般ユーザーで一覧 | そのドキュメントが**1件も返らない**。`total` にも含まれない | 自動 |
| TC-AP-066 | 認可（IDOR / S-05） | 同上 | そのドキュメントIDで詳細 API | `Document not found`（存在有無を漏らさない） | 自動 |
| TC-AP-067 | 認可 | 同上 | ツリー API | そのフォルダがリストに現れない | 自動 |
| TC-AP-068 | 認可 | `edit` 権限のみ | ツリー API | 参照でき、`can_edit: true` | 自動 |
| TC-AP-069 | 認可 | `view` 権限のみ | ツリー API | 参照でき、`can_edit: false` | 自動 |
| TC-AP-070 | 認可 | 権限を剥奪した直後 | 一覧を再取得 | 即座に見えなくなる（キャッシュしていない） | 自動 |
| TC-AP-071 | 認可 | 役割の階層（上位役割） | 上位役割のユーザー | 下位役割向けの権限では参照できない（役割IDの完全一致） | 自動 |
| TC-AP-072 | 認可 | グループに複数所属 | 一覧 | いずれかのグループ権限があれば参照できる | 自動 |
| TC-AP-073 | 認可 | Documents モジュールの権限なし | 各 API | 拒否される（S-02） | 自動 |

### 4.5 スター API

| ID | 観点 | 前提 | 手順 | 期待結果 | 自動化 |
|---|---|---|---|---|---|
| TC-AP-080 | 正常系（DT-6） | − | `api=StarAPI&record=<id>&starred=true` | `starred: true`。一覧の `starred` が `true` | 自動 |
| TC-AP-081 | 正常系（DT-6） | − | `starred=false` | `starred: false` | 自動 |
| TC-AP-082 | 境界値（DT-6） | − | `starred=1` / `'true'` / 未指定 / `'TRUE'` | `1` / `1` / `0` / `0` | 自動 |
| TC-AP-083 | 冪等性 | − | 同じ値で2回 | 結果が同じ。行が重複しない | 自動 |
| TC-AP-084 | 異常系（S-41） | − | `record` 未指定 | HTTP 400 | 自動 |
| TC-AP-085 | 異常系（S-41） | − | `record=999999` / 削除済み / 他モジュールのID | HTTP 404 `Record not found` | 自動 |
| TC-AP-086 | ユーザー分離（S-40） | ユーザーA がスター | ユーザーB で一覧 | B の `starred` は `false` | 自動 |
| TC-AP-087 | 認可 | 書き込み権限なし | 実行 | 拒否される | 自動 |

## 5. 検討したが対象外とした観点（N/A）

| 観点 | 判断 |
|---|---|
| SQL インジェクション（値パラメータ） | N/A: すべてプレースホルダ。ソート項目のみホワイトリストで対応（TC-AP-015/016 で検証） |
| フォルダ権限の継承（親→子） | N/A: 本実装は継承しない（フォルダ単位に権限を持つ）。仕様として明示 |
| レコード単位の共有ルール（vtiger 標準の共有設定） | N/A: Documents の一覧・詳細 API はフォルダ権限のみで制御している。標準の共有設定との整合は要件外 |
| 一覧の N+1 クエリ | 部分的に N/A: `download_url` 生成でレコードごとに添付を引く。性能要件が出た場合に別途対応 |
| 大量データ（10万件）の性能 | 手動: `COUNT(*)` と `LIKE '%...%'` があるため、必要に応じて実測する |
