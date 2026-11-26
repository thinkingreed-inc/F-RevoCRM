import { useCallback, useEffect, useRef, useState } from "react";
import type { GetHistoryResponse } from "@/types/history";

export interface UseRecordHistoryResult {
  data: GetHistoryResponse | null;
  loading: boolean;
  error: string | null;
  reload: () => void;
}

/**
 * レコードの更新履歴をまとめて取得する。
 * 絞り込み・ページングはクライアント側で行うため、取得は 1 回だけ。
 */
export function useRecordHistory(
  module: string,
  recordId: string,
  refreshKey?: string | number,
): UseRecordHistoryResult {
  const [data, setData] = useState<GetHistoryResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [reloadCount, setReloadCount] = useState(0);
  const requestId = useRef(0);

  useEffect(() => {
    if (!module || !recordId) {
      setError("module / record-id is required");
      setLoading(false);
      return;
    }
    const current = ++requestId.current;
    const controller = new AbortController();
    setLoading(true);
    setError(null);

    const params = new URLSearchParams({
      module: "ModTracker",
      api: "GetHistory",
      parent_module: module,
      parent_id: recordId,
    });

    fetch(`index.php?${params.toString()}`, {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
      signal: controller.signal,
    })
      .then(async (response) => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const json = (await response.json()) as GetHistoryResponse;
        if (!json.success) throw new Error("success=false");
        if (current === requestId.current) setData(json);
      })
      .catch((err: unknown) => {
        if (controller.signal.aborted) return;
        console.error("useRecordHistory error:", err);
        if (current === requestId.current)
          setError(err instanceof Error ? err.message : String(err));
      })
      .finally(() => {
        if (current === requestId.current && !controller.signal.aborted)
          setLoading(false);
      });

    return () => controller.abort();
  }, [module, recordId, refreshKey, reloadCount]);

  const reload = useCallback(() => setReloadCount((n) => n + 1), []);

  return { data, loading, error, reload };
}
