import { test, expect } from "../../fixtures/isolated";
import { url } from "../../utils/util";

/**
 * メールテンプレート / PDFテンプレート — 機能一覧 36 / 37
 *
 * どちらもヘッダーメニュー(TOOLS アプリ)から遷移する通常モジュール。
 * 一覧が正しく開く(タイトル・権限エラー無し・追加ボタン)ことのスモークテスト。
 */
test.describe("テンプレート系モジュール", () => {
  test("メールテンプレート一覧が表示される", async ({ page }) => {
    await page.goto(url("index.php?module=EmailTemplates&view=List&app=TOOLS"));
    await page.waitForLoadState("networkidle");
    await expect(page).toHaveTitle(/メールテンプレート/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
  });

  test("PDFテンプレート一覧が表示される", async ({ page }) => {
    await page.goto(url("index.php?module=PDFTemplates&view=List&app=TOOLS"));
    await page.waitForLoadState("networkidle");
    await expect(page).toHaveTitle(/PDFテンプレート/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
  });
});
