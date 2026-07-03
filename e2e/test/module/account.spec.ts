import { test, expect } from "../../fixtures/isolated";
import { url } from "../../utils/util";

test.describe("顧客企業モジュールのテスト", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(
      url("index.php?module=Accounts&view=List&viewname=4&app=MARKETING")
    );
  });

  test("顧客企業リストに表示されているべき要素のテスト", async ({ page }) => {
    await expect(page.getByText("顧客企業の追加").first()).toBeVisible();
    await expect(page.getByText("インポート").first()).toBeVisible();
    await expect(page.getByText("カスタマイズ").first()).toBeVisible();
    await expect(page.getByText("個人リスト").first()).toBeVisible();
    await expect(page.getByText("共有リスト").first()).toBeVisible();

    // カスタマイズを押したときの動作確認
    await page.getByText("カスタマイズ").first().click();
    await expect(page.getByText("顧客企業 項目の編集").first()).toBeVisible();
    await expect(
      page.getByText("顧客企業 ワークフローの編集").first()
    ).toBeVisible();
    await page.getByText("カスタマイズ").first().click(); //閉じる
  });
});
