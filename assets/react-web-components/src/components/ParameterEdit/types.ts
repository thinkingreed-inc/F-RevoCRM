/**
 * システム変数（Parameters）の型定義
 */

/**
 * パラメータの値の型
 */
export type ParameterType = 'boolean' | 'integer' | 'string';

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
  /** 新しい値 */
  value: string;
  /** 新しい備考 */
  description: string;
  /** シークレットフラグ（オプション、0→1のみ可能） */
  secret?: number;
}

/**
 * Save APIのレスポンス
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
  /** 保存成功時のコールバック */
  onSave?: (data: { id: number; key: string; value: string; description: string }) => void;
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
