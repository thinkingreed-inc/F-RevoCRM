import { test, expect } from "@playwright/test";
import { gotoSettings } from "../../utils/settings";

/**
 * C-06 ログイン履歴 (ユーザー管理 > ログイン履歴)
 *
 * 読み取り専用の一覧。temp はログアウト→別パスワードで再ログイン(=共有セッション破壊、
 * かつ別環境のパスワードがハードコードで実行不能)していたが、履歴確認が目的なので
 * 一覧に自分(admin)の記録が出ていることの検証に置き換える(ログアウトしない)。
 */
test.describe("管理: ログイン履歴 (LoginHistory)", () => {
  test("ログイン履歴一覧に admin の記録が表示される", async ({ page }) => {
    await gotoSettings(page, { module: "LoginHistory", view: "List" });

    // ユーザー名列は表示名(admin=システム管理者)で出る
    const rows = page.locator("table#listview-table tr.listViewEntries");
    await expect(rows.first()).toBeVisible();
    await expect(
      rows.filter({ hasText: "システム管理者" }).first()
    ).toBeVisible();
  });
});
