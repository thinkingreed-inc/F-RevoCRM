# E2E テストカバレッジ & タスクリスト

F-RevoCRM の E2E（Playwright）テストについて、**どの機能が存在し / どこまでテストできているか / これから何を書くべきか** を一覧化したドキュメント。
新しいテストを追加したら、このファイルの該当行のステータスと「タスク」を更新すること。

- 対象コード時点: `feature/e2e` ブランチ（2026-07-02 現在）
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

現在の spec ファイルは 35 本。カバレッジは大きく 4 系統。

| 系統 | ファイル | 内容 | 状態 |
|---|---|---|---|
| 基本画面 | `test/general.spec.ts` | トップ（ダッシュボード）要素、サイドバー開閉 | 🟡 表示確認のみ |
| モジュール個別 | `test/account.spec.ts` | 顧客企業リストの表示要素・カスタマイズメニュー | 🟡 Accounts のみ |
| 共通 CRUD | `test/fr.common.spec.ts` | **17 モジュールの新規作成 / 編集 / 削除**（`FrTest` 汎用ドライバ） | ✅ |
| 管理設定 | `test/admin/*.spec.ts`（32 本） | システム管理画面の C〜I グループ | ✅/⏭️ 混在 |

**共通 CRUD の対象モジュール（17）**: Accounts, Contacts, Potentials, Leads, Products, Assets, Campaigns, Dailyreports, Faq, HelpDesk, PriceBooks, Project, ProjectMilestone, ProjectTask, ServiceContracts, Services, Vendors。

**共通 CRUD の対象外**: 在庫（Inventory）系 **Invoice / Quotes / SalesOrder / PurchaseOrder**。
理由（`fr.common.spec.ts` の注記）: 明細（productid）の登録に商品検索オートコンプリートが必要だが、本環境では連番検索・名称検索とも 0 件を返し、明細を JS 直挿ししても合計が 0 のまま保存ボタンが無効化されるため。→ 商品検索が機能する環境が用意できれば対象に戻せる。

### 大きな空白（優先的に埋めたい領域）

1. **横断的な共通機能がほぼ未テスト** — 一覧検索 / リスト（CustomView）/ フォロー / タグ / インポート / エクスポート / 一括削除 / インライン編集 / クイック作成 / 関連一覧 / 更新履歴 / コメント / 複製 / PDF。CRUD の「登録・変更・削除」以外の共通機能はほぼ ❌。
2. **モジュール固有機能が全モジュールで未テスト** — メール送信 / SMS / 地図 / リード昇格 / 見積・請求・受注・発注の相互生成 / PDF エクスポート / 繰り返し請求 / FAQ 変換 / 組織階層 / iCal / vCard など。
3. **在庫系 4 モジュール** — CRUD も固有機能（PDF・相互生成）も ❌。
4. **管理設定に一部空白** — 送信メールサーバー / スケジューラー / メールコンバーター / SMS 通知 の専用 spec が無い。
5. **認証系** — ログイン成功のみ（`auth.setup.ts`）。パスワード再発行・MFA・失敗系は ❌。

---

## 2. 共通機能（全モジュール横断）

一覧・詳細・登録の共通挙動。実装は `modules/Vtiger/` と `layouts/v7/modules/Vtiger/`。

| No. | 機能 | 実装（代表） | 状態 | タスク |
|---|---|---|---|---|
| 2-1 | 一覧表示 | `modules/Vtiger/views/List.php` | 🟡 | Accounts のみ表示確認。汎用の一覧表示検証（列・件数・並び）を追加 |
| 2-4 | 一覧からの検索（列検索） | `ListViewContents.tpl` / `List.js` | ❌ | 列フィルタで絞り込み → 結果件数を検証 |
| 2-2 | リスト機能（個人 / 共有 CustomView） | `modules/CustomView/` | ❌ | リスト作成・複製・共有・切替のテスト |
| 3-1 | フォロー（☆ / Watching） | 一覧 `LBL_ADD_STAR` / 詳細 `#starToggle` | ❌ | 一覧・詳細でのフォロー付与/解除、フォロー絞り込み |
| 4-1 | タグ 付与 / 変更 / 削除 | `DetailViewTagList.tpl` / `Tag.js` | ❌ | 詳細でのタグ追加・編集・×削除、一覧一括付与 |
| 5-1 | PDF エクスポート | `modules/Vtiger/actions/ExportPDF.php` | ❌ | 在庫系詳細の「その他」→ PDF 出力（要 PDF テンプレート） |
| 6-1/6-2 | 概要 / 詳細画面 | `modules/Vtiger/views/Detail.php` | 🟡 | CRUD 後に詳細表示は検証済。概要画面の主要項目表示は未 |
| 7-1 | 関連一覧 | `.relatedListPanel` / RelatedList | ❌ | 関連一覧の表示・「追加」からの関連レコード作成 |
| 8-1 | 更新履歴（ModTracker） | `modules/ModTracker/` | ❌ | 編集後に履歴が増えることを検証 |
| 9-1 | コメント（ModComments） | `modules/ModComments/` | ❌ | コメント投稿 → 表示を検証（有効モジュールのみ） |
| 10-1 | CSV エクスポート | `modules/Vtiger/actions/ExportData.php` | ❌ | 一覧「その他」→ エクスポート、選択レコードのみ出力 |
| 11-1 | CSV インポート | `modules/Import/` | ❌ | 追加 / 更新（スキップ・上書き・マージ）、マッピング保存の各フロー |
| 12-1 | 一覧から登録（EditView） | `modules/Vtiger/views/Edit.php` | ✅ | 17 モジュールで実施済（`FrTest`） |
| 12-2 | クイック作成（＋ボタン） | `views/QuickCreateAjax.php` | ❌ | ＋ボタン・関連＋ボタンからの登録 |
| 12-2 | 編集（編集画面） | 同上 | ✅ | 17 モジュールで実施済 |
| 12-2 | クイック編集（鉛筆） | インライン edit | ❌ | 1 項目インライン編集 |
| 12-2 | 一覧からの更新（ダブルクリック） | `List.js` inline edit | ❌ | 一覧ダブルクリック編集 |
| 12-3 | 削除 / 一括削除 | 詳細「その他」/ 一覧ゴミ箱 | 🟡 | 単体削除は ✅。一覧一括削除（最大 500）は ❌ |
| 13-1 | ダッシュボード / ウィジェット | `modules/Home/views/DashBoard.php` | 🟡 | 表示確認のみ。ダッシュボード追加・ウィジェット追加/削除は ❌ |
| 14-1 | カレンダー（月/週/日/概要） | `modules/Calendar/views/Calendar.php` | ❌ | 各表示モード切替、イベント作成・編集 |
| 35-1 | ゴミ箱（復元 / 完全削除） | `modules/RecycleBin/` | ❌ | 削除 → 復元、完全削除の検証 |
| — | グローバル検索 | `modules/Vtiger/apis/SearchRecords.php` | ❌ | トップバー検索 → 結果表示 |

---

## 3. 業務モジュール（固有機能）

各モジュールの **基本 CRUD** と **モジュール固有機能** を分けて管理。CRUD は上表 12-x に準拠。

| No. | モジュール | CRUD | 固有機能（実装） | 固有機能テスト | タスク |
|---|---|---|---|---|---|
| 18 | 顧客企業 Accounts | ✅ | 組織階層, SMSを送る, 予定/TODOの登録, 担当の変更, 複製（**実機の詳細で確認済**）, メール, 地図(Google) | ❌ | 組織階層表示、メール送信起動、地図表示 |
| 17 | 顧客担当者 Contacts | ✅ | メール, 活動, ToDo, SMS, 地図, vCard インポート | ❌ | vCard インポート、メール送信起動 |
| 16 | リード Leads | ✅ | **昇格 `ConvertLead`**, メール, 活動, ToDo, SMS, 地図 | ❌ | 昇格（顧客企業/担当者/案件へ）フローの検証 |
| 19 | 案件 Potentials | ✅ | 見積/請求/受注作成, **プロジェクト変換 `ConvertPotential`**, 活動, ToDo | ❌ | 案件→見積/請求/受注 生成、プロジェクト作成 |
| 20 | 製品 Products | ✅ | 見積/請求/発注/受注作成, 在庫管理, 自動計算, SubProducts | ❌ | 在庫・自動計算、各ドキュメント生成 |
| 21 | サービス Services | ✅ | 見積/請求/発注/受注作成 | ❌ | 各ドキュメント生成 |
| 23 | 見積 Quotes | ❌ | **PDF**, メール, 請求生成, 受注生成, 発注生成, 明細 | ❌ | ⚠️ 明細登録が要商品検索。CRUD 復活と生成/PDF |
| 25 | 請求 Invoice | ❌ | **PDF**, メール, 発注作成, 繰り返し請求, 明細 | ❌ | 同上 |
| 26 | 受注 SalesOrder | ❌ | **PDF**, メール, 請求生成, 発注生成, 繰り返し請求, 明細 | ❌ | 同上 |
| 27 | 発注 PurchaseOrder | ❌ | **PDF**, メール, 明細 | ❌ | 同上 |
| 28 | 発注先 Vendors | ✅ | メールを送る, 作成 発注, 複製（**実機の詳細で確認済**） | ❌ | 発注作成起動 |
| 24 | 価格表 PriceBooks | ✅ | 価格更新 `ListPriceUpdate` | ❌ | 製品価格の上書き設定 |
| 29 | チケット HelpDesk | ✅ | メール, **FAQ 変換**, 契約の自動計算 | ❌ | FAQ 変換フロー |
| 30 | FAQ Faq | ✅ | リッチテキスト | ❌ | リッチテキスト入力 |
| 31 | 契約 ServiceContracts | ✅ | 使用済み単位/進捗/期間の自動計算 | ❌ | チケット連動の自動計算 |
| 15 | キャンペーン Campaigns | ✅ | リード/顧客担当者 一括関連付け, メール配信 | ❌ | 一括関連付け、メール配信 |
| 22 | 日報 Dailyreports | ✅ | 活動の自動関連付け（日/週） | ❌ | 活動自動紐付けの検証 |
| 32 | プロジェクト Project | ✅ | チャート `ExportChart`, 各種ウィジェット | ❌ | チャート表示、ウィジェット |
| 33 | タスク ProjectTask | ✅ | （固有機能なし） | — | — |
| 34 | マイルストーン ProjectMilestone | ✅ | （Project 内ウィジェット経由） | ❌ | — |
| — | 資産 Assets | ✅ | ❓ 固有機能不明 | ❓ | 固有機能の有無を確認 |
| 39/40 | 活動 / ToDo（Calendar） | ❌ | iCal インポート/エクスポート, 繰り返し, 重複検出, 共有カレンダー, カンバン(ToDo) | ❌ | 活動・ToDo の CRUD、iCal、カンバン |

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
| 36 | メールテンプレート | `module=EmailTemplates&view=List&app=TOOLS`（画面名「メールテンプレート」。ヘッダーメニュー = TOOLS アプリの通常モジュール。実機確認済） | ❌ |
| 37 | PDF テンプレート | `module=PDFTemplates&view=List&app=TOOLS`（画面名「PDFテンプレート」。**モジュール名は `PDFTemplates`**。ヘッダーメニューから遷移。実機確認済） | ❌ |
| 50-4 | 重複検出の設定 | LayoutEditor 内（独立画面なし。`module=DuplicateCheck` は実体なしのフォールバック） | ❌ |
| 50-3 | 関連表示の設定 | LayoutEditor 内 | ❌ |

> 実機で確認した設定トップメニュー項目（34）と本ドキュメントの C〜I マッピングは一致。設定メニューに出ないが画面が存在するもの（SMS通知は Settings 配下、メール/PDFテンプレートは**ヘッダーメニューの TOOLS アプリ**の通常モジュール）は上表に集約。

---

## 5. 認証・ログイン系

| No. | 機能 | 実装 | 状態 | タスク |
|---|---|---|---|---|
| 1-1 | ログイン認証（パスワード） | `modules/Users/views/Login.php` | 🟡 | `auth.setup.ts` で成功のみ。**失敗系（誤パスワード）** が ❌ |
| 1-1 | 多要素認証 MFA | `modules/Users/views/MultiFactorAuthLogin.php`（実装あり） | ❌ | MFA 有効化 → ログインフロー |
| 1-1 | SAML 認証 | mainline に未統合（`option-saml` ブランチのみ） | ❓ | 現行コードでは対象外 |
| 1-2 | パスワード再発行 | ログイン画面のリンク（`Login.tpl`。実機でリンク存在を確認）+ `ChangePassword.php` | ❌ | ログイン画面からの再発行、個人設定からの変更 |
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
- [x] CSV インポート（No.11-1） → `test/common/common.import.spec.ts`（Accounts。最小CSV `fixtures/import_accounts.csv`）。**要点**: ヘッダは自動マップされないため 3ステップ目で先頭列を `accountname` に明示マッピングが必要。※重複時スキップ/上書き/マージの検証は、Accounts に重複検出項目の設定が要るため未対応（別タスク）。他モジュール展開も未
- [ ] タグの一括付与（一覧）/ タグの変更 / フォロー絞り込み（残タスク。詳細のタグ追加+削除は実装済）

### P2: 在庫系モジュールと連携機能

- [ ] 商品検索が機能する環境（またはモック）を用意し、在庫系 CRUD を `fr.common` に復帰（Invoice/Quotes/SalesOrder/PurchaseOrder）
- [ ] 見積 → 請求 / 受注 / 発注 の相互生成（No.23-2 等）
- [ ] PDF エクスポート（在庫系詳細）（No.5-1）
- [ ] 繰り返し請求（受注・請求）

### P3: モジュール固有フロー

- [x] 更新履歴（No.8-1） → `test/common/common.history.spec.ts`
- [ ] リード昇格 ConvertLead（No.16-2）※調査済・未完。昇格画面は開き Accounts/Contacts トグルは ON だが、`保存`クリックでレコードが作成されず home へ戻る。convert の実行アクション(専用ボタン/React)を要特定
- [ ] 案件 → プロジェクト変換 ConvertPotential（No.19-2）
- [ ] チケット → FAQ 変換（No.29-2）
- [ ] メール送信起動 / SMS / 地図表示（主要モジュール）
- [ ] 活動・ToDo の CRUD、iCal、カンバン、カレンダー各表示（No.14-1, 39, 40）
- [ ] 関連一覧の表示・「追加」からの登録（No.7-1）※関連タブの AJAX ロードが不安定（要安定化）／コメント（No.9-1）※投稿ボタンのセレクタが不明瞭

### P4: 管理設定の空白・skip 解消

- [ ] skip 中の spec を有効化: C-04 共有ルール / D-06 入力制限 / F-05 構成エディタ / H-01 税の管理（在庫フォーム汚染の後始末込み）
- [ ] skip 中（実装確認から）: D-09 レコードタイプ / D-10 申請フィールド / D-11 承認フロー
- [x] 新規 spec: E-02 スケジューラー / E-04 メールコンバーター / F-04 送信メールサーバー / F-08 SMS通知（各スモーク） → `test/admin/`
- [ ] 新規 spec（残）: メール・PDF テンプレート
- [ ] E-01 Webフォーム「編集」テストの有効化

### P5: 認証・その他

- [x] ログイン失敗系（誤パスワード）（No.1-1） → `test/common/common.login.spec.ts`
- [ ] パスワード再発行フロー（No.1-2）
- [ ] MFA 有効化ログインフロー（No.1-1）
- [ ] ダッシュボード追加・ウィジェット追加/削除（No.13-1）
- [ ] グローバル検索 ※調査中。検索入力がトップバーのトグル配下にあり、直接のセレクタ未特定（アイコン押下で展開が必要と推定）
- [ ] Documents モジュールの扱いをトリアージ（テスト対象にするか）

---

## 付録: 参考情報

- 共通 CRUD ドライバ: `e2e/model/FrTest.ts`（`fillAllFields` → `saveAndVerify`。API describe を使い項目型ごとに値生成・検証）
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
