import { useCallback, useEffect, useState } from "react";
import type {
  SharedLink,
  SharedLinkInfo,
  SharedLinkInput,
  SharedLinkListResult,
  SharedLinkSaveResult,
  SharedLinkSaveSetResult,
  SharedLinkSet,
} from "@/types/sharedLinks";

/**
 * 共有リンクのデータ取得・更新
 *
 * サーバー側は Settings:SharedLinks の SharedLinkAPI（JSON）。
 * 設定機能のためシステム管理者のみ利用できる。
 */

interface ApiResponse<T> {
  success?: boolean;
  result?: T;
  error?: { message?: string; code?: string };
}

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

/** SharedLinkAPI を呼び出す */
export async function callSharedLinkApi<T>(
  mode: string,
  params: Record<string, string> = {},
): Promise<T> {
  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "SharedLinks");
  body.append("parent", "Settings");
  body.append("api", "SharedLinkAPI");
  body.append("mode", mode);
  for (const [key, value] of Object.entries(params)) {
    body.append(key, value);
  }

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: body.toString(),
  });
  const text = await response.text();
  let data: ApiResponse<T>;
  try {
    data = JSON.parse(text) as ApiResponse<T>;
  } catch {
    throw new Error("Invalid response");
  }
  if (data.success === false || data.error) {
    throw new Error(
      data.error?.message || data.error?.code || "Request failed",
    );
  }
  return (data.result ?? data) as T;
}

interface UseSharedLinksResult {
  info: SharedLinkInfo | null;
  sets: SharedLinkSet[];
  links: SharedLink[];
  isLoading: boolean;
  isSaving: boolean;
  error: string | null;
  setError: (message: string | null) => void;
  /** リンクを保存する。入力に誤りがあれば saved=false と項目ごとのエラーを返す */
  save: (
    input: SharedLinkInput,
    setId: number,
    id?: number,
  ) => Promise<SharedLinkSaveResult>;
  remove: (id: number) => Promise<void>;
  /** 渡された ID の順に並び替える */
  reorder: (ids: number[]) => Promise<void>;
  /** ウィジェットを作る（id があれば見出しを変える） */
  saveSet: (title: string, id?: number) => Promise<SharedLinkSaveSetResult>;
  /** ウィジェットを削除する（中のリンクも消える） */
  removeSet: (id: number) => Promise<void>;
}

/** 共有リンクのデータを扱うフック */
export function useSharedLinks(): UseSharedLinksResult {
  const [info, setInfo] = useState<SharedLinkInfo | null>(null);
  const [sets, setSets] = useState<SharedLinkSet[]>([]);
  const [links, setLinks] = useState<SharedLink[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadList = useCallback(async () => {
    const result = await callSharedLinkApi<SharedLinkListResult>("list");
    setSets(result.sets || []);
    setLinks(result.links || []);
  }, []);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setIsLoading(true);
      setError(null);
      try {
        const loadedInfo = await callSharedLinkApi<SharedLinkInfo>("info");
        if (cancelled) return;
        setInfo(loadedInfo);
        await loadList();
      } catch (e) {
        if (!cancelled) setError(e instanceof Error ? e.message : String(e));
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [loadList]);

  const save = useCallback(
    async (input: SharedLinkInput, setId: number, id?: number) => {
      setIsSaving(true);
      setError(null);
      try {
        const params: Record<string, string> = {
          link: JSON.stringify(input),
          set_id: String(setId),
        };
        if (id) params.id = String(id);
        const result = await callSharedLinkApi<SharedLinkSaveResult>(
          "save",
          params,
        );
        if (result.saved) {
          await loadList();
        }
        return result;
      } finally {
        setIsSaving(false);
      }
    },
    [loadList],
  );

  const remove = useCallback(
    async (id: number) => {
      setIsSaving(true);
      setError(null);
      try {
        await callSharedLinkApi("delete", { id: String(id) });
        await loadList();
      } finally {
        setIsSaving(false);
      }
    },
    [loadList],
  );

  const reorder = useCallback(
    async (ids: number[]) => {
      setIsSaving(true);
      setError(null);
      try {
        await callSharedLinkApi("reorder", { ids: ids.join(",") });
        await loadList();
      } finally {
        setIsSaving(false);
      }
    },
    [loadList],
  );

  const saveSet = useCallback(
    async (title: string, id?: number) => {
      setIsSaving(true);
      setError(null);
      try {
        const params: Record<string, string> = { title };
        if (id) params.id = String(id);
        const result = await callSharedLinkApi<SharedLinkSaveSetResult>(
          "save_set",
          params,
        );
        if (result.saved) {
          await loadList();
        }
        return result;
      } finally {
        setIsSaving(false);
      }
    },
    [loadList],
  );

  const removeSet = useCallback(
    async (id: number) => {
      setIsSaving(true);
      setError(null);
      try {
        await callSharedLinkApi("delete_set", { id: String(id) });
        await loadList();
      } finally {
        setIsSaving(false);
      }
    },
    [loadList],
  );

  return {
    info,
    sets,
    links,
    isLoading,
    isSaving,
    error,
    setError,
    save,
    remove,
    reorder,
    saveSet,
    removeSet,
  };
}
