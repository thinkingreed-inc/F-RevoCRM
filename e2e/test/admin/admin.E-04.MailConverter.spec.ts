import { test, expect } from "../../fixtures/isolated";
import { gotoSettings } from "../../utils/settings";

/**
 * E-04 メールコンバーター (MailConverter) — 機能一覧 63-x
 *
 * メールを取り込みチケット/リード等を作成する設定画面。到達性と画面表示の
 * スモークテスト(メールボックス設定/スキャン設定への入口が開くこと)。
 */
test.describe("管理: メールコンバーター (MailConverter)", () => {
  test("メールコンバーター画面が表示される", async ({ page }) => {
    await gotoSettings(page, { module: "MailConverter", view: "List" });
    await expect(page).toHaveTitle(/メールコンバータ/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
  });
});
