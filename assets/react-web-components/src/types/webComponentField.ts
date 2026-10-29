/**
 * UIType を Web コンポーネントで表示するときの共通の型定義
 *
 * Smarty（uitypes/WebComponentField.tpl / WebComponentFieldDetailView.tpl）が
 * UIType クラスの getWebComponentProps() を JSON にして props 属性に入れる。
 */

/** 表示の種類 */
export type WebComponentFieldMode = "edit" | "detail";

/** すべての UIType Web コンポーネントが受け取る props */
export interface WebComponentFieldProps {
  mode: WebComponentFieldMode;
  /** 項目を持つモジュール */
  module: string;
  /** 項目名（送信する入力欄の name） */
  fieldName: string;
  /** 表示名（翻訳済み） */
  fieldLabel: string;
  /** レコードID（新規作成時は 0） */
  recordId: number;
  mandatory: boolean;
  readonly: boolean;
  /**
   * 送信する値を入れる hidden 入力の ID（編集時のみ）
   * Web コンポーネントは値が変わったらこの入力を書き換える
   */
  inputId?: string;
}
