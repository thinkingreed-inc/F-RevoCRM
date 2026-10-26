/**
 * ファイルアップロード項目の API 呼び出し（module=<モジュール>&api=FileField）
 */
import type {
  FileFieldConfigResponse,
  FileFieldErrorResponse,
  FileFieldThumbnailResponse,
  FileFieldUploadResponse,
} from "@/types/fileUploadField";

const ENDPOINT = "index.php";

function isErrorResponse(data: unknown): data is FileFieldErrorResponse {
  return (
    typeof data === "object" &&
    data !== null &&
    (data as { success?: unknown }).success === false &&
    typeof (data as { error?: unknown }).error === "object"
  );
}

/**
 * 応答を JSON として読み、エラー形式ならメッセージ付きで例外にする
 * （vtiger は API のエラーも HTTP 200 で返すことがあるため、本文でも判定する）
 */
async function readJson<T>(response: Response): Promise<T> {
  let data: unknown = null;
  try {
    data = await response.json();
  } catch {
    data = null;
  }
  if (isErrorResponse(data)) {
    throw new Error(data.error.message || `HTTP ${response.status}`);
  }
  if (!response.ok || data === null) {
    throw new Error(`HTTP ${response.status}`);
  }
  return data as T;
}

function buildQuery(params: Record<string, string | number>): string {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) =>
    query.set(key, String(value)),
  );
  return query.toString();
}

/** 項目の設定（手書き入力か、アップロードできる大きさ）を取得する */
export async function fetchFileFieldConfig(
  module: string,
  fieldName: string,
): Promise<FileFieldConfigResponse> {
  const response = await fetch(
    `${ENDPOINT}?${buildQuery({ module, api: "FileField", mode: "config", field: fieldName })}`,
    { credentials: "same-origin" },
  );
  return readJson<FileFieldConfigResponse>(response);
}

/** ファイルをドキュメントとして登録する */
export async function uploadFieldFile(
  module: string,
  fieldName: string,
  file: Blob,
  fileName: string,
): Promise<FileFieldUploadResponse> {
  const body = new FormData();
  body.append("module", module);
  body.append("api", "FileField");
  body.append("mode", "upload");
  body.append("field", fieldName);
  body.append("file", file, fileName);
  if (window.csrfMagicName && window.csrfMagicToken) {
    body.append(window.csrfMagicName, window.csrfMagicToken);
  }
  const response = await fetch(ENDPOINT, {
    method: "POST",
    body,
    credentials: "same-origin",
  });
  return readJson<FileFieldUploadResponse>(response);
}

/** 画像の縮小版を取得する */
export async function fetchFieldThumbnail(
  module: string,
  fieldName: string,
  documentId: number,
  recordId?: number,
): Promise<FileFieldThumbnailResponse> {
  const params: Record<string, string | number> = {
    module,
    api: "FileField",
    mode: "thumbnail",
    field: fieldName,
    document: documentId,
  };
  if (recordId) {
    params.record = recordId;
  }
  const response = await fetch(`${ENDPOINT}?${buildQuery(params)}`, {
    credentials: "same-origin",
  });
  return readJson<FileFieldThumbnailResponse>(response);
}
