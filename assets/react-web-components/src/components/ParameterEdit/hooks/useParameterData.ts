import { useState, useCallback } from "react";
import {
  ParameterGetRecordApiResponse,
  ParameterRecord,
  ParameterSaveApiResponse,
  ParameterSaveRequest,
  ParameterSaveResponse,
} from "../types";
import "@/types/vtigerApp";

/**
 * APIのベースURL
 */
const getApiBaseUrl = () => {
  // 現在のページのURLからベースURLを取得
  const baseUrl =
    window.location.origin + window.location.pathname.replace(/\/[^/]*$/, "");
  return baseUrl.replace(/\/layouts\/.*$/, "").replace(/\/modules\/.*$/, "");
};

/**
 * CSRF トークンのパラメータ名と値を取得する
 *
 * csrf-magic は XMLHttpRequest だけを書き換えてトークンを自動付与するため、
 * fetch で POST する場合は自分で付ける必要がある。
 * 値は csrf-magic.js がグローバル（csrfMagicName / csrfMagicToken）に持っている。
 */
export const getCsrfParam = (): { name: string; token: string } => {
  const name =
    typeof window.csrfMagicName === "string"
      ? window.csrfMagicName
      : "__vtrftk";
  if (typeof window.csrfMagicToken === "string") {
    return { name, token: window.csrfMagicToken };
  }

  // フォールバック: 画面に埋め込まれた hidden input から取得する
  const input = document.querySelector(
    'input[name="__vtrftk"]',
  ) as HTMLInputElement | null;
  return { name, token: input?.value ?? "" };
};

/**
 * useParameterData - システム変数データの取得・保存を行うカスタムフック
 */
export function useParameterData() {
  const [data, setData] = useState<ParameterRecord | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  /**
   * レコードを取得
   */
  const fetchRecord = useCallback(
    async (id: number): Promise<ParameterRecord | null> => {
      setLoading(true);
      setError(null);

      try {
        // useRecordData.tsの方式に合わせてindex.php+apiパラメータで呼び出し
        const baseUrl = getApiBaseUrl();
        const url = `${baseUrl}/index.php?module=Parameters&parent=Settings&api=GetRecord&id=${id}`;
        const response = await fetch(url, {
          method: "GET",
          credentials: "same-origin",
          headers: {
            Accept: "application/json",
          },
        });

        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}));
          throw new Error(
            errorData.error?.message || `HTTP ${response.status}`,
          );
        }

        const result: ParameterGetRecordApiResponse = await response.json();

        if (result.success === false) {
          throw new Error(result.error?.message || "Failed to fetch record");
        }

        // APIレスポンスからデータを取得
        const record = (result.result ?? result) as ParameterRecord;
        setData(record);
        return record;
      } catch (err) {
        const message = err instanceof Error ? err.message : "Unknown error";
        setError(message);
        return null;
      } finally {
        setLoading(false);
      }
    },
    [],
  );

  /**
   * レコードを保存
   */
  const saveRecord = useCallback(
    async (request: ParameterSaveRequest): Promise<ParameterSaveResponse> => {
      setSaving(true);
      setError(null);

      try {
        const params = new URLSearchParams({
          module: "Parameters",
          parent: "Settings",
          api: "Save",
          id: String(request.id),
        });
        // value / description は変更する場合のみ送信する。
        // 未送信の場合はサーバー側が既存値を維持するため、値を取得できない
        // シークレット変数を備考だけ編集しても値が壊れない。
        if (request.value !== undefined) {
          params.set("value", request.value);
        }
        if (request.description !== undefined) {
          params.set("description", request.description);
        }
        if (request.secret !== undefined) {
          params.set("secret", String(request.secret));
        }
        const csrf = getCsrfParam();
        params.set(csrf.name, csrf.token);

        const response = await fetch(`${getApiBaseUrl()}/index.php`, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            Accept: "application/json",
          },
          body: params.toString(),
        });

        const result: ParameterSaveApiResponse = await response.json();
        // 成功時は API が { saved: true } を返す。
        // エラー時は Vtiger_Response が { success: false, error: {...} } を返す。
        if (result.success === false) {
          throw new Error(result.error?.message || "Save failed");
        }
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        return { success: true };
      } catch (err) {
        const message = err instanceof Error ? err.message : "Save failed";
        setError(message);
        return { success: false, error: message };
      } finally {
        setSaving(false);
      }
    },
    [],
  );

  /**
   * エラーをクリア
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  /**
   * データをクリア
   */
  const clearData = useCallback(() => {
    setData(null);
    setError(null);
  }, []);

  return {
    data,
    loading,
    saving,
    error,
    fetchRecord,
    saveRecord,
    clearError,
    clearData,
  };
}
