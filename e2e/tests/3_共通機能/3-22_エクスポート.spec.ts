import { test, expect } from "../../fixtures/isolated";
import { gotoList } from "../../utils/listview";

/**
 * 共通機能: CSV エクスポート — 機能一覧 10-1
 *
 * 一覧の「その他」→「エクスポート」で ExportData モーダルが開く。
 * モーダルの実行ボタン(button.btn-success.btn-lg「エクスポート …」)を押すと
 * CSV がダウンロードされる。既定は「現在のページをエクスポートする」のため
 * レコード選択なしで実行できる。
 */
test.describe("共通: CSV エクスポート", () => {
  test("一覧からエクスポートすると CSV がダウンロードされる", async ({
    page,
  }) => {
    await gotoList(page, "Accounts");

    // 一括操作の「その他」を開き、エクスポートを選択
    await page
      .locator(".listViewMassActions button.dropdown-toggle")
      .first()
      .click();
    await page.locator("#Accounts_listView_advancedAction_LBL_EXPORT").click();

    // エクスポート設定モーダル
    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible();

    // 実行 → ダウンロードを待つ
    const [download] = await Promise.all([
      page.waitForEvent("download", { timeout: 15000 }),
      modal.locator("button.btn-success.btn-lg").first().click(),
    ]);

    expect(download.suggestedFilename()).toMatch(/\.csv$/i);
  });
});
