import { useCallback, useEffect, useState } from "react";
import type {
  ComplianceSettingsInfo,
  DeadlineSettings,
  RecalculateResult,
  SaveSettingsResult,
} from "../types/documentsCompliance";

/**
 * 電子帳簿保存法設定のデータ取得・更新
 *
 * サーバー側は Settings:DocumentsCompliance の ComplianceSettingsAPI（JSON）。
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

/** ComplianceSettingsAPI を呼び出す */
async function callApi<T>(
  mode: string,
  params: Record<string, string> = {},
): Promise<T> {
  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "DocumentsCompliance");
  body.append("parent", "Settings");
  body.append("api", "ComplianceSettingsAPI");
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
    data = JSON.parse(text);
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

interface UseComplianceSettingsResult {
  info: ComplianceSettingsInfo | null;
  isLoading: boolean;
  isSaving: boolean;
  error: string | null;
  setError: (message: string | null) => void;
  /** 設定を保存する */
  save: (settings: DeadlineSettings) => Promise<void>;
  /** 既存ドキュメントの入力期限を再計算する */
  recalculate: () => Promise<RecalculateResult>;
}

/**
 * 電子帳簿保存法設定を扱うフック
 */
export function useComplianceSettings(): UseComplianceSettingsResult {
  const [info, setInfo] = useState<ComplianceSettingsInfo | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setIsLoading(true);
      setError(null);
      try {
        const loaded = await callApi<ComplianceSettingsInfo>("info");
        if (!cancelled) setInfo(loaded);
      } catch (e) {
        if (!cancelled) setError(e instanceof Error ? e.message : String(e));
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const save = useCallback(async (settings: DeadlineSettings) => {
    setIsSaving(true);
    setError(null);
    try {
      const result = await callApi<SaveSettingsResult>("save", {
        policy: settings.policy,
        business_days: String(settings.business_days),
        cycle_months: String(settings.cycle_months),
        warning_days: String(settings.warning_days),
      });
      setInfo((current) =>
        current
          ? { ...current, settings: result.settings, example: result.example }
          : current,
      );
    } finally {
      setIsSaving(false);
    }
  }, []);

  const recalculate = useCallback(async () => {
    setIsSaving(true);
    setError(null);
    try {
      return await callApi<RecalculateResult>("recalculate");
    } finally {
      setIsSaving(false);
    }
  }, []);

  return { info, isLoading, isSaving, error, setError, save, recalculate };
}
