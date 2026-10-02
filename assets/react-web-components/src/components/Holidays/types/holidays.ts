/** 休祝日マスタの型定義 */

/** 休日区分（営業日に数えるかどうか） */
export type DayType = "holiday" | "workday";

/** 休日種別 */
export type HolidayType = "national" | "company" | "other";

/** 選択肢 */
export interface HolidayOption {
  value: string;
  label: string;
}

/** 休祝日レコード */
export interface HolidayRecord {
  holidayid: number;
  holiday_date: string;
  holiday_name: string;
  day_type: DayType;
  holiday_type: HolidayType;
  description: string;
  /** 曜日（0:日曜〜6:土曜） */
  weekday: number;
}

/** 画面の初期情報（選択肢・週休設定・ラベル） */
export interface HolidayInfo {
  day_types: HolidayOption[];
  holiday_types: HolidayOption[];
  /** 週休の曜日（0:日曜〜6:土曜） */
  weekly_holidays: number[];
  /** 曜日の表示名（添字 0:日曜〜6:土曜） */
  weekday_labels: string[];
  available_years: number[];
  current_year: number;
  /** 国民の祝日の一括登録（計算）に対応している開始年 */
  supported_from_year: number;
  /** 内閣府公表CSVの取得元URL */
  official_csv_url: string;
  labels: Record<string, string>;
}

/** 一覧の取得結果 */
export interface HolidayListResult {
  year: number;
  records: HolidayRecord[];
  total: number;
  available_years: number[];
}

/** 一括登録の結果 */
export interface GenerateResult {
  year: number;
  registered: number;
  skipped: number;
}

/** 内閣府公表データの取り込み結果 */
export interface ImportResult {
  added: number;
  updated: number;
  removed: number;
  years: number[];
  total_in_csv: number;
  year_from: number | null;
  year_to: number | null;
}

/** 週休設定の保存結果 */
export interface SettingsResult {
  /** 保存後の週休の曜日（0:日曜〜6:土曜） */
  weekly_holidays: number[];
}

/** 編集フォームの入力値 */
export interface HolidayFormValues {
  holidayid?: number;
  holiday_date: string;
  holiday_name: string;
  day_type: DayType;
  holiday_type: HolidayType;
  description: string;
}
