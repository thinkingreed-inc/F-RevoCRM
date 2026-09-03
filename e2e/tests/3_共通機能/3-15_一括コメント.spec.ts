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
 * 共通機能: 一括コメント(Mass Add Comment) — 機能一覧 9-1(一括)
 *
 * 一覧でレコードを選択し、一括アクション「コメント追加」
 * (#<Module>_listView_massAction_LBL_ADD_COMMENT)でモーダルを開き、
 * textarea[name="commentcontent"] に本文を入れて保存(button[name="saveButton"])。
 * 反映を対象レコードの詳細(コメント欄)で確認する。
 *
 * 並行実行(DB 共有)でも安全なよう、専用の顧客企業を作って操作・後始末する。
 */
test.describe("共通: 一括コメント", () => {
  test("一覧で選択したレコードに一括コメントできる", async ({ page }) => {
    const name = `E2Emcmt${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);
    const body = `E2E一括コメント_${generateRandomString(6)}`;

    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    const row = listRows(page).filter({ hasText: name }).first();
    await expect(row).toBeVisible();
    await row.locator("input.listViewEntriesCheckBox").check();

    const comment = page.locator(
      "#Accounts_listView_massAction_LBL_ADD_COMMENT"
    );
    await expect(comment).toBeEnabled();
    await comment.click();

    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 15000 });
    await modal.locator('textarea[name="commentcontent"]').fill(body);
    await modal.locator('button[name="saveButton"]').click();
    await expect(modal).toBeHidden({ timeout: 20000 });
    await page.waitForLoadState("networkidle");

    await clearListSearch(page);

    // 反映確認: 詳細画面のコメント欄に本文が出る
    await gotoDetail(page, "Accounts", recordId);
    await expect(page.getByText(body).first()).toBeVisible({ timeout: 15000 });

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
