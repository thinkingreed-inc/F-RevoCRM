import { test, expect } from "@playwright/test";
import { loginInIsolatedContext } from "../../utils/settings";
import { gotoList, firstRecordId, gotoDetail } from "../../utils/listview";
import { url } from "../../utils/util";
import { seedSpec, passwordFor } from "../../fixtures/seedSpec";

/**
 * 共通機能: 項目レベル権限（プロファイル/役割） — 「この項目だけ 見えない / 編集できない」
 *
 * 拡充ベースラインは Sales Profile を複製し Accounts の特定項目だけ制限したペルソナ
 * `e2e_p_field` を持つ（モジュール/アクションは通常）:
 *   - phone   … 非表示（vtiger_profile2field.visible=1）
 *   - website … 編集不可（visible=0, readonly=1）
 *   - accountname … 通常（対照）
 *
 * 検証（本ビルドの実挙動を実機で確認した 3 状態の切り分け）:
 *  | 項目 | 詳細で見える | 編集画面に出る |
 *  |---|---|---|
 *  | accountname(通常) | ○ | ○(編集可) |
 *  | website(readonly=1) | ○(閲覧可) | ✗(編集画面に出ない=編集不可) |
 *  | phone(visible=1) | ✗(見えない) | ✗ |
 *  - 編集 input id = `#Accounts_editView_fieldName_<field>`（無い=編集不可/非表示）。
 *  - 詳細値セル id = `#Accounts_detailView_fieldValue_<field>`（概要タブには項目名 id が無い為
 *    「詳細」タブで判定）。readonly と hidden の差は「詳細で見えるか」で切り分く。
 *
 * Accounts は Public 共有なので sharing と切り離せる。開いた admin 所有レコードは保存しない（READ 専用）。
 */

const fp = seedSpec.fieldPerm;
const M = fp.module; // Accounts
const editId = (f: string) => `#${M}_editView_fieldName_${f}`;
const detailValId = (f: string) => `#${M}_detailView_fieldValue_${f}`;

test.describe("共通: 項目レベル権限 (プロファイル/役割)", () => {
  test(`${fp.userName}: 非表示=詳細も無 / 編集不可=詳細のみ可(編集画面に出ない) / 通常=編集可`, async ({
    browser,
  }) => {
    test.setTimeout(60000);
    const { context, page } = await loginInIsolatedContext(
      browser,
      fp.userName,
      passwordFor(fp.userName)
    );
    try {
      await gotoList(page, M);
      const recordId = await firstRecordId(page);

      // --- 編集画面: 通常のみ編集可。編集不可(readonly)/非表示は編集画面に出ない ---
      await page.goto(
        url(`index.php?module=${M}&view=Edit&record=${recordId}&app=MARKETING`)
      );
      await page.waitForLoadState("domcontentloaded");
      await expect(page.locator(editId(fp.normalField))).toBeEnabled();
      await expect(page.locator(editId(fp.readonlyField))).toHaveCount(0);
      await expect(page.locator(`[name="${fp.readonlyField}"]`)).toHaveCount(0);
      await expect(page.locator(editId(fp.hiddenField))).toHaveCount(0);
      await expect(page.locator(`[name="${fp.hiddenField}"]`)).toHaveCount(0);

      // --- 詳細画面(全項目タブ): 通常/編集不可は閲覧可、非表示だけ値セルが無い ---
      await gotoDetail(page, M, recordId);
      await page
        .locator("li.tab-item")
        .filter({ hasText: "詳細" })
        .first()
        .locator("a")
        .first()
        .click();
      await expect(page.locator(detailValId(fp.normalField))).toBeVisible({
        timeout: 15000,
      });
      // readonly 項目は「見えるが編集不可」→ 詳細では値セルが存在する
      await expect(page.locator(detailValId(fp.readonlyField))).toBeVisible();
      // hidden 項目は「見えない」→ 詳細でも値セルが無い
      await expect(page.locator(detailValId(fp.hiddenField))).toHaveCount(0);
    } finally {
      await context.close();
    }
  });
});
