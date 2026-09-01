import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listSearch,
  listRows,
  clearListSearch,
  createAccount,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: クイックプレビュー — 機能一覧 6 系(一覧からの簡易詳細)
 *
 * 一覧の行の目アイコン(a.quickView)をクリックすると、右側にクイックプレビュー
 * (.quickPreview)が開き、対象レコードの主要項目が表示される。
 *
 * 並行実行(DB 共有)でも安全なよう、専用の顧客企業を作って操作・後始末する。
 */
test.describe("共通: クイックプレビュー", () => {
  test("一覧の目アイコンでレコードのプレビューが開く", async ({ page }) => {
    test.setTimeout(60000);
    const name = `E2Eqp${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);

    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    const row = listRows(page).filter({ hasText: name }).first();
    await expect(row).toBeVisible();
    await row.hover();
    await row.locator("a.quickView").click();

    const preview = page.locator(".quickPreview").first();
    await expect(preview).toBeVisible({ timeout: 15000 });
    await expect(preview).toContainText(name);

    // プレビューは外部リソース(地図等)を読むためこのページは networkidle に到達しない。
    // 先に詳細へ遷移(プレビューから離脱)してから後始末する。
    await deleteViaDetail(page, "Accounts", recordId);
    await gotoList(page, "Accounts");
    await clearListSearch(page);
  });
});
