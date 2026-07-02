import { test, expect } from "@playwright/test";
import { url } from "../utils/util";

// 【一時】失敗時スクショの日本語フォント(豆腐対策)を検証するための意図的失敗テスト。
// 確認できたらこのファイルごと削除する。
test.describe("[TEMP] 失敗時スクリーンショット確認", () => {
  test("わざと失敗して顧客企業リストの画面を撮る", async ({ page }) => {
    await page.goto(
      url("index.php?module=Accounts&view=List&viewname=4&app=MARKETING")
    );
    // 実在しない文言を検証してわざと失敗させる（この時点の画面がスクショされる）
    await expect(
      page.getByText("存在しないはずのテキスト_SCREENSHOT_CHECK").first()
    ).toBeVisible({ timeout: 5000 });
  });
});
