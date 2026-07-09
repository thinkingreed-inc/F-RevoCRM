import { expect, type Page } from "@playwright/test";

/** 詳細画面のコメント欄に本文を入力して投稿し、一覧に表示されることを確認する。 */
export async function postComment(page: Page, body: string): Promise<void> {
  const textarea = page.locator("textarea.commentcontent:visible").first();
  await expect(textarea).toBeVisible();
  await textarea.fill(body);
  await page
    .locator('button.detailViewSaveComment[data-mode="add"]:visible')
    .first()
    .click();
  await page.waitForLoadState("networkidle");
  await expect(
    page.getByText(body).and(page.locator(":visible")).first()
  ).toBeVisible();
}
