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

/**
 * 詳細画面のコメント欄に本文＋ファイル添付で投稿し、本文と添付ファイル名(basename)が
 * 一覧に表示されることを確認する。
 *
 * 添付 UI は「概要」タブの ModComments ウィジェット(`.addCommentBlock`)内に
 * `input[type="file"]`(jQuery MultiFile 適用済み, name="filename[]")として存在する。
 * `gotoDetail` の既定遷移(タブ指定なし)は「概要」タブに着地するため、追加のタブ
 * クリックは不要。
 */
export async function postCommentWithFile(
  page: Page,
  body: string,
  filePath: string
): Promise<void> {
  const textarea = page.locator("textarea.commentcontent:visible").first();
  await expect(textarea).toBeVisible();
  await textarea.fill(body);
  // コメント欄本体(返信/編集用の非表示ブロックには添付 UI が無いため、
  // 表示中の入力欄が属する .addCommentBlock 内の file input に限定する)
  const addCommentBlock = textarea.locator(
    "xpath=ancestor::div[contains(@class,'addCommentBlock')][1]"
  );
  await addCommentBlock.locator('input[type="file"]').first().setInputFiles(filePath);
  await page
    .locator('button.detailViewSaveComment[data-mode="add"]:visible')
    .first()
    .click();
  await page.waitForLoadState("networkidle");
  await expect(
    page.getByText(body).and(page.locator(":visible")).first()
  ).toBeVisible();
  const fileName = filePath.replace(/^.*[/\\]/, "");
  await expect(
    page.getByText(fileName).and(page.locator(":visible")).first()
  ).toBeVisible();
}
