import { expect, Page } from "@playwright/test";
import { waitSeconds } from "./util";

export async function sidebarTest(page: Page){
  // ID: appnavigatorをクリックしてサイドバーを開く
  await page.click('id=appnavigator');
  await waitSeconds(page, 1000);
  // #app-menu以下で、設定というテキストが表示されているかを確認
  await expect(page.locator('#app-menu').getByText('設定').first()).toBeVisible();
  await expect(page.locator('#app-menu').getByText('ドキュメント').first()).toBeVisible();
  await expect(page.locator('#app-menu').getByText('メールマネージャー').first()).toBeVisible();

  // ID: appnavigatorをクリックしてサイドバーを閉じる
  await page.click('id=menu-toggle-action');
  await waitSeconds(page, 1000);
  await expect(page.locator('#app-menu').getByText('設定').first()).not.toBeVisible();
  await expect(page.locator('#app-menu').getByText('ドキュメント').first()).not.toBeVisible();
  await expect(page.locator('#app-menu').getByText('メールマネージャー').first()).not.toBeVisible();
}