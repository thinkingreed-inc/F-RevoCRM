# E2E テストカバレッジ & タスクリスト

F-RevoCRM の E2E（Playwright）テストについて、**どの機能が存在し / どこまでテストできているか / これから何を書くべきか** を一覧化したドキュメント。
新しいテストを追加したら、このファイルの該当行のステータスと「タスク」を更新すること。

- 対象コード時点: `feature/e2e-共通機能追加` ブランチ（2026-07-06 現在）
- 機能一覧の No. は社内の「機能一覧」（1-1〜78-1）に対応。コードから追加で発見した機能は §6 に別掲。
- 調査方法: 既存 `e2e/test/` の読み取り＋ `modules/` のコード調査（業務モジュール / 管理設定 / 横断機能の 3 系統を並行調査）に加え、テスト環境（`E2E_BASE_URL`）に admin でログインして**実画面で裏取り**（設定メニュー・各画面の到達性・詳細画面の固有アクション）。
- **実機検証の前提**: ローカル DB はデータが少なく、レコードが存在するのは `Accounts / Products / Services / Project / Vendors`（＝ `seed.setup.ts` の対象）のみ。そのため詳細画面の固有アクションを実データで確認できたのは Accounts・Vendors 等に限られ、他モジュールは**コード確認済（実データ未検証）**扱い。

## 凡例

| 記号 | 意味 |
|---|---|
| ✅ | テストあり・実行中（グリーン） |
| 🟡 | 部分的（画面表示のみ / CRUD のみで固有機能は未検証、等） |
| ⏭️ | spec は存在するが `test.skip` / `test.describe.skip` で無効化中 |
| ❌ | テストなし（未着手） |
| ❓ | 実装の有無・仕様が要確認 |

---

## 1. 現状サマリ

現在の spec ファイルは 83 本。テストは目的別に 4 ディレクトリへ整理。

| ディレクトリ | 内容 | 状態 |
|---|---|---|
| `test/common/`（34 本） | 全モジュール横断の共通機能（検索/フォロー/タグ+一括タグ/エクスポート/インポート/一括削除+ゴミ箱/クイック作成/クイック編集/一括編集/更新履歴/コメント+一括コメント/関連一覧+関連追加/リスト(CustomView)/概要詳細タブ/クイックプレビュー/カレンダー表示/ログイン失敗/**権限・可視範囲**/**アクション権限**/**項目権限**/**出力権限**/**共有ルール**/**所有者変更**/**タグ絞り込み**/**CustomView条件**/**ページング・列ソート**/**検索・絞り込み**/**更新履歴の削除・復元**/**インポート履歴**/**編集画面の読み取り専用項目表示**） | ✅ 実行中 |
| `test/module/`（11 本） | モジュール固有機能（Accounts 一覧表示/カスタマイズ + 各モジュールの詳細固有アクション: Accounts/Contacts/Leads/Potentials/Vendors/HelpDesk のメール・SMS・変換・作成起動、Calendar 作成編集、メール/PDFテンプレート一覧、**在庫系 Invoice/Quotes/SalesOrder/PurchaseOrder の CRUD + 割引/税/合計の監査**、**カレンダー基本パターン(単一ユーザー: 登録/タイトル・時刻変更/非公開/公開化=`calendar.basic.spec.ts`)**） | 🟡→✅ 起動確認 + 在庫は監査済 + カレンダー基本 |
| `test/admin/*.spec.ts`（36 本） | システム管理画面の C〜I グループ + スモーク（E-02/E-04/F-04/F-08） | ✅/⏭️ 混在 |
| `test/fr.common.spec.ts` | **17 モジュールの新規作成 / 編集 / 削除**（`FrTest` 汎用ドライバ） | ✅ |
| `test/matrix/`（spec 1 本 + ドライバ: capabilities.ts / MatrixTest.ts） | モジュール×機能セル(25 ケース: 再利用系/個人リスト×4/複製×2/詳細編集/ファイル×2/コメント添付/関連×3/共有リスト×2/**マイリスト×2**)を `capabilities.ts` の `ModuleMatrix`(`cases`)で判定し `matrix.spec.ts` が生成実行する能力表ドライバ。**全 29 モジュールを有効化(describe駆動)**。`searchField`(名前列)は describe.labelFields、`relatedSpec`(関連)は親/子 describe から自動導出。使い捨てレコードは Webservice API 優先で作成。汎用ドライバで作成/操作できない特殊モジュール(インベントリ=明細必須 / Calendar / EmailTemplates・PDFTemplates / Portal / Documents・Dailyreports の一部)は reason 付き skip・na に退避(実機能は各専用spec で担保)。フルマトリクスは ~354 パス | ✅(全29モジュール, 特殊ケースは reason 付き skip) |
| `test/general.spec.ts` | トップ（ダッシュボード）要素、サイドバー開閉 | 🟡 表示確認のみ |

> 並行実行対応: `test/common/` `test/module/` はワーカー単位で個別ログインする `fixtures/isolated.ts` を使用し、各テストが専用レコードを作成→操作→削除する。API セッション取得は `auth.setup` で一度だけ行い使い回す。
> **CI 並列度は `workers=4`**（`playwright.config.ts`, `retries=2`）。高並列で顕在化する「待ち条件の脆さ」は条件ベース待ちへ順次根治済み（列検索の All CV 固定 / 保存前のモーダル閉じ保証 / 条件追加ボタンの再試行 / サイドバー CV の「もっと」展開 / 条件値の確定待ち）。CI 実行時間は約 14.8 分（workers=1）→ **6.8 分**（workers=4）。残る `networkidle` 依存は新たな flaky が出たら都度 条件ベース待ちへ置換していく方針。

**共通 CRUD の対象モジュール（17）**: Accounts, Contacts, Potentials, Leads, Products, Assets, Campaigns, Dailyreports, Faq, HelpDesk, PriceBooks, Project, ProjectMilestone, ProjectTask, ServiceContracts, Services, Vendors。

**共通 CRUD（`fr.common` / `FrTest`）の対象外**: 在庫（Inventory）系 **Invoice / Quotes / SalesOrder / PurchaseOrder**。
理由: 明細（productid）を含む作成/編集は汎用ドライバでは表現できないため。→ 明細専用ドライバ `utils/lineitem.ts` を用意し、**CRUD + 割引/税/合計の監査**を `test/module/inventory.spec.ts`（ダイアログ経路）/ `test/module/inventory.lineitem.spec.ts`（インライン検索 + 製品追加/サービス追加）で検証済み（§3 / P2）。
※ かつて「商品検索オートコンプリートが 0 件で明細を作れない」ためブロックされていたが、真因は本体バグ（`getSearchResult` の `label` 曖昧）。**この不具合は main #1704 で修正済み（マージ済）** のため、現在は **品目名インライン検索** でも明細を登録できる。E2E は両経路を検証:（a）**商品ポップアップ(箱アイコン)ダイアログ** → `module/inventory.spec.ts`、（b）**インライン検索 + 製品追加/サービス追加** → `module/inventory.lineitem.spec.ts`。いずれも 有効・価格付き商品を dump に焼き込むこと（`seed-spec.inventory`）が前提。

### 進捗と残り

- ✅ **横断共通機能はひと通り実装**（§2 参照）: 一覧検索 / フォロー / タグ追加削除 / エクスポート / インポート(パターン別) / 一括削除+ゴミ箱 / クイック作成 / クイック編集 / 更新履歴 / コメント / 関連一覧表示 / ログイン失敗。
- 🟡 **モジュール固有機能は主要な「起動/遷移」を検証** — メール送信 / SMS / 組織階層 / 予定・ToDo登録 / **リード昇格（起動+保存）** / 案件のプロジェクト変換 / 見積・受注・発注の作成画面遷移 / チケット→FAQ変換 を Accounts/Contacts/Leads/Potentials/Vendors/HelpDesk で検証（§3）。**保存まで**の相互生成 / PDF エクスポート / 繰り返し請求 / 地図 / iCal は未（§3）。
- ✅ **在庫系 4 モジュール** — CRUD + **割引/税/合計/調整の監査**を実装（`module/inventory.spec.ts`、明細ドライバ `utils/lineitem.ts`）。相互生成 / PDF / 繰返請求は次段（§3 / P2）。
- 🟡 **管理設定** — 未テスト画面 4 種はスモーク追加済み。skip 中（C-04/D-06/F-05/H-01 等）の有効化が残る（§4）。
- 🟡 **認証系** — ログイン失敗は実装済。パスワード再発行・MFA は未（§5）。
- ⚠️ **積み残しの finicky 項目**: 一覧ダブルクリック編集（プレビュー重なり）・パスワード再発行（トグル発火せず）。各行に調査結果を注記。（リード昇格の保存は原因特定し解消済 → §3 / P3）
- 🟡 **【閲覧制限・権限周り】拡充ベースラインで基盤が入り、可視範囲は検証開始**。ベースライン dump に
  **専用テストユーザー + ロール階層 + グループ + 所有者違いのレコード島**を投入し（`seed-spec.json` /
  `setup/scripts/seed_e2e_data.php`、README「拡充ベースラインのデータ」参照）、`loginInIsolatedContext`
  で各ユーザーの可視範囲を突き合わせる E2E を新設した:
  - ✅ **共有ルール（組織 Private / 役割階層 / グループ）** によるレコードの可視範囲（自分/部下/全体）
    → `test/common/common.permission.spec.ts`（Leads を Private 化し、部長=全部下 / 課長=自課 / 課員=自分 /
    グループ=メンバーのみ を件数で検証。期待値は `seedSpec.ts` が唯一の出所）
  - ✅ プロファイル／役割による**モジュール・アクションの可視/不可視**（表示・作成・編集・削除）
    → `test/common/common.permission-action.spec.ts`（Sales Profile を複製し Accounts の権限だけ書換えた
    3 ペルソナ: 非表示=権限拒否画面 / 閲覧のみ=追加・編集・削除ボタン無 / 削除不可=削除だけ無。
    制限された操作の UI 要素はサーバ側で DOM から除外されるため `toHaveCount(0)` で判定。dump ビルド時に
    `isPermitted` で期待可否を一致確認済み）
  - ✅ **項目レベルアクセス**（この項目だけ 見えない/編集できない）
    → `test/common/common.permission-field.spec.ts`（複製プロファイルで `phone`=非表示 /
    `website`=編集不可。詳細で見える/編集画面に出る の 3 状態を切り分け: 通常=両方○ / readonly=詳細のみ○ /
    hidden=両方✗。`profile2field.visible=1`→非表示・`readonly=1`→編集不可）
  - ✅ **カスタム共有ルール（datashare ROLE→ROLE）**
    → `test/common/common.sharing-rule.spec.ts`（何も所有しない観測者ロールへ `Leads: MGRA ロール → 観測者
    read-only` を共有。観測者は MGRA 所有の 4 件だけ見え、MGRB/配下 REPA は見えない=ルールは共有元ロール限定。
    既存の階層テストの件数は不変）
  - ✅ **エクスポート/インポート権限（profile2utility）** → `test/common/common.permission-export.spec.ts`
    （Export 許可ペルソナ=リンク有/Import 画面可、既定拒否の Sales ユーザー=リンク無/Import 権限拒否）
  - ✅ **所有者変更（Transfer Ownership）** → `test/common/common.masstransfer.spec.ts`
    （詳細から別ユーザーへ所有者変更し、担当が変わることを確認）
  - ✅ **組織共有の設定画面（C-04）** → `test/admin/admin.C-04.SharingAccess.spec.ts`（読み取りで Leads=非公開 /
    Accounts=公開 を確認。保存の全体再計算レースを避け skip 解消）
  → 権限まわりは概ね網羅。残りは項目単位の write 権限差分など細部のみ。
- ✅ **モジュール横断の能力表マトリクス（`test/matrix/`）を全 29 モジュールへ展開**。共通機能 25 ケース
  （一覧再利用系 / 個人リスト×4 / 複製×2 / 詳細編集 / ファイル×2 / コメント添付 / 関連×3 /
  共有リスト×2 / **マイリスト×2**）を `capabilities.ts` の `ModuleMatrix`(`module`/`app`/`enabled`/`cases`)
  で宣言し `test/matrix/matrix.spec.ts` が生成実行する。ドライバ(`MatrixTest`)を **describe 駆動**化し、
  `searchField`(名前列)を `describe.labelFields`、`relatedSpec`(関連の親→子参照)を親/子 describe から
  自動導出。使い捨てレコードは Webservice API 優先で作成する。全 29 モジュール(顧客担当者/案件/リード/
  製品/サービス/ドキュメント/プロジェクト/タスク/マイルストーン/資産/契約/発注先/価格表/発注/受注/
  請求/見積/キャンペーン/日報/カレンダー/チケット/FAQ/メール・PDFテンプレート/SMS通知/ブックマーク(Portal)
  + 新規モジュール/申請)を `enabled: true` で実行。フルマトリクスは **~354 パス**。
  - **汎用ドライバで作成/操作できない特殊モジュールは reason 付き skip / na に退避**(実機能は各専用spec で担保):
    インベントリ(Invoice/Quotes/SalesOrder/PurchaseOrder=明細必須) / Calendar(日時ウィジェット・CV作成) /
    EmailTemplates・PDFTemplates(テンプレート専用エディタ) / Portal(ブックマーク簡易UI) /
    Documents(filelocationtype・自身にDocuments関連なし) / Dailyreports(カスタム詳細・名前列が一覧列に無い・
    複製アクション無し・コメント欄が概要に出ない) / ProjectTask・Faq(コメント欄が概要に出ない) / Faq(複製の
    名前列が textarea)。
  - ✅ **解消済み**: `list.cv.shared.other`(共有 CustomView 別ユーザー表示)/ `list.cv.mine.other`
    (マイリスト別ユーザー非表示)は、`CustomView_Record_Model::getAll()` の非 admin 向け SQL が参照する
    `vtiger_cv2role`/`vtiger_cv2rs` の欠落が原因で全非 admin の CustomView が 0 件になる不具合だったが、
    追加マイグレーション `setup/migration/scripts/20260709161603_add_missing_cv2role_cv2rs_tables.php` を
    コミットし、ローカル(reset-local-db.sh)・CI(run-e2e.sh)とも dump 投入後の `run_migration.php --all` で
    自動適用されるようにして **run へ復帰**。
- ✅ **カレンダー基本パターン(単一ユーザー分)を実装** → `test/module/calendar.basic.spec.ts`(+ `utils/calendar.ts`)。
  標準 Edit フォームで扱える 時間区切り予定の 登録 / タイトル変更 / 時刻変更 / 非公開登録 / 公開化 を、
  入力値=API 保存値で検証し、Calendar リストビュー(件名の列検索)で登録・表示を確認する。
  - ⏭️ **カレンダー 次段**: 終日 / 繰り返し(日週月年) / 複製 / 招待 / 他者予定の削除(#864) /
    共有カレンダーの別ユーザー表示 / その他追加機能(#1186 他ユーザーのカレンダー設定変更, #1191 共有メモ,
    #1193 マイグループ表示記憶)。終日・繰り返し・招待は FullCalendar(v3)オーバーレイ UI(JS 駆動)が、
    共有・招待は複数ユーザーのカレンダー共有設定が必要。
  - ⏭️ **対象外/保留**: MFA 記憶(#1397)=不要(対象外)。管理者(ID=1)削除禁止=第2管理者フィクスチャ要のため保留。

---

## 2. 共通機能（全モジュール横断）

一覧・詳細・登録の共通挙動。実装は `modules/Vtiger/` と `layouts/v7/modules/Vtiger/`。

| No. | 機能 | 実装（代表） | 状態 | spec / タスク |
|---|---|---|---|---|
| 2-1 | 一覧表示 | `modules/Vtiger/views/List.php` | 🟡 | Accounts のみ表示確認(`module/account`)。汎用の一覧表示検証は未 |
| 2-4 | 一覧からの検索（列検索） | `ListViewContents.tpl` / `List.js` | ✅ | `common/common.search.spec.ts` |
| 2-2 | リスト機能（個人 / 共有 / マイ CustomView） | `modules/CustomView/` | ✅ | `common/common.customview.spec.ts`（個人リストの 作成 / 切替 / 複製 / 削除）+ `test/matrix/matrix.spec.ts`（全モジュールで 個人×4 / 共有(自分・別ユーザー) / マイ(自分・別ユーザー非表示)）。別ユーザー表示は cv2role/cv2rs マイグレーション追加で run 化済み |
| 3-1 | フォロー（☆ / Watching） | 一覧 `a.markStar` / 詳細 `#starToggle` | ✅ | `common/common.follow.spec.ts`（一覧・詳細トグル。絞り込みは未） |
| 4-1 | タグ 付与 / 変更 / 削除 | `DetailViewTagList.tpl` / `Tag.js` | 🟡 | `common/common.tag.spec.ts`（詳細の追加+×削除）+ `common/common.masstag.spec.ts`（一覧選択→一括付与）。タグ変更・フォロー絞り込みは未 |
| 5-1 | PDF エクスポート | `modules/Vtiger/actions/ExportPDF.php` | ❌ | 在庫系詳細の「その他」→ PDF 出力（要 PDF テンプレート・在庫レコード。P2） |
| 6-1/6-2 | 概要 / 詳細画面 | `modules/Vtiger/views/Detail.php` | ✅ | `common/common.summary.spec.ts`（概要タブで主要項目、詳細タブへ切替で全項目表示） |
| 7-1 | 関連一覧 | `li.tab-item[data-module]` / RelatedList | ✅ | `common/common.relatedlist.spec.ts`（表示）+ `common/common.relatedadd.spec.ts`（「追加」から関連レコード作成）+ `test/matrix/matrix.spec.ts`(Accounts→Contacts)で **関連タブの検索/検索リセット/関連レコード詳細への遷移** を追加検証 |
| 8-1 | 更新履歴（ModTracker） | `modules/ModTracker/` | ✅ | `common/common.history.spec.ts` |
| 9-1 | コメント（ModComments） | `modules/ModComments/` | ✅ | `common/common.comment.spec.ts`（詳細投稿）+ `common/common.masscomment.spec.ts`（一覧選択→一括コメント）+ `test/matrix/matrix.spec.ts`(Accounts) で **コメントへのファイル添付** を追加検証 |
| — | レコード複製（一覧・詳細） | 詳細「その他」→ LBL_DUPLICATE / `utils/duplicate.ts` | ✅(Accounts) | `test/matrix/matrix.spec.ts`（一覧経由の複製→一覧に2件表示、詳細経由の複製→複製先詳細に元内容を反映。**添付ファイルの引き継ぎは非検証**）。他モジュールは `capabilities.ts` の展開ゲートで段階有効化 |
| — | ファイル（アップロード / ダウンロード, Documents 経由） | `utils/documentsFile.ts` | ✅(Accounts) | `test/matrix/matrix.spec.ts`（詳細からファイルをアップロードして表示確認、アップロード済ファイルをダウンロードして非空を確認）。他モジュールは展開ゲートで段階有効化 |
| 10-1 | CSV エクスポート | `modules/Vtiger/actions/ExportData.php` | ✅ | `common/common.export.spec.ts` |
| 11-1 | CSV インポート | `modules/Import/` | ✅ | `common/common.import.spec.ts`（複数行/ヘッダなし/スキップ/上書き/マージ。マッピング保存・他モジュールは未） |
| 12-1 | 一覧から登録（EditView） | `modules/Vtiger/views/Edit.php` | ✅ | 17 モジュールで実施済（`FrTest`） |
| 12-2 | クイック作成（＋ボタン） | `views/QuickCreateAjax.php`（React WC） | ✅ | `common/common.quickcreate.spec.ts` |
| 12-2 | 編集（編集画面） | 同上 | ✅ | 17 モジュールで実施済 |
| 12-2 | クイック編集（鉛筆） | インライン edit | ✅ | `common/common.quickedit.spec.ts`（概要の1項目編集） |
| 12-2 | 一括編集（Mass Edit） | `ListViewActions.tpl` LBL_EDIT / MassEdit | ✅ | `common/common.massedit.spec.ts`（一覧選択→項目を編集対象に含め値設定→保存を詳細で確認） |
| 6-3 | クイックプレビュー | 一覧行 `a.quickView` / `.quickPreview` | ✅ | `common/common.quickpreview.spec.ts`（目アイコンで主要項目のプレビュー表示） |
| 12-2 | 一覧からの更新（ダブルクリック） | `List.js` inline edit | ❌ | 調査済・未採用（dblclick でクイックプレビューが重なりハング。要安定化） |
| 12-3 | 削除 / 一括削除 | 詳細「その他」/ 一覧ゴミ箱 | ✅ | 単体は `FrTest`、一括削除は `common/common.recyclebin.spec.ts` |
| 13-1 | ダッシュボード / ウィジェット | `modules/Home/views/DashBoard.php` | 🟡 | 表示確認のみ(`general`)。ダッシュボード追加・ウィジェット追加/削除は未 |
| 14-1 | カレンダー（月/週/日/概要） | `modules/Calendar/views/Calendar.php` | 🟡 | `common/common.calendarview.spec.ts`（月/週/日/概要 の表示モード切替）+ イベント作成/編集は `module/calendar.spec.ts`。iCal・カンバンは未 |
| 35-1 | ゴミ箱（復元 / 完全削除） | `modules/RecycleBin/` | ✅ | `common/common.recyclebin.spec.ts`（削除→復元→完全削除） |
| — | グローバル検索 | `modules/Vtiger/apis/SearchRecords.php` | ✅ | `common/common.globalsearch.spec.ts`（ヘッダー検索→統合検索結果にヒット） |

---

## 3. 業務モジュール（固有機能）

各モジュールの **基本 CRUD** と **モジュール固有機能** を分けて管理。CRUD は上表 12-x に準拠。

| No. | モジュール | CRUD | 固有機能（実装） | 固有機能テスト | タスク |
|---|---|---|---|---|---|
| 18 | 顧客企業 Accounts | ✅ | 組織階層, SMSを送る, 予定/TODOの登録, 担当の変更, 複製, メール, 地図(Google) | 🟡 メール/組織階層/SMS/予定・TODO登録 の起動を検証 → `module/account.spec.ts` | 地図表示、担当変更/複製 |
| 17 | 顧客担当者 Contacts | ✅ | メール, 活動, ToDo, SMS, 地図, vCard インポート | 🟡 メール/SMS の起動を検証 → `module/contacts.spec.ts` | 地図（vCard インポートは本ビルドに機能なし） |
| 16 | リード Leads | ✅ | **昇格 `ConvertLead`**, メール, 活動, ToDo, SMS, 地図 | ✅ メール/SMS/予定・TODO 起動 + **昇格の保存(顧客企業作成)** を検証 → `module/leads.spec.ts` | 地図、昇格時の案件(Potentials)同時作成 |
| 19 | 案件 Potentials | ✅ | 見積/請求/受注作成, **プロジェクト変換 `ConvertPotential`**, 活動, ToDo | 🟡 メール/プロジェクト変換モーダル/見積・受注 作成画面遷移 の起動を検証 → `module/potentials.spec.ts` | 見積/請求/受注 **生成の保存**、活動/ToDo |
| 20 | 製品 Products | ✅ | 見積/請求/発注/受注作成, 在庫管理, 自動計算, SubProducts | ❌ | 在庫・自動計算、各ドキュメント生成 |
| 21 | サービス Services | ✅ | 見積/請求/発注/受注作成 | ❌ | 各ドキュメント生成 |
| 23 | 見積 Quotes | ✅ | **PDF**, メール, 請求生成, 受注生成, 発注生成, 明細 | 🟡 CRUD+明細(割引/税/合計)監査 → `module/inventory.spec.ts` | 生成/PDF |
| 25 | 請求 Invoice | ✅ | **PDF**, メール, 発注作成, 繰り返し請求, 明細 | 🟡 同上(received/balance 含む) | 生成/PDF/繰返請求 |
| 26 | 受注 SalesOrder | ✅ | **PDF**, メール, 請求生成, 発注生成, 繰り返し請求, 明細 | 🟡 同上 | 生成/PDF/繰返請求 |
| 27 | 発注 PurchaseOrder | ✅ | **PDF**, メール, 明細 | 🟡 同上(定価=仕入原価は明示設定) | 生成/PDF |
| 28 | 発注先 Vendors | ✅ | メールを送る, 作成 発注, 複製 | 🟡 メール/発注作成画面遷移 の起動を検証 → `module/vendors.spec.ts` | 複製 |
| 24 | 価格表 PriceBooks | ✅ | 価格更新 `ListPriceUpdate` | ❌ | 製品価格の上書き設定 |
| 29 | チケット HelpDesk | ✅ | メール, **FAQ 変換**, 契約の自動計算 | 🟡 メール/FAQ変換(FAQ編集画面への遷移) の起動を検証 → `module/helpdesk.spec.ts` | 契約の自動計算、解決策ありチケットの即時FAQ生成 |
| 30 | FAQ Faq | ✅ | リッチテキスト | ❌ | リッチテキスト入力 |
| 31 | 契約 ServiceContracts | ✅ | 使用済み単位/進捗/期間の自動計算 | ❌ | チケット連動の自動計算 |
| 15 | キャンペーン Campaigns | ✅ | リード/顧客担当者 一括関連付け, メール配信 | ❌ | 一括関連付け、メール配信 |
| 22 | 日報 Dailyreports | ✅ | 活動の自動関連付け（日/週） | ❌ | 活動自動紐付けの検証 |
| 32 | プロジェクト Project | ✅ | チャート `ExportChart`, 各種ウィジェット | ❌ | チャート表示、ウィジェット |
| 33 | タスク ProjectTask | ✅ | （固有機能なし） | — | — |
| 34 | マイルストーン ProjectMilestone | ✅ | （Project 内ウィジェット経由） | ❌ | — |
| — | 資産 Assets | ✅ | ❓ 固有機能不明 | ❓ | 固有機能の有無を確認 |
| 39/40 | 活動 / ToDo（Calendar） | 🟡 作成・編集 → `module/calendar.spec.ts` | iCal インポート/エクスポート, 繰り返し, 重複検出, 共有カレンダー, カンバン(ToDo) | ❌ | UI削除・iCal・カンバン・カレンダー表示 |

> **注**: 上表「CRUD ✅」は `fr.common.spec.ts` の対象を指す。Calendar（活動/ToDo）は共通 CRUD の対象外で、専用画面のため個別対応が必要。
> **固有機能の検証状況**: Accounts・Vendors はローカルにレコードがあり詳細の固有アクションを**実機確認済**。それ以外（Contacts / Leads / Potentials / HelpDesk / Quotes / Invoice / Assets / Documents 等）はローカル DB に 0 件のため実データで詳細を開けず、**コード確認のみ**（agent 調査＋`modules/<M>/views|actions` の実装で裏取り）。テスト作成時は事前シードが前提。

---

## 4. 管理（システム設定）

既存 spec の ID 体系（C〜I グループ）に沿って整理。**ギャップになっている番号（E-02, F-04）と未着手の設定** をタスク化。

### C グループ: ユーザー / 権限

| ID | 機能 | spec | 状態 |
|---|---|---|---|
| C-01 | ユーザー Users | `admin.C-01.user` | ✅ |
| C-02 | 役割 Roles | `admin.C-02.Roles` | ✅ |
| C-03 | プロファイル Profiles | `admin.C-03.Profiles` | ✅ |
| C-04 | 共有ルール SharingAccess | `admin.C-04.SharingAccess` | ✅ 読み取りで組織共有(Leads=非公開/Accounts=公開)を確認(保存は全体再計算レースの為避ける) |
| C-05 | グループ Groups | `admin.C-05.Groups` | ✅ |
| C-06 | ログイン履歴 LoginHistory | `admin.C-06.LoginHistory` | ✅ |

### D グループ: モジュール / 項目カスタマイズ

| ID | 機能 | spec | 状態 |
|---|---|---|---|
| D-01 | モジュール管理 ModuleManager | `admin.D-01` | ✅ |
| D-02 | レイアウトエディタ（項目設定 / 関連表示 / 重複検出） | `admin.D-02.LayoutEditor` | 🟡 項目編集中心。関連表示・重複検出の設定は未確認 |
| D-03 | レコード番号 CustomRecordNumbering | `admin.D-03` | ✅ |
| D-04 | 選択肢エディタ Picklist | `admin.D-04` | ✅ |
| D-05 | 選択肢の連動 PickListDependency | `admin.D-05` | ✅ |
| D-06 | 入力制限 CustomValidation | `admin.D-06` | ⏭️ **skip** → 有効化タスク |
| D-07 | 文言変更 LanguageConverter | `admin.D-07` | ✅ |
| D-08 | モジュールビルダー | `admin.D-08.ModuleBuilder` | ✅ |
| D-09 | レコードタイプ RecordType | `admin.D-09` | ⏭️ **skip**（標準の設定メニューには非表示。到達手段の確認要） |
| D-10 | 申請フィールド RecordField | `admin.D-10` | ⏭️ **skip**（同上） |
| D-11 | 承認フロー ApprovalFlow | `admin.D-11` | ⏭️ **skip**。実機確認: `module=Approval` は空ページ（未実装の可能性大）→ 対象外の判断も検討 |

### E グループ: 自動化

| ID | 機能 | spec | 状態 |
|---|---|---|---|
| E-01 | Webフォーム Webforms | `admin.E-01.Webforms` | 🟡 「編集」テストは `test.skip` |
| **E-02** | （欠番）**スケジューラー** | — | ❌ 新規。実機確認済: `module=CronTasks&parent=Settings&view=List`（画面名「スケジューラ」） |
| E-03 | ワークフロー Workflows | `admin.E-03.Workflows` | ✅ |
| **E-04?** | **メールコンバーター** | — | ❌ 新規。実機確認済: `module=MailConverter&parent=Settings&view=List`（画面名「メールコンバータ」） |

### F グループ: 構成

| ID | 機能 | spec | 状態 |
|---|---|---|---|
| F-01 | 企業の詳細 CompanyDetails | `admin.F-01` | ✅ |
| F-02 | 顧客ポータル CustomerPortal | `admin.F-02` | ✅ |
| F-03 | 通貨 Currency | `admin.F-03` | ✅ |
| **F-04** | （欠番）**送信メールサーバー** | — | ❌ 新規。実機確認済: `module=Vtiger&parent=Settings&view=OutgoingServerDetail`（画面名「送信メールサーバー」） |
| F-05 | 構成エディタ ConfigEditor | `admin.F-05` | ⏭️ **skip** → 有効化タスク |
| F-06 | メニュー設定 MenuEditor | `admin.F-06` | ✅ |
| F-07 | システム変数 Parameters | `admin.F-07` | ✅ |

### G グループ: マッピング

| ID | 機能 | spec | 状態 |
|---|---|---|---|
| G-01 | リード昇格のマッピング | `admin.G-01.Leads` | ✅ |
| G-02 | 案件→プロジェクトのマッピング | `admin.G-02.Potentials` | ✅ |

### H グループ: 販売管理設定

| ID | 機能 | spec | 状態 |
|---|---|---|---|
| H-01 | 税の管理 TaxIndex | `admin.H-01.TaxIndex` | ⏭️ **skip**（在庫フォーム汚染で fr.common に波及するため。単体では green） |
| H-02 | 諸条件 TermsAndConditions | `admin.H-02` | ✅ |

### I グループ: 個人設定

| ID | 機能 | spec | 状態 |
|---|---|---|---|
| I-01 | 個人設定 PreferenceDetail | `admin.I-01` | ✅ |
| I-02 | カレンダー設定 CalendarSettings | `admin.I-02` | ✅ |
| I-03 | 個人タグ Tags | `admin.I-03` | ✅ |

### 管理設定で spec が全く無いもの（新規タスク）

いずれも admin で実際にアクセスして画面が開くことを確認済（URL は `index.php?...` に続くパラメータ）。

| 機能一覧 No. | 機能 | 到達 URL（実機確認済） | 状態 |
|---|---|---|---|
| 61-1 | スケジューラー | `module=CronTasks&parent=Settings&view=List` | ✅ `admin.E-02.CronTasks.spec.ts`（スモーク） |
| 63-x | メールコンバーター | `module=MailConverter&parent=Settings&view=List` | ✅ `admin.E-04.MailConverter.spec.ts`（スモーク） |
| 67-1 | 送信メールサーバー | `module=Vtiger&parent=Settings&view=OutgoingServerDetail` | ✅ `admin.F-04.OutgoingServer.spec.ts`（スモーク） |
| 78-1 | SMS 通知 | `module=SMSNotifier&parent=Settings&view=List` | ✅ `admin.F-08.SMSNotifier.spec.ts`（スモーク） |
| 36 | メールテンプレート | `module=EmailTemplates&view=List&app=TOOLS` | ✅ `test/module/templates.spec.ts`（スモーク） |
| 37 | PDF テンプレート | `module=PDFTemplates&view=List&app=TOOLS`（モジュール名は `PDFTemplates`） | ✅ `test/module/templates.spec.ts`（スモーク） |
| 50-4 | 重複検出の設定 | LayoutEditor 内（独立画面なし。`module=DuplicateCheck` は実体なしのフォールバック） | ❌ |
| 50-3 | 関連表示の設定 | LayoutEditor 内 | ❌ |

> 実機で確認した設定トップメニュー項目（34）と本ドキュメントの C〜I マッピングは一致。設定メニューに出ないが画面が存在するもの（SMS通知は Settings 配下、メール/PDFテンプレートは**ヘッダーメニューの TOOLS アプリ**の通常モジュール）は上表に集約。

---

## 5. 認証・ログイン系

| No. | 機能 | 実装 | 状態 | タスク |
|---|---|---|---|---|
| 1-1 | ログイン認証（パスワード） | `modules/Users/views/Login.php` | ✅ | 成功=`auth.setup.ts`、**失敗系（誤パスワード）**=`common/common.login.spec.ts` |
| 1-1 | 多要素認証 MFA | `modules/Users/views/MultiFactorAuthLogin.php`（実装あり） | ❌ | MFA 有効化 → ログインフロー |
| 1-1 | SAML 認証 | mainline に未統合（`option-saml` ブランチのみ） | ❓ | 現行コードでは対象外 |
| 1-2 | パスワード再発行 | ログイン画面のリンク（`Login.tpl`。実機でリンク存在を確認）+ `ChangePassword.php` | ❌ | 調査済・未完。`a.forgotPasswordLink` クリックで `#emailId`+送信フォームが出る想定だが、テスト実行時にトグルが発火せず未成立 |
| 1-3 | ログイン画面の広告（RSS） | `modules/Rss` | ❌ | GitHub 版のみ。表示有無の確認 |

---

## 6. コードにあるが「機能一覧」に無い機能（要トリアージ）

機能一覧（1-1〜78-1）に明示が無いが、コードに存在する機能。テスト対象にするか要判断。

| 機能 | 実装 | メモ |
|---|---|---|
| ドキュメント Documents | `modules/Documents` | ファイルプレビュー / DL / 整合性チェック / メール添付。**機能一覧に未記載だがモジュール有効**（実機のメニュー・関連一覧に「ドキュメント」表示を確認） |
| レポート Reports | `modules/Reports`（一覧 41-1 にはあり） | 集計・グラフ。CRUD/共通テスト対象外 |
| グローバル検索 | `modules/Vtiger/apis/SearchRecords.php` | トップバー検索 |
| 拡張ストア ExtensionStore | `Settings:ExtensionStore` | 管理設定 |
| 電話統合 PBXManager | `Settings:PBXManager` | 管理設定 |
| 地図連携 Google Map | `modules/Google/views/Map.php` | Accounts/Contacts 等の地図表示 |
| 監査証跡 / システム情報 / 通知スケジューラー / 在庫通知 | Settings（メニュー非表示） | 隠し設定。優先度低 |

---

## 7. 優先度付きタスクリスト

> チェックボックスは着手・完了管理用。上から着手推奨。

### P1: 影響が大きく、汎用ドライバで横展開しやすい共通機能

- [x] 一覧の列検索（絞り込み → 該当レコード検証）（No.2-4） → `test/common/common.search.spec.ts`
- [x] フォロー（一覧☆ / 詳細 starToggle）ON/OFF（No.3-1） → `test/common/common.follow.spec.ts`
- [x] タグ 追加 / ×削除（詳細画面）（No.4-1） → `test/common/common.tag.spec.ts`
- [x] 一括削除（一覧チェック → ゴミ箱）と ゴミ箱での復元・完全削除（No.12-3, 35-1） → `test/common/common.recyclebin.spec.ts`
- [x] クイック作成（＋ボタン）（No.12-2） → `test/common/common.quickcreate.spec.ts`（React WebコンポーネントのFC作成ダイアログ）
- [x] クイック編集（鉛筆・インライン）（No.12-2） → `test/common/common.quickedit.spec.ts`（概要の1項目編集）
- [x] CSV エクスポート（No.10-1） → `test/common/common.export.spec.ts`
- [ ] 一覧ダブルクリック編集（No.12-2）※調査済・未採用。行 dblclick でインライン編集は動くが、同時にクイックプレビューが開き後続操作を阻害しハングするため安定化が必要（`.listViewEntries` dblclick → `input[name=...]` → `.inline-save .save`）
- [x] CSV インポート（No.11-1） → `test/common/common.import.spec.ts`（Accounts、パターン別5種: 複数行/ヘッダなし/重複スキップ/上書き/マージ）。ウィザードは `utils/import.ts` で driver 化。**知見**: ヘッダは自動マップされず列順に明示割当が必要／重複突合は既定 `accountname`／スキップは未更新・上書き/マージは更新（本ビルドでは CSV に列自体が無い項目の空白化は起きず保持）。※他モジュール展開は未
- [x] タグの一括付与（一覧）（No.4-1）→ `test/common/common.masstag.spec.ts`（一覧で選択→一括「タグの追加」で新規タグ作成・付与、詳細で確認）
- [x] リスト機能 CustomView（個人リスト 作成 / 切替 / 複製 / 削除）（No.2-2）→ `test/common/common.customview.spec.ts`。**知見**: 作成/複製モーダルの保存は AJAX ハンドラ登録後にクリックしないとネイティブ GET になり保存されない（クリック前に待機を入れる）。切替は `#module-filters a.filterName`(すべて#4 含む)、複製は行アクションポップオーバー `li.duplicateFilter`(元名がプリセット)、削除は `li.deleteFilter`。**高並列知見(workers=4対応)**: サイドバーは CV を先頭10件しか表示せず11件目以降は `filterHidden hide`(「もっと」`a.toggleFilterSize` の裏)。並列で他テストの CV が増えると作成直後の CV が隠れて可視待ちがタイムアウトするため、行を探す前にトグルを展開する(`revealHiddenFilters`)。ロールへの共有設定は未
- [x] 概要/詳細タブ（No.6-1/6-2）→ `test/common/common.summary.spec.ts`（概要タブ主要項目、詳細タブ全項目）
- [x] 関連一覧からの追加（No.7-1）→ `test/common/common.relatedadd.spec.ts`（「追加」ボタン=React ダイアログで関連レコード作成）
- [x] カレンダー表示モード切替（No.14-1）→ `test/common/common.calendarview.spec.ts`（月/週/日/概要 の fc ボタン）
- [x] 一括編集（No.12-2）→ `test/common/common.massedit.spec.ts`（項目を `#include_in_mass_edit_<field>` で編集対象に含め値設定→保存）
- [x] 一括コメント（No.9-1）→ `test/common/common.masscomment.spec.ts`
- [x] クイックプレビュー → `test/common/common.quickpreview.spec.ts`。**知見**: プレビューは地図等の外部リソースを読み、オフラインでは当該ページが `networkidle` に到達しない。後始末は先に別画面へ遷移してから行う（プレビューを開いたまま networkidle 待ちするとハングする）
- [x] タグで絞り込み（サイドバー「タグ」欄）→ `test/common/common.tagfilter.spec.ts`。**知見**: 保留原因だった「作成直後のタグが出ない」は、タグ(`vtiger_freetags`)と付与(`vtiger_freetagged_objects`)を dump に焼き込んで安定化。`#listViewTagContainer span.tag` をクリックで `[E2E-PAGE]` の付与 7 件に絞り込める
- [x] 一覧ソート（列ヘッダ `a.listViewContentHeaderValues[data-columnname]`）→ `test/common/common.paging.spec.ts`。**知見**: 保留原因だった「クリック後 `networkidle` に到達せずハング」は、`waitForLoadState` を使わず**先頭行テキストが期待値になるまでポーリング待ち**（`expect(firstRow).toContainText(...)`）することで回避。列検索で絞り込んでいてもヘッダはソート可能（`SEARCH_MODE_RESULTS` にはならない）。ゼロ埋め連番の `[E2E-PAGE]` 島で昇順/降順を決定論的に検証
- [x] **一覧ページング（ページ送り / 末尾ページ / 境界ボタン）** → `test/common/common.paging.spec.ts`。`[E2E-PAGE]` 250 件を絞り込み、20 件/ページ・`#NextPageButton`/`#PreviousPageButton` の活性・末尾端数を検証。**知見**: PageJump ドロップダウン(`#pageToJumpSubmit`)は `#PageJumpDropDown li` の `stopImmediatePropagation` で発火しにくいため、末尾へは Next 連打で到達させる方が安定
- [x] **権限 / 可視範囲（誰のデータが見えるか）** → `test/common/common.permission.spec.ts`。拡充ベースラインのロール階層 + グループ + Private 化 Leads を使い、`loginInIsolatedContext` で各ユーザーの可視件数を検証（§1 の🟡参照）。期待値は `fixtures/seedSpec.ts`
- [x] **アクション権限（プロファイル/役割: 非表示 / 閲覧のみ / 削除不可）** → `test/common/common.permission-action.spec.ts`。Sales Profile を複製し Accounts の権限だけ書換えた 3 ペルソナで、権限拒否画面・追加/編集/削除ボタンの有無を検証。**知見**: 制限操作の UI 要素はサーバ側で DOM から除外されるので `toHaveCount(0)`。権限拒否は HTTP 200 + `span.genHeaderSmall`/`img[src*="denied.gif"]` で判定
- [x] **項目レベル権限（この項目だけ 見えない/編集できない）** → `test/common/common.permission-field.spec.ts`。複製プロファイルで `profile2field.visible=1`(非表示)/`readonly=1`(編集不可)。**知見**: 本ビルドでは readonly 項目は編集画面に **input が出ない**(disabled ではない)ので「詳細で見える(`#Mod_detailView_fieldValue_<f>`)が編集画面に無い」で編集不可を判定。hidden は詳細でも無し
- [x] **カスタム共有ルール（datashare ROLE→ROLE）** → `test/common/common.sharing-rule.spec.ts`。`Settings_SharingAccess_Rule_Model` で `Leads: MGRA ロール→観測者ロール read-only` を作成。観測者は共有元ロール所有の 4 件だけ見え、他ロール/配下ロールは 0 件。**知見**: メンバー id は `Roles:H7`(単一コロン)、`save()` が `recalculateSharingRules()` で sharing_privileges を再生成
- [x] **検索/絞り込み（既知件数）** → `test/common/common.filter.spec.ts`。`[E2E-SRCH]` 島（industry 別 + 一意トークン）で、列検索の既知件数・一意トークン単一ヒット・グローバル検索ヒットを検証
- [x] 所有者変更（Transfer Ownership）→ `test/common/common.masstransfer.spec.ts`。**知見**: 一覧の検索→選択は並列負荷で不安定な為、作成した record id で詳細画面から直接「担当の変更」→ `#Accounts_detailView_moreAction_LBL_TRANSFER_OWNERSHIP` → モーダル `#transferOwnerId`/`#related_modules`(必須)。担当の反映は詳細タブの `#Accounts_detailView_fieldValue_assigned_user_id` で確認
- [x] CustomView の**絞り込み条件**ビルダ → `test/common/common.customview-condition.spec.ts`。**知見**: 実行行は「条件を追加」で `.conditionList` に生成（初期 `.conditionRow` は隠しテンプレート）。項目/演算子 select は select2 で隠れる為 `value` 直接設定+`change` 発火。テキスト項目 accountname の contains 条件で決定論的に 10 件検証（picklist は値 UI が select2 化し不安定）。**高並列知見(workers=4対応)**: 「条件を追加」ボタンのハンドラは AJAX で非同期登録されるため、行が出るまでクリック再試行(二重生成ガード)。また columnname 変更で値UIが再生成され、固定待ちだと入力値が流されて消える(→空条件で0件)ため、値が確定するまで(`toHaveValue`)検証つきで再試行する。**ロール共有**の CustomView は未
- [x] エクスポート/インポート権限 → `test/common/common.permission-export.spec.ts`（§1 権限節参照）
- [ ] タグの変更 / フォロー絞り込み（保留。詳細のタグ追加+削除・一覧一括付与は実装済）※タグ変更は Settings/Tags 上に明確な rename UI が見当たらず、フォロー絞り込みは「フォロー中のみ表示」の絞り込み UI が本ビルドに見当たらないため、UI 特定後に着手
- [ ] ダッシュボード追加・ウィジェット追加/削除（No.13-1）※ウィジェット追加後の DOM が React 混在で不定・管理者ダッシュボードを汚すため保留
- [x] 閲覧制限の E2E 化（可視範囲＝組織/役割/グループ共有 + カスタム共有ルール + アクション権限 + 項目レベル権限）→ `common.permission.spec.ts` / `common.sharing-rule.spec.ts` / `common.permission-action.spec.ts` / `common.permission-field.spec.ts`。残りはエクスポート/インポート権限・一括所有者変更・設定画面 C-04 SharingAccess の skip 解消
- [x] モジュール×機能セルの**能力表マトリクス**（複製×2 / ファイル×2 / コメント添付 / 関連×3 / 共有リスト×2 / マイリスト×2 / 個人リスト×4 / 一覧再利用系 / 詳細編集）→ `test/matrix/matrix.spec.ts`（`model/matrix/capabilities.ts` / `model/matrix/MatrixTest.ts`）。**全 29 モジュールを describe 駆動で展開**(searchField/relatedSpec を describe から自動導出、レコードは API 優先作成)。フルマトリクス ~354 パス。特殊モジュール(インベントリ/Calendar/テンプレート/Portal/Documents・Dailyreports の一部 等)は reason 付き skip・na(実機能は各専用spec で担保)。§1 参照

### P2: 在庫系モジュールと連携機能

- [x] 在庫系 CRUD + **割引/税/合計/調整の監査**(ダイアログ経路) → `test/module/inventory.spec.ts`（Invoice/Quotes/SalesOrder/PurchaseOrder の4モジュール）。明細ドライバは `utils/lineitem.ts`。**知見/重要**:
  - 明細の商品選択は**商品ポップアップ(箱アイコン)ダイアログ**でも可能(`Vtiger_ListView_Model::getInstanceForPopup()`、ListView検索・列修飾あり)。
  - dump に 有効(`discontinued=1`)・単価付き の商品/サービス `[E2E-INV]` を焼き込む(`seed-spec.inventory` / `seed_e2e_data.php`)。API最小シードは `discontinued=0` になり検索に出ないため。
  - 監査: `qty×定価` の小計、行割引(%)の割引額/割引後合計、グループ税額と `総計 = 税抜合計 + 税額`(税ゼロでも成立)、数量変更での再計算。定価は決定論のため明示設定(PO は選択時に定価=仕入原価0のため必須)。
  - ヘッダ必須: 参照(account_id/vendor_id)は選択時と同じ hidden+display 直接設定、必須ピックリスト(quotestage/sostatus/invoicestatus/postatus)は最初の実値を設定。
- [x] 明細の**インライン検索(品目名オートコンプリート) + 製品追加/サービス追加** → `test/module/inventory.lineitem.spec.ts`(Invoice で代表検証、明細UIは4モジュール共通)。①`#addProduct` ②`#addService` ③製品のインライン検索 ④サービスのインライン検索 を検証。**#1704 修正後に動作**(下記)。行の検索モジュールは行の `.lineItemPopup[data-module-name]` で決まる(addService 行は Services 検索)。
- [ ] 見積 → 請求 / 受注 / 発注 の相互生成（No.23-2 等）※DetailViewの生成リンク(quote_id/salesorder_id/invoice_id を Edit へ)経由。次段。
- [ ] PDF エクスポート（在庫系詳細）（No.5-1）※`vtiger_pdftemplates` に各モジュールのテンプレートが必要(base install に同梱あり)。次段。
- [ ] 繰り返し請求（受注・請求）

> **監査で発見 → main #1704 で修正済み(マージ済)**: 在庫明細の名称検索
> `Products_Record_Model::getSearchResult()`(`modules/Products/models/Record.php`)は SELECT の `label` を
> 無修飾参照しており、`vtiger_products`/`vtiger_service` にも同名カラムがある環境で **`label` 曖昧(ERROR 1052)**
> → `pquery` が握り潰し常に 0件で、品目名インライン検索が全滅していた。#1704 で `vtiger_products.label` /
> `vtiger_service.label` に明示修飾して解消。E2E はマージ後にインライン検索経路(`inventory.lineitem.spec.ts`)を追加。
> ※ 連番検索 `searchRecordsOnSequenceNumber()`(`modules/Products/models/Module.php`)の `setype` 未SELECT は
> 別の未修正バグ(連番=商品番号での明細検索は依然0件)。E2E は名称検索/ダイアログで成立するため非ブロッカー。

### P3: モジュール固有フロー

- [x] 更新履歴（No.8-1） → `test/common/common.history.spec.ts`
- [x] 案件 → プロジェクト変換 ConvertPotential（No.19-2）→ `module/potentials.spec.ts`（変換モーダル「案件のコンバート」の起動を検証。生成の保存までは未）
- [x] チケット → FAQ 変換（No.29-2）→ `module/helpdesk.spec.ts`（解決策が空のチケットで FAQ 編集画面への遷移=変換フロー起動を検証。解決策ありの即時FAQ生成は未）
- [x] メール送信起動 / SMS 起動（Accounts/Contacts/Leads/Vendors/HelpDesk/Potentials の各 `module/*.spec.ts`。Accounts は組織階層/SMS/予定・TODO、Leads は SMS/予定・TODO も）※地図表示は残
- [x] リード昇格 ConvertLead（No.16-2）→ `module/leads.spec.ts`（**起動**＋**保存**まで検証。昇格で顧客企業が作成され詳細へ遷移、作成された顧客企業/顧客担当者は後始末で削除）。**根本原因（過去の未成立）**: リードに company が無いと Accounts 作成チェックがオンにならず、`vtValidate` が必須項目（会社名など）未入力で native submit を止めていた／`submitHandler` バインド前にクリックし `modules` 空で `SaveConvertLead` に届いていた。対策: company+lastname を持つ fresh リードを用意し、`#convertLeadForm` 描画（=submit 登録完了）を待ってから保存する。**注意**: 昇格済みリードは `isLeadConverted()` で昇格ボタンが消えるため、毎回使い捨てリードを作る
- [x] 活動/ToDo の作成・編集（No.39/40） → `test/module/calendar.spec.ts`（UI削除はカレンダー上の別フロー・iCal・カンバン・カレンダー表示は残）
- [x] 関連一覧の表示（No.7-1） → `test/common/common.relatedlist.spec.ts`（顧客担当者タブを開いて関連一覧表示を確認。「追加」からの登録までは未）
- [x] コメント投稿（No.9-1） → `test/common/common.comment.spec.ts`

### P4: 管理設定の空白・skip 解消

- [x] C-04 共有ルール（SharingAccess）の skip 解消 → 読み取り検証で有効化（`admin.C-04.SharingAccess`）
- [ ] skip 中の残り spec を有効化: D-06 入力制限 / F-05 構成エディタ / H-01 税の管理（在庫フォーム汚染の後始末込み）
- [ ] skip 中（実装確認から）: D-09 レコードタイプ / D-10 申請フィールド / D-11 承認フロー
- [x] 新規 spec: E-02 スケジューラー / E-04 メールコンバーター / F-04 送信メールサーバー / F-08 SMS通知（各スモーク） → `test/admin/`
- [ ] 新規 spec（残）: メール・PDF テンプレート
- [ ] E-01 Webフォーム「編集」テストの有効化

### P5: 認証・その他

- [x] ログイン失敗系（誤パスワード）（No.1-1） → `test/common/common.login.spec.ts`
- [ ] パスワード再発行フロー（No.1-2）※🚧 **環境ブロック**。`forgotPassword.php` は再発行リンクを**メール送信**するフローで、SMTP 無しのオフライン環境では UI トグルまでしか検証できずリセット完了(受信リンク)まで到達不可。UI トグル部分のみ将来検討
- [ ] MFA 有効化ログインフロー（No.1-1）※🚧 **環境ブロック**。TOTP(時刻ベースコード)入力が必須で、シード済み `totp_secret` からテスト内で OTP を算出しない限り UI から駆動不可。対象外（必要なら seed した秘密鍵から OTP 生成して挑戦）
- [ ] ダッシュボード追加・ウィジェット追加/削除（No.13-1）
- [x] グローバル検索 → `test/common/common.globalsearch.spec.ts`（ヘッダー検索アイコン→キーワード→統合検索結果）
- [ ] Documents モジュールの扱いをトリアージ（テスト対象にするか）

---

## 付録: 参考情報

- 共通 CRUD ドライバ: `e2e/model/FrTest.ts`（`fillAllFields` → `saveAndVerify`。API describe を使い項目型ごとに値生成・検証）
- 固有アクション用レコード用意: `e2e/utils/record.ts`（`createRecordViaApi` / `deleteRecordViaApi`。詳細画面の固有アクション検証向けに、必須項目だけ埋めた対象レコードを Webservice API で用意・後始末。必須の関連項目は seed 済み被参照モジュールの既存レコードを充当）
- 管理設定ヘルパ: `e2e/utils/settings.ts`（`gotoSettings` / `saveAndSettle`）
- API クライアント: `e2e/model/fetcher.ts`（Webservice API。シード・件数取得・レコード取得に使用）
- 認証・シード: `e2e/auth.setup.ts` / `e2e/seed.setup.ts`（参照される側モジュールを事前シード）
- 管理設定 URL 規約: `index.php?module={Sub}&parent=Settings&view={View}`（Vtiger 系設定は `module=Vtiger&parent=Settings&view={View}`）

### 実機検証ログ（2026-07-02, テスト環境 `E2E_BASE_URL`, admin）

- 設定トップメニュー項目 34 件を抽出し C〜I マッピングと突合 → 一致。
- 到達性を実機確認: スケジューラー(CronTasks) / メールコンバーター(MailConverter) / 送信メールサーバー(OutgoingServerDetail) / SMS通知(SMSNotifier:List) はいずれも実画面が開く。
- メール/PDF テンプレートはヘッダーメニュー（TOOLS アプリ）から遷移する通常モジュールと確定: `EmailTemplates&view=List&app=TOOLS` / `PDFTemplates&view=List&app=TOOLS`（`PDFMaker` では開かない）。
- 承認フロー(`module=Approval`)・重複検出(`module=DuplicateCheck`) は実体のあるページが開かず（未実装/フォールバック）。
- 詳細画面の固有アクションを実機確認: Accounts（組織階層/SMS/予定・TODO登録/担当変更/複製 等）、Vendors（メールを送る/作成 発注/複製）。
- ログイン画面にパスワード再発行リンクの存在を確認。
- 制約: ローカル DB は `Accounts/Products/Services/Project/Vendors` 以外ほぼ 0 件。他モジュールの詳細固有アクションの実機確認には事前シードが必要。
