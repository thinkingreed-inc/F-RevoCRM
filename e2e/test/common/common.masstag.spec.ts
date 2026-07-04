import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listSearch,
  listRows,
  clearListSearch,
  openMassMore,
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: タグの一括付与(一覧) — 機能一覧 4-1(一覧一括)
 *
 * 一覧でレコードを選択し、一括アクション「タグの追加」
 * (#<Module>_listView_massAction_LBL_ADD_TAG)でタグ追加モーダルを開く。
 * 新規タグ名を input[name="createNewTag"] に入力して保存(button[name="saveButton"])。
 * 付与されたことを対象レコードの詳細画面のタグ一覧(.detailTagList)で確認する。
 *
 * 並行実行(DB 共有)でも安全なよう、専用の顧客企業を作って操作・後始末する。
 */
test.describe("共通: タグ一括付与", () => {
  test("一覧で選択したレコードにタグを一括付与できる", async ({ page }) => {
    const name = `E2Emtag${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);
    const tagName = `e2emtag${generateRandomString(6)}`;

    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    const row = listRows(page).filter({ hasText: name }).first();
    await expect(row).toBeVisible();
    await row.locator("input.listViewEntriesCheckBox").check();

    // 一括アクション「タグの追加」を開く(既定 hide のため more を開いてから)
    await openMassMore(page);
    await page.locator("#Accounts_listView_massAction_LBL_ADD_TAG").click();

    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 15000 });
    await modal.locator('input[name="createNewTag"]').fill(tagName);
    await modal.locator('button[name="saveButton"]').click();
    await page.waitForLoadState("networkidle");

    // 検索条件はセッションに残るため、詳細確認の前に一覧で解除しておく
    await clearListSearch(page);

    // 詳細画面のタグ一覧に付与したタグが出る
    await gotoDetail(page, "Accounts", recordId);
    await expect(
      page
        .locator(".detailTagList span.tag")
        .filter({ hasText: tagName })
        .first()
    ).toBeVisible({ timeout: 15000 });

    // 後始末: 使い捨てレコードを削除
    await deleteViaDetail(page, "Accounts", recordId);
  });
});
