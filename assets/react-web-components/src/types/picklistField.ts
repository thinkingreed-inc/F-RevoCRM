/**
 * ピックリスト／複数選択ピックリスト項目（uitype 15/16/33）の Web コンポーネント型定義（#1677）
 *
 * Smarty（uitypes/WebComponentField.tpl）が UIType クラスの getWebComponentProps()
 * = PicklistDisplayConfig::buildProps() の結果を JSON にして props 属性に入れる。
 * 送信する値は hidden 入力（props.inputId）に ' |##| ' 連結（複数）または単一値で書き戻す。
 */

import type { WebComponentFieldProps } from "./webComponentField";

/** 実効描画スタイル（サーバ側 PicklistDisplayConfig::resolveEffectiveStyle が決定） */
export type PicklistEffectiveStyle = "checkbox" | "radio" | "dropdown";

/** ユーザー設定の表示方式（vtiger_picklist.input_style）。null/auto は自動 */
export type PicklistInputStyle =
  "checkbox" | "radio" | "dropdown" | "auto" | null;

/** 選択肢（値・ラベル・色） */
export interface PicklistWebComponentOption {
  value: string;
  label: string;
  /** 選択肢の色（HEX）。未設定なら null */
  color?: string | null;
}

/** <fr-field-picklist> / <fr-field-multipicklist> の props */
export interface PicklistWebComponentProps extends WebComponentFieldProps {
  /** 複数選択（uitype 33）なら true */
  multiple: boolean;
  /** 選択肢一覧 */
  options: PicklistWebComponentOption[];
  /** 現在の選択値（複数は配列、単一は要素0 or 1の配列） */
  value: string[];
  /** ユーザー設定の表示方式（null/auto=自動） */
  inputStyle: PicklistInputStyle;
  /** サーバが解決した実効スタイル（これに従って描画する） */
  effectiveStyle: PicklistEffectiveStyle;
  /** チェック/ラジオに切り替える選択肢数の閾値 */
  threshold: number;
  /** チェック/ラジオ表示の列数（1 or 2） */
  columns: number;
}
