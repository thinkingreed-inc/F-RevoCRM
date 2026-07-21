import { expect, type Page } from "@playwright/test";
import { frgetDescribe, frRetrieve } from "../../../model/fetcher";
import { fillField } from "../../../utils/field";
import { getFieldValue } from "../../../utils/field";
import { generateRandomString } from "../../../utils/util";
import { BASE_URL } from "../../../utils/util";
import type { FRDescribeFieldsTypeWithModuleName } from "../../../model/types/frTest";
import { CASES, FIELD_LABELS, type FieldKey, type ValidationCase } from "../fixtures/validation-matrix";

const MODULE = "Faq";

export class FieldValidation {
  private constructor(
    private sessionName: string,
    private describe: { idPrefix: string; fields: FRDescribeFieldsTypeWithModuleName[] },
    private byLabel: Map<string, FRDescribeFieldsTypeWithModuleName>
  ) {}

  static async create(sessionName: string): Promise<FieldValidation> {
    const d = await frgetDescribe(sessionName, MODULE);
    if (!d) throw new Error("Faq describe 取得失敗");
    const fields = d.fields.map((f) => ({ moduleName: MODULE, ...f })) as FRDescribeFieldsTypeWithModuleName[];
    const byLabel = new Map<string, FRDescribeFieldsTypeWithModuleName>();
    for (const f of fields) byLabel.set(f.label, f);
    return new FieldValidation(sessionName, { idPrefix: d.idPrefix, fields }, byLabel);
  }

  resolve(key: FieldKey): FRDescribeFieldsTypeWithModuleName {
    const label = FIELD_LABELS[key];
    const f = this.byLabel.get(label);
    if (!f) throw new Error(`検証項目が見つかりません(dump未反映?): ${label}`);
    return f;
  }

  private createUrl(): string {
    return `${BASE_URL}index.php?module=${MODULE}&view=Edit&app=MARKETING`;
  }

  /** Faq の必須項目を有効値で埋める。戻り値は hash(文字列項目に含まれる)。 */
  private async fillMandatory(page: Page, target: FRDescribeFieldsTypeWithModuleName): Promise<string> {
    const hash = generateRandomString(8);
    for (const f of this.describe.fields) {
      if (f.mandatory !== true || f.editable === false) continue;
      if (f.name === target.name) continue; // 対象は後で上書き
      const v = (await getFieldValue(f, hash)) || `E2E_${hash}`;
      await fillField(page, f, v as string);
    }
    return hash;
  }

  async runCase(page: Page, c: ValidationCase): Promise<void> {
    const target = this.resolve(c.field);
    await page.goto(this.createUrl());
    await page.waitForLoadState("domcontentloaded");
    await this.fillMandatory(page, target);

    // 対象項目に入力
    if (c.field === "reference") {
      await fillField(page, target, ""); // fillField(reference)が先頭Accountsを選択
    } else if (c.field === "multipick") {
      await page.selectOption(`select[name="${target.name}"]`, c.input.split(","));
    } else {
      await fillField(page, target, c.input);
    }

    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 12000 }).catch(() => {});
    const saved = page.url().match(/[?&]record=(\d+)/);

    switch (c.expect.kind) {
      case "rejectWithError": {
        // 保存されず編集画面に留まる。可視のバリデーションエラーがある。
        expect(saved, `保存されない想定だが record=${saved?.[1]} が発行された`).toBeNull();
        const errs = await page.locator("label.error:visible, span.error:visible").count();
        expect(errs).toBeGreaterThan(0);
        return;
      }
      case "acceptAsIs":
      case "acceptNormalized":
      case "truncate":
      case "storedAsPlainText":
      case "notRendered": {
        expect(saved, "保存されて record= が発行される想定").not.toBeNull();
        const recordId = saved![1];
        const stored = await frRetrieve(this.sessionName, `${this.describe.idPrefix}x${recordId}`);
        const value = stored ? (stored[target.name] ?? "") : "";
        await this.assertStored(page, recordId, c, value);
        return;
      }
    }
  }

  private async assertStored(page: Page, recordId: string, c: ValidationCase, stored: string): Promise<void> {
    const e = c.expect;
    if (e.kind === "acceptAsIs") {
      // 数値系は数値比較、他は文字列(前後空白許容)
      if (["decimal", "integer", "percent", "currency"].includes(c.field)) {
        expect(Number(stored)).toBe(Number(c.input));
      } else if (c.field === "checkbox") {
        expect(["1", "on", "true"]).toContain(String(stored).toLowerCase());
      } else if (c.field === "reference") {
        expect(stored).not.toBe("");
      } else {
        expect(stored.trim()).toBe(c.input.trim());
      }
    } else if (e.kind === "acceptNormalized") {
      expect(Number(stored)).toBe(Number(e.stored));
    } else if (e.kind === "truncate") {
      expect(stored.length).toBe(e.maxLen);
    } else if (e.kind === "storedAsPlainText") {
      expect(stored).toBe(c.input); // SQL実行されず平文
    } else if (e.kind === "notRendered") {
      // 詳細画面でスクリプトが live node として存在しないこと
      await page.goto(
        `${BASE_URL}index.php?module=${MODULE}&view=Detail&app=MARKETING&record=${recordId}&mode=showDetailViewByMode&requestMode=full`
      );
      await page.waitForLoadState("domcontentloaded");
      const liveScripts = await page.locator("#detailView script").filter({ hasText: "alert('XSS')" }).count();
      expect(liveScripts).toBe(0);
    }
  }
}

export { CASES };
