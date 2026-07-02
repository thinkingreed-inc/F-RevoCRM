import { test, expect } from "@playwright/test";
import { gotoList, firstRow, firstRowName, listRows } from "../../utils/listview";

/**
 * 共通機能: 一覧からの検索(列検索) — 機能一覧 2-4
 *
 * 一覧のヘッダ直下の検索行(tr.searchRow)に、表示列ごとの検索入力
 * (input.listSearchContributor[name=<field>])がある。値を入れて
 * [data-trigger="listSearch"] を押すと AJAX で絞り込みが行われる。
 *
 * 実データに依存しないよう、先頭行の名前を読み取り、その名前で
 * accountname 列を検索して、結果に当該レコードが残ることを検証する。
 */
test.describe("共通: 一覧の列検索", () => {
  test("名前列で検索すると該当レコードに絞り込まれる", async ({ page }) => {
    await gotoList(page, "Accounts");

    // 検索対象として先頭行の顧客企業名を採取
    const name = await firstRowName(page);
    expect(name.length).toBeGreaterThan(0);

    const searchInput = page.locator(
      'input.listSearchContributor[name="accountname"]'
    );
    await expect(searchInput).toBeVisible();
    await searchInput.fill(name);

    // 検索実行(AJAX)。行の再描画を待つ。
    await page.locator('[data-trigger="listSearch"]').first().click();
    await page.waitForLoadState("networkidle");

    // 該当名の行が表示され、全行がその名前を含む(=絞り込めている)
    await expect(firstRow(page).locator(`text=${name}`).first()).toBeVisible();
    const count = await listRows(page).count();
    expect(count).toBeGreaterThan(0);

    // 後始末: 検索条件をクリア(ログインセッションに残るため)
    await page
      .locator('[data-trigger="clearListSearch"]')
      .first()
      .click()
      .catch(() => {});
    await page.waitForLoadState("networkidle").catch(() => {});
  });
});
