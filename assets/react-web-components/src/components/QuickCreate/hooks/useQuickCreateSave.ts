import { useState, useCallback } from 'react';
import {
  UseQuickCreateSaveResult,
  QuickCreateSaveResult,
  QuickCreateSaveResponse
} from '../../../types/quickcreate';
import { transformCalendarDateTime } from '../../../utils/datetime';

/**
 * QuickCreate保存処理のHook
 */
export function useQuickCreateSave(module: string): UseQuickCreateSaveResult {
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * CSRFトークンを取得
   */
  const getCsrfToken = useCallback((): { name: string; value: string } | null => {
    const csrfName = (window as any).csrfMagicName;
    const csrfToken = (window as any).csrfMagicToken;

    if (csrfName && csrfToken) {
      return { name: csrfName, value: csrfToken };
    }
    return null;
  }, []);

  /**
   * レコードを保存
   */
  const save = useCallback(async (
    formData: Record<string, any>
  ): Promise<QuickCreateSaveResult> => {
    setIsSaving(true);
    setError(null);

    try {
      // URLパラメータ（ルーティング用）
      const urlParams = new URLSearchParams({
        module: module,
        api: 'Save'
      });

      // リクエストボディ構築
      const bodyParams = new URLSearchParams();

      // CSRFトークン追加（必須）
      const csrf = getCsrfToken();
      if (!csrf) {
        throw new Error('CSRFトークンが取得できませんでした。ページをリロードしてください。');
      }
      bodyParams.append(csrf.name, csrf.value);

      // Calendar/Events用の日時フィールド変換
      const isCalendarModule = module === 'Calendar' || module === 'Events';
      const processedData = isCalendarModule
        ? transformCalendarDateTime(formData)
        : formData;

      // Calendar/Events用のcalendarModuleパラメータ追加
      // VtigerのSaveAjaxはCalendarモジュール保存時にcalendarModuleパラメータを必要とする
      if (isCalendarModule) {
        bodyParams.append('calendarModule', module);

        // 繰り返し活動編集モードのパラメータ追加
        // recurringEditMode: 'current' | 'future' | 'all'
        // 繰り返し活動を編集する際、どの範囲を更新するかを指定
        if (processedData.recurringEditMode) {
          bodyParams.append('recurringEditMode', String(processedData.recurringEditMode));
        }
      }

      // フォームデータ追加（空値・false値は除外）
      Object.entries(processedData).forEach(([key, value]) => {
        // undefined, null, 空文字, falseは送信しない（ただし0は送信する）
        if (value !== undefined && value !== null && value !== '' && value !== false) {
          // 配列の場合は複数パラメータとして送信（selectedusers[]形式）
          if (Array.isArray(value)) {
            value.forEach(item => {
              bodyParams.append(`${key}[]`, String(item));
            });
          } else if (key === 'is_allday' && value === true) {
            // is_allday はPHP側が "on" を期待するため、trueの場合は "on" として送信
            bodyParams.append(key, 'on');
          } else {
            bodyParams.append(key, String(value));
          }
        }
      });

      // Events/Calendar の場合、contactidlist パラメータを追加
      // contact_id の値を contactidlist として送信し、contact_id は最初のIDのみに設定
      if (isCalendarModule && processedData.contact_id) {
        const contactIdValue = String(processedData.contact_id);
        if (contactIdValue) {
          // contactidlist パラメータを追加
          bodyParams.append('contactidlist', contactIdValue);

          // contact_id は最初の1つのIDのみ設定（セミコロン区切りの場合）
          const firstId = contactIdValue.split(';')[0];
          // contact_id を上書き（既に追加されている場合は削除してから）
          bodyParams.delete('contact_id');
          bodyParams.append('contact_id', firstId);
        }
      }

      const response = await fetch(`?${urlParams.toString()}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: bodyParams.toString()
      });

      // 認証・認可エラーのハンドリング
      if (response.status === 401) {
        throw new Error('セッションがタイムアウトしました。ページをリロードして再度ログインしてください。');
      }
      if (response.status === 403) {
        throw new Error('このレコードを作成・編集する権限がありません。');
      }

      const data: QuickCreateSaveResponse = await response.json();

      if (!response.ok) {
        const errorMessage = (data as any).message ||
                            (data as any).error?.message ||
                            '保存に失敗しました';
        throw new Error(errorMessage);
      }

      // 成功結果を構築
      const recordId = data._recordId || (data as any).result?._recordId;
      const recordLabel = data._recordLabel || (data as any).result?._recordLabel;

      const result: QuickCreateSaveResult = {
        success: true,
        recordId: recordId,
        recordLabel: recordLabel,
        module: module,
        detailViewUrl: recordId
          ? `index.php?module=${module}&view=Detail&record=${recordId}`
          : undefined
      };

      return result;

    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '保存に失敗しました';
      setError(errorMessage);

      return {
        success: false,
        module: module,
        error: errorMessage
      };

    } finally {
      setIsSaving(false);
    }
  }, [module, getCsrfToken]);

  /**
   * エラーをクリア
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  return {
    save,
    isSaving,
    error,
    clearError
  };
}

export default useQuickCreateSave;
