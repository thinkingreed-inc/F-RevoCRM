/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

import { describe, it, expect } from "vitest";
import { toBooleanProp } from "./webComponentProps";

describe("toBooleanProp", () => {
  it("boolean の true をそのまま true として扱う", () => {
    expect(toBooleanProp(true)).toBe(true);
  });

  it('文字列の "true" を true として扱う（カスタム要素の属性は文字列で渡るため）', () => {
    expect(toBooleanProp("true")).toBe(true);
  });

  it('大文字小文字や前後の空白を無視して "true" を判定する', () => {
    expect(toBooleanProp("TRUE")).toBe(true);
    expect(toBooleanProp(" true ")).toBe(true);
  });

  it("値なし（空文字）の属性を true として扱う", () => {
    expect(toBooleanProp("")).toBe(true);
  });

  it("boolean の false は false のまま扱う", () => {
    expect(toBooleanProp(false)).toBe(false);
  });

  it('文字列の "false" と "0" は false として扱う', () => {
    expect(toBooleanProp("false")).toBe(false);
    expect(toBooleanProp("0")).toBe(false);
  });

  it("undefined は false として扱う", () => {
    expect(toBooleanProp(undefined)).toBe(false);
  });
});
