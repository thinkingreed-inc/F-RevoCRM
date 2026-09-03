import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listRows,
  expectSearchCount,
  clearListSearch,
} from "../../utils/listview";
import { url } from "../../utils/util";
import { seedSpec } from "../../fixtures/seedSpec";

/**
 * 共通機能: 検索 / 絞り込み(既知件数) — TEST_COVERAGE の検索・フィルタギャップ
 *
 * 拡充ベースラインの [E2E-SRCH] Accounts(industry を 6 種 × 10 件で均等分布、
 * 1 件だけ一意トークン ZZUNIQAcctFindme を持つ)を使い、絞り込み結果が決定論的な
 * 既知件数になることを検証する。
 *
 * 並列安全: プレフィックス/一意トークンで絞る READ 専用。他テストの Accounts 増減に
 * 影響されない(該当部分集合は不変)。件数は 1 ページ(20 件)以内に収まる。
 */

const sr = seedSpec.accountSearch;
// 一意トークンで名前が置換される industry(先頭= Banking)以外を件数検証に使う
const cleanIndustry = sr.industries[1]; // "Chemicals": 10 件すべてが industry 名を含む

test.describe("共通: 検索/絞り込み(既知件数)", () => {
  test(`列検索: "${cleanIndustry}" で ${sr.perIndustry} 件に絞り込める`, async ({
    page,
  }) => {
    await gotoList(page, "Accounts");
    await expectSearchCount(
      page,
      "accountname",
      `${sr.prefix} ${cleanIndustry}`,
      sr.perIndustry
    );
    await clearListSearch(page);
  });

  test("列検索: 一意トークンで単一ヒット", async ({ page }) => {
    await gotoList(page, "Accounts");
    await expectSearchCount(page, "accountname", sr.globalToken, 1);
    await expect(
      listRows(page).filter({ hasText: sr.globalToken }).first()
    ).toBeVisible();
    await clearListSearch(page);
  });

  test("グローバル検索: 一意トークンで該当レコードがヒットする", async ({
    page,
  }) => {
    await page.goto(url("index.php"));
    await page.waitForLoadState("networkidle");
    await page
      .locator(".search-link span.fa-search, span.fa-search")
      .first()
      .click()
      .catch(() => {});
    const searchInput = page.getByPlaceholder("キーワード").first();
    await expect(searchInput).toBeVisible();
    await searchInput.fill(sr.globalToken);
    await searchInput.press("Enter");
    await page.waitForLoadState("networkidle");
    await expect(
      page.getByText(sr.globalToken).and(page.locator(":visible")).first()
    ).toBeVisible({ timeout: 15000 });
  });
});
