// Excel「3_〇_OSS版_モジュール管理.xlsx」判定=OK 列の転記(単一の真実)。
// 各モジュールについて「ON→アプリメニューにリンクが出る / OFF→出ない」を検証する。
//
// menu 種別(リンクが実際に描画される場所。DOM を実機ダンプして確定):
//   appmenu … サイドバーのアプリメニュー #app-menu 内の <a href="…module=X&…">
//             (Vtiger_MenuStructure / getAllVisibleModules 由来。presence が
//              {0,2} のもののみ描画されるため無効化=presence1 で消える)
//   misc    … #app-menu 下部の .app-item-misc(<div data-default-url="…module=X…">)。
//             ドキュメント / メールマネージャーはグループ外の特別枠で描画される。
//             hasModulePermission() ガードに isActive() が含まれるため無効化で消える。
//   topbar  … 上部ナビ nav.app-fixed-navbar 内の <a href="…module=X&…">。
//             カレンダー / レポートはアイコンリンクとしてここに描画される
//             (同じく hasModulePermission ガードで無効化時に消える)。
//
// 内部名はすべて DB(vtiger_tab / vtiger_app2tab)と実機 DOM で確認済み。推測なし。
// 特筆: 「ブックマーク」ラベルの実体は Portal モジュール(module=Portal→ラベル=ブックマーク)。

export type MenuKind = "appmenu" | "misc" | "topbar";

export interface ManagedModule {
  /** vtiger 内部モジュール名(vtiger_tab.name) */
  name: string;
  /** 日本語ラベル(テスト表示名) */
  label: string;
  /** メニューリンクが描画される場所 */
  menu: MenuKind;
}

export const MODULES: ManagedModule[] = [
  // --- コア業務モジュール(Excel 判定=OK の主対象) ---
  { name: "Potentials", label: "案件", menu: "appmenu" },
  { name: "Contacts", label: "顧客担当者", menu: "appmenu" },
  { name: "Accounts", label: "顧客企業", menu: "appmenu" },
  { name: "Leads", label: "リード", menu: "appmenu" },
  { name: "Documents", label: "ドキュメント", menu: "misc" },
  { name: "Calendar", label: "カレンダー", menu: "topbar" },
  { name: "HelpDesk", label: "チケット", menu: "appmenu" },
  { name: "Products", label: "製品", menu: "appmenu" },
  { name: "Faq", label: "FAQ", menu: "appmenu" },
  { name: "Vendors", label: "発注先", menu: "appmenu" },
  { name: "PriceBooks", label: "価格表", menu: "appmenu" },
  { name: "Quotes", label: "見積", menu: "appmenu" },
  { name: "PurchaseOrder", label: "発注", menu: "appmenu" },
  { name: "SalesOrder", label: "受注", menu: "appmenu" },
  { name: "Invoice", label: "請求", menu: "appmenu" },
  { name: "Campaigns", label: "キャンペーン", menu: "appmenu" },
  { name: "ServiceContracts", label: "契約", menu: "appmenu" },
  { name: "Services", label: "サービス", menu: "appmenu" },
  { name: "ProjectMilestone", label: "マイルストーン", menu: "appmenu" },
  { name: "ProjectTask", label: "タスク", menu: "appmenu" },
  { name: "Project", label: "プロジェクト", menu: "appmenu" },
  { name: "SMSNotifier", label: "SMS通知", menu: "appmenu" },
  { name: "Assets", label: "資産・レンタル管理", menu: "appmenu" },
  { name: "Dailyreports", label: "日報", menu: "appmenu" },
  // --- 付随モジュール(内部名を DB から解決) ---
  { name: "RecycleBin", label: "ごみ箱", menu: "appmenu" },
  { name: "Rss", label: "Rss", menu: "appmenu" },
  { name: "Reports", label: "レポート", menu: "topbar" },
  { name: "PDFTemplates", label: "PDFテンプレート", menu: "appmenu" },
  { name: "MailManager", label: "メールマネージャー", menu: "misc" },
  { name: "Portal", label: "ブックマーク", menu: "appmenu" },
];

/** テスト対象の内部名一覧(DB セーフティネット / 検証用) */
export const MODULE_NAMES: string[] = MODULES.map((m) => m.name);

// --- 除外(EXCLUDE / NA / SKIP)。失敗させずに理由付きで対象外とする ---
// EXCLUDE:
//   Webforms(Webフォーム) … ModuleManager 上ではトグル可能だが、トップレベルの
//     アプリメニューにリンクを持たない(設定 / リード配下からのみ到達)。
//     「メニュー表示切替」で検証する対象が存在しないため除外。
// NA(Excel が '-'):
//   Emails(メール), Webmails, 承認アクション, Googleカレンダー … OSS のメニュー
//     対象外。承認アクション / Googleカレンダーは対応する vtiger_tab も無い。
// SKIP(OSS 版に非搭載):
//   申請, Excelテンプレート。
export const EXCLUSIONS = [
  { label: "Webフォーム", name: "Webforms", kind: "EXCLUDE", reason: "アプリメニューにトップレベルのリンクを持たない(設定/リード配下からのみ)" },
  { label: "メール", name: "Emails", kind: "NA", reason: "Excel 判定 '-'" },
  { label: "Webmails", name: "Webmails", kind: "NA", reason: "Excel 判定 '-'" },
  { label: "承認アクション", name: "-", kind: "NA", reason: "Excel 判定 '-' / 対応 tab なし" },
  { label: "Googleカレンダー", name: "-", kind: "NA", reason: "Excel 判定 '-' / 対応 tab なし" },
  { label: "申請", name: "-", kind: "SKIP", reason: "OSS 版に非搭載" },
  { label: "Excelテンプレート", name: "-", kind: "SKIP", reason: "OSS 版に非搭載" },
] as const;
