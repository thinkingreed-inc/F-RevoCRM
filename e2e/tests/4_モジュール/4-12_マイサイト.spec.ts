import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import { confirmYes } from "../../utils/settings";

/**
 * マイサイト(Portal) — TEST_COVERAGE P0-B / B3
 *
 * ヘッダの「マイサイト」。ブックマーク(名前 + URL)を登録すると
 * サイドバーから外部サイトを開ける機能。エンティティモジュールではなく
 * `vtiger_portal` テーブルを直接読み書きする独自実装。
 *
 * 一括削除は **モジュール固有実装**:
 *   modules/Portal/actions/MassDelete.php → Portal_Module_Model::deleteRecords()
 * で `DELETE FROM vtiger_portal` を直接発行する(共通の delete フローを通らない)。
 * 共通機能側の一括削除テスト(3-26_ゴミ箱)は Accounts 固定なので、
 * この固有実装は誰も通していなかった。
 *
 * 画面構造(実機で確認):
 *   一覧:     index.php?module=Portal&view=List
 *   追加:     button.addBookmark → モーダル(#bookmarkName / #bookmarkUrl、
 *             保存は button[name="saveButton"])
 *   一括削除: #Portal_listview_massAction (共通の massAction_LBL_DELETE ではない)
 */

async function gotoPortalList(page: import("@playwright/test").Page) {
  await page.goto(url("index.php?module=Portal&view=List"));
  await page.waitForLoadState("networkidle");
}

test.describe("マイサイト(Portal)", () => {
  test("ブックマークを登録して一括削除できる", async ({ page }) => {
    test.setTimeout(120000);
    const name = `E2Ebm${generateRandomString(6)}`;

    await gotoPortalList(page);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);

    // --- 追加 ---
    const addBtn = page.locator("button.addBookmark").first();
    await expect(addBtn, "「ブックマークを追加」があること").toBeVisible({
      timeout: 20000,
    });
    await addBtn.click();
    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 20000 });
    await modal.locator("#bookmarkName").fill(name);
    await modal.locator("#bookmarkUrl").fill("http://example.com/e2e");
    // ModalFooter.tpl の保存ボタンは class ではなく name="saveButton"
    await modal.locator('button[name="saveButton"]').first().click();
    await expect(modal).toBeHidden({ timeout: 20000 });
    await page.waitForLoadState("networkidle");

    // --- 一覧に出る ---
    await gotoPortalList(page);
    const row = page
      .locator("tr.listViewEntries")
      .filter({ hasText: name })
      .first();
    await expect(row, "登録したブックマークが一覧に出ること").toBeVisible({
      timeout: 20000,
    });

    // --- 一括削除(Portal_List_Js.massDeleteRecords) ---
    await row.locator("input.listViewEntriesCheckBox").check();
    const del = page.locator("#Portal_listview_massAction");
    await expect(del).toBeEnabled();
    await del.click();
    await confirmYes(page);

    // 削除は Ajax。goto で POST を中断しないよう、まずその場で消えるのを待つ
    await expect(
      page.locator("tr.listViewEntries").filter({ hasText: name })
    ).toHaveCount(0, { timeout: 30000 });

    await gotoPortalList(page);
    await expect(
      page.locator("tr.listViewEntries").filter({ hasText: name })
    ).toHaveCount(0, { timeout: 15000 });
  });
});
