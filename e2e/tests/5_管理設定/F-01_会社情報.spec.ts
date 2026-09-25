import { test, expect } from "../../fixtures/isolated";
import { generateRandomString } from "../../utils/util";
import { gotoSettings } from "../../utils/settings";

/**
 * F-01 企業の詳細 (System > 企業の詳細)
 *
 * 設定編集型の代表。単一の設定フォームを編集し、保存値が詳細に反映されることを
 * 検証する。全体設定に相当するため、元の会社名を退避 → 検証 → 原状復帰し、
 * 後続テストへ影響を残さない。
 */
test.describe("管理: 企業の詳細 (CompanyDetails)", () => {
  const detailParams = { module: "Vtiger", view: "CompanyDetails" };

  test("会社名を編集して反映され、元に戻せる", async ({ page }) => {
    const testName = `E2Eテスト会社${generateRandomString(5)}`;

    // 編集画面を開き、元の会社名を退避する
    await gotoSettings(page, detailParams);
    await page.getByRole("button", { name: "編集", exact: true }).first().click();
    const orgInput = page.locator('input[name="organizationname"]');
    await expect(orgInput).toBeVisible();
    const originalName = await orgInput.inputValue();

    // テスト値に更新して保存する
    await orgInput.fill(testName);
    await page.locator("button.saveButton").click();

    // 詳細に更新後の会社名が反映されていること
    await gotoSettings(page, detailParams);
    await expect(
      page.getByRole("cell", { name: testName, exact: true })
    ).toBeVisible();

    // 原状復帰: 元の会社名に戻す
    await page.getByRole("button", { name: "編集", exact: true }).first().click();
    await expect(orgInput).toBeVisible();
    await orgInput.fill(originalName);
    await page.locator("button.saveButton").click();

    // 元の会社名に戻っていること
    await gotoSettings(page, detailParams);
    if (originalName.trim()) {
      await expect(
        page.getByRole("cell", { name: originalName, exact: true })
      ).toBeVisible();
    }
  });
});
