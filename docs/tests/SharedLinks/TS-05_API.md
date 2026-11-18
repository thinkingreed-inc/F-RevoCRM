# TS-05 管理画面の API（SharedLinkAPI）

対象: `modules/Settings/SharedLinks/apis/SharedLinkAPI.php`（`?module=SharedLinks&parent=Settings&api=SharedLinkAPI`）

## 1. 期待される振る舞い（仕様）

| # | 仕様 | 仕様書 |
|---|---|---|
| S-01 | システム管理者だけが使える。管理者以外は 403 | 6.4, 8.1 |
| S-02 | `info` / `list` は読み取り。`save` / `delete` / `reorder` は POST と CSRF トークンが必須 | 8.1 |
| S-03 | `info` は差し込める項目・公開範囲の選択肢（役割は階層順、ユーザーは有効なユーザーだけ）・入力の上限・画面の文言を返す | 8.1 |
| S-04 | `list` は無効を含むすべてのリンクを並び順で返し、対象の表示名・削除済みかどうか、項目のラベル・削除されたかどうか、警告の有無を付ける | 8.1 |
| S-05 | `save` は `link`（JSON）を受け取る。`id` が無ければ追加して末尾に並べ、あれば更新（パラメータ・対象はまとめて置き換え） | 8.1 |
| S-06 | `save` で入力に誤りがあれば保存せず、`saved=false` と項目ごとのエラー（翻訳済みの文言）を返す | 8.1 |
| S-07 | `delete` はリンクとパラメータ・対象を削除する | 8.1 |
| S-08 | `reorder` は `ids` の順に並び順を振り直す | 8.1 |
| S-09 | 存在しないリンクの `id`・ウィジェットの `id` / `set_id` は 404。`link` が JSON でない・`ids` に数字以外・不明な mode は 400 | 8.1 |
| S-11 | ウィジェットの作成・見出しの変更・削除（`save_set` / `delete_set`）は TS-08 | 8.1 |
| S-10 | URL の `&` などを加工せずに保存する（`getRaw` で受け取る） | 8.1 |

## 2. デシジョン表

### DT-1: 権限と要求の種類

| 条件 | R1 | R2 | R3 | R4 | R5 |
|---|---|---|---|---|---|
| 管理者 | − | ✓ | ✓ | ✓ | ✓ |
| mode | * | info / list | save / delete / reorder / save_set / delete_set | 同左 | 同左 |
| HTTP メソッド | * | GET / POST | GET | POST | POST |
| CSRF トークン | * | * | * | 無し・不正 | 正しい |
| **結果** | 403 | 受け付ける | 拒否（Invalid request） | 拒否 | 受け付ける |
| ケース | TC-API-001 | TC-API-002 | TC-API-003 | TC-API-004 | TC-API-005 |

### DT-2: save

| 条件 | R1 | R2 | R3 | R4 | R5 | R6 |
|---|---|---|---|---|---|---|
| `link` が JSON の配列 | − | ✓ | ✓ | ✓ | ✓ | ✓ |
| `id` | * | 無し | 存在する | 存在しない | 数字でない | * |
| 入力が妥当 | * | ✓ | ✓ | * | * | − |
| **結果** | 400 | 追加（末尾） | 更新 | 404 | 404 | `saved=false` と errors |
| ケース | TC-API-010 | TC-API-011 | TC-API-012 | TC-API-013 | TC-API-014 | TC-API-015 |

### DT-3: delete / reorder

| mode | 入力 | 結果 | ケース |
|---|---|---|---|
| delete | 存在する id | 削除（パラメータ・対象も） | TC-API-020 |
| delete | 存在しない id / `abc` | 404 | TC-API-021 |
| reorder | `3,1,2` | その順に並ぶ | TC-API-022 |
| reorder | `1,x` | 400 | TC-API-023 |
| reorder | `2,2,1` | 重複を除いて `2,1` の順 | TC-API-024 |
| （不明） | `mode=unknown` | 400 | TC-API-025 |

### DT-4: list の警告

| 条件 | R1 | R2 | R3 | R4 |
|---|---|---|---|---|
| 削除された項目を使うパラメータがある | − | ✓ | − | − |
| 削除された対象がある | − | − | 一部 | すべて |
| **has_warning** | false | true | true | true |
| ケース | TC-API-030 | TC-API-031 | TC-API-032 | TC-API-033 |

## 3. テストケース

| ID | 観点 | 内容 | 期待 | 自動化 |
|---|---|---|---|---|
| TC-API-001 | 認可 | 管理者でないユーザーで info / list / save / delete / reorder / save_set / delete_set | すべて 403（ApiForbiddenException）。DB は変わらない | 自動（Integration / E2E） |
| TC-API-002 | 正常 | 管理者で info / list を GET | 受け付ける | 自動（Integration） |
| TC-API-003 | 異常 | 管理者で save / delete / reorder / save_set / delete_set を GET | `Invalid request` | 自動（Integration） |
| TC-API-004 | セキュリティ | 管理者で save / save_set を CSRF トークン無しの POST | 拒否。保存されない | 自動（E2E） |
| TC-API-005 | 正常 | 管理者で save を CSRF トークン付きの POST | 保存される | 自動（E2E・管理画面の操作） |
| TC-API-006 | 認可 | 未ログインで list | ログインを求められ、データを返さない | 自動（E2E） |
| TC-API-010 | 異常 | `link=not-json` | 400（ApiBadRequestException） | 自動（Integration） |
| TC-API-011 | 正常 | 新規追加を 3 回 | `saved=true` と ID。末尾に並ぶ | 自動（Integration） |
| TC-API-012 | 正常 | 既存の更新 | 内容とパラメータ・対象が置き換わる | 自動（Integration） |
| TC-API-013 | 異常 | `id=999999999` | 404 | 自動（Integration） |
| TC-API-014 | 異常 | `id=abc` | 404 | 自動（Integration） |
| TC-API-015 | 異常 | 誤りを複数含む | `saved=false`、`title`・`url`・`params.0.value`・`targets.0` のエラー。文言は翻訳済み（`LBL_` を含まない）。保存されない | 自動（Integration） |
| TC-API-016 | 正常 | URL `https://a.example.com/?x=1&y=2` を保存 | `&` がエンティティ化されずに保存・返却される | 自動（Integration） |
| TC-API-020〜025 | 正常・異常 | DT-3 の各行 | 表のとおり | 自動（Integration） |
| TC-API-030〜033 | 正常 | DT-4 の各列 | 表のとおり。対象には `name` と `deleted`、パラメータには `label` と `missing` が付く | 自動（Integration） |
| TC-API-034 | 正常 | list の並び | 無効なリンクも含め、並び順で返る | 自動（Integration） |
| TC-API-035 | 正常 | list の `post_blocked` | POST で送り先が `form-action` で許可されていなければ true。許可されていれば false。GET は常に false。ウィジェットに渡すデータにも付く | 自動（Integration） |
| TC-API-040 | 正常 | info の項目 | `id`・`full_name`・`email1` を含み、パスワード類を含まない | 自動（Integration） |
| TC-API-041 | 正常 | info の役割 | 親の後ろに子が並び、`depth` を持つ | 自動（Integration / Unit） |
| TC-API-042 | 正常 | info のユーザー | 有効なユーザーだけ | 自動（Integration） |
| TC-API-043 | 正常 | info の上限・文言 | 上限（見出しの上限を含む）がサーバーの定数と一致。文言は英語ファイルのキーがすべてそろい、翻訳済み | 自動（Integration） |
