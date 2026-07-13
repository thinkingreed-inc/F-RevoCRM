import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  deleteViaDetail,
} from "../../utils/listview";
import { url, generateRandomString } from "../../utils/util";

/**
 * 共通機能: 更新履歴(ModTracker) — 機能一覧 8-1
 *
 * レコード作成時点で「作成」の履歴が残る。詳細の更新履歴表示
 * (mode=showRecentActivities)を開き、作成者(システム管理者)を含む履歴が
 * 表示されることを検証する。
 */
test.describe("共通: 更新履歴", () => {
  test("作成したレコードの更新履歴が表示される", async ({ page }) => {
    const name = `E2Ehist${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);

    await page.goto(
      url(
        `index.php?module=Accounts&view=Detail&record=${recordId}&mode=showRecentActivities&page=1&app=MARKETING`
      )
    );
    await page.waitForLoadState("networkidle");

    // 作成の履歴(作成者=システム管理者)が表示される。
    // レスポンシブ用の非表示複製があるため :visible なものに限定する。
    await expect(
      page
        .getByText("システム管理者")
        .and(page.locator(":visible"))
        .first()
    ).toBeVisible();

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
