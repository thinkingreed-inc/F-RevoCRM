import { test, expect } from "../../fixtures/isolated";
import { gotoSettings, loginInIsolatedContext } from "../../utils/settings";

/**
 * C-06 ログイン履歴 (ユーザー管理 > ログイン履歴)
 *
 * 読み取り専用の一覧。temp はログアウト→別パスワードで再ログイン(=共有セッション破壊、
 * かつ別環境のパスワードがハードコードで実行不能)していたが、履歴確認が目的なので
 * 一覧に自分(admin)の記録が出ていることの検証に置き換える(ログアウトしない)。
 *
 * 一覧は login_time の降順・1ページ 20 件で、検索も並べ替えも無い。admin のログインは
 * ワーカー起動時(fixtures/isolated)にしか起きないため、並行実行で seed ユーザー(e2e_*)の
 * ログインが積み上がると実行後半には 1 ページ目から押し出される(2026-09-29 に実際に失敗:
 * 1 ページ目の範囲が 19:02〜19:04 に対し admin の直近記録は 18:49)。
 * そこで検証直前に admin のログイン記録を作ってから一覧を確認する。
 */
test.describe("管理: ログイン履歴 (LoginHistory)", () => {
  test("ログイン履歴一覧に admin の記録が表示される", async ({
    page,
    browser,
  }) => {
    // 共有 storageState を汚さない独立 context でログインし、直近の記録を作る
    const { context } = await loginInIsolatedContext(
      browser,
      process.env.E2E_USER_NAME || "admin",
      process.env.E2E_USER_PASSWORD || "Admin1234/"
    );
    try {
      await gotoSettings(page, { module: "LoginHistory", view: "List" });

      // ユーザー名列は表示名(admin=システム管理者)で出る
      const rows = page.locator("table#listview-table tr.listViewEntries");
      await expect(rows.first()).toBeVisible();
      await expect(
        rows.filter({ hasText: "システム管理者" }).first()
      ).toBeVisible();
    } finally {
      await context.close();
    }
  });
});
