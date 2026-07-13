import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: クイック編集(鉛筆・インライン編集) — 機能一覧 12-2
 *
 * 概要/詳細画面で各項目にマウスオーバーすると鉛筆(.editAction)が出る。押すと
 * その項目だけインライン入力に変わり(Detail.js ajaxEditHandling)、チェック
 * (.fa-check)で 1 項目だけ保存できる。既定は概要(summary-table)が表示される。
 *
 * 専用の顧客企業を作り、概要の「顧客企業名」をインライン編集して反映を検証する。
 */
test.describe("共通: クイック編集(インライン)", () => {
  test("概要画面で顧客企業名をインライン編集できる", async ({ page }) => {
    const name = `E2Eqe${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);
    await gotoDetail(page, "Accounts", recordId);

    // 概要の「顧客企業名」セル
    const cell = page
      .locator("table.summary-table td.fieldValue")
      .filter({ has: page.locator('[data-name="accountname"]') })
      .first();
    await expect(cell).toBeVisible();

    // 鉛筆を出して押す → インライン入力へ
    await cell.hover();
    await cell.locator(".editAction").first().click();

    const newName = `${name}_edited`;
    const input = cell.locator('input[name="accountname"]');
    await expect(input).toBeVisible();
    await input.fill(newName);

    // インライン保存(チェック)。セル内の保存アイコンを押す。
    await cell.locator(".fa-check").first().click();
    await page.waitForLoadState("networkidle");

    // 反映確認(概要セルが編集後の値になる)
    await expect(cell).toContainText(newName);

    // 後始末
    await deleteViaDetail(page, "Accounts", recordId);
  });
});
