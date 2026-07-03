import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import { gotoDetail, deleteViaDetail } from "../../utils/listview";

/**
 * チケットの固有アクション(詳細画面) — 機能一覧 29-2
 *
 * 詳細の「メールを送る」がメール作成モーダルを起動することを検証。
 * 作成には件名 + 優先度/ステータス(必須ピックリスト)が必要。専用レコードで並行安全。
 */
test.describe("チケットの固有アクション", () => {
  test("メール作成モーダルが起動する", async ({ page }) => {
    const title = `E2Ehd${generateRandomString(6)}`;

    await page.goto(url("index.php?module=HelpDesk&view=Edit&app=SUPPORT"));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="ticket_title"]', title);
    // 必須ピックリスト(優先度/ステータス)を先頭の実オプションで選択
    await page
      .locator('select[name="ticketpriorities"]')
      .selectOption({ index: 1 });
    await page
      .locator('select[name="ticketstatus"]')
      .selectOption({ index: 1 });
    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
    const recordId = page.url().match(/record=(\d+)/)![1];

    await gotoDetail(page, "HelpDesk", recordId);
    await page
      .locator("#HelpDesk_detailView_basicAction_LBL_SEND_EMAIL")
      .click();
    await expect(
      page.locator(".modal-content:visible").filter({ hasText: "メールの作成" }).first()
    ).toBeVisible();

    await deleteViaDetail(page, "HelpDesk", recordId);
  });
});
