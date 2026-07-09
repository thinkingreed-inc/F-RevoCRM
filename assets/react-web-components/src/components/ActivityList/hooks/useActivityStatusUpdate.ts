import { useState, useCallback } from "react";

/**
 * useActivityStatusUpdate フックの戻り値
 */
export interface UseActivityStatusUpdateResult {
  /** ステータス更新関数 */
  updateStatus: (
    activityId: string,
    fieldName: string,
    newValue: string,
    activityType: string,
  ) => Promise<void>;
  /** 更新中フラグ */
  isUpdating: boolean;
  /** エラーメッセージ */
  error: string | null;
}

/**
 * アクティビティのステータスを更新するHook
 *
 * vtiger の標準的な Save API を使用してステータスフィールドを更新します。
 *
 * @example
 * const { updateStatus, isUpdating, error } = useActivityStatusUpdate();
 *
 * // Task のステータスを更新
 * await updateStatus('123', 'taskstatus', 'Completed');
 *
 * // Meeting のステータスを更新
 * await updateStatus('456', 'eventstatus', 'Held');
 */
export function useActivityStatusUpdate(): UseActivityStatusUpdateResult {
  const [isUpdating, setIsUpdating] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * ステータス更新関数
   *
   * @param activityId アクティビティのレコードID
   * @param fieldName ステータスフィールド名（taskstatus または eventstatus）
   * @param newValue 新しいステータス値
   * @param activityType アクティビティタイプ（Task, Meeting, Call）
   * @throws エラー発生時は例外をスローします
   */
  const updateStatus = useCallback(
    async (
      activityId: string,
      fieldName: string,
      newValue: string,
      activityType: string,
    ): Promise<void> => {
      // 入力検証
      if (!activityId || !activityId.trim()) {
        const errorMessage = "アクティビティIDが指定されていません";
        setError(errorMessage);
        throw new Error(errorMessage);
      }

      if (!fieldName || !fieldName.trim()) {
        const errorMessage = "フィールド名が指定されていません";
        setError(errorMessage);
        throw new Error(errorMessage);
      }

      if (!newValue || !newValue.trim()) {
        const errorMessage = "ステータス値が指定されていません";
        setError(errorMessage);
        throw new Error(errorMessage);
      }

      setIsUpdating(true);
      setError(null);

      try {
        // CSRF トークンを取得
        const csrfInput = document.querySelector(
          'input[name="__vtrftk"]',
        ) as HTMLInputElement;
        const csrfToken = csrfInput?.value || "";

        if (!csrfToken) {
          throw new Error(
            "CSRFトークンが見つかりません。ページを再読み込みしてください。",
          );
        }

        // FormData でリクエストを構築
        // SaveAjax アクションを使用してJSON応答を取得
        // calendarModule: Task は 'Calendar'、Meeting/Call は 'Events'
        const calendarModule = activityType === "Task" ? "Calendar" : "Events";

        const formData = new FormData();
        formData.append("__vtrftk", csrfToken);
        formData.append("module", "Calendar");
        formData.append("calendarModule", calendarModule);
        formData.append("action", "SaveAjax");
        formData.append("record", activityId);
        formData.append("field", fieldName);
        formData.append("value", newValue);

        // API 呼び出し
        const response = await fetch("index.php", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          body: formData,
        });

        // HTTP エラーチェック
        if (!response.ok) {
          if (response.status === 401) {
            throw new Error(
              "セッションがタイムアウトしました。ページを再読み込みしてください。",
            );
          }
          if (response.status === 403) {
            throw new Error("編集権限がありません");
          }
          throw new Error(`APIエラー: ${response.status}`);
        }

        // レスポンスのパース
        const data = await response.json();

        if (!data.success) {
          throw new Error(
            data.error?.message || "ステータスの更新に失敗しました",
          );
        }
      } catch (err) {
        const errorMessage =
          err instanceof Error
            ? err.message
            : "ステータスの更新中にエラーが発生しました";
        setError(errorMessage);
        console.error("useActivityStatusUpdate error:", err);
        throw err;
      } finally {
        setIsUpdating(false);
      }
    },
    [],
  );

  return {
    updateStatus,
    isUpdating,
    error,
  };
}

export default useActivityStatusUpdate;
