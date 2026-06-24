import { useState, useCallback } from "react";

interface UploadResult {
  success: boolean;
  id?: number;
  title?: string;
  filename: string;
  filesize?: number;
  filetype?: string;
  error?: string;
}

interface UseFileUploadResult {
  isUploading: boolean;
  progress: number;
  results: UploadResult[];
  error: string | null;
  upload: (files: FileList | File[], folderId: number, parentModule?: string, parentId?: number) => Promise<void>;
}

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as any).csrfMagicName;
  const csrfToken = (window as any).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

export function useFileUpload(onComplete?: () => void): UseFileUploadResult {
  const [isUploading, setIsUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [results, setResults] = useState<UploadResult[]>([]);
  const [error, setError] = useState<string | null>(null);

  const upload = useCallback(
    async (files: FileList | File[], folderId: number, parentModule?: string, parentId?: number) => {
      const fileArray = Array.from(files);
      if (fileArray.length === 0) return;
      if (fileArray.length > 10) {
        setError("LBL_MAX_UPLOAD_FILES");
        return;
      }

      setIsUploading(true);
      setProgress(0);
      setError(null);
      setResults([]);

      try {
        const csrf = getCsrfToken();
        const formData = new FormData();
        if (csrf) {
          formData.append(csrf.name, csrf.value);
        }
        formData.append("module", "Documents");
        formData.append("action", "QuickUpload");
        formData.append("folderid", String(folderId));
        if (parentModule) {
          formData.append("parent_module", parentModule);
        }
        if (parentId) {
          formData.append("parent_id", String(parentId));
        }

        fileArray.forEach((file) => {
          formData.append("files[]", file);
        });

        const xhr = new XMLHttpRequest();
        const uploadPromise = new Promise<UploadResult[]>((resolve, reject) => {
          xhr.upload.addEventListener("progress", (e) => {
            if (e.lengthComputable) {
              setProgress(Math.round((e.loaded / e.total) * 100));
            }
          });

          xhr.addEventListener("load", () => {
            try {
              const data = JSON.parse(xhr.responseText);
              if (data.success === false || data.error) {
                reject(new Error(data.error?.message || "Upload failed"));
              } else {
                const result = data.result || data;
                resolve(result.files);
              }
            } catch {
              reject(new Error("Invalid response"));
            }
          });

          xhr.addEventListener("error", () => reject(new Error("Network error")));
          xhr.addEventListener("abort", () => reject(new Error("Upload aborted")));

          xhr.open("POST", "index.php");
          xhr.setRequestHeader("Accept", "application/json");
          xhr.withCredentials = true;
          xhr.send(formData);
        });

        const uploadResults = await uploadPromise;
        setResults(uploadResults);
        setProgress(100);
        onComplete?.();
      } catch (e: any) {
        setError(e.message || "Upload failed");
      } finally {
        setIsUploading(false);
      }
    },
    [onComplete]
  );

  return { isUploading, progress, results, error, upload };
}
