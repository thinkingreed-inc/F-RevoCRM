import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  deleteViaDetail,
} from "../../utils/listview";
import { url, generateRandomString } from "../../utils/util";

/**
 * 共通機能: グローバル検索 — 機能一覧 41 系(横断検索)
 *
 * ヘッダーの検索(キーワード入力)にレコード名を入れて検索すると、統合検索結果に
 * 該当レコードが表示されることを検証する。専用の顧客企業を作成して検索・後始末。
 */
test.describe("共通: グローバル検索", () => {
  test("キーワード検索で作成レコードがヒットする", async ({ page }) => {
    const name = `E2Egs${generateRandomString(8)}`;
    const recordId = await createAccount(page, name);

    await page.goto(url("index.php"));
    await page.waitForLoadState("networkidle");

    // ヘッダーの検索アイコンでキーワード入力を出す
    await page
      .locator(".search-link span.fa-search, span.fa-search")
      .first()
      .click()
      .catch(() => {});
    const searchInput = page.getByPlaceholder("キーワード").first();
    await expect(searchInput).toBeVisible();
    await searchInput.fill(name);
    await searchInput.press("Enter");
    await page.waitForLoadState("networkidle");

    // 統合検索結果に作成レコードが現れる
    await expect(
      page.getByText(name).and(page.locator(":visible")).first()
    ).toBeVisible({ timeout: 15000 });

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
