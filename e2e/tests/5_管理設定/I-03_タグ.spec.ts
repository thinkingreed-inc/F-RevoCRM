import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * I-03 個人タグ (Tags / 個人設定 > 個人タグ)
 *
 * 一覧CRUD型。タグ一覧(Settings:Tags:List)に対して 追加→編集→削除 を直列で検証する。
 * 一覧の各行は tr.listViewEntries で、タグ名は td.listViewEntryValue に表示される。
 * 行アクションは編集(triggerEdit / fa-pencil)と削除(triggerDelete / fa-trash)。
 *
 * 追加/編集はいずれも AJAX 保存(画面遷移しない)ため saveAndSettle で完了を待つ。
 * モーダルは追加(#addTagSettings 内)と編集(#editTagContainer)で別物なので、
 * それぞれ表示中(.modal-content:visible)へスコープして操作する。
 * 作成したタグはテスト末尾で削除して後始末する。
 */
test.describe.serial("管理: 個人タグ (Tags)", () => {
  const params = { module: "Tags", view: "List" };
  const token = generateRandomString(8);
  const nameAdd = `e2etagadd${token}`;
  const nameEdit = `e2etagedit${token}`;

  // タグ名を表示するデータ行(値セルで一意特定)
  const row = (page: Page, text: string) =>
    page
      .locator("tr.listViewEntries")
      .filter({ has: page.locator("td.listViewEntryValue", { hasText: text }) });

  // 表示中のモーダル
  const modal = (page: Page) => page.locator(".modal-content:visible");

  test("個人タグの追加", async ({ page }) => {
    await gotoSettings(page, params);

    // 「タグを追加」ボタンで追加モーダルを開く
    await page.getByRole("button", { name: "タグを追加" }).first().click();
    await expect(modal(page).locator('input[name="createNewTag"]')).toBeVisible();

    // タグ名を入力して保存(AJAX)
    await modal(page).locator('input[name="createNewTag"]').fill(nameAdd);
    await saveAndSettle(
      page,
      modal(page).locator('button[name="saveButton"]')
    );

    // 再読込して一覧に追加したタグが現れること
    await gotoSettings(page, params);
    await expect(row(page, nameAdd)).toHaveCount(1);
  });

  test("個人タグの編集", async ({ page }) => {
    await gotoSettings(page, params);

    // 追加したタグ行の編集アイコンで編集モーダルを開く
    const target = row(page, nameAdd);
    await target.locator('a[onclick*="triggerEdit"]').click();
    await expect(modal(page).locator('input[name="tagName"]')).toBeVisible();

    // タグ名を書き換えて保存(AJAX)
    await modal(page).locator('input[name="tagName"]').fill(nameEdit);
    await saveAndSettle(
      page,
      modal(page).locator('button.saveTag[name="saveButton"]')
    );

    // 再読込して 新しい名前が現れ、元の名前は消えていること
    await gotoSettings(page, params);
    await expect(row(page, nameEdit)).toHaveCount(1);
    await expect(row(page, nameAdd)).toHaveCount(0);
  });

  test("個人タグの削除", async ({ page }) => {
    await gotoSettings(page, params);

    // 編集後のタグ行の削除アイコン → 確認ダイアログで「はい」
    await row(page, nameEdit).locator('a[onclick*="triggerDelete"]').click();
    await confirmYes(page);
    await page.waitForLoadState("networkidle").catch(() => {});

    // 再読込して一覧から削除したタグが消えていること
    await gotoSettings(page, params);
    await expect(row(page, nameEdit)).toHaveCount(0);
  });
});
