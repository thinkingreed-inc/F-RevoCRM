/**
 * フォルダ選択（select）の選択肢を階層順に組み立てる
 *
 * フォルダ一覧 API はフラットな配列を返すため、そのまま並べると
 * どれがサブフォルダなのか、どのフォルダの下なのかが分からない。
 * 親のすぐ下に子が来るように並べ替え、深さに応じてインデントを付ける。
 *
 * インデントに半角スペースを使うと option では詰められてしまうため、
 * ノーブレークスペース（U+00A0）を使う。
 */
import type { Folder } from "../types/documents";

/** 1段ぶんのインデント */
const INDENT = "\u00A0\u00A0\u00A0\u00A0";

/** 子フォルダの目印 */
const BRANCH = "\u2514\u00A0";

/** 階層が壊れている（循環している）場合の打ち切り深さ */
const MAX_DEPTH = 50;

export interface FolderOption {
  id: number;
  /** フォルダ名（インデントなし） */
  name: string;
  /** 階層の深さ（ルート直下が 0） */
  depth: number;
  /** インデント付きの表示名 */
  label: string;
  /** 選べないフォルダ（親をたどれるよう、消さずに選択不可で残す） */
  disabled: boolean;
}

export interface BuildFolderOptionsParams {
  /** 選択肢から完全に外すフォルダID（移動先に自分自身や子孫を選べないようにする用） */
  excludeIds?: number[];
  /** 選択はできないが一覧には残すフォルダの判定 */
  isDisabled?: (folder: Folder) => boolean;
}

/** 深さに応じた表示名を作る */
export function indentFolderName(name: string, depth: number): string {
  if (depth <= 0) return name;
  return INDENT.repeat(depth - 1) + BRANCH + name;
}

/**
 * フォルダを階層順に並べた選択肢を返す
 *
 * 親が一覧に無いフォルダ（権限で見えない親を持つ場合など）はルート扱いにして、
 * 選択肢から消えてしまわないようにする。
 */
export function buildFolderOptions(
  folders: Folder[],
  params: BuildFolderOptionsParams = {},
): FolderOption[] {
  const { excludeIds = [], isDisabled } = params;
  const excluded = new Set(excludeIds);
  const visible = folders.filter((f) => !excluded.has(f.id));
  const visibleIds = new Set(visible.map((f) => f.id));

  // 入力の並び（API が sequence・名前順で返す）を保ったまま親ごとにまとめる
  const childrenOf = new Map<number, Folder[]>();
  const roots: Folder[] = [];
  visible.forEach((folder) => {
    const parentId = folder.parent_id || 0;
    // 親が見えない場合は行き場が無くなるためルートとして扱う
    if (parentId === 0 || !visibleIds.has(parentId)) {
      roots.push(folder);
      return;
    }
    const siblings = childrenOf.get(parentId);
    if (siblings) siblings.push(folder);
    else childrenOf.set(parentId, [folder]);
  });

  const options: FolderOption[] = [];
  const walked = new Set<number>();

  const walk = (folder: Folder, depth: number) => {
    // 階層が循環していても止まらないようにする
    if (walked.has(folder.id) || depth > MAX_DEPTH) return;
    walked.add(folder.id);

    options.push({
      id: folder.id,
      name: folder.name,
      depth,
      label: indentFolderName(folder.name, depth),
      disabled: isDisabled ? isDisabled(folder) : false,
    });
    (childrenOf.get(folder.id) || []).forEach((child) =>
      walk(child, depth + 1),
    );
  };

  roots.forEach((root) => walk(root, 0));

  // 循環などで到達できなかったフォルダも末尾に残す（選択肢から消さない）
  visible.forEach((folder) => {
    if (walked.has(folder.id)) return;
    walked.add(folder.id);
    options.push({
      id: folder.id,
      name: folder.name,
      depth: 0,
      label: folder.name,
      disabled: isDisabled ? isDisabled(folder) : false,
    });
  });

  return options;
}
