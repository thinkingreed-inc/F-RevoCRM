/**
 * 活動の期間重複チェックに関する型定義
 *
 * サーバ側は modules/Calendar/actions/FetchOverlapEventsBeforeSave.php が
 * 重複している活動を HTML メッセージとして返す。
 */

/**
 * 期間重複チェックAPIのレスポンス
 *
 * 成功時は result.message に確認ダイアログへ表示する HTML が入る。
 * 重複が無い場合 message は空文字となる。
 */
export interface OverlapCheckResponse {
  success: boolean;
  result?: {
    message?: string;
  };
  error?: {
    message?: string;
  };
}

/**
 * 期間重複チェックへ渡すフォームデータ
 *
 * QuickCreate のフォーム値をそのまま受け取れるよう、未知のキーも許容する。
 */
export interface OverlapCheckFormData {
  /** レコードID（編集時のみ） */
  record_id?: string | number;
  /** 担当ユーザID */
  assigned_user_id?: string | number;
  /** 招待者のユーザID一覧 */
  selectedusers?: Array<string | number>;
  /** 開始日（YYYY-MM-DD）。date_start が日時形式の場合は呼び出し側で分割済みの値 */
  date_start?: string;
  /** 開始時刻（HH:mm） */
  time_start?: string;
  /** 終了日（YYYY-MM-DD） */
  due_date?: string;
  /** 終了時刻（HH:mm） */
  time_end?: string;
  /** 終日フラグ */
  is_allday?: boolean | string;
  /** 繰り返し予定の更新範囲 */
  recurringEditMode?: string;
  /** 繰り返し間隔 */
  repeat_frequency?: string | number;
  /** 繰り返し種別 */
  recurringtype?: string;
  /** 繰り返しの終了日 */
  calendar_repeat_limit_date?: string;
  [key: string]: unknown;
}

/**
 * useOverlapCheck の戻り値
 */
export interface UseOverlapCheckResult {
  /**
   * 期間が重複する活動を問い合わせる
   *
   * @param formData フォームの入力値
   * @returns 重複がある場合は確認ダイアログに表示する HTML、重複が無ければ null
   * @throws 通信エラー・サーバエラー時
   */
  checkOverlap: (formData: OverlapCheckFormData) => Promise<string | null>;
  /** 問い合わせ中かどうか */
  isChecking: boolean;
}
