import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * F-03 通貨 (System > 通貨)
 *
 * 一覧CRUD型。ただし通貨の追加/編集はモーダル(EditAjax)上のフォームで行い、
 * 保存は AJAX(action=SaveAjax)で一覧を再描画する(画面遷移しない)。
 *
 * - 追加:「通貨の追加」ボタン → モーダルの currency_name(select2)は既定で
 *   未登録通貨の先頭が選択される。換算率(conversion_rate)を入力して保存すると
 *   一覧に新しい行が増える。
 * - 編集:追加した通貨の行を開き、換算率を変更 → 一覧の該当行に反映される。
 * - 削除:通貨の削除は「別通貨への移行(transform_to_id)」を伴う特殊操作で、
 *   実質的に非アクティブ化/移行の運用となるため、本E2Eでは対象外とする
 *   (add+edit のみ検証)。
 *
 * モーダルは複数存在しうるため、操作対象は常に .modal-content:visible に限定する。
 */
test.describe.serial("管理: 通貨 (Currency)", () => {
  const params = { module: "Currency", view: "List" };

  // 表示中モーダルのフォーム要素
  const modal = (page: Page) => page.locator(".modal-content:visible");

  // 一覧のデータ行(アクションセルを持つ tr)
  const dataRows = (page: Page) =>
    page.locator("tr.listViewEntries").filter({ has: page.locator(".table-actions") });

  test("通貨の追加(換算率を入力して一覧に追加される)", async ({ page }) => {
    await gotoSettings(page, params);

    const before = await dataRows(page).count();

    // 「通貨の追加」モーダルを開く
    await page.getByRole("button", { name: "通貨の追加" }).click();
    const form = modal(page);
    await expect(form.locator("#editCurrency")).toBeVisible();

    // 既定で選択される通貨名を退避(この通貨コードが一覧に追加される想定)
    const addedCode = await form.locator('input[name="currency_code"]').inputValue();

    // 換算率を入力して保存(AJAX 保存 → 一覧再描画)
    const rate = String(10 + Math.floor(Math.random() * 90));
    await form.locator('input[name="conversion_rate"]').fill(rate);
    await saveAndSettle(page, form.locator("button[name=\"saveButton\"]"));

    // 一覧が再読込され、行が1件増えていること
    await gotoSettings(page, params);
    await expect
      .poll(async () => dataRows(page).count())
      .toBe(before + 1);

    // 追加した通貨コードが一覧に現れていること
    await expect(dataRows(page).filter({ hasText: addedCode })).toHaveCount(1);
  });

  test("通貨の編集(換算率を変更して一覧に反映される)", async ({ page }) => {
    await gotoSettings(page, params);

    // 先頭のデータ行を編集対象にする(基準通貨は換算率固定のため、末尾行を対象にする)
    const rows = dataRows(page);
    const target = rows.last();
    await expect(target).toBeVisible();
    const targetCode = (await target.locator("td.listViewEntryValue").nth(1).innerText()).trim();

    // 行クリックで編集モーダルを開く(鉛筆アイコンのある行はクリックで編集)
    await target.click();
    const form = modal(page);
    await expect(form.locator("#editCurrency")).toBeVisible();

    // 換算率を新しい値に変更して保存
    const newRate = String(100 + Math.floor(Math.random() * 900));
    await form.locator('input[name="conversion_rate"]').fill(newRate);
    await saveAndSettle(page, form.locator("button[name=\"saveButton\"]"));

    // 一覧を再読込し、対象コードの行に新しい換算率が反映されていること
    await gotoSettings(page, params);
    const editedRow = dataRows(page).filter({ hasText: targetCode });
    await expect(editedRow).toHaveCount(1);
    await expect(editedRow).toContainText(newRate);
  });

  // 注記: 通貨の削除は transform_to_id(移行先通貨)の指定を伴う特殊操作であり、
  // 単純な削除としては提供されていない(移行/非アクティブ化の運用)。
  // そのため本テストでは削除は検証しない。
});
