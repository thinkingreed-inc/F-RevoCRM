import { useState, useCallback } from 'react';
import { ParameterRecord, ParameterSaveRequest, ParameterSaveResponse } from '../types';

/**
 * APIのベースURL
 */
const getApiBaseUrl = () => {
  // 現在のページのURLからベースURLを取得
  const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
  return baseUrl.replace(/\/layouts\/.*$/, '').replace(/\/modules\/.*$/, '');
};

/**
 * CSRFトークンを取得
 */
export const getCsrfToken = (): string => {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  if (metaTag) {
    return metaTag.getAttribute('content') || '';
  }
  // フォールバック: hidden inputから取得
  const input = document.querySelector('input[name="__vtrftk"]') as HTMLInputElement;
  return input?.value || '';
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
  const fetchRecord = useCallback(async (id: number): Promise<ParameterRecord | null> => {
    setLoading(true);
    setError(null);

    try {
      // useRecordData.tsの方式に合わせてindex.php+apiパラメータで呼び出し
      const baseUrl = getApiBaseUrl();
      const url = `${baseUrl}/index.php?module=Parameters&parent=Settings&api=GetRecord&id=${id}`;
      const response = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error?.message || `HTTP ${response.status}`);
      }

      const result = await response.json();

      if (result.success === false) {
        throw new Error(result.error?.message || 'Failed to fetch record');
      }

      // APIレスポンスからデータを取得
      const record: ParameterRecord = result.result || result;
      setData(record);
      return record;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Unknown error';
      setError(message);
      return null;
    } finally {
      setLoading(false);
    }
  }, []);

  /**
   * レコードを保存
   */
  const saveRecord = useCallback((request: ParameterSaveRequest): Promise<ParameterSaveResponse> => {
    setSaving(true);
    setError(null);

    return new Promise((resolve) => {
      const params: any = {
        module: 'Parameters',
        parent: 'Settings',
        api: 'Save',
        id: String(request.id),
        value: request.value ?? '',
        description: request.description ?? '',
      };
      if (request.secret !== undefined) {
        params.secret = String(request.secret);
      }

      // app.request.post方式（vtiger標準API呼び出し）
      // @ts-ignore: app.requestはグローバル（vtiger環境）
      app.request.post({ data: params }).then(
        function(err: any, data: any) {
          setSaving(false);
          if (err === null && data && data.success) {
            resolve({ success: true });
          } else {
            const errorMsg = err?.message || data?.error?.message || 'Save failed';
            setError(errorMsg);
            resolve({ success: false, error: errorMsg });
          }
        }
      );
    });
  }, []);

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
