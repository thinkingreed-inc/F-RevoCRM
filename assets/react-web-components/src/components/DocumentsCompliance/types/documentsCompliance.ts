/** 電子帳簿保存法設定の型定義 */

/** 入力期限の方針 */
export type DeadlinePolicy = "prompt" | "cycle";

/** 方針の選択肢 */
export interface PolicyOption {
  value: DeadlinePolicy;
  label: string;
  description: string;
}

/** 入力期限の設定値 */
export interface DeadlineSettings {
  policy: DeadlinePolicy;
  /** 猶予の営業日数 */
  business_days: number;
  /** 業務処理サイクル（月） */
  cycle_months: number;
  /** 期限間近とする営業日数 */
  warning_days: number;
}

/** 現在の設定で計算した期限の例 */
export interface DeadlineExample {
  receipt_date: string;
  input_deadline: string | null;
  status: string | null;
}

/** 画面の初期情報 */
export interface ComplianceSettingsInfo {
  policies: PolicyOption[];
  settings: DeadlineSettings;
  example: DeadlineExample;
  /** 入力できる営業日数の上限 */
  max_days: number;
  /** 入力できる業務処理サイクルの上限（月） */
  max_cycle_months: number;
  /** 休祝日マスタへのリンク */
  holidays_url: string;
  labels: Record<string, string>;
}

/** 設定の保存結果 */
export interface SaveSettingsResult {
  settings: DeadlineSettings;
  example: DeadlineExample;
}

/** 入力期限の再計算結果 */
export interface RecalculateResult {
  checked: number;
  updated: number;
}
