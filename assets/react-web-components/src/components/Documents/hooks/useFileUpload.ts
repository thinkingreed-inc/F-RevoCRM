import { useState, useCallback } from "react";
import { uploadFileInChunks } from "../utils/chunkUpload";

interface UploadResult {
  success: boolean;
  filename: string;
  error?: string;
}

interface UseFileUploadResult {
  isUploading: boolean;
  progress: number;
  results: UploadResult[];
  error: string | null;
  upload: (
    files: FileList | File[],
    folderId: number,
    parentModule?: string,
    parentId?: number,
  ) => Promise<void>;
}

/** 1回のドロップで受け付ける最大ファイル数 */
const MAX_FILES = 10;

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

/** Save アクションのレスポンスからエラーメッセージを取り出す */
function extractSaveError(text: string): string | null {
  if (!text.includes('"success":false')) return null;
  try {
    const data = JSON.parse(text);
    const message = data?.error?.message || data?.error?.code;
    if (typeof message === "string" && message.trim() !== "") return message;
  } catch {
    // JSON でない場合はメッセージを取り出せない
  }
  return "";
}

/**
 * 分割アップロード済みのファイルからドキュメントを登録する
 *
 * 標準の Save アクションに chunk_upload_id を渡すことで、
 * 項目値・関連付け・変更履歴・ファイルバージョンの処理をそのまま利用する。
 */
async function createDocument(
  uploadId: string,
  file: File,
  folderId: number,
  parentModule?: string,
  parentId?: number,
): Promise<void> {
  const csrf = getCsrfToken();
  const params = new URLSearchParams();
  if (csrf) params.append(csrf.name, csrf.value);
  params.append("module", "Documents");
  params.append("action", "Save");
  params.append("chunk_upload_id", uploadId);
  // 拡張子を除いたファイル名をタイトルにする
  params.append("notes_title", file.name.replace(/\.[^.]+$/, ""));
  params.append("filelocationtype", "I");
  params.append("filestatus", "1");
  params.append("folderid", String(folderId));
  if (parentModule && parentId) {
    params.append("relationOperation", "true");
    params.append("sourceModule", parentModule);
    params.append("sourceRecord", String(parentId));
  }

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString(),
  });
  const text = await response.text();
  const saveError = extractSaveError(text);
  if (saveError !== null) {
    throw new Error(saveError || "Failed to save");
  }
}

/**
 * ドラッグ＆ドロップでのファイル登録
 *
 * PHP の1リクエスト上限に依存しないよう、常に分割アップロードで送信する。
 */
export function useFileUpload(onComplete?: () => void): UseFileUploadResult {
  const [isUploading, setIsUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [results, setResults] = useState<UploadResult[]>([]);
  const [error, setError] = useState<string | null>(null);

  const upload = useCallback(
    async (
      files: FileList | File[],
      folderId: number,
      parentModule?: string,
      parentId?: number,
    ) => {
      const fileArray = Array.from(files);
      if (fileArray.length === 0) return;
      if (fileArray.length > MAX_FILES) {
        setError("LBL_MAX_UPLOAD_FILES");
        return;
      }

      setIsUploading(true);
      setProgress(0);
      setError(null);
      setResults([]);

      const uploadResults: UploadResult[] = [];
      let lastError: string | null = null;
      try {
        for (let i = 0; i < fileArray.length; i++) {
          const file = fileArray[i];
          try {
            const uploadId = await uploadFileInChunks(file, (percent) => {
              // 全ファイルを通した進捗にする
              setProgress(
                Math.round(((i + percent / 100) / fileArray.length) * 100),
              );
            });
            await createDocument(
              uploadId,
              file,
              folderId,
              parentModule,
              parentId,
            );
            uploadResults.push({ success: true, filename: file.name });
          } catch (e) {
            const message = e instanceof Error ? e.message : "Upload failed";
            lastError = message;
            uploadResults.push({
              success: false,
              filename: file.name,
              error: message,
            });
          }
        }
        setResults(uploadResults);
        setProgress(100);
        if (lastError !== null) {
          setError(lastError);
        }
        onComplete?.();
      } finally {
        setIsUploading(false);
      }
    },
    [onComplete],
  );

  return { isUploading, progress, results, error, upload };
}
