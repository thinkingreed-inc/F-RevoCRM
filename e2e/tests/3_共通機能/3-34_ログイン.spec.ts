import { test, expect } from "@playwright/test";
import { BASE_URL } from "../../utils/util";

/**
 * 共通機能: ログイン認証(失敗系) — 機能一覧 1-1
 *
 * 誤ったパスワードではログインできず、ログイン画面に留まりエラー
 * (#validationMessage.failureMessage)が表示されることを検証する。
 * 認証済み storageState を使わないクリーンな context で実行する。
 */
test.use({ storageState: { cookies: [], origins: [] } });

test.describe("共通: ログイン(失敗系)", () => {
  test("誤ったパスワードではログインできない", async ({ page }) => {
    // ログイン画面は外部 CDN 画像を参照するため、'load'/'networkidle' 待ちだと
    // オフライン環境で外部リクエストが完了せずタイムアウトする。DOM 構築完了で判定する。
    await page.goto(BASE_URL, { waitUntil: "domcontentloaded" });
    await page.fill("id=username", process.env.E2E_USER_NAME || "admin");
    await page.fill("id=password", "definitely-wrong-password-000");
    await page.getByRole("button", { name: "ログイン" }).click();
    await page.waitForLoadState("domcontentloaded");

    // ログイン画面に留まる(ユーザー名入力欄が見えている)
    await expect(page.locator("#username")).toBeVisible();
    // 失敗メッセージが表示される
    await expect(page.locator("#validationMessage")).toBeVisible();
  });
});
