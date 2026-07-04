import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: 概要 / 詳細 画面 — 機能一覧 6-1 / 6-2
 *
 * 詳細画面は既定で「概要」タブ(li.tab-item.active)が開き、主要項目
 * (.summaryView 内 tr.summaryViewEntries)が表示される。「詳細」タブへ切り替えると
 * アクティブが移り、全項目が表示されることを検証する。
 *
 * 主要セレクタ(実画面で確定): li.tab-item(span.tab-label に 概要/詳細)/ .summaryView /
 * tr.summaryViewEntries
 */
test.describe("共通: 概要/詳細タブ", () => {
  test("概要タブで主要項目、詳細タブで全項目が表示される", async ({
    page,
  }) => {
    const name = `E2Esum${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);
    await gotoDetail(page, "Accounts", recordId);

    // 概要タブが既定でアクティブ。概要に主要項目(顧客企業名)が出る
    const summaryTab = page
      .locator("li.tab-item")
      .filter({ hasText: "概要" })
      .first();
    await expect(summaryTab).toHaveClass(/active/);
    const summary = page.locator(".summaryView").first();
    await expect(summary).toBeVisible();
    await expect(summary.locator("tr.summaryViewEntries").first()).toBeVisible();
    await expect(summary).toContainText(name);

    // 詳細タブへ切替 → アクティブになり、全項目(顧客企業名)が見える
    const detailTab = page
      .locator("li.tab-item")
      .filter({ hasText: "詳細" })
      .first();
    await detailTab.locator("a").first().click();
    await expect(detailTab).toHaveClass(/active/, { timeout: 15000 });
    await expect(page.getByText(name).first()).toBeVisible();

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
