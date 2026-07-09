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
      // Task 2 以降で run へ切替(それまでは未実装 skip)
      "list.cv.shared.self": "skip",
      "list.cv.shared.other": "skip",
      "detail.file.upload": "skip",
      "detail.file.download": "skip",
      "detail.comment.file": "skip",
      "related.search": "skip",
      "related.searchReset": "skip",
      "related.navigate": "skip",
      // Task 1 で run のまま緑化する再利用系:
      // list.create.detail / list.edit / list.delete / list.search / detail.delete / detail.comment.post
      // Task 2 で実装済み(run 既定):
      // list.cv.personal.show / list.cv.personal.delete / list.cv.personal.dup / list.cv.personal.edit
    },
  },
];
