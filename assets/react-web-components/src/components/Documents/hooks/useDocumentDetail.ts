import { useState, useCallback, useEffect } from "react";
import type { DocumentDetail } from "../types/documents";

interface UseDocumentDetailResult {
  document: DocumentDetail | null;
  isLoading: boolean;
  error: string | null;
  reload: () => void;
}

export function useDocumentDetail(
  recordId: number | null
): UseDocumentDetailResult {
  const [document, setDocument] = useState<DocumentDetail | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchDetail = useCallback(async () => {
    if (!recordId) {
      setDocument(null);
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const url = new URLSearchParams({
        module: "Documents",
        api: "DetailAPI",
        record: String(recordId),
      });

      const response = await fetch(`index.php?${url.toString()}`, {
        method: "GET",
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });

      const data = await response.json();
      if (data.success === false || data.error) {
        throw new Error(data.error?.message || "Failed to fetch document");
      }

      setDocument(data.result || data);
    } catch (e: any) {
      setError(e.message || "Unknown error");
    } finally {
      setIsLoading(false);
    }
  }, [recordId]);

  useEffect(() => {
    fetchDetail();
  }, [fetchDetail]);

  return { document, isLoading, error, reload: fetchDetail };
}
