import { test, expect } from "../../fixtures/isolated";
import { gotoSettings } from "../../utils/settings";

/**
 * F-04 送信メールサーバー (OutgoingServer) — 機能一覧 67-1
 *
 * メール送信用 SMTP の設定画面。到達性と画面表示のスモークテスト。
 */
test.describe("管理: 送信メールサーバー (OutgoingServer)", () => {
  test("送信メールサーバー設定画面が表示される", async ({ page }) => {
    await gotoSettings(page, { module: "Vtiger", view: "OutgoingServerDetail" });
    await expect(page).toHaveTitle(/送信メールサーバー/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
  });
});
