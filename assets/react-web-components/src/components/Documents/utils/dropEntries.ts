/**
 * ドラッグ＆ドロップされた項目の展開
 *
 * フォルダをドロップした場合、DataTransfer.files にはフォルダ自身が
 * 中身のないエントリとして入るため、そのまま送るとアップロードに失敗する。
 * webkitGetAsEntry() で階層をたどり、ファイルと相対パスの組に展開する。
 */

/** ドロップされた1ファイル */
export interface DroppedFile {
  file: File;
  /** ドロップ地点からのフォルダ階層（ファイル名は含まない）。直下のファイルは空配列 */
  dirPath: string[];
}

export interface DroppedEntries {
  files: DroppedFile[];
  /** 走査を打ち切ったか（件数上限に達した） */
  truncated: boolean;
}

/** たどるフォルダ階層の上限（循環・異常な深さで止まらなくなるのを防ぐ） */
const MAX_DEPTH = 20;

/** 1回のドロップで走査するファイル数の上限（これを超えたら打ち切る） */
export const MAX_SCAN_FILES = 5000;

interface FileSystemEntryLike {
  isFile: boolean;
  isDirectory: boolean;
  name: string;
  file?: (
    onSuccess: (file: File) => void,
    onError?: (e: unknown) => void,
  ) => void;
  createReader?: () => {
    readEntries: (
      onSuccess: (entries: FileSystemEntryLike[]) => void,
      onError?: (e: unknown) => void,
    ) => void;
  };
}

function getEntry(item: DataTransferItem): FileSystemEntryLike | null {
  const anyItem = item as DataTransferItem & {
    webkitGetAsEntry?: () => FileSystemEntryLike | null;
    getAsEntry?: () => FileSystemEntryLike | null;
  };
  const get = anyItem.webkitGetAsEntry || anyItem.getAsEntry;
  if (typeof get !== "function") return null;
  try {
    return get.call(anyItem);
  } catch {
    return null;
  }
}

function readFile(entry: FileSystemEntryLike): Promise<File | null> {
  return new Promise((resolve) => {
    if (typeof entry.file !== "function") {
      resolve(null);
      return;
    }
    entry.file(
      (file) => resolve(file),
      () => resolve(null),
    );
  });
}

/**
 * ディレクトリの中身をすべて読む
 *
 * readEntries は1回で最大100件しか返さないため、空になるまで繰り返す。
 */
function readAllEntries(
  entry: FileSystemEntryLike,
): Promise<FileSystemEntryLike[]> {
  return new Promise((resolve) => {
    if (typeof entry.createReader !== "function") {
      resolve([]);
      return;
    }
    const reader = entry.createReader();
    const all: FileSystemEntryLike[] = [];
    const readBatch = () => {
      reader.readEntries(
        (entries) => {
          if (entries.length === 0) {
            resolve(all);
            return;
          }
          all.push(...entries);
          readBatch();
        },
        () => resolve(all),
      );
    };
    readBatch();
  });
}

/**
 * DataTransfer をファイルと相対パスの組に展開する
 *
 * webkitGetAsEntry に対応していないブラウザでは DataTransfer.files を
 * そのまま使う（階層は再現できないが、ファイルの登録はできる）。
 */
export async function collectDroppedEntries(
  dataTransfer: DataTransfer,
): Promise<DroppedEntries> {
  const items = dataTransfer.items ? Array.from(dataTransfer.items) : [];
  const entries = items
    .filter((item) => item.kind === "file")
    .map((item) => getEntry(item))
    .filter((entry): entry is FileSystemEntryLike => entry !== null);

  if (entries.length === 0) {
    // 階層を取れない場合はフラットに扱う
    return {
      files: Array.from(dataTransfer.files || []).map((file) => ({
        file,
        dirPath: [],
      })),
      truncated: false,
    };
  }

  const files: DroppedFile[] = [];
  let truncated = false;

  const walk = async (
    entry: FileSystemEntryLike,
    dirPath: string[],
  ): Promise<void> => {
    if (truncated) return;
    if (entry.isFile) {
      const file = await readFile(entry);
      if (file) {
        if (files.length >= MAX_SCAN_FILES) {
          truncated = true;
          return;
        }
        files.push({ file, dirPath });
      }
      return;
    }
    if (!entry.isDirectory) return;
    if (dirPath.length >= MAX_DEPTH) return;

    const children = await readAllEntries(entry);
    const childPath = [...dirPath, entry.name];
    for (const child of children) {
      await walk(child, childPath);
      if (truncated) return;
    }
  };

  for (const entry of entries) {
    await walk(entry, []);
    if (truncated) break;
  }

  return { files, truncated };
}
