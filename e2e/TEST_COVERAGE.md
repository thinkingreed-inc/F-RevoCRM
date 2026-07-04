# E2E テストカバレッジ & タスクリスト

F-RevoCRM の E2E（Playwright）テストについて、**どの機能が存在し / どこまでテストできているか / これから何を書くべきか** を一覧化したドキュメント。
新しいテストを追加したら、このファイルの該当行のステータスと「タスク」を更新すること。

- 対象コード時点: `feature/e2e-共通機能追加` ブランチ（2026-07-03 現在）
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

現在の spec ファイルは 64 本。テストは目的別に 4 ディレクトリへ整理。

| ディレクトリ | 内容 | 状態 |
|---|---|---|
| `test/common/`（17 本） | 全モジュール横断の共通機能（検索/フォロー/タグ+一括タグ/エクスポート/インポート/一括削除+ゴミ箱/クイック作成/クイック編集/更新履歴/コメント/関連一覧+関連追加/リスト(CustomView)/概要詳細タブ/カレンダー表示/ログイン失敗） | ✅ 実行中 |
| `test/module/`（8 本） | モジュール固有機能（Accounts 一覧表示/カスタマイズ + 各モジュールの詳細固有アクション: Accounts/Contacts/Leads/Potentials/Vendors/HelpDesk のメール・SMS・変換・作成起動、Calendar 作成編集、メール/PDFテンプレート一覧） | 🟡 主要モジュールの起動確認 |
| `test/admin/*.spec.ts`（36 本） | システム管理画面の C〜I グループ + スモーク（E-02/E-04/F-04/F-08） | ✅/⏭️ 混在 |
| `test/fr.common.spec.ts` | **17 モジュールの新規作成 / 編集 / 削除**（`FrTest` 汎用ドライバ） | ✅ |
| `test/general.spec.ts` | トップ（ダッシュボード）要素、サイドバー開閉 | 🟡 表示確認のみ |

> 並行実行対応: `test/common/` `test/module/` はワーカー単位で個別ログインする `fixtures/isolated.ts` を使用し、各テストが専用レコードを作成→操作→削除する。API セッション取得は `auth.setup` で一度だけ行い使い回す（`--workers=1` 不要）。

**共通 CRUD の対象モジュール（17）**: Accounts, Contacts, Potentials, Leads, Products, Assets, Campaigns, Dailyreports, Faq, HelpDesk, PriceBooks, Project, ProjectMilestone, ProjectTask, ServiceContracts, Services, Vendors。

**共通 CRUD の対象外**: 在庫（Inventory）系 **Invoice / Quotes / SalesOrder / PurchaseOrder**。
理由（`fr.common.spec.ts` の注記）: 明細（productid）の登録に商品検索オートコンプリートが必要だが、本環境では連番検索・名称検索とも 0 件を返し、明細を JS 直挿ししても合計が 0 のまま保存ボタンが無効化されるため。→ 商品検索が機能する環境が用意できれば対象に戻せる。

### 進捗と残り

- ✅ **横断共通機能はひと通り実装**（§2 参照）: 一覧検索 / フォロー / タグ追加削除 / エクスポート / インポート(パターン別) / 一括削除+ゴミ箱 / クイック作成 / クイック編集 / 更新履歴 / コメント / 関連一覧表示 / ログイン失敗。
- 🟡 **モジュール固有機能は主要な「起動/遷移」を検証** — メール送信 / SMS / 組織階層 / 予定・ToDo登録 / **リード昇格（起動+保存）** / 案件のプロジェクト変換 / 見積・受注・発注の作成画面遷移 / チケット→FAQ変換 を Accounts/Contacts/Leads/Potentials/Vendors/HelpDesk で検証（§3）。**保存まで**の相互生成 / PDF エクスポート / 繰り返し請求 / 地図 / iCal は未（§3）。
- ❌ **在庫系 4 モジュール** — CRUD も固有機能（PDF・相互生成）も未（§2 の P2）。
- 🟡 **管理設定** — 未テスト画面 4 種はスモーク追加済み。skip 中（C-04/D-06/F-05/H-01 等）の有効化が残る（§4）。
- 🟡 **認証系** — ログイン失敗は実装済。パスワード再発行・MFA は未（§5）。
- ⚠️ **積み残しの finicky 項目**: 一覧ダブルクリック編集（プレビュー重なり）・パスワード再発行（トグル発火せず）。各行に調査結果を注記。（リード昇格の保存は原因特定し解消済 → §3 / P3）
- 🔴 **【重要・カバレッジが弱い領域】閲覧制限・権限周りはほぼ未検証**。現状は全テストを **admin（全権限）** 単一ユーザーで実行しており、以下がまったく担保できていない:
  - プロファイル／役割による**モジュール・アクションの可視/不可視**（作成・編集・削除・エクスポート等の権限）
  - **共有ルール（組織/役割/グループ）** によるレコードの可視範囲（自分/部下/全体）
  - **項目レベルアクセス**（項目の表示・編集可否）
  - 一般ユーザー視点での一覧/詳細/関連の**見え方の差**（admin では常に全件見えるため差分が出ない）
  → 対策方針: 権限の異なる**専用テストユーザー**を用意し、admin と非管理ユーザーで同一操作の可否・可視範囲を突き合わせる E2E を新設する（別ユーザー context は `utils/settings.ts` の `loginInIsolatedContext` を利用可能）。§4 C グループ（共有ルール C-04 は現状 skip）と連動。

---

## 2. 共通機能（全モジュール横断）

一覧・詳細・登録の共通挙動。実装は `modules/Vtiger/` と `layouts/v7/modules/Vtiger/`。

| No. | 機能 | 実装（代表） | 状態 | spec / タスク |
|---|---|---|---|---|
| 2-1 | 一覧表示 | `modules/Vtiger/views/List.php` | 🟡 | Accounts のみ表示確認(`module/account`)。汎用の一覧表示検証は未 |
| 2-4 | 一覧からの検索（列検索） | `ListViewContents.tpl` / `List.js` | ✅ | `common/common.search.spec.ts` |
| 2-2 | リスト機能（個人 / 共有 CustomView） | `modules/CustomView/` | 🟡 | `common/common.customview.spec.ts`（個人リストの 作成 / 切替 / 複製 / 削除）。ロールへの共有設定は未 |
| 3-1 | フォロー（☆ / Watching） | 一覧 `a.markStar` / 詳細 `#starToggle` | ✅ | `common/common.follow.spec.ts`（一覧・詳細トグル。絞り込みは未） |
| 4-1 | タグ 付与 / 変更 / 削除 | `DetailViewTagList.tpl` / `Tag.js` | 🟡 | `common/common.tag.spec.ts`（詳細の追加+×削除）+ `common/common.masstag.spec.ts`（一覧選択→一括付与）。タグ変更・フォロー絞り込みは未 |
| 5-1 | PDF エクスポート | `modules/Vtiger/actions/ExportPDF.php` | ❌ | 在庫系詳細の「その他」→ PDF 出力（要 PDF テンプレート・在庫レコード。P2） |
| 6-1/6-2 | 概要 / 詳細画面 | `modules/Vtiger/views/Detail.php` | ✅ | `common/common.summary.spec.ts`（概要タブで主要項目、詳細タブへ切替で全項目表示） |
| 7-1 | 関連一覧 | `li.tab-item[data-module]` / RelatedList | ✅ | `common/common.relatedlist.spec.ts`（表示）+ `common/common.relatedadd.spec.ts`（「追加」から関連レコード作成） |
| 8-1 | 更新履歴（ModTracker） | `modules/ModTracker/` | ✅ | `common/common.history.spec.ts` |
| 9-1 | コメント（ModComments） | `modules/ModComments/` | ✅ | `common/common.comment.spec.ts` |
| 10-1 | CSV エクスポート | `modules/Vtiger/actions/ExportData.php` | ✅ | `common/common.export.spec.ts` |
| 11-1 | CSV インポート | `modules/Import/` | ✅ | `common/common.import.spec.ts`（複数行/ヘッダなし/スキップ/上書き/マージ。マッピング保存・他モジュールは未） |
| 12-1 | 一覧から登録（EditView） | `modules/Vtiger/views/Edit.php` | ✅ | 17 モジュールで実施済（`FrTest`） |
| 12-2 | クイック作成（＋ボタン） | `views/QuickCreateAjax.php`（React WC） | ✅ | `common/common.quickcreate.spec.ts` |
| 12-2 | 編集（編集画面） | 同上 | ✅ | 17 モジュールで実施済 |
| 12-2 | クイック編集（鉛筆） | インライン edit | ✅ | `common/common.quickedit.spec.ts`（概要の1項目編集） |
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
| 23 | 見積 Quotes | ❌ | **PDF**, メール, 請求生成, 受注生成, 発注生成, 明細 | ❌ | ⚠️ 明細登録が要商品検索。CRUD 復活と生成/PDF |
| 25 | 請求 Invoice | ❌ | **PDF**, メール, 発注作成, 繰り返し請求, 明細 | ❌ | 同上 |
| 26 | 受注 SalesOrder | ❌ | **PDF**, メール, 請求生成, 発注生成, 繰り返し請求, 明細 | ❌ | 同上 |
| 27 | 発注 PurchaseOrder | ❌ | **PDF**, メール, 明細 | ❌ | 同上 |
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
| C-04 | 共有ルール SharingAccess | `admin.C-04.SharingAccess` | ⏭️ **skip** → 有効化タスク |
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
- [x] リスト機能 CustomView（個人リスト 作成 / 切替 / 複製 / 削除）（No.2-2）→ `test/common/common.customview.spec.ts`。**知見**: 作成/複製モーダルの保存は AJAX ハンドラ登録後にクリックしないとネイティブ GET になり保存されない（クリック前に待機を入れる）。切替は `#module-filters a.filterName`(すべて#4 含む)、複製は行アクションポップオーバー `li.duplicateFilter`(元名がプリセット)、削除は `li.deleteFilter`。ロールへの共有設定は未
- [x] 概要/詳細タブ（No.6-1/6-2）→ `test/common/common.summary.spec.ts`（概要タブ主要項目、詳細タブ全項目）
- [x] 関連一覧からの追加（No.7-1）→ `test/common/common.relatedadd.spec.ts`（「追加」ボタン=React ダイアログで関連レコード作成）
- [x] カレンダー表示モード切替（No.14-1）→ `test/common/common.calendarview.spec.ts`（月/週/日/概要 の fc ボタン）
- [ ] タグの変更 / フォロー絞り込み（保留。詳細のタグ追加+削除・一覧一括付与は実装済）※タグ変更は Settings/Tags 上に明確な rename UI が見当たらず、フォロー絞り込みは「フォロー中のみ表示」の絞り込み UI が本ビルドに見当たらないため、UI 特定後に着手
- [ ] ダッシュボード追加・ウィジェット追加/削除（No.13-1）※ウィジェット追加後の DOM が React 混在で不定・管理者ダッシュボードを汚すため保留
- [ ] 閲覧制限（プロファイル/共有ルール/項目レベルアクセス）の E2E 化 ※このカバレッジ表に横断項目として不足。別ユーザーでの可視範囲検証を含め後続で整理・追加する

### P2: 在庫系モジュールと連携機能

- [ ] 商品検索が機能する環境（またはモック）を用意し、在庫系 CRUD を `fr.common` に復帰（Invoice/Quotes/SalesOrder/PurchaseOrder）
- [ ] 見積 → 請求 / 受注 / 発注 の相互生成（No.23-2 等）
- [ ] PDF エクスポート（在庫系詳細）（No.5-1）
- [ ] 繰り返し請求（受注・請求）

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

- [ ] skip 中の spec を有効化: C-04 共有ルール / D-06 入力制限 / F-05 構成エディタ / H-01 税の管理（在庫フォーム汚染の後始末込み）
- [ ] skip 中（実装確認から）: D-09 レコードタイプ / D-10 申請フィールド / D-11 承認フロー
- [x] 新規 spec: E-02 スケジューラー / E-04 メールコンバーター / F-04 送信メールサーバー / F-08 SMS通知（各スモーク） → `test/admin/`
- [ ] 新規 spec（残）: メール・PDF テンプレート
- [ ] E-01 Webフォーム「編集」テストの有効化

### P5: 認証・その他

- [x] ログイン失敗系（誤パスワード）（No.1-1） → `test/common/common.login.spec.ts`
- [ ] パスワード再発行フロー（No.1-2）※調査済・未完。`a.forgotPasswordLink` クリックで再発行フォーム(`#emailId`+送信)が出る想定だが、テスト実行時にトグルが発火せず未成立
- [ ] MFA 有効化ログインフロー（No.1-1）
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
