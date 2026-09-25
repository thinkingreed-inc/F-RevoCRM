import { test, expect } from "../../fixtures/isolated";
import { gotoList, listRows } from "../../utils/listview";
import { seedSpec } from "../../fixtures/seedSpec";

/**
 * 共通機能: サイドバーのタグ絞り込み — TEST_COVERAGE の保留ギャップ
 *
 * 【保留原因】テスト時にタグを作ると反映が不定（作成直後のタグが出ない）。
 * 【対策】タグ（vtiger_freetags）と付与（vtiger_freetagged_objects）を dump に焼き込み、
 *   `[E2E-PAGE]` の先頭 taggedCount 件に付与した安定状態にした。サイドバーの当該タグを
 *   クリックすると、その一覧が付与レコードだけに絞り込まれることを検証する。
 *
 * READ 専用（タグ付与済みの固定集合を絞るだけ）。件数は 1 ページ内に収まる。
 */

const tf = seedSpec.tagFilter;

test.describe("共通: サイドバーのタグ絞り込み", () => {
  test(`タグ「${tf.tagName}」で ${tf.taggedCount} 件に絞り込める`, async ({
    page,
  }) => {
    test.setTimeout(60000);
    await gotoList(page, tf.module);
    const tag = page
      .locator("#listViewTagContainer span.tag, #module-filters span.tag")
      .filter({ hasText: tf.tagName })
      .first();
    await expect(tag).toBeVisible({ timeout: 15000 });
    await tag.click();
    await page.waitForLoadState("networkidle").catch(() => {});
    await expect(listRows(page)).toHaveCount(tf.taggedCount);
  });
});
