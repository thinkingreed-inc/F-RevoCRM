import { test, expect } from "@playwright/test";
import { url, generateRandomString } from "../../utils/util";
import {
  gotoList,
  listRows,
  listSearch,
  clearListSearch,
} from "../../utils/listview";
import { confirmYes } from "../../utils/settings";

/**
 * 共通機能: 一括削除 と ゴミ箱(復元 / 完全削除) — 機能一覧 12-3 / 35-1
 *
 * 使い捨ての顧客企業を1件作り、
 *  1) 一覧でチェック→一括削除(#..._massAction_LBL_DELETE) でゴミ箱へ送る
 *  2) ゴミ箱(sourceModule=Accounts)で復元(LBL_RESTORE)して戻す
 *  3) 再度削除し、ゴミ箱で完全削除(LBL_DELETE)して後始末する
 *
 * 削除の確認は app.helper.showConfirmationBox の「Yes」(.confirm-box-ok)。
 * ゴミ箱は sourceModule クエリで対象モジュールを直接指定できる
 * (RecycleBin_List_View::initializeListViewContents)。
 */
test.describe("共通: 一括削除とゴミ箱", () => {
  test("一括削除→ゴミ箱で復元→完全削除できる", async ({ page }) => {
    const name = `E2Ebin${generateRandomString(6)}`;

    // --- 使い捨てレコードを作成 ---
    await page.goto(url("index.php?module=Accounts&view=Edit&app=MARKETING"));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="accountname"]', name);
    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });

    // --- 一覧で検索して選択 → 一括削除 ---
    const selectAndDelete = async () => {
      await gotoList(page, "Accounts");
      await listSearch(page, "accountname", name);
      await expect(
        listRows(page).first().locator(`text=${name}`)
      ).toBeVisible();

      await listRows(page)
        .first()
        .locator("input.listViewEntriesCheckBox")
        .check();
      const del = page.locator("#Accounts_listView_massAction_LBL_DELETE");
      await expect(del).toBeEnabled();
      await del.click();
      await confirmYes(page);
      await page.waitForLoadState("networkidle");
    };
    await selectAndDelete();

    // 一覧から消えている(検索結果0件)
    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    await expect(page.locator(`tr.listViewEntries >> text=${name}`)).toHaveCount(
      0
    );

    // --- ゴミ箱で対象を確認して復元 ---
    const gotoBin = async () => {
      await page.goto(
        url("index.php?module=RecycleBin&view=List&sourceModule=Accounts")
      );
      await page.waitForLoadState("networkidle");
    };
    await gotoBin();
    const binRow = listRows(page).filter({ hasText: name }).first();
    await expect(binRow).toBeVisible();
    await binRow.locator("input.listViewEntriesCheckBox").check();
    const restore = page.locator(
      "#RecycleBin_listView_massAction_LBL_RESTORE"
    );
    await expect(restore).toBeEnabled();
    await restore.click();
    await confirmYes(page);
    await page.waitForLoadState("networkidle");

    // 復元後: ゴミ箱から消えている
    await gotoBin();
    await expect(
      page.locator(`tr.listViewEntries >> text=${name}`)
    ).toHaveCount(0);

    // --- 後始末: 再削除 → ゴミ箱で完全削除 ---
    await selectAndDelete();
    await gotoBin();
    const binRow2 = listRows(page).filter({ hasText: name }).first();
    await expect(binRow2).toBeVisible();
    await binRow2.locator("input.listViewEntriesCheckBox").check();
    const purge = page.locator("#RecycleBin_listView_massAction_LBL_DELETE");
    await expect(purge).toBeEnabled();
    await purge.click();
    await confirmYes(page);
    await page.waitForLoadState("networkidle");

    // 完全削除後: ゴミ箱から消えている
    await gotoBin();
    await expect(
      page.locator(`tr.listViewEntries >> text=${name}`)
    ).toHaveCount(0);

    // 後始末: 検索条件はセッションに残るため、Accounts 一覧の絞り込みを解除する
    // (共有セッションのため、残すと後続テストの一覧が0件になり波及する)
    await gotoList(page, "Accounts");
    await clearListSearch(page);
  });
});
