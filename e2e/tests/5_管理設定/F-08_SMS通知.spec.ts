import { test, expect } from "../../fixtures/isolated";
import { gotoSettings } from "../../utils/settings";

/**
 * F-08 SMS通知 (SMSNotifier) — 機能一覧 78-1
 *
 * SMS プロバイダーの設定画面。到達性と画面表示のスモークテスト。
 */
test.describe("管理: SMS通知 (SMSNotifier)", () => {
  test("SMS通知設定画面が表示される", async ({ page }) => {
    await gotoSettings(page, { module: "SMSNotifier", view: "List" });
    await expect(page).toHaveTitle(/SMS/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
  });
});
