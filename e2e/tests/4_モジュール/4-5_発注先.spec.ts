import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import { gotoDetail, deleteViaDetail } from "../../utils/listview";

/**
 * 発注先の固有アクション(詳細画面) — 機能一覧 28-2
 *
 * 詳細の「メールを送る」がメール作成モーダルを起動することを検証。
 * 専用レコードで並行安全。
 */
test.describe("発注先の固有アクション", () => {
  test("メール作成モーダルが起動する", async ({ page }) => {
    const name = `E2Evd${generateRandomString(6)}`;

    await page.goto(url("index.php?module=Vendors&view=Edit&app=SUPPORT"));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="vendorname"]', name);
    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
    const recordId = page.url().match(/record=(\d+)/)![1];

    await gotoDetail(page, "Vendors", recordId);
    await page
      .locator("#Vendors_detailView_basicAction_LBL_SEND_EMAIL")
      .click();
    await expect(
      page.locator(".modal-content:visible").filter({ hasText: "メールの作成" }).first()
    ).toBeVisible();

    // 発注の作成(「その他」メニュー → 発注の編集画面へ遷移)
    await gotoDetail(page, "Vendors", recordId);
    await page
      .locator(".detailViewButtoncontainer button.dropdown-toggle")
      .first()
      .click();
    await page
      .locator(
        '.detailViewButtoncontainer .dropdown-menu a[href*="module=PurchaseOrder"][href*="view=Edit"]'
      )
      .first()
      .click();
    await page.waitForURL(/[?&]module=PurchaseOrder&view=Edit/, {
      timeout: 15000,
    });

    await deleteViaDetail(page, "Vendors", recordId);
  });
});
