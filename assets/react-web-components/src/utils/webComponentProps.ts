/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * カスタム要素（Web Components）経由で渡る真偽値 props を正規化する。
 *
 * HTML の属性値は常に文字列のため、`<calendar-quick-create is-duplicate="true">`
 * のように指定された値は本来 `"true"` という文字列として props に届く。
 * 受け側が `value === true` のような厳密比較をしていると、この文字列は偽と判定され、
 * 複製モードが編集モードとして扱われるといった不具合につながる。
 *
 * 現状は createWebComponent の属性パースが JSON.parse のフォールバックを持つため
 * 結果的に boolean へ変換されているが、その暗黙の挙動に依存しないよう、
 * boolean と文字列の双方をここで受け付ける。
 *
 * HTML の真偽値属性の慣習に合わせ、属性が存在すれば true とみなす。
 * ただし `"false"` / `"0"` のような明示的な否定は false として扱う。
 */
export function toBooleanProp(value: boolean | string | undefined): boolean {
  if (typeof value === "boolean") {
    return value;
  }
  if (typeof value !== "string") {
    return false;
  }
  const normalized = value.trim().toLowerCase();
  return normalized !== "false" && normalized !== "0";
}
