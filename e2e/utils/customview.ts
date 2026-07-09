import { expect, type Page, type Locator } from "@playwright/test";
import { gotoList } from "./listview";
import { confirmYes } from "./settings";

/**
 * 個人リスト(CustomView)操作の共通ヘルパ。
 *
 * common.customview.spec.ts の Accounts 固定ロジックを module 引数化して汎用化したもの。
 * マトリクステスト(list.cv.personal.*)と共通機能テストの両方から利用する。
 *
 * 主要セレクタ(実画面で確定):
 *  - 作成ボタン: #createFilter (data-url=CustomView&view=EditAjax)
 *  - 作成/複製/編集モーダル: 名前 input[name="viewname"] / 保存 button.saveButton
 *    (列は既定で選択済みのため、名前だけ入れて保存できる)
 *  - サイドバー: #module-filters li.listViewFilter a.filterName[data-filter-id]
 *  - 行アクション(ホバーで表示): span.js-popover-container →
 *    .popover li.editFilter / li.deleteFilter / li.duplicateFilter / li.toggleDefault
 *
 * 【知見】作成/複製/編集モーダルの保存(button.saveButton)の AJAX ハンドラは、EditAjax の
 * モーダル読込後に非同期で登録される。登録前にクリックするとネイティブ GET 送信
 * (action=Save への生遷移)になり保存が確定しない。そのため保存前に待機を入れる。
 * また保存直後の networkidle は保存前の idle で即解決するため、view=List&viewname=NN
 * への遷移完了を待ってから次へ進む。
 */

/** サイドバーの、指定名を含むリスト項目(複数可)。 */
export function itemsBy(page: Page, name: string): Locator {
  return page
    .locator("#module-filters li.listViewFilter")
    .filter({ hasText: name });
}

/**
 * サイドバーの隠れフィルタを展開する。
 *
 * SidebarEssentials.tpl は CustomView を先頭 10 件しか表示せず、11 件目以降は
 * `filterHidden hide`(「もっと」トグルの裏)になる。並列実行で他テストの CV が同時に
 * 増えると、作成直後の CV が 10 件を超えて隠れ、可視待ちがタイムアウトする。
 * `a.toggleFilterSize` を押して隠れ項目を可視化してから探す(隠れ項目があるグループのみ)。
 */
export async function revealHiddenFilters(page: Page): Promise<void> {
  await page
    .evaluate(() => {
      document
        .querySelectorAll("#module-filters a.toggleFilterSize")
        .forEach((t) => {
          const grp = t.closest(".list-group");
          if (grp && grp.querySelector("li.filterHidden.hide")) {
            (t as HTMLElement).click();
          }
        });
    })
    .catch(() => {});
}

/** 開いている作成/複製/編集モーダルに名前を入れて保存する(AJAX 確定まで待つ)。 */
export async function saveModalAs(
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
export async function expectFilterInSidebar(
  page: Page,
  module: string,
  name: string,
  present: boolean
): Promise<void> {
  await expect(async () => {
    await gotoList(page, module);
    if (present) {
      await revealHiddenFilters(page); // 10 件超で隠れる CV を展開してから可視確認
      await expect(itemsBy(page, name).first()).toBeVisible({ timeout: 3000 });
    } else {
      await expect(itemsBy(page, name)).toHaveCount(0, { timeout: 3000 });
    }
  }).toPass({ timeout: 30000 });
}

/** 新規個人リストを作成する。 */
export async function createPersonalFilter(
  page: Page,
  module: string,
  name: string
): Promise<void> {
  await gotoList(page, module);
  await page.locator("#createFilter").click();
  const modal = page.locator(".modal-content:visible").first();
  await expect(modal).toBeVisible({ timeout: 15000 });
  await saveModalAs(page, modal, name);
  await expectFilterInSidebar(page, module, name, true);
}

/** 行アクションのポップオーバーを開き、指定リストアクションの Locator を返す。 */
async function openRowAction(
  page: Page,
  name: string,
  li: string
): Promise<Locator> {
  await revealHiddenFilters(page); // 10 件超で隠れる場合があるので展開してから
  const item = itemsBy(page, name).first();
  await item.hover();
  await item.locator("span.js-popover-container").first().click();
  const action = page.locator(`.popover ${li}`).first();
  await expect(action).toBeVisible({ timeout: 8000 });
  return action;
}

/** 行アクションのポップオーバーから、指定リストを削除する。 */
export async function deletePersonalFilter(
  page: Page,
  module: string,
  name: string
): Promise<void> {
  const del = await openRowAction(page, name, "li.deleteFilter");
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
  await expectFilterInSidebar(page, module, name, false);
}

/** 行アクション「複製」から、指定リストを複製する。 */
export async function duplicatePersonalFilter(
  page: Page,
  module: string,
  src: string,
  dup: string
): Promise<void> {
  const dupAction = await openRowAction(page, src, "li.duplicateFilter");
  await dupAction.click();
  const modal = page.locator(".modal-content:visible").first();
  await expect(modal).toBeVisible({ timeout: 15000 });
  await expect(modal.locator('input[name="viewname"]')).toHaveValue(src);
  await saveModalAs(page, modal, dup);
  await expectFilterInSidebar(page, module, dup, true);
}

/** 行アクション「編集」から、指定リストの名前を変更する。 */
export async function editPersonalFilter(
  page: Page,
  module: string,
  name: string,
  newName: string
): Promise<void> {
  const editAction = await openRowAction(page, name, "li.editFilter");
  await editAction.click();
  const modal = page.locator(".modal-content:visible").first();
  await expect(modal).toBeVisible({ timeout: 15000 });
  await saveModalAs(page, modal, newName);
  await expectFilterInSidebar(page, module, newName, true);
}
