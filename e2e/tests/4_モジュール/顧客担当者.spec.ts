import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import { gotoDetail, deleteViaDetail } from "../../utils/listview";

/**
 * 顧客担当者の固有アクション(詳細画面) — 機能一覧 17-3
 *
 * 詳細の「メールを送る」「SMSを送る」が各モーダルを起動することを検証
 * (送信自体は副作用のため起動確認まで)。専用レコードで並行安全。
 */
test.describe("顧客担当者の固有アクション", () => {
  const visibleModal = (
    page: import("@playwright/test").Page,
    title: string
  ) => page.locator(".modal-content:visible").filter({ hasText: title }).first();

  test("メール作成 / SMS送信 の各モーダルが起動する", async ({ page }) => {
    const name = `E2Ect${generateRandomString(6)}`;

    // 顧客担当者を作成(必須: 姓)
    await page.goto(url("index.php?module=Contacts&view=Edit&app=MARKETING"));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="lastname"]', name);
    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
    const recordId = page.url().match(/record=(\d+)/)![1];

    // メール作成モーダル
    await gotoDetail(page, "Contacts", recordId);
    await page
      .locator("#Contacts_detailView_basicAction_LBL_SEND_EMAIL")
      .click();
    await expect(visibleModal(page, "メールの作成")).toBeVisible();

    // SMS送信モーダル(「その他」メニュー内)
    await gotoDetail(page, "Contacts", recordId);
    await page
      .locator(".detailViewButtoncontainer button.dropdown-toggle")
      .first()
      .click();
    await page.locator("#Contacts_detailView_moreAction_LBL_SEND_SMS").click();
    await expect(visibleModal(page, "SMSを送る")).toBeVisible();

    await deleteViaDetail(page, "Contacts", recordId);
  });
});
