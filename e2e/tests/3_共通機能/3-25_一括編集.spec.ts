import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listSearch,
  listRows,
  clearListSearch,
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: 一括編集(Mass Edit) — 機能一覧 12-2(一括編集)
 *
 * 一覧でレコードを選択し、一括アクション「編集」
 * (#<Module>_listView_massAction_LBL_EDIT)で一括編集モーダルを開く。
 * 各項目は編集対象チェック(#include_in_mass_edit_<field>)を入れてから値を設定し、
 * 保存(button.saveButton)する。反映を対象レコードの詳細で確認する。
 *
 * 並行実行(DB 共有)でも安全なよう、専用の顧客企業を作って操作・後始末する。
 */
test.describe("共通: 一括編集", () => {
  test("一覧で選択したレコードの項目を一括編集できる", async ({ page }) => {
    const name = `E2Emedit${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);
    const website = `https://e2e-${generateRandomString(6).toLowerCase()}.example.com`;

    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    const row = listRows(page).filter({ hasText: name }).first();
    await expect(row).toBeVisible();
    await row.locator("input.listViewEntriesCheckBox").check();

    const edit = page.locator("#Accounts_listView_massAction_LBL_EDIT");
    await expect(edit).toBeEnabled();
    await edit.click();

    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 15000 });
    // Web サイト項目を「編集対象」に含めてから値を設定する
    await modal.locator("#include_in_mass_edit_website").check();
    await modal.locator('input[name="website"]').fill(website);
    await modal.locator("button.saveButton").first().click();
    await expect(modal).toBeHidden({ timeout: 20000 });
    await page.waitForLoadState("networkidle");

    // 検索条件はセッションに残るため、確認前に一覧で解除する
    await clearListSearch(page);

    // 反映確認: 詳細画面に一括編集した値が出る
    await gotoDetail(page, "Accounts", recordId);
    await expect(page.getByText(website).first()).toBeVisible({
      timeout: 15000,
    });

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
