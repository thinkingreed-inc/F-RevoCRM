import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * F-07 システム変数 (System > システム構成 > システム変数 / Settings.Parameters)
 *
 * カスタムパラメータ(システム変数)の 追加 → 編集 → 削除 を通しで検証する。
 * これらはグローバルなシステム変数のため、既存の値には手を触れず、
 * ユニークなトークンで自分専用の 1 レコードを作成 → 検証 → 最後に必ず削除して
 * 原状復帰する(後続テストへ痕跡を残さない)。
 *
 * 追加/編集/削除で状態を引き継ぐため serial で実行する。
 */
test.describe.serial("管理: システム変数 (Parameters)", () => {
  // settingsUrl が parent=Settings を付与するため、module/view のみ指定する
  const listParams = { module: "Parameters", view: "List" };

  // 一意トークン。キーは英数と ._ のみ許可(SaveAjax のクライアント検証に準拠)
  const token = generateRandomString(6);
  const paramKey = `e2e_test_${token}`;
  const paramValue = `value_${token}`;
  const editedValue = `edited_${token}`;

  /** 表示中のモーダル内フォーム(EditAjax の #editCurrency)へスコープする */
  const editModal = (page: Page) =>
    page.locator(".modal-content:visible form#editCurrency");

  test("システム変数を追加できる", async ({ page }) => {
    await gotoSettings(page, listParams);

    // ツールバーの「追加」ボタン。triggerAdd でモーダルが開く
    await page.locator("button.addButton").first().click();

    const modal = editModal(page);
    await expect(modal).toBeVisible();
    await modal.locator('input[name="key"]').fill(paramKey);
    await modal.locator('input[name="value"]').fill(paramValue);

    // AJAX 保存。保存後は一覧が再描画される
    await saveAndSettle(page, modal.locator('button[name="saveButton"]'));

    // 追加したキーが一覧に現れること(リロードして永続化を確認)
    await gotoSettings(page, listParams);
    await expect(
      page.locator("#listview-table").getByText(paramKey, { exact: true })
    ).toBeVisible();
    await expect(
      page.locator("#listview-table").getByText(paramValue, { exact: true })
    ).toBeVisible();
  });

  test("システム変数を編集して値が反映される", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 追加したキーの行の編集(鉛筆)アイコンをクリックしてモーダルを開く
    const targetRow = page
      .locator("#listview-table tr.listViewEntries")
      .filter({ hasText: paramKey });
    await expect(targetRow).toBeVisible();
    await targetRow.locator("a[title] i.fa-pencil").click();

    const modal = editModal(page);
    await expect(modal).toBeVisible();
    // キーが編集対象の行のものであることを確認してから値を変更する
    await expect(modal.locator('input[name="key"]')).toHaveValue(paramKey);
    await modal.locator('input[name="value"]').fill(editedValue);

    await saveAndSettle(page, modal.locator('button[name="saveButton"]'));

    // 変更後の値がリロード後も一覧に反映されていること
    await gotoSettings(page, listParams);
    await expect(
      page.locator("#listview-table").getByText(editedValue, { exact: true })
    ).toBeVisible();
    await expect(
      page.locator("#listview-table").getByText(paramValue, { exact: true })
    ).toHaveCount(0);
  });

  test("システム変数を削除して原状復帰する", async ({ page }) => {
    await gotoSettings(page, listParams);

    const targetRow = page
      .locator("#listview-table tr.listViewEntries")
      .filter({ hasText: paramKey });
    await expect(targetRow).toBeVisible();

    // 削除(ゴミ箱)アイコン → 確認ダイアログの「はい」
    await targetRow.locator("a[title] i.fa-trash").click();
    await confirmYes(page);
    await page.waitForLoadState("networkidle").catch(() => {});

    // リロード後、追加したキーが一覧から消えていること
    await gotoSettings(page, listParams);
    await expect(
      page.locator("#listview-table").getByText(paramKey, { exact: true })
    ).toHaveCount(0);
  });
});
