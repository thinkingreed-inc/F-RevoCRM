import { expect, Locator, Page } from "@playwright/test";
import { waitSeconds } from "./util";

/** サイドバーで開閉を確認するトップレベル項目のラベル */
const SIDEBAR_APP_LABELS = ["設定", "ドキュメント", "メールマネージャー"];

/**
 * サイドバー（#app-menu）のトップレベル項目を取得する。
 *
 * トップレベルのアプリ項目は `.app-name`、アプリグループ（ツール等）の中に
 * 折りたたまれているモジュール項目は `.module-name` で描画される。
 * 「ドキュメント」はトップレベル項目とツールグループ内の両方に存在するため、
 * `getByText()` だけで絞ると折りたたみ側（常に hidden）を拾ってしまう。
 * トップレベルの開閉を検証するので `.app-name` に限定する。
 */
function sidebarAppItem(page: Page, label: string): Locator {
  return page.locator("#app-menu .app-name", { hasText: label }).first();
}

export async function sidebarTest(page: Page) {
  // ID: appnavigatorをクリックしてサイドバーを開く
  await page.click("id=appnavigator");
  await waitSeconds(page, 1000);
  // #app-menu以下で、各トップレベル項目が表示されているかを確認
  for (const label of SIDEBAR_APP_LABELS) {
    await expect(sidebarAppItem(page, label)).toBeVisible();
  }

  // ID: menu-toggle-actionをクリックしてサイドバーを閉じる
  await page.click("id=menu-toggle-action");
  await waitSeconds(page, 1000);
  for (const label of SIDEBAR_APP_LABELS) {
    await expect(sidebarAppItem(page, label)).not.toBeVisible();
  }
}
