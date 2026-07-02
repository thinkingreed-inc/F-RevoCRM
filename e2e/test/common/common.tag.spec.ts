import { test, expect } from "@playwright/test";
import {
  gotoList,
  firstRecordId,
  gotoDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: タグの追加・削除 — 機能一覧 4-1
 *
 * 詳細画面の「タグの追加」(#addTagTriggerer)でモーダルを開き、新規タグ名を
 * input[name="createNewTag"] に入力して保存(button[name="saveButton"])。
 * 追加後、詳細のタグ一覧(.tagContainer)に当該タグが表示される。
 * 後始末として作成したタグを × (i.deleteTag) で外す。
 *
 * 個人タグは作成者しか削除できないため、テスト内で作った分をそのまま外す。
 */
test.describe("共通: タグ", () => {
  test("詳細画面でタグを追加し、削除できる", async ({ page }) => {
    await gotoList(page, "Accounts");
    const recordId = await firstRecordId(page);
    await gotoDetail(page, "Accounts", recordId);

    const tagName = `e2etag${generateRandomString(6)}`;

    // タグ追加モーダルを開く(showModal で複製表示されるため :visible で捉える)
    await page.locator("#addTagTriggerer").click();
    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible();

    // createNewTag は通常の text 入力。新規タグ名を入力し、submit(saveButton)で保存。
    // (Enter でも form submit されるが、submit ハンドラが hideModal するため
    //  ボタンクリックに統一する)
    await modal.locator('input[name="createNewTag"]').fill(tagName);
    await modal.locator('button[name="saveButton"]').click();
    await page.waitForLoadState("networkidle");

    // 詳細の表示タグ一覧(.detailTagList)に追加したタグが現れる。
    // (.tagContainer には非表示の viewAllTags 複製もあるため表示用リストに限定する)
    const tag = page
      .locator(".detailTagList span.tag")
      .filter({ hasText: tagName });
    await expect(tag.first()).toBeVisible();

    // 後始末: 追加したタグを × (i.deleteTag) で外す。
    // deleteTag は確認なしで delete AJAX を投げ、クリックした要素を除去する。
    await tag.first().hover();
    await tag.first().locator("i.deleteTag").click();
    await page.waitForLoadState("networkidle").catch(() => {});

    // 表示タグ一覧から消える
    await expect(
      page.locator(".detailTagList span.tag").filter({ hasText: tagName })
    ).toHaveCount(0);
  });
});
