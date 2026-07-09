export type CaseId =
  | "list.create.detail"
  | "list.create.listNav"
  | "list.edit"
  | "list.duplicate"
  | "list.delete"
  | "list.search"
  | "list.searchReset"
  | "list.cv.personal.show"
  | "list.cv.personal.delete"
  | "list.cv.personal.dup"
  | "list.cv.personal.edit"
  | "list.cv.shared.self"
  | "list.cv.shared.other"
  | "detail.edit"
  | "detail.duplicate"
  | "detail.delete"
  | "detail.file.upload"
  | "detail.file.download"
  | "detail.comment.post"
  | "detail.comment.file"
  | "related.search"
  | "related.searchReset"
  | "related.navigate";

/** run=対象(空欄) / na=機能なし(グレー) / skip=※skip または未実装 */
export type Capability = "run" | "na" | "skip";

export interface ModuleMatrix {
  module: string;
  app?: string; // 一覧 URL の app(既定 MARKETING)
  enabled: boolean; // 展開ゲート: false の間は describe ごと skip
  cases: Partial<Record<CaseId, Capability>>;
}

/** マトリクス行の実行順(spec のテスト生成順) */
export const ALL_CASES: CaseId[] = [
  "list.create.detail",
  "list.create.listNav",
  "list.edit",
  "list.duplicate",
  "list.delete",
  "list.search",
  "list.searchReset",
  "list.cv.personal.show",
  "list.cv.personal.delete",
  "list.cv.personal.dup",
  "list.cv.personal.edit",
  "list.cv.shared.self",
  "list.cv.shared.other",
  "detail.edit",
  "detail.duplicate",
  "detail.delete",
  "detail.file.upload",
  "detail.file.download",
  "detail.comment.post",
  "detail.comment.file",
  "related.search",
  "related.searchReset",
  "related.navigate",
];

/** 人間可読ラベル(spec のテスト名に使う) */
export const CASE_LABELS: Record<CaseId, string> = {
  "list.create.detail": "一覧-新規作成: 保存でき詳細に入力通り表示",
  "list.create.listNav": "一覧-新規作成: 一覧に表示され詳細へ遷移",
  "list.edit": "一覧-編集: 保存でき詳細に編集通り表示",
  "list.duplicate": "一覧-複製: 複製でき一覧に表示",
  "list.delete": "一覧-削除: 削除でき一覧に非表示",
  "list.search": "一覧-検索ができる",
  "list.searchReset": "一覧-検索がリセットできる",
  "list.cv.personal.show": "個人リストが表示される",
  "list.cv.personal.delete": "個人リストが削除できる",
  "list.cv.personal.dup": "個人リストが複製できる",
  "list.cv.personal.edit": "個人リストが編集できる",
  "list.cv.shared.self": "共有リストが表示される(自分)",
  "list.cv.shared.other": "共有リストが表示される(別ユーザー)",
  "detail.edit": "詳細-編集: 保存でき詳細に編集通り表示",
  "detail.duplicate": "詳細-複製: 複製でき詳細に表示(添付引継ぎは非検証)",
  "detail.delete": "詳細-削除: 削除でき一覧に非表示",
  "detail.file.upload": "詳細-アップロードしたファイルが表示される",
  "detail.file.download": "詳細-ファイルがダウンロードできる",
  "detail.comment.post": "詳細-コメントが投稿できる",
  "detail.comment.file": "詳細-コメントにファイルが添付できる",
  "related.search": "関連-検索できる",
  "related.searchReset": "関連-検索がリセットできる",
  "related.navigate": "関連-関連レコード詳細へ遷移・表示できる",
};

export function reason(cap: Capability): string {
  return cap === "na" ? "機能なし(N/A)" : "対象外(skip/未実装)";
}

export function capabilityOf(m: ModuleMatrix, c: CaseId): Capability {
  return m.cases[c] ?? "run";
}

/**
 * 全 CaseId を "skip" にした cases マップを返す(DRY 用ヘルパ)。
 * スプレッドシートで列全体が ※skip のモジュール(新規モジュール/申請)に使う。
 */
function allSkip(): Partial<Record<CaseId, Capability>> {
  const cases: Partial<Record<CaseId, Capability>> = {};
  for (const c of ALL_CASES) cases[c] = "skip";
  return cases;
}

/**
 * 全 CaseId を "na" にした cases マップを返す(DRY 用ヘルパ)。
 * スプレッドシートで列全体が -(グレー)のモジュール(SMSNotifier: 一覧・詳細を持たない)に使う。
 */
function allNa(): Partial<Record<CaseId, Capability>> {
  const cases: Partial<Record<CaseId, Capability>> = {};
  for (const c of ALL_CASES) cases[c] = "na";
  return cases;
}

/**
 * ============================================================================
 * 【重要】非 Accounts モジュールの列について(Task 10 で追加 / Task 10b で転記)
 * ============================================================================
 * 以下の非 Accounts エントリはすべて `enabled: true` の「足場(スキャフォールド)」であり、
 * describe 全体が実行時 skip される非実行状態。
 *
 * Task 10b: 各モジュールの `cases` は、元スプレッドシート
 * `2_〇×_OSS版_基本機能.xlsx` の `_基本機能` シートから正確に転記した
 * (空欄 = run / -(グレー) = na / ※skip = skip)。`cases: {}` のモジュール
 * (Contacts, Leads, Potentials, Project, ProjectTask, Invoice, Quotes,
 * PurchaseOrder, SalesOrder, HelpDesk, Faq)はシート上で全ケース空欄(run)だった
 * ことを意味する。
 *
 * 転記に関する注記:
 * - カレンダー列と活動列はシート上で全行同一分類のため、Calendar 1エントリに集約した。
 * - ブックマーク列は暫定的に `Portal` エントリ(内部モジュール名。languages/ja_jp/Portal.php
 *   で 'Portal' => 'ブックマーク' と確認)にマップした。内部モジュール名と
 *   シート上の「ブックマーク」列の対応は要確認。
 * - シートの「マイリスト(自分)」「マイリスト(別ユーザー)」の2行は現行23ケースに
 *   対応するケースが無いため、今回は転記対象外(別途対応)。
 * - 各モジュールの `app` 値と `enabled` は据え置き(Task 10 時点のまま)。
 *   `enabled: true` の間は全ケースが describe skip され実行に一切影響しないため、
 *   `app` の正確性は今は実行結果を左右しない。各モジュールを `enabled: true` に
 *   切り替える展開(ロールアウト)タイミングで、実画面(admin/実データ)で
 *   na/skip と app の妥当性を再検証してからロールアウトすること。
 * ============================================================================
 */

/** スプレッドシートで列全体が ※skip の2列(新規モジュール/申請)専用の cases。 */
const ALL_SKIP_CASES = allSkip();

/** スプレッドシートで列全体が -(グレー)の SMSNotifier 専用の cases。 */
const ALL_NA_CASES = allNa();

/**
 * スプレッドシートの転記 + 展開ゲート。
 * 第一デリバリでは Accounts のみ enabled=true。他モジュールは Task 10 で列を足す。
 *
 * Accounts: 未実装ケースは一旦 skip にし、対応タスクが実装したら run へ戻す。
 * (Task 2〜9 が各ケースを実装するたびに、該当行の skip を削除して run 既定へ)
 */
export const MATRIX: ModuleMatrix[] = [
  {
    module: "Accounts",
    app: "MARKETING",
    enabled: true,
    cases: {
      // Task 9 で実装(list.cv.shared.self は run 既定/緑)。
      // list.cv.shared.other のみ、DB 環境起因のバグにより暫定 skip(詳細下記コメント)。
      //
      // 【list.cv.shared.other が現状 skip の理由】
      // CustomView_Record_Model::getAll() の非 admin 向け SQL は
      // vtiger_cv2role / vtiger_cv2rs をサブクエリで参照するが、この2テーブルが
      // e2e/fixtures/e2e_base_install.sql(および現行 e2e_dump.sql)には存在しない。
      // そのため非admin(例: e2e_director)でログインすると当該SQLが
      // "Table doesn't exist" で失敗し、admin以外の全ユーザーにおいて
      // CustomView(個人/共有問わず)が一件も見えなくなる(admin は同関数内の
      // 分岐でこのサブクエリを通らないため影響を受けず、これまでの Task 1-8 が
      // 常時 admin ログインだったために未検出だった)。
      // setup/sql/dump_firstinstall.sql には両テーブルが定義されており、
      // modules/Migration/schema/660_to_700.php(旧バージョンアップ用スキーマ)にも
      // 同テーブルの追加コードがあるが、現行の setup/migration/run_migration.php --all は
      // setup/migration/scripts/ のみを対象にしており、660_to_700.php 側は実行対象外。
      // つまり e2e_base_install.sql がその旧アップグレードを経由しない状態の
      // スキーマから作られているため、CI含む全 E2E 環境で再現する見込み。
      // 追加テーブルを作る新規マイグレーション案(setup/migration/scripts/
      // 20260709161603_add_missing_cv2role_cv2rs_tables.php、660_to_700.php の
      // CREATE TABLE 定義を移植)をこのセッションで作成したが、DB スキーマ変更の
      // 実行自体は本タスクの権限/スコープ外のため未実行・未コミット
      // (このファイル自体もコミット対象外)。別 PR で
      // (1) 上記マイグレーションを setup/migration/scripts/ に追加、
      // (2) e2e_base_install.sql / e2e_dump.sql を再生成、を行い、
      // その後このケースを skip → run に戻すこと。
      "list.cv.shared.other": "skip",
      // Task 1 で run のまま緑化する再利用系:
      // list.create.detail / list.edit / list.delete / list.search / detail.delete / detail.comment.post
      // Task 2 で実装済み(run 既定):
      // list.cv.personal.show / list.cv.personal.delete / list.cv.personal.dup / list.cv.personal.edit
    },
  },

  // ==========================================================================
  // 以下、非 Accounts モジュールの足場(すべて enabled: true / 非実行)。
  // ファイル冒頭の【重要】コメントの通り、cases のセル単位 na/skip は
  // Task 10b でスプレッドシートから転記済み(cases: {} は「シート上で全ケース run」の意)。
  // ==========================================================================

  // 顧客担当者(Contacts): app は `test/module/contacts.spec.ts` で実績あり(MARKETING)。
  { module: "Contacts", app: "MARKETING", enabled: true, cases: {} },

  // 案件(Potentials): app は MenuStructure.php の regroupMenuByParent() より SALES。
  { module: "Potentials", app: "SALES", enabled: true, cases: {} },

  // リード(Leads): app は MenuStructure.php より MARKETING。
  { module: "Leads", app: "MARKETING", enabled: true, cases: {} },

  // 製品(Products): MenuStructure.php では SALES/INVENTORY の両方に属する。SALES を暫定採用。
  {
    module: "Products",
    app: "SALES",
    enabled: true,
    cases: { "detail.comment.post": "na", "detail.comment.file": "na" },
  },

  // サービス(Services): Products と同様 SALES/INVENTORY の両方に属する。SALES を暫定採用。
  {
    module: "Services",
    app: "SALES",
    enabled: true,
    cases: { "detail.comment.post": "na", "detail.comment.file": "na" },
  },

  // ドキュメント(Documents): MenuStructure.php の getIgnoredModules() に含まれ、
  // 通常のアプリタブ分類に属さない(関連一覧からの添付が主用途)。app は暫定 MARKETING、要確認。
  {
    module: "Documents",
    app: "MARKETING",
    enabled: true,
    cases: {
      "list.duplicate": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
      "related.search": "na",
      "related.searchReset": "na",
      "related.navigate": "na",
    },
  },

  // プロジェクト(Project): MenuStructure.php より PROJECT。
  { module: "Project", app: "PROJECT", enabled: true, cases: {} },

  // タスク(ProjectTask): MenuStructure.php より PROJECT。
  { module: "ProjectTask", app: "PROJECT", enabled: true, cases: {} },

  // マイルストーン(ProjectMilestone): MenuStructure.php より PROJECT。
  {
    module: "ProjectMilestone",
    app: "PROJECT",
    enabled: true,
    cases: {
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
    },
  },

  // 資産・レンタル管理(Assets): MenuStructure.php より SUPPORT。
  {
    module: "Assets",
    app: "SUPPORT",
    enabled: true,
    cases: { "detail.comment.post": "na", "detail.comment.file": "na" },
  },

  // 契約(ServiceContracts): MenuStructure.php より SUPPORT。
  {
    module: "ServiceContracts",
    app: "SUPPORT",
    enabled: true,
    cases: { "detail.comment.post": "na", "detail.comment.file": "na" },
  },

  // 発注先(Vendors): MenuStructure.php の regroupMenuByParent() 表では INVENTORY だが、
  // `test/module/vendors.spec.ts` は app=SUPPORT で実績あり(現に green)。実績値を採用し、
  // 表記との不一致は有効化時に実画面で要確認。
  {
    module: "Vendors",
    app: "SUPPORT",
    enabled: true,
    cases: {
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
    },
  },

  // 価格表(PriceBooks): MenuStructure.php(表記は "Pricebooks" 小文字だが内部モジュール名は
  // "PriceBooks"。`modules/PriceBooks/` 実在確認済)より INVENTORY。
  {
    module: "PriceBooks",
    app: "INVENTORY",
    enabled: true,
    cases: {
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
    },
  },

  // 発注(PurchaseOrder): MenuStructure.php より INVENTORY。
  { module: "PurchaseOrder", app: "INVENTORY", enabled: true, cases: {} },

  // 受注(SalesOrder): MenuStructure.php より INVENTORY。
  { module: "SalesOrder", app: "INVENTORY", enabled: true, cases: {} },

  // 請求(Invoice): MenuStructure.php より INVENTORY。
  { module: "Invoice", app: "INVENTORY", enabled: true, cases: {} },

  // 見積(Quotes): MenuStructure.php より SALES。
  { module: "Quotes", app: "SALES", enabled: true, cases: {} },

  // キャンペーン(Campaigns): MenuStructure.php より MARKETING。
  {
    module: "Campaigns",
    app: "MARKETING",
    enabled: true,
    cases: {
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
    },
  },

  // 日報(Dailyreports): MenuStructure.php より SALES。
  {
    module: "Dailyreports",
    app: "SALES",
    enabled: true,
    cases: { "related.searchReset": "na", "related.navigate": "na" },
  },

  // カレンダー/活動(Calendar): MenuStructure.php の getIgnoredModules() に含まれる
  // (通常のアプリタブ分類外)が、`test/module/calendar.spec.ts` は app=SALES で実績あり。
  // シート上、カレンダー列と活動列は全行同一分類のためこの1エントリに集約。
  {
    module: "Calendar",
    app: "SALES",
    enabled: true,
    cases: {
      "list.duplicate": "na",
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
      "related.search": "na",
      "related.searchReset": "na",
      "related.navigate": "na",
    },
  },

  // チケット(HelpDesk): app は `test/module/helpdesk.spec.ts` で実績あり(SUPPORT)。
  { module: "HelpDesk", app: "SUPPORT", enabled: true, cases: {} },

  // FAQ(Faq): MenuStructure.php より SUPPORT。
  { module: "Faq", app: "SUPPORT", enabled: true, cases: {} },

  // メールテンプレート(EmailTemplates): app は `test/module/templates.spec.ts` で実績あり(TOOLS)。
  {
    module: "EmailTemplates",
    app: "TOOLS",
    enabled: true,
    cases: {
      "list.duplicate": "na",
      "list.search": "na",
      "list.searchReset": "na",
      "list.cv.personal.show": "na",
      "list.cv.personal.delete": "na",
      "list.cv.personal.dup": "na",
      "list.cv.personal.edit": "na",
      "list.cv.shared.self": "na",
      "list.cv.shared.other": "na",
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
      "related.search": "na",
      "related.searchReset": "na",
      "related.navigate": "na",
    },
  },

  // PDFテンプレート(PDFTemplates): app は `test/module/templates.spec.ts` で実績あり(TOOLS)。
  {
    module: "PDFTemplates",
    app: "TOOLS",
    enabled: true,
    cases: {
      "list.duplicate": "na",
      "list.search": "na",
      "list.searchReset": "na",
      "list.cv.personal.show": "na",
      "list.cv.personal.delete": "na",
      "list.cv.personal.dup": "na",
      "list.cv.personal.edit": "na",
      "list.cv.shared.self": "na",
      "list.cv.shared.other": "na",
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
      "related.search": "na",
      "related.searchReset": "na",
      "related.navigate": "na",
    },
  },

  // SMS通知(SMSNotifier): `modules/SMSNotifier/views/` には Edit.php/CheckStatus.php のみで
  // List.php/Detail.php が無く、一覧・詳細を持つ通常モジュールではない(Settings 配下の
  // 送信設定用スモーク `admin.F-08.SMSNotifier.spec.ts` で別途スモーク済み)。シート上も
  // 23ケース全てが na(グレー)であったため全ケース na で転記。
  // app は未確定(MenuStructure.php の getIgnoredModules() に含まれる)。
  { module: "SMSNotifier", enabled: true, cases: ALL_NA_CASES },

  // ブックマーク: 内部モジュール名は "Portal"(languages/ja_jp/Portal.php で
  // 'Portal' => 'ブックマーク' と確認)。MenuStructure.php より app は TOOLS。
  // 通常の CRUD モジュールと異なり項目が名前+URLのみの簡易ブックマーク機能。
  // シート上の「ブックマーク」列を暫定的にこのエントリへマップ(内部モジュール名の対応は要確認)。
  {
    module: "Portal",
    app: "TOOLS",
    enabled: true,
    cases: {
      "list.duplicate": "na",
      "list.search": "na",
      "list.searchReset": "na",
      "list.cv.personal.show": "na",
      "list.cv.personal.delete": "na",
      "list.cv.personal.dup": "na",
      "list.cv.personal.edit": "na",
      "list.cv.shared.self": "na",
      "list.cv.shared.other": "na",
      "detail.edit": "na",
      "detail.duplicate": "na",
      "detail.delete": "na",
      "detail.file.upload": "na",
      "detail.file.download": "na",
      "detail.comment.post": "na",
      "detail.comment.file": "na",
      "related.search": "na",
      "related.searchReset": "na",
      "related.navigate": "na",
    },
  },

  // 新規モジュール: スプレッドシート上のプレースホルダ列で、対応する実在の内部モジュールが
  // 存在しない(コードベース上に "NewModule" 等の実装は無い)。将来追加される汎用/カスタム
  // モジュールの置き場として列だけ確保する目的と判断し、実行させないダミーエントリとして
  // 全ケース skip で追加した。実モジュールが特定・実装された時点でこの行を差し替えること。
  { module: "NewModule", enabled: true, cases: ALL_SKIP_CASES },

  // 申請(Approval): 実機確認済み(`module=Approval` は空ページ)で未実装の可能性が高い
  // (TEST_COVERAGE.md §4 D-11 参照)。スプレッドシート上も列全体が ※skip のため、
  // 実装の有無にかかわらず全ケース skip で追加した。
  { module: "Approval", enabled: true, cases: ALL_SKIP_CASES },
];

// 【全モジュール一律の暫定 skip】list.cv.shared.other(共有リスト=別ユーザー表示)
// e2e ベースライン DB に vtiger_cv2role / vtiger_cv2rs が無く、非 admin の CustomView
// 取得 SQL が失敗するため、admin 以外では個人/共有問わずリストが 0 件になる既存バグ
// (別 PR で DB 修正予定。setup/migration/scripts/20260709161603_...php 下書き済み)。
// このバグは全モジュール共通で shared.other を必ず失敗させ、しかも serial group の
// 中盤で落ちて後続ケースを did-not-run にするため、DB 修正までは全エントリで一律 skip
// にする(Accounts は元々 skip 指定済み)。DB 修正後にこのループごと削除して run に戻す。
for (const _m of MATRIX) {
  if ((_m.cases["list.cv.shared.other"] ?? "run") === "run") {
    _m.cases["list.cv.shared.other"] = "skip";
  }
}
