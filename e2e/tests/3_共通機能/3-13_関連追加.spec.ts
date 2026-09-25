import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: 関連一覧からの追加 — 機能一覧 7-1
 *
 * 顧客企業の詳細で「顧客担当者(Contacts)」関連タブを開き、「追加」
 * (a.addButton[href*="module=Contacts&view=Edit"])から新規の関連レコードを作成する。
 * 作成後、顧客企業の Contacts 関連一覧に当該レコードが現れることを検証する。
 *
 * 「追加」は関連付け済みの編集画面(親=顧客企業がプリセット)へ遷移し、保存で
 * 関連付いた顧客担当者が作成される。作成レコードは後始末で削除する。
 */
test.describe("共通: 関連一覧からの追加", () => {
  test("関連一覧の追加から顧客担当者を作成できる", async ({ page }) => {
    const accName = `E2Eradd${generateRandomString(6)}`;
    const lastName = `E2Ect${generateRandomString(6)}`;
    const accId = await createAccount(page, accName);
    await gotoDetail(page, "Accounts", accId);

    // 顧客担当者(Contacts)関連タブを開く
    const tab = page.locator('li.tab-item[data-module="Contacts"]').first();
    await tab.locator("a").first().click();
    await expect(tab).toHaveClass(/active/, { timeout: 15000 });

    // 「追加」(新規作成: Contacts の Edit へ遷移)
    // 「追加」は <button class="addButton">(遷移先は data-url)。関連モジュール名
    // (顧客担当者)を含むボタンが新規作成の「追加」。
    const addBtn = page
      .locator("button.addButton")
      .filter({ hasText: "顧客担当者" })
      .first();
    await expect(addBtn).toBeVisible({ timeout: 15000 });
    await addBtn.click();

    // 「追加」は作成用の React ダイアログ([role=dialog]、親=顧客企業がプリセット)を開く。
    // 保存ボタンは React 製で name 属性が無いため role で捉える(quickcreate と同様)。
    const modal = page.getByRole("dialog");
    await expect(modal).toBeVisible({ timeout: 15000 });
    await modal.locator('input[name="lastname"]').first().fill(lastName);
    await modal.getByRole("button", { name: "保存" }).first().click();
    await expect(modal).toBeHidden({ timeout: 20000 });
    await page.waitForLoadState("networkidle");

    // 関連付け確認: 顧客企業の Contacts 関連一覧に作成した担当者が出る
    await gotoDetail(page, "Accounts", accId);
    await page
      .locator('li.tab-item[data-module="Contacts"] a')
      .first()
      .click();
    const relRow = page
      .locator("tr.listViewEntries")
      .filter({ hasText: lastName })
      .first();
    await expect(relRow).toBeVisible({ timeout: 15000 });

    // 後始末: 作成した顧客担当者 → 顧客企業 の順で削除
    const href = await relRow
      .locator('a[href*="view=Detail"]')
      .first()
      .getAttribute("href");
    const contactId = href?.match(/record=(\d+)/)?.[1];
    if (contactId) {
      await deleteViaDetail(page, "Contacts", contactId);
    }
    await deleteViaDetail(page, "Accounts", accId);
  });
});
