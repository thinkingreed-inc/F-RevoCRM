import { test, expect } from "../../fixtures/isolated";
import { gotoSettings } from "../../utils/settings";

/**
 * E-02 スケジューラー (CronTasks) — 機能一覧 61-1
 *
 * バッチ処理の一覧・実行間隔設定画面。到達性と画面が正しく開くこと(タイトル)、
 * 権限エラーが出ないことを検証するスモークテスト。
 */
test.describe("管理: スケジューラー (CronTasks)", () => {
  test("スケジューラー画面が表示される", async ({ page }) => {
    await gotoSettings(page, { module: "CronTasks", view: "List" });
    await expect(page).toHaveTitle(/スケジューラ/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
    // 一覧が描画されている(スケジューラのジョブ行)
    await expect(page.locator("tr.listViewEntries").first()).toBeVisible();
  });
});
