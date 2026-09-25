import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoList, listRows, listSearch } from "../../utils/listview";
import { seedSpec } from "../../fixtures/seedSpec";

/**
 * 共通機能: 一覧のページング / 列ソート — TEST_COVERAGE の保留ギャップ
 *
 * 拡充ベースラインの [E2E-PAGE] Accounts(ゼロ埋め連番 0001..0250, admin 所有)を
 * accountname 列検索で絞り込み、決定論的にページ送り/末尾ページ/列ソート昇降順を検証する。
 *
 * 並列安全: [E2E-PAGE] プレフィックスで絞るので、他テストが Accounts を増減させても
 * 対象 250 件は不変(READ 専用)。ゼロ埋めにより名前昇順=数値順が保証される。
 *
 * 【知見】列ソート/ページ送りの AJAX 後は networkidle に達しない事があるため(TEST_COVERAGE
 * 記載)、waitForLoadState は使わず「先頭行テキストが期待値になる」ことをポーリング待ちする。
 */

const pg = seedSpec.accountPaging;
const P = pg.prefix;
const name = (n: number) => `${P} ${String(n).padStart(4, "0")}`;

/** 先頭データ行の名前リンク。 */
function firstRowLink(page: Page) {
  return page
    .locator('tr.listViewEntries a[href*="view=Detail"]:visible')
    .first();
}

/** 先頭行が指定テキストを含むまで待つ(AJAX 完了の代替待機)。 */
async function waitFirstRow(page: Page, text: string) {
  await expect(firstRowLink(page)).toContainText(text, { timeout: 20000 });
}

/** accountname 列を指定方向にソートする(必要なら 2 回クリックして向きを合わせる)。 */
async function sortByName(page: Page, order: "ASC" | "DESC") {
  const header = page.locator(
    'th a.listViewContentHeaderValues[data-columnname="accountname"]'
  );
  for (let i = 0; i < 2; i++) {
    const next = await header.getAttribute("data-nextsortorderval");
    // 目的の向きが「次のクリックで得られる向き」なら 1 回クリックで確定する
    if ((next || "").toUpperCase() === order) {
      await header.click();
      return;
    }
    await header.click();
    // クリックで一覧が再描画され data-nextsortorderval が反転する。反転を待つ。
    await expect(header).not.toHaveAttribute(
      "data-nextsortorderval",
      next || "",
      { timeout: 20000 }
    );
  }
}

test.describe("共通: 一覧のページング/列ソート", () => {
  test("ページ送り・末尾ページ・境界ボタン", async ({ page }) => {
    test.setTimeout(90000);
    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", P);

    // 決定論のため名前昇順に固定(0001 が先頭)
    await sortByName(page, "ASC");
    await waitFirstRow(page, name(1));

    // 1 ページ目: 20 件、前ページ無効
    await expect(listRows(page)).toHaveCount(pg.pageSize);
    await expect(page.locator("#PreviousPageButton")).toBeDisabled();
    await expect(page.locator("#NextPageButton")).toBeEnabled();

    // 次ページ → 0021 が先頭
    await page.locator("#NextPageButton").click();
    await waitFirstRow(page, name(pg.pageSize + 1));

    // 末尾ページまで「次へ」で送る(250/20 = 13 ページ)。ゼロ埋めにより各ページ先頭は既知。
    const lastPage = Math.ceil(pg.count / pg.pageSize);
    const firstOfLast = (lastPage - 1) * pg.pageSize + 1;
    for (let p = 3; p <= lastPage; p++) {
      await page.locator("#NextPageButton").click();
      await waitFirstRow(page, name((p - 1) * pg.pageSize + 1));
    }

    // 末尾ページ: 端数 10 件、次ページ無効
    await expect(page.locator("#NextPageButton")).toBeDisabled();
    await expect(listRows(page)).toHaveCount(pg.count - firstOfLast + 1);
  });

  test("列ソート昇順/降順が効く", async ({ page }) => {
    test.setTimeout(90000);
    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", P);

    await sortByName(page, "ASC");
    await waitFirstRow(page, name(1)); // 昇順先頭 = 0001

    await sortByName(page, "DESC");
    await waitFirstRow(page, name(pg.count)); // 降順先頭 = 0250
  });
});
