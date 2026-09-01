import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: 関連一覧 — 機能一覧 7-1
 *
 * 詳細画面の関連タブ(li.tab-item[data-module=...])を開くと、その関連モジュールの
 * 関連一覧が AJAX で読み込まれ、「追加」等の操作が表示される。顧客企業の
 * 「顧客担当者(Contacts)」関連タブを開き、関連一覧が表示されることを検証する。
 */
test.describe("共通: 関連一覧", () => {
  test("関連タブを開くと関連一覧が表示される", async ({ page }) => {
    const recordId = await createAccount(
      page,
      `E2Erelacc${generateRandomString(6)}`
    );
    await gotoDetail(page, "Accounts", recordId);

    // 顧客担当者(Contacts)の関連タブを開く
    const tab = page.locator('li.tab-item[data-module="Contacts"]').first();
    await expect(tab).toBeVisible();
    await tab.locator("a").first().click();

    // 関連タブがアクティブになり、関連一覧コンテンツ(追加ボタン)が現れる
    await expect(tab).toHaveClass(/active/, { timeout: 15000 });
    await expect(
      page.getByText("追加").and(page.locator(":visible")).first()
    ).toBeVisible();

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
