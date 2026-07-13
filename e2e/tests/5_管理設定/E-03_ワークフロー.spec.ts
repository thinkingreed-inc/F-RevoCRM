import { test, expect } from "../../fixtures/isolated";
import { generateRandomString } from "../../utils/util";
import { gotoSettings } from "../../utils/settings";

/**
 * E-03 ワークフロー (自動化 > ワークフロー)
 *
 * 一覧＋作成/編集＋トグルON/OFF の代表。作成→名称変更→無効化(トグルOFF)を直列で行い、
 * それぞれが一覧に反映されることを検証する。作成したワークフロー名そのものを追跡する。
 */
test.describe.serial("管理: ワークフロー (Workflows)", () => {
  const listParams = { module: "Workflows", view: "List" };
  // 追加名と編集名は互いに部分文字列にならないようにする(hasText の部分一致で
  // 「旧名が消えたこと」を検証するため)。
  const token = generateRandomString(5);
  const name = `E2Eワークフローadd${token}`;
  const editedName = `E2Eワークフローedit${token}`;

  const row = (page: import("@playwright/test").Page, text: string) =>
    page.locator("tr.listViewEntries").filter({ hasText: text });

  test("ワークフローの追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    await page.getByText("新しいワークフロー").first().click();
    await page.locator('input[name="workflowname"]').fill(name);
    await page.locator("button.saveButton").click();

    // 一覧に作成したワークフローが現れること
    await gotoSettings(page, listParams);
    await expect(row(page, name)).toBeVisible();
  });

  test("ワークフローの編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    await row(page, name).first().click();
    const nameInput = page.locator('input[name="workflowname"]');
    await expect(nameInput).toBeVisible();
    await nameInput.fill(editedName);
    await page.locator("button.saveButton").click();

    // 一覧に変更後の名称が現れ、元の名称は消えていること
    await gotoSettings(page, listParams);
    await expect(row(page, editedName)).toBeVisible();
    await expect(row(page, name)).toHaveCount(0);
  });

  test("ワークフローの無効化(トグルOFF)", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = row(page, editedName).first();
    const toggle = target.locator(".bootstrap-switch");
    // 初期状態は有効(ON)
    await expect(toggle).toHaveClass(/bootstrap-switch-on/);

    await toggle.click();
    await expect(toggle).toHaveClass(/bootstrap-switch-off/);

    // リロード後も無効(OFF)が保持されていること
    await gotoSettings(page, listParams);
    await expect(
      row(page, editedName).first().locator(".bootstrap-switch")
    ).toHaveClass(/bootstrap-switch-off/);
  });
});
