import { expect, Page, Locator } from "@playwright/test";
import { url } from "./util";

/**
 * 一覧(List)画面まわりの共通ヘルパ。
 *
 * P1 の横断機能テスト(フォロー/タグ/検索/一括削除/エクスポート)で共有する定型を集約する。
 * セレクタは layouts/v7 の ListViewContents.tpl / ListViewActions.tpl /
 * ListViewRecordActions.tpl と実画面 DOM から確定したものを使う。
 */

/**
 * モジュール一覧の「すべて(All)」CustomView の cvid。
 *
 * 並列実行では全ワーカーが同一 admin でログインするため、CustomView 作成系テスト
 * (common.customview* が新規フィルタ CV を作り view=List&viewname=NN へ遷移)が
 * admin の「現在のリスト」をフィルタ付き CV に切り替えると、viewname 無指定の一覧が
 * そのフィルタ CV を表示し、島テストの列検索が 0 件になる(workers を上げると多発)。
 * 一覧を All に固定してこの汚染を防ぐ。値は拡充ベースライン dump の All CV(cvid)。
 */
const MODULE_ALL_VIEW: Record<string, string> = { Accounts: "4" };

/** モジュールの一覧画面へ遷移する(既定アプリは MARKETING)。既知モジュールは All CV に固定。 */
export async function gotoList(
  page: Page,
  moduleName: string,
  app = "MARKETING",
  viewname?: string
): Promise<void> {
  const cv = viewname ?? MODULE_ALL_VIEW[moduleName];
  const vn = cv ? `&viewname=${cv}` : "";
  await page.goto(
    url(`index.php?module=${moduleName}&view=List&app=${app}${vn}`)
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

/**
 * 一覧の列検索を実行する。検索実行ボタンは「検索条件が既にある」と hide に
 * なる(ListViewContents.tpl)ため、入力欄で Enter を押して確実に発火させる。
 */
export async function listSearch(
  page: Page,
  field: string,
  value: string
): Promise<void> {
  const input = page.locator(
    `input.listSearchContributor[name="${field}"]`
  );
  await input.fill(value);
  await input.press("Enter");
  await page.waitForLoadState("networkidle");
}

/**
 * 列検索して結果件数が期待値になることを確認する（高負荷での反映ラグに強い）。
 *
 * `listSearch` は networkidle 待ちだが、最大並列/外部リソース待ちの環境では検索の
 * 反映がずれて件数 assert がレースすることがある。検索→件数確認を toPass でリトライし、
 * 期待件数に収束するまで再検索する。件数系の島テスト（権限/検索/共有ルール/タグ）で使う。
 */
export async function expectSearchCount(
  page: Page,
  field: string,
  value: string,
  count: number
): Promise<void> {
  await expect(async () => {
    await listSearch(page, field, value);
    await expect(listRows(page)).toHaveCount(count, { timeout: 3000 });
  }).toPass({ timeout: 25000 });
}

/** 一覧の検索条件をクリアする(セッションに残るため後始末に使う)。 */
export async function clearListSearch(page: Page): Promise<void> {
  await page
    .locator('[data-trigger="clearListSearch"]')
    .first()
    .click()
    .catch(() => {});
  await page.waitForLoadState("networkidle").catch(() => {});
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

/**
 * 使い捨ての顧客企業を作成し record ID を返す。
 * 並行実行では DB が共有されるため、状態を伴うテストは「先頭の任意レコード」に
 * 依存せず、自分専用のレコードを作って操作・後始末するのが安全。
 */
export async function createAccount(
  page: Page,
  name: string
): Promise<string> {
  await page.goto(url("index.php?module=Accounts&view=Edit&app=MARKETING"));
  await page.waitForLoadState("domcontentloaded");
  await page.fill('input[name="accountname"]', name);
  await page.locator("button.saveButton").first().click();
  await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
  const id = page.url().match(/record=(\d+)/)?.[1];
  if (!id) {
    throw new Error(`顧客企業の作成に失敗しました: ${name}`);
  }
  return id;
}

/**
 * 詳細画面の「その他」→「削除」でレコードを削除する(後始末用)。
 * 確認ダイアログの Yes まで押す。失敗しても後始末なので握りつぶす。
 */
export async function deleteViaDetail(
  page: Page,
  moduleName: string,
  recordId: string
): Promise<void> {
  try {
    await gotoDetail(page, moduleName, recordId);
    await page.click("text=その他");
    await page.click("text=削除");
    await page
      .locator('.modal-content >> text=Yes, .confirm-box-ok')
      .first()
      .click({ timeout: 6000 });
    await page.waitForLoadState("networkidle");
  } catch {
    /* 後始末のため失敗は無視 */
  }
}
