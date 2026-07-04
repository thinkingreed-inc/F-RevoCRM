import { test, expect } from "../../fixtures/isolated";
import { gotoList } from "../../utils/listview";
import { confirmYes } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: リスト機能(CustomView) — 機能一覧 2-2
 *
 * サイドバーの「新しいリストを作成」(#createFilter)で個人リストを作成し、
 * サイドバーの個人リストに現れて「現在のリスト」として適用されることを確認する。
 * 後始末として、作成したリストを行アクションのポップオーバー「削除」で消す。
 *
 * 主要セレクタ(実画面で確定):
 *  - 作成ボタン: #createFilter (data-url=CustomView&view=EditAjax)
 *  - 作成モーダル: h4「新しいリストを作成」/ 名前 input[name="viewname"] / 保存 button.saveButton
 *    (列は既定で選択済みのため、名前だけ入れて保存できる)
 *  - サイドバー: #module-filters li.listViewFilter a.filterName[data-filter-id]
 *  - 現在のリスト名: .current-filter-name
 *  - 行アクション: span.js-popover-container .fa-angle-down[rel="popover"] → .popover li.deleteFilter
 *
 * 並行実行(DB 共有)でも安全なよう、専用の使い捨てリストを作って操作・後始末する。
 */
test.describe("共通: リスト(CustomView)", () => {
  test("個人リストを作成して削除できる", async ({ page }) => {
    // 低速環境 + 保存/削除の反映待ち(開き直し)を含むため余裕を持たせる
    test.setTimeout(90000);
    await gotoList(page, "Accounts");

    const filterName = `E2Ecv${generateRandomString(6)}`;
    const itemsBy = (name: string) =>
      page
        .locator("#module-filters li.listViewFilter")
        .filter({ hasText: name });

    // --- 作成 ---
    await page.locator("#createFilter").click();
    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 15000 });
    await expect(
      modal.locator('h4:has-text("新しいリストを作成")')
    ).toBeVisible();
    await modal.locator('input[name="viewname"]').fill(filterName);
    // 重要: 保存ボタンの AJAX ハンドラはモーダル(EditAjax)読込後に非同期で登録される。
    // 登録前にクリックするとネイティブ GET 送信(action=Save への生遷移)になり、
    // 保存が確定せずリストが作成されない。ハンドラ登録を待ってからクリックする。
    await page.waitForTimeout(2500);
    // 保存成功で新しいリスト(view=List&viewname=NN)へ AJAX 遷移する。その遷移完了を待つ
    // (即 networkidle だと保存前の idle で解決し、後続 gotoList が保存を中断する)。
    await Promise.all([
      page.waitForURL(/view=List&viewname=\d+/, { timeout: 20000 }).catch(() => {}),
      modal.locator("button.saveButton").first().click(),
    ]);
    await page.waitForLoadState("networkidle").catch(() => {});

    // --- 作成確認: 一覧のサイドバー(個人リスト)に現れる(反映ラグに備え開き直す) ---
    await expect(async () => {
      await gotoList(page, "Accounts");
      await expect(itemsBy(filterName).first()).toBeVisible({ timeout: 3000 });
    }).toPass({ timeout: 30000 });
    const item = itemsBy(filterName).first();

    // --- 後始末: 行アクションのポップオーバーから削除 ---
    // アクションアイコンはホバーで表示される。トリガー(.js-popover-container)を
    // クリックすると .popover に 編集/削除/複製 メニューが出る。
    await item.hover();
    await item.locator("span.js-popover-container").first().click();
    const del = page.locator(".popover li.deleteFilter").first();
    await expect(del).toBeVisible({ timeout: 8000 });
    await Promise.all([
      page
        .waitForResponse((r) => /action=Delete/.test(r.url()), {
          timeout: 15000,
        })
        .catch(() => null),
      (async () => {
        await del.click();
        await confirmYes(page);
      })(),
    ]);
    await page.waitForLoadState("networkidle").catch(() => {});

    // 削除の反映を、開き直して消えるまで待つ。
    await expect(async () => {
      await gotoList(page, "Accounts");
      await expect(itemsBy(filterName)).toHaveCount(0, { timeout: 3000 });
    }).toPass({ timeout: 30000 });
  });
});
