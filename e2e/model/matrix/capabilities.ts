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
];
