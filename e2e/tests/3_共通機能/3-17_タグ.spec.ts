import { test, expect } from "../../fixtures/isolated";
import {
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: タグの追加・削除 — 機能一覧 4-1
 *
 * 詳細画面の「タグの追加」(#addTagTriggerer)でモーダルを開き、新規タグ名を
 * input[name="createNewTag"] に入力して保存(button[name="saveButton"])。
 * 追加後、詳細のタグ一覧(.detailTagList)に当該タグが表示される。
 * 後始末として作成したタグを × (i.deleteTag) で外す。
 *
 * 並行実行(DB 共有)でも安全なよう、専用の顧客企業を作って操作・後始末する。
 */
test.describe("共通: タグ", () => {
  test("詳細画面でタグを追加し、削除できる", async ({ page }) => {
    const recordId = await createAccount(
      page,
      `E2Etagacc${generateRandomString(6)}`
    );
    await gotoDetail(page, "Accounts", recordId);

    const tagName = `e2etag${generateRandomString(6)}`;

    // タグ追加モーダルを開く(showModal で複製表示されるため :visible で捉える)
    await page.locator("#addTagTriggerer").click();
    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible();

    // createNewTag は通常の text 入力。新規タグ名を入力し、submit(saveButton)で保存。
    await modal.locator('input[name="createNewTag"]').fill(tagName);
    await modal.locator('button[name="saveButton"]').click();
    await page.waitForLoadState("networkidle");

    // 詳細の表示タグ一覧(.detailTagList)に追加したタグが現れる。
    const tag = page
      .locator(".detailTagList span.tag")
      .filter({ hasText: tagName });
    await expect(tag.first()).toBeVisible();

    // 追加したタグを × (i.deleteTag) で外す(確認なしで delete AJAX)。
    await tag.first().hover();
    await tag.first().locator("i.deleteTag").click();
    await page.waitForLoadState("networkidle").catch(() => {});

    // 表示タグ一覧から消える
    await expect(
      page.locator(".detailTagList span.tag").filter({ hasText: tagName })
    ).toHaveCount(0);

    // 後始末: 使い捨てレコードを削除
    await deleteViaDetail(page, "Accounts", recordId);
  });
});
