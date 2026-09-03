/**
 * 分割アップロード
 *
 * PHP の upload_max_filesize / post_max_size は1リクエストの上限であり、
 * 設定変更なしには超えられない。そのためファイルを chunk_size ごとに分割して送信し、
 * サーバー側（Documents ChunkUpload API）で結合する。
 * 結合済みファイルは chunk_upload_id を Save アクションに渡すことで添付される。
 */

/** ChunkUpload API の設定値 */
export interface ChunkUploadInfo {
  /** 1リクエストで送るサイズ（バイト） */
  chunkSize: number;
  /** 1ファイルの最大サイズ（バイト） */
  maxSize: number;
  /** 最大サイズの表示用文字列（例: 2 GB） */
  maxSizeLabel: string;
  /** 1リクエストで送れる上限（バイト）。これを超えるファイルは分割送信が必要 */
  singleRequestLimit: number;
}

interface ApiResponse<T> {
  success?: boolean;
  result?: T;
  error?: { message?: string; code?: string };
}

/** チャンク送信に失敗したときの再試行回数 */
const MAX_RETRY = 3;

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

/** ChunkUpload API を呼び出す */
async function callChunkApi<T>(
  mode: string,
  fields: Record<string, string>,
  file?: { name: string; blob: Blob },
): Promise<T> {
  const csrf = getCsrfToken();
  const formData = new FormData();
  if (csrf) formData.append(csrf.name, csrf.value);
  formData.append("module", "Documents");
  formData.append("api", "ChunkUpload");
  formData.append("mode", mode);
  for (const [key, value] of Object.entries(fields)) {
    formData.append(key, value);
  }
  if (file) {
    formData.append("chunk", file.blob, file.name);
  }

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { Accept: "application/json" },
    body: formData,
  });
  const text = await response.text();
  let data: ApiResponse<T>;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error("Invalid response");
  }
  if (data.success === false || data.error) {
    throw new Error(data.error?.message || data.error?.code || "Upload failed");
  }
  return (data.result ?? data) as T;
}

/** 分割アップロードの設定値をサーバーから取得する */
export async function fetchChunkUploadInfo(): Promise<ChunkUploadInfo> {
  const result = await callChunkApi<{
    chunk_size: number;
    max_size: number;
    max_size_label: string;
    single_request_limit: number;
  }>("info", {});
  return {
    chunkSize: Number(result.chunk_size),
    maxSize: Number(result.max_size),
    maxSizeLabel: String(result.max_size_label),
    singleRequestLimit: Number(result.single_request_limit),
  };
}

/**
 * ファイルを分割してアップロードし、chunk_upload_id を返す
 *
 * @param file 対象ファイル
 * @param onProgress 進捗コールバック（0-100）
 * @param signal 中断用シグナル
 */
export async function uploadFileInChunks(
  file: File,
  onProgress?: (percent: number) => void,
  signal?: { aborted: boolean },
): Promise<string> {
  const init = await callChunkApi<{ upload_id: string; chunk_size: number }>(
    "init",
    {
      filename: file.name,
      filetype: file.type || "application/octet-stream",
      filesize: String(file.size),
    },
  );
  const uploadId = init.upload_id;
  const chunkSize = Number(init.chunk_size);

  try {
    // chunk_size が 0 や不正値だと1チャンクも進まず無限ループになるため、先に弾く
    if (!Number.isFinite(chunkSize) || chunkSize <= 0) {
      throw new Error("Invalid chunk size");
    }
    let offset = 0;
    let index = 0;
    while (offset < file.size) {
      if (signal?.aborted) {
        throw new Error("Upload aborted");
      }
      const blob = file.slice(offset, offset + chunkSize);
      let lastError: unknown = null;
      let sent = false;
      // 一時的な失敗は再試行する（サーバー側は順序を検証しているので同じ index を送り直す）
      for (let attempt = 0; attempt < MAX_RETRY && !sent; attempt++) {
        try {
          await callChunkApi(
            "chunk",
            {
              upload_id: uploadId,
              chunk_index: String(index),
            },
            { name: file.name, blob },
          );
          sent = true;
        } catch (e) {
          lastError = e;
        }
      }
      if (!sent) {
        throw lastError instanceof Error
          ? lastError
          : new Error("Upload failed");
      }
      offset += blob.size;
      index++;
      onProgress?.(Math.min(99, Math.round((offset / file.size) * 100)));
    }
    onProgress?.(100);
    return uploadId;
  } catch (e) {
    // 失敗時はサーバーの一時ファイルを片付ける
    try {
      await callChunkApi("abort", { upload_id: uploadId });
    } catch {
      // 後始末の失敗は無視する（一定時間後にサーバー側で自動削除される）
    }
    throw e;
  }
}
