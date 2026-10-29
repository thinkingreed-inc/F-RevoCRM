import type { WebComponentFieldProps } from "@/types/webComponentField";

/** 年月項目の Web コンポーネントが使う文言（Vtiger の言語ファイル。翻訳済み） */
export type YearMonthLabels = Partial<Record<string, string>>;

/**
 * <fr-field-yearmonth> が受け取る props
 * （Vtiger_YearMonth_UIType::getWebComponentProps の戻り値）
 */
export interface YearMonthWebComponentProps extends WebComponentFieldProps {
  /** 値（月初日 YYYY-MM-01。未入力は空文字） */
  value: string;
  /** 表示の書式（{Y} 年、{M} 月、{MM} 月（2桁）） */
  displayFormat: string;
  labels: YearMonthLabels;
}
