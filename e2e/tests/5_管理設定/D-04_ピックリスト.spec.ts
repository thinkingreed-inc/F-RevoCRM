import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings } from "../../utils/settings";

/**
 * D-04 選択肢エディタ (モジュール管理 > 選択肢エディタ)
 *
 * 一覧CRUD型の代表。既定で選択される選択肢フィールド(案件/タイプ)に対し、
 * 追加→編集→削除を直列で行い、各操作が一覧に反映されることを検証する。
 * 行は data-key(=選択肢の値そのもの)で一意に特定するため prefix 検索の曖昧さがない。
 *
 * 注意: モーダルの土台(.modal-content)は非表示の雛形と表示中のものが2つ存在するため、
 * モーダル操作は必ず表示中(:visible)へスコープする。
 */
test.describe.serial("管理: 選択肢エディタ (Picklist)", () => {
  const listParams = { module: "Picklist", view: "Index" };
  const token = generateRandomString(8);
  const value = `e2e${token}`;
  const editedValue = `e2eedit${token}`;

  // 選択肢の値を表す行(data-key に値そのものが入る)
  const row = (page: Page, key: string) =>
    page.locator(`tr.pickListValue[data-key="${key}"]`);

  // 表示中のモーダル
  const modal = (page: Page) => page.locator(".modal-content:visible");

  test("選択肢の追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    await page.getByRole("button", { name: "選択肢の追加" }).first().click();

    // モーダル先頭の select2(「選択肢名」。2つ目は「役割」)にキー入力し、Enter でタグ化する。
    // select2 のタグ生成はキーストロークで発火するため pressSequentially を使う。
    const valueWidget = modal(page).locator(".select2-container").first();
    const tagInput = valueWidget.locator("input.select2-input");
    await tagInput.click();
    await tagInput.pressSequentially(value);
    await tagInput.press("Enter");
    await expect(
      valueWidget.locator("li.select2-search-choice")
    ).toContainText(value);

    await modal(page).locator('button.btn-success[name="saveButton"]').click();

    // 一覧に追加した値が現れること(AJAX保存の反映ラグに強くするため開き直してリトライ)
    await expect(async () => {
      await gotoSettings(page, listParams);
      await expect(row(page, value)).toBeVisible({ timeout: 3000 });
    }).toPass({ timeout: 25000 });
  });

  test("選択肢の編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = row(page, value);
    await target.hover();
    await target.locator("a.renameItem").click();

    await modal(page).locator('input[name="renamedValue"]').fill(editedValue);
    await modal(page).locator('button.btn-success[name="saveButton"]').click();

    // 新しい値が現れ、元の値は消えていること(反映ラグに強く)
    await expect(async () => {
      await gotoSettings(page, listParams);
      await expect(row(page, editedValue)).toBeVisible({ timeout: 3000 });
      await expect(row(page, value)).toHaveCount(0, { timeout: 3000 });
    }).toPass({ timeout: 25000 });
  });

  test("選択肢の削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = row(page, editedValue);
    await target.hover();
    await target.locator("a.deleteItem").click();

    // 削除確認モーダルの削除ボタン
    await modal(page).locator('button.btn-danger[name="saveButton"]').click();

    // 一覧から削除した値が消えていること(反映ラグに強く)
    await expect(async () => {
      await gotoSettings(page, listParams);
      await expect(row(page, editedValue)).toHaveCount(0, { timeout: 3000 });
    }).toPass({ timeout: 25000 });
  });
});
