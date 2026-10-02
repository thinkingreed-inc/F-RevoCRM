/**
 * フォルダ階層の用意（フォルダのドラッグ＆ドロップ用）
 *
 * FolderAPI の ensurePath を呼び、同じ親に同名フォルダがあれば再利用し、
 * 無ければ作成して末端のフォルダIDを返す。
 */

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) return { name: csrfName, value: csrfToken };
  return null;
}

interface EnsurePathResponse {
  success?: boolean;
  result?: { folderid?: number };
  folderid?: number;
  error?: { message?: string };
}

/** 解決済みのフォルダIDを覚えておくキャッシュ（同じ階層を何度も作らないため） */
export type FolderPathCache = Map<string, number>;

export function createFolderPathCache(): FolderPathCache {
  return new Map();
}

function cacheKey(baseFolderId: number, dirPath: string[]): string {
  return `${baseFolderId}/${dirPath.join("/")}`;
}

/**
 * フォルダ階層を用意して末端のフォルダIDを返す
 *
 * @param dirPath ドロップ地点からのフォルダ階層。空配列なら baseFolderId をそのまま返す
 * @param baseFolderId 起点のフォルダID
 * @param cache 同一ドロップ内で共有するキャッシュ
 */
export async function ensureFolderPath(
  dirPath: string[],
  baseFolderId: number,
  cache: FolderPathCache = createFolderPathCache(),
): Promise<number> {
  if (dirPath.length === 0) return baseFolderId;

  const key = cacheKey(baseFolderId, dirPath);
  const cached = cache.get(key);
  if (cached !== undefined) return cached;

  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "Documents");
  body.append("api", "FolderAPI");
  body.append("mode", "ensurePath");
  body.append("parent_folderid", String(baseFolderId));
  body.append("path", JSON.stringify(dirPath));

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: body.toString(),
  });

  let data: EnsurePathResponse;
  try {
    data = await response.json();
  } catch {
    throw new Error("Invalid response");
  }
  if (data.success === false || data.error) {
    throw new Error(data.error?.message || "Failed to create folder");
  }
  const folderId = Number(data.result?.folderid ?? data.folderid);
  if (!Number.isFinite(folderId) || folderId <= 0) {
    throw new Error("Failed to create folder");
  }

  cache.set(key, folderId);
  return folderId;
}
