import { expect, type Page } from "@playwright/test";

/**
 * 詳細画面まわりの関連タブ(RelatedList)操作の共通ヘルパ。
 *
 * セレクタは layouts/v7/modules/Vtiger/RelatedList.tpl と、それを操作する
 * public/layouts/v7/modules/Vtiger/resources/Detail.js
 * (registerRelatedListSearch/registerEventForRelatedTabClick)を実装確認して確定した。
 */

/**
 * 関連タブ(li.tab-item[data-module=...])を開き、アクティブ化 + 関連一覧の
 * AJAX読み込み完了(検索行の表示)まで待つ。
 */
export async function openRelatedTab(
  page: Page,
  relatedModule: string
): Promise<void> {
  const tab = page
    .locator(`li.tab-item[data-module="${relatedModule}"]`)
    .first();
  await expect(tab).toBeVisible();
  await tab.locator("a").first().click();
  await expect(tab).toHaveClass(/active/, { timeout: 15000 });
  // 関連一覧の内容は AJAX で遅延読み込まれる(Detail.js loadSelectedTabContents)。
  // 検索行(searchRow)が現れるまで待ってから検索操作に入る。
  await expect(
    page.locator(".relatedContents .searchRow").first()
  ).toBeVisible({ timeout: 15000 });
}

/**
 * 関連一覧の列検索を実行する。
 *
 * 検索実行トリガは data-trigger="relatedListSearch"(RelatedList.tpl)だが、この
 * ボタンは `#detailView` フォーム内の `<button>`(type未指定 = 既定 submit)であり、
 * 実ブラウザで直接クリックするとネイティブの submit/blur 経路に乗ってしまい、
 * 入力を空にした直後のクリックで「直前の検索値」が復元されたまま送信される事例を
 * 実機で確認した(mandatory 項目の値復元系の副作用と推測)。
 * Detail.js の registerRelatedListSearch は Enter キー押下でも同ボタンを
 * `jQuery.trigger('click')`(合成イベント)するだけで、この経路では副作用が
 * 再現しないため、検索は入力欄への Enter 押下で発火させる。
 */
export async function relatedSearch(
  page: Page,
  field: string,
  value: string
): Promise<void> {
  const input = page
    .locator(`.relatedContents input.listSearchContributor[name="${field}"]`)
    .first();
  await input.fill(value);
  await input.press("Enter");
  await page.waitForLoadState("networkidle");
}

/**
 * 関連一覧の検索をリセットする。
 *
 * 一覧(List)画面(utils/listview.ts の clearListSearch)には専用のクリアボタン
 * (data-trigger="clearListSearch")があるが、関連一覧には同等のトリガが存在しない
 * (Detail.js の registerRelatedListSearch を確認済み。related には
 * clearListSearch/relatedListClearSearch のイベント登録が無い)。
 * そのため各検索入力を空にしてから Enter で再検索を発火し、無条件の状態に戻す。
 * (再検索の発火はボタン直接クリックではなく Enter を使う。理由は relatedSearch
 *  の同名コメント参照)
 */
export async function relatedSearchReset(page: Page): Promise<void> {
  const inputs = page.locator(".relatedContents input.listSearchContributor");
  const n = await inputs.count();
  for (let i = 0; i < n; i++) {
    await inputs.nth(i).fill("");
  }
  if (n > 0) {
    await inputs.first().press("Enter");
    await page.waitForLoadState("networkidle");
  }

  const clearedInputs = page.locator(
    ".relatedContents input.listSearchContributor"
  );
  const clearedCount = await clearedInputs.count();
  for (let i = 0; i < clearedCount; i++) {
    await expect(clearedInputs.nth(i)).toHaveValue("", { timeout: 5000 });
  }
}

/**
 * 関連一覧の先頭行の詳細リンクへ遷移し、遷移先 record ID を返す。
 * リンクは RelatedList.tpl の `<a href="{$RELATED_RECORD->getDetailViewUrl()}">`
 * (Vtiger_Record_Model::getDetailViewUrl が view=Detail&record=<id> を含む)。
 */
export async function navigateToRelatedDetail(page: Page): Promise<string> {
  const link = page
    .locator('.relatedContents a[href*="view=Detail"]:visible')
    .first();
  await expect(link).toBeVisible();
  const href = await link.getAttribute("href");
  const id = href?.match(/record=(\d+)/)?.[1] ?? "";
  await link.click();
  await expect(page).toHaveURL(/view=Detail/);
  return id;
}
