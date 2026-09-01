import { expect, type Page } from "@playwright/test";

/** 詳細画面のコメント欄に本文を入力して投稿し、一覧に表示されることを確認する。 */
export async function postComment(page: Page, body: string): Promise<void> {
  const textarea = page.locator("textarea.commentcontent:visible").first();
  await expect(textarea).toBeVisible();
  await textarea.fill(body);
  await postAndAwaitRender(page, () =>
    page
      .locator('button.detailViewSaveComment[data-mode="add"]:visible')
      .first()
      .click()
  );
  // 投稿後、コメント一覧に本文が表示されることを確認する(高並列の再描画遅延に耐える)
  await expect(
    page.getByText(body).and(page.locator(":visible")).first()
  ).toBeVisible({ timeout: COMMENT_RENDER_TIMEOUT });
}

/**
 * コメント保存の待ち方(高並列対策)。
 *
 * 保存は index.php への POST(module=ModComments, action=SaveAjax)。成功後に
 * `loadWidget` が別 AJAX でコメント一覧を再描画する。`networkidle` は「POST 完了〜
 * 再描画開始の隙間」で誤って解決しうるため、高並列で再描画が遅れると本文がまだ
 * DOM に無く、既定 5s のアサートが落ちる(単体では速いので顕在化しない)。
 * そこで networkidle には依存せず、保存 POST の応答を待ってから、負荷耐性のある
 * タイムアウトで「本文が出現する」という観測可能な結果を条件待ちする。
 */
const COMMENT_RENDER_TIMEOUT = 20000;

async function postAndAwaitRender(
  page: Page,
  clickSave: () => Promise<void>
): Promise<void> {
  await Promise.all([
    // 保存 POST(index.php への POST。action=SaveAjax は POST ボディ内なので URL では
    // 判定できず、index.php への POST 応答で保存完了を anchor する)。取りこぼしても
    // 後段の本文出現待ちが担保するため握りつぶす。
    page
      .waitForResponse(
        (r) =>
          r.request().method() === "POST" && /\/index\.php(\?|$)/.test(r.url()),
        { timeout: COMMENT_RENDER_TIMEOUT }
      )
      .catch(() => null),
    clickSave(),
  ]);
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
  await postAndAwaitRender(page, () =>
    page
      .locator('button.detailViewSaveComment[data-mode="add"]:visible')
      .first()
      .click()
  );
  await expect(
    page.getByText(body).and(page.locator(":visible")).first()
  ).toBeVisible({ timeout: COMMENT_RENDER_TIMEOUT });
  const fileName = filePath.replace(/^.*[/\\]/, "");
  await expect(
    page.getByText(fileName).and(page.locator(":visible")).first()
  ).toBeVisible({ timeout: COMMENT_RENDER_TIMEOUT });
}
