import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: コメント(ModComments) — 機能一覧 9-1
 *
 * 概要/詳細のコメント欄(textarea.commentcontent)に入力し、「投稿」
 * (button.detailViewSaveComment[data-mode="add"])でコメントを登録できる。
 * 投稿後、コメント一覧に本文が表示されることを検証する。
 *
 * 専用の顧客企業を作成して操作・後始末する(並行安全)。
 */
test.describe("共通: コメント", () => {
  test("詳細画面でコメントを投稿できる", async ({ page }) => {
    const recordId = await createAccount(
      page,
      `E2Ecmtacc${generateRandomString(6)}`
    );
    await gotoDetail(page, "Accounts", recordId);

    const body = `E2Eコメント_${generateRandomString(6)}`;

    // 表示中のコメント入力欄へ本文を入力して投稿
    const textarea = page.locator("textarea.commentcontent:visible").first();
    await expect(textarea).toBeVisible();
    await textarea.fill(body);
    await page
      .locator('button.detailViewSaveComment[data-mode="add"]:visible')
      .first()
      .click();
    await page.waitForLoadState("networkidle");

    // 投稿したコメント本文がコメント一覧に表示される
    await expect(
      page.getByText(body).and(page.locator(":visible")).first()
    ).toBeVisible();

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
