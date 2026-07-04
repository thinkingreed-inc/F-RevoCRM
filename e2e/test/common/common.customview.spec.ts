import { test, expect } from "../../fixtures/isolated";
import type { Page, Locator } from "@playwright/test";
import { gotoList } from "../../utils/listview";
import { confirmYes } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: リスト機能(CustomView) — 機能一覧 2-2
 *
 * 個人リストの 作成 / 切替 / 複製 / 削除 を検証する。
 *
 * 主要セレクタ(実画面で確定):
 *  - 作成ボタン: #createFilter (data-url=CustomView&view=EditAjax)
 *  - 作成/複製モーダル: h4「新しいリストを作成」/ 名前 input[name="viewname"] / 保存 button.saveButton
 *    (列は既定で選択済みのため、名前だけ入れて保存できる)
 *  - サイドバー: #module-filters li.listViewFilter a.filterName[data-filter-id]
 *  - 現在のリスト名: .current-filter-name
 *  - 行アクション(ホバーで表示): span.js-popover-container →
 *    .popover li.editFilter / li.deleteFilter / li.duplicateFilter / li.toggleDefault
 *
 * 並行実行(DB 共有)でも安全なよう、専用の使い捨てリストを作って操作・後始末する。
 *
 * 【知見】作成/複製モーダルの保存(button.saveButton)の AJAX ハンドラは、EditAjax の
 * モーダル読込後に非同期で登録される。登録前にクリックするとネイティブ GET 送信
 * (action=Save への生遷移)になり保存が確定しない。そのため保存前に待機を入れる。
 * また保存直後の networkidle は保存前の idle で即解決するため、view=List&viewname=NN
 * への遷移完了を待ってから次へ進む。
 */

/** サイドバーの、指定名を含むリスト項目(複数可)。 */
function itemsBy(page: Page, name: string): Locator {
  return page
    .locator("#module-filters li.listViewFilter")
    .filter({ hasText: name });
}

/** 開いている作成/複製モーダルに名前を入れて保存する(AJAX 確定まで待つ)。 */
async function saveModalAs(
  page: Page,
  modal: Locator,
  name: string
): Promise<void> {
  await modal.locator('input[name="viewname"]').fill(name);
  // AJAX 保存ハンドラの登録待ち(登録前クリックはネイティブ GET になり保存されない)
  await page.waitForTimeout(2500);
  await Promise.all([
    page
      .waitForURL(/view=List&viewname=\d+/, { timeout: 20000 })
      .catch(() => {}),
    modal.locator("button.saveButton").first().click(),
  ]);
  await page.waitForLoadState("networkidle").catch(() => {});
}

/** 作成確認/削除確認: 一覧を開き直して反映されるまで待つ(反映ラグ対策)。 */
async function expectInSidebar(
  page: Page,
  name: string,
  present: boolean
): Promise<void> {
  await expect(async () => {
    await gotoList(page, "Accounts");
    if (present) {
      await expect(itemsBy(page, name).first()).toBeVisible({ timeout: 3000 });
    } else {
      await expect(itemsBy(page, name)).toHaveCount(0, { timeout: 3000 });
    }
  }).toPass({ timeout: 30000 });
}

/** 新規個人リストを作成する。 */
async function createFilter(page: Page, name: string): Promise<void> {
  await page.locator("#createFilter").click();
  const modal = page.locator(".modal-content:visible").first();
  await expect(modal).toBeVisible({ timeout: 15000 });
  await expect(
    modal.locator('h4:has-text("新しいリストを作成")')
  ).toBeVisible();
  await saveModalAs(page, modal, name);
  await expectInSidebar(page, name, true);
}

/** 行アクションのポップオーバーから、指定リストを削除する。 */
async function deleteFilter(page: Page, name: string): Promise<void> {
  const item = itemsBy(page, name).first();
  await item.hover();
  await item.locator("span.js-popover-container").first().click();
  const del = page.locator(".popover li.deleteFilter").first();
  await expect(del).toBeVisible({ timeout: 8000 });
  await Promise.all([
    page
      .waitForResponse((r) => /action=Delete/.test(r.url()), { timeout: 15000 })
      .catch(() => null),
    (async () => {
      await del.click();
      await confirmYes(page);
    })(),
  ]);
  await page.waitForLoadState("networkidle").catch(() => {});
  await expectInSidebar(page, name, false);
}

test.describe("共通: リスト(CustomView)", () => {
  test("個人リストを作成して削除できる", async ({ page }) => {
    test.setTimeout(90000);
    await gotoList(page, "Accounts");
    const name = `E2Ecv${generateRandomString(6)}`;
    await createFilter(page, name);
    await deleteFilter(page, name);
  });

  test("リストを切り替えられる", async ({ page }) => {
    test.setTimeout(90000);
    await gotoList(page, "Accounts");
    const name = `E2Ecvsw${generateRandomString(6)}`;
    await createFilter(page, name);

    // 標準リスト「すべて」へ切替 → 現在のリスト名が変わる
    await page
      .locator("#module-filters a.filterName")
      .filter({ hasText: "すべて" })
      .first()
      .click();
    await page.waitForLoadState("networkidle");
    await expect(page.locator(".current-filter-name")).toContainText("すべて");

    // 作成したリストへ戻す
    await itemsBy(page, name).first().locator("a.filterName").click();
    await page.waitForLoadState("networkidle");
    await expect(page.locator(".current-filter-name")).toContainText(name);

    await deleteFilter(page, name);
  });

  test("リストを複製できる", async ({ page }) => {
    test.setTimeout(120000);
    await gotoList(page, "Accounts");
    const src = `E2Ecvsrc${generateRandomString(6)}`;
    const dup = `E2Ecvdup${generateRandomString(6)}`;
    await createFilter(page, src);

    // 行アクション「複製」→ 元名がプリセットされた作成モーダルが開く
    const item = itemsBy(page, src).first();
    await item.hover();
    await item.locator("span.js-popover-container").first().click();
    const dupItem = page.locator(".popover li.duplicateFilter").first();
    await expect(dupItem).toBeVisible({ timeout: 8000 });
    await dupItem.click();

    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 15000 });
    await expect(modal.locator('input[name="viewname"]')).toHaveValue(src);
    await saveModalAs(page, modal, dup);
    await expectInSidebar(page, dup, true);

    // 後始末: 複製元・複製先の両方を削除
    await deleteFilter(page, dup);
    await deleteFilter(page, src);
  });
});
