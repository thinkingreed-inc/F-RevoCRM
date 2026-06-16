import { useState, useCallback, useEffect, useRef } from "react";
import type {
  DocumentRecord,
  SortConfig,
  FilterType,
} from "../types/documents";

interface UseDocumentsListParams {
  folderId?: number | "all";
  filterType?: FilterType;
  searchKeyword?: string;
  sort?: SortConfig;
  page?: number;
  pageLimit?: number;
}

interface UseDocumentsListResult {
  records: DocumentRecord[];
  total: number;
  page: number;
  pageLimit: number;
  isLoading: boolean;
  error: string | null;
  reload: () => void;
}

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as any).csrfMagicName;
  const csrfToken = (window as any).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

export function useDocumentsList(
  params: UseDocumentsListParams
): UseDocumentsListResult {
  const [records, setRecords] = useState<DocumentRecord[]>([]);
  const [total, setTotal] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  const fetchList = useCallback(async () => {
    // 前回のリクエストをキャンセル
    if (abortRef.current) {
      abortRef.current.abort();
    }
    const controller = new AbortController();
    abortRef.current = controller;

    setIsLoading(true);
    setError(null);

    try {
      const csrf = getCsrfToken();
      const bodyParams = new URLSearchParams();
      if (csrf) {
        bodyParams.append(csrf.name, csrf.value);
      }
      bodyParams.append("module", "Documents");
      bodyParams.append("api", "ListAPI");

      if (params.folderId !== undefined && params.folderId !== "all") {
        bodyParams.append("folder_id", String(params.folderId));
      }
      if (params.filterType && params.filterType !== "all") {
        bodyParams.append("filter_type", params.filterType);
      }
      if (params.searchKeyword) {
        bodyParams.append("search_keyword", params.searchKeyword);
      }
      if (params.sort) {
        bodyParams.append("sort_by", params.sort.field);
        bodyParams.append("sort_order", params.sort.order);
      }
      if (params.page) {
        bodyParams.append("page", String(params.page));
      }
      if (params.pageLimit) {
        bodyParams.append("pageLimit", String(params.pageLimit));
      }
      const response = await fetch("index.php", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: bodyParams.toString(),
        signal: controller.signal,
      });

      const data = await response.json();
      if (data.success === false || data.error) {
        throw new Error(data.error?.message || "Failed to fetch documents");
      }

      const result = data.result || data;
      setRecords(result.records);
      setTotal(result.total);
    } catch (e: any) {
      if (e.name !== "AbortError") {
        setError(e.message || "Unknown error");
      }
    } finally {
      setIsLoading(false);
    }
  }, [
    params.folderId,
    params.filterType,
    params.searchKeyword,
    params.sort?.field,
    params.sort?.order,
    params.page,
    params.pageLimit,
  ]);

  useEffect(() => {
    fetchList();
  }, [fetchList]);

  return {
    records,
    total,
    page: params.page || 1,
    pageLimit: params.pageLimit || 20,
    isLoading,
    error,
    reload: fetchList,
  };
}
