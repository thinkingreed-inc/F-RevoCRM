import { expect, Page, Locator } from "@playwright/test";
import { url } from "./util";

/**
 * 一覧(List)画面まわりの共通ヘルパ。
 *
 * P1 の横断機能テスト(フォロー/タグ/検索/一括削除/エクスポート)で共有する定型を集約する。
 * セレクタは layouts/v7 の ListViewContents.tpl / ListViewActions.tpl /
 * ListViewRecordActions.tpl と実画面 DOM から確定したものを使う。
 */

/** モジュールの一覧画面へ遷移する(既定アプリは MARKETING)。 */
export async function gotoList(
  page: Page,
  moduleName: string,
  app = "MARKETING"
): Promise<void> {
  await page.goto(
    url(`index.php?module=${moduleName}&view=List&app=${app}`)
  );
  await page.waitForLoadState("networkidle");
}

/** データ行(ヘッダ・検索行を除く)の Locator。 */
export function listRows(page: Page): Locator {
  return page.locator("tr.listViewEntries");
}

/** 先頭データ行。 */
export function firstRow(page: Page): Locator {
  return listRows(page).first();
}

/**
 * 先頭行の「名前」列テキストを取得する。名前列は詳細リンク(getFullDetailViewUrl)
 * を持つアンカーなので、行内の view=Detail リンクの文言を採用する。
 */
export async function firstRowName(page: Page): Promise<string> {
  // 行内には「詳細」ドロップダウン項目(hide)と名前セルのリンクの両方が
  // view=Detail を指すため、表示中(:visible)のリンク=名前セルを採る。
  const link = firstRow(page)
    .locator('a[href*="view=Detail"]:visible')
    .first();
  await expect(link).toBeVisible();
  return (await link.textContent())?.trim() || "";
}

/** 先頭行のチェックボックスを選択する。 */
export async function selectFirstRow(page: Page): Promise<void> {
  await firstRow(page).locator("input.listViewEntriesCheckBox").check();
}

/**
 * 一括操作の「その他」ドロップダウンを開く。
 * 一括アクション(フォロー/タグ等)は既定で hide のため、選択後に開いて使う。
 */
export async function openMassMore(page: Page): Promise<void> {
  await page
    .locator(".listViewMassActions button.dropdown-toggle")
    .first()
    .click();
}

/** 先頭行の record ID(数値)を取得する。 */
export async function firstRecordId(page: Page): Promise<string> {
  const href = await firstRow(page)
    .locator('a[href*="view=Detail"]')
    .first()
    .getAttribute("href");
  const id = href?.match(/record=(\d+)/)?.[1];
  if (!id) {
    throw new Error(`先頭行から record ID を取得できませんでした: ${href}`);
  }
  return id;
}

/** 指定モジュール・レコードの詳細画面へ遷移する。 */
export async function gotoDetail(
  page: Page,
  moduleName: string,
  recordId: string,
  app = "MARKETING"
): Promise<void> {
  await page.goto(
    url(
      `index.php?module=${moduleName}&view=Detail&record=${recordId}&app=${app}`
    )
  );
  await page.waitForLoadState("networkidle");
}
