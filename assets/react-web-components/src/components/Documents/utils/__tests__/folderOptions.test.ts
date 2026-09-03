import { describe, it, expect } from "vitest";
import { buildFolderOptions, indentFolderName } from "../folderOptions";
import type { Folder } from "../../types/documents";

/** インデントに使う文字（option では半角スペースが詰められるため） */
const NBSP = "\u00A0";
const BRANCH = `└${NBSP}`;
const INDENT = NBSP.repeat(4);

/** テスト用のフォルダを作る */
function folder(
  id: number,
  name: string,
  parentId = 0,
  extra: Partial<Folder> = {},
): Folder {
  return {
    id,
    name,
    description: "",
    parent_id: parentId,
    sequence: id,
    count: 0,
    ...extra,
  };
}

describe("indentFolderName", () => {
  it("ルートはそのまま", () => {
    expect(indentFolderName("営業資料", 0)).toBe("営業資料");
  });

  it("子には目印が付く", () => {
    expect(indentFolderName("見積書", 1)).toBe(`${BRANCH}見積書`);
  });

  it("孫は1段ぶん下がる", () => {
    expect(indentFolderName("2026年", 2)).toBe(`${INDENT}${BRANCH}2026年`);
  });
});

describe("buildFolderOptions", () => {
  it("親のすぐ下に子が並ぶ", () => {
    const folders = [
      folder(1, "営業資料"),
      folder(2, "製品情報"),
      folder(3, "見積書", 1),
      folder(4, "2026年", 3),
      folder(5, "カタログ", 2),
    ];
    const options = buildFolderOptions(folders);
    expect(options.map((o) => o.id)).toEqual([1, 3, 4, 2, 5]);
    expect(options.map((o) => o.depth)).toEqual([0, 1, 2, 0, 1]);
  });

  it("同じ階層は元の並び順を保つ", () => {
    const folders = [folder(1, "A"), folder(3, "C", 1), folder(2, "B", 1)];
    const options = buildFolderOptions(folders);
    expect(options.map((o) => o.name)).toEqual(["A", "C", "B"]);
  });

  it("深さに応じたインデントが付く", () => {
    const folders = [folder(1, "親"), folder(2, "子", 1), folder(3, "孫", 2)];
    const options = buildFolderOptions(folders);
    expect(options.map((o) => o.label)).toEqual([
      "親",
      `${BRANCH}子`,
      `${INDENT}${BRANCH}孫`,
    ]);
  });

  it("excludeIds のフォルダは選択肢から消える", () => {
    const folders = [folder(1, "親"), folder(2, "子", 1), folder(3, "別")];
    const options = buildFolderOptions(folders, { excludeIds: [1] });
    // 親を除くと子は行き場が無くなるためルート扱いで残す（消さない）
    expect(options.map((o) => o.id)).toEqual([2, 3]);
    expect(options.map((o) => o.depth)).toEqual([0, 0]);
  });

  it("親が一覧に無いフォルダもルートとして残す", () => {
    const folders = [folder(5, "孤児", 99)];
    const options = buildFolderOptions(folders);
    expect(options).toHaveLength(1);
    expect(options[0].depth).toBe(0);
    expect(options[0].label).toBe("孤児");
  });

  it("選べないフォルダは消さずに disabled で残す", () => {
    const folders = [
      folder(1, "読み取り専用", 0, { can_edit: false }),
      folder(2, "その下", 1, { can_edit: true }),
    ];
    const options = buildFolderOptions(folders, {
      isDisabled: (f) => f.can_edit === false,
    });
    expect(options.map((o) => o.id)).toEqual([1, 2]);
    expect(options.map((o) => o.disabled)).toEqual([true, false]);
    // 親が選べなくても階層は保たれる
    expect(options[1].depth).toBe(1);
  });

  it("階層が循環していても止まらない", () => {
    const folders = [folder(1, "A", 2), folder(2, "B", 1)];
    const options = buildFolderOptions(folders);
    expect(options.map((o) => o.id).sort()).toEqual([1, 2]);
  });

  it("空の一覧は空を返す", () => {
    expect(buildFolderOptions([])).toEqual([]);
  });
});
