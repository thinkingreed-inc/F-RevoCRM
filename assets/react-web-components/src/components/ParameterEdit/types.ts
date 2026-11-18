/**
 * システム変数（Parameters）の型定義
 */

/**
 * パラメータの値の型
 * font は PDF_DEFAULT_FONT 用。値はフォント名で、編集画面ではフォントの一覧から選ぶ
 */
export type ParameterType = "boolean" | "integer" | "string" | "font";

/**
 * パラメータレコード
 * GetRecord APIのレスポンス形式
 */
export interface ParameterRecord {
  /** レコードID */
  id: number;
  /** パラメータキー（例: FORCE_MULTI_FACTOR_AUTH） */
  key: string;
  /** 値（secret=1の場合は空文字） */
  value: string;
  /** 値の型 */
  type: ParameterType;
  /** シークレットフラグ（1=マスク表示） */
  secret: number;
  /** 説明 */
  description: string;
}

/**
 * Save APIのリクエストパラメータ
 */
export interface ParameterSaveRequest {
  /** レコードID */
  id: number;
  /**
   * 新しい値
   * 省略した場合は送信されず、サーバー側の既存値が維持される。
   * シークレット変数の値を変更しないまま保存するケースで使う。
   */
  value?: string;
  /**
   * 新しい備考
   * 省略した場合は送信されず、サーバー側の既存値が維持される。
   */
  description?: string;
  /**
   * シークレットフラグ（オプション）
   * 0↔1 のどちらにも変更できるが、1→0（解除）には value の再送信が必要。
   * 未入力のまま解除できると、秘匿していた値がそのまま画面に出てしまうため。
   * boolean 型には設定できない（値が 2 択しかなくマスクしても秘匿にならない）。
   */
  secret?: number;
}

/**
 * GetRecord APIのレスポンス
 * Vtiger_Api_Controller は結果を result に包んで返す
 */
export interface ParameterGetRecordApiResponse {
  success?: boolean;
  result?: ParameterRecord;
  error?: { message?: string };
}

/**
 * Save APIのレスポンス（サーバーが返す生のJSON）
 *
 * 成功時は API が { saved: true } を返し、
 * エラー時は Vtiger_Response が { success: false, error: {...} } を返す。
 */
export interface ParameterSaveApiResponse {
  saved?: boolean;
  success?: boolean;
  error?: { message?: string };
}

/**
 * Save APIのレスポンス（フックが返す形式）
 */
export interface ParameterSaveResponse {
  success: boolean;
  error?: string;
}

/**
 * ParameterEditコンポーネントのProps
 */
export interface ParameterEditProps {
  /** 編集対象のレコードID */
  recordId?: string;
  /** モーダルの開閉状態 */
  isOpen?: boolean;
  /**
   * 保存成功時のコールバック
   * value / description は送信した場合のみ渡す（未送信＝既存値を維持）
   */
  onSave?: (data: {
    id: number;
    key: string;
    value?: string;
    description?: string;
  }) => void;
  /** キャンセル時のコールバック */
  onCancel?: () => void;
  /** 開閉状態変更時のコールバック */
  onOpenChange?: (isOpen: boolean) => void;
}

/**
 * フォームの状態
 */
export interface ParameterFormState {
  /** 編集中の値 */
  value: string;
  /** シークレット設定 */
  secret: boolean;
  /** 説明文 */
  description: string;
  /** バリデーションエラー */
  error: string | null;
}

/** GetFonts APIの結果 */
export interface ParameterFontsResult {
  /** 選べるフォント名（libraries/tcpdf/fonts/ にあるもの） */
  fonts: string[];
  /** PDF_DEFAULT_FONT が空のときに使うフォント */
  fallback: string;
}

/**
 * GetFonts APIのレスポンス
 * 成功時は結果（ParameterFontsResult）がそのまま返る（Vtiger_Response の EMIT_PURE_JSON）。
 * エラー時は { success: false, error: {...} } が返る。
 */
export interface ParameterFontsApiResponse extends Partial<ParameterFontsResult> {
  success?: boolean;
  /** 結果が result に包まれて返る場合 */
  result?: ParameterFontsResult;
  error?: { message?: string };
}

/** UploadFont APIの結果 */
export interface ParameterUploadFontResult {
  /** 追加したフォント名 */
  font: string;
}

/**
 * UploadFont APIのレスポンス
 * 成功時は結果（ParameterUploadFontResult）がそのまま返る。エラー時は { success: false, error: {...} }
 */
export interface ParameterUploadFontApiResponse extends Partial<ParameterUploadFontResult> {
  success?: boolean;
  /** 結果が result に包まれて返る場合 */
  result?: ParameterUploadFontResult;
  error?: { message?: string };
}
