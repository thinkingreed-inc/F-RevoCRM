import { expect, type Page } from "@playwright/test";
import * as path from "path";
import { gotoDetail } from "./listview";

/**
 * ドキュメント関連(Documents)のファイルアップロード/ダウンロード共通ヘルパ。
 *
 * ドキュメントの関連リストは React の Web コンポーネント
 * (`<documents-related-list>` / assets/react-web-components) に置き換わっている。
 * 旧 UI(`.relatedContainer .dropdown-toggle` →「新しいドキュメント▼」→
 * `#VtigerAction a` → `form[name="upload"]` のモーダル)は存在しないため、
 * 新 UI のボタンとモーダルを操作する。
 *
 * 要素の特定にはコンポーネント側に付けた `data-testid` を使う。
 * React 側はインラインスタイルでクラス名を持たず、ラベルは翻訳で変わるため、
 * 見た目や文言に依存しない目印を用意している。
 *
 *   documents-related-add       … 「ドキュメントの追加」ボタン(関連リスト)
 *   documents-related-select    … 「既存のドキュメントを選択」ボタン(関連リスト)
 *   documents-related-download  … 一覧行のダウンロードリンク
 *   document-title-input        … 登録モーダルのタイトル入力
 *   document-file-input         … 登録モーダルのファイル入力(表示は隠れている)
 *   document-save-button        … 登録モーダルの保存ボタン
 */

/** 関連リストの読み込み完了を待つ */
async function openDocumentsTab(page: Page): Promise<void> {
  // 関連タブが多いモジュール(Services 等)では「ドキュメント」タブが直接の
  // li.tab-item ではなく「もっと ▼」(li.related-tab-more-element)配下の
  // li.more-tab に押し出される。まず直接タブを探し、無ければ「もっと」を開いて
  // more-tab を辿る(ModuleRelatedTabs.tpl のオーバーフロー構造に対応)。
  const directTab = page
    .locator('li.tab-item[data-module="Documents"]')
    .first();
  if (await directTab.isVisible().catch(() => false)) {
    await directTab.locator("a").first().click();
  } else {
    const more = page
      .locator("li.related-tab-more-element .dropdown-toggle")
      .first();
    await expect(more).toBeVisible({ timeout: 15000 });
    await more.click();
    await page
      .locator('li.more-tab[data-module="Documents"] a')
      .first()
      .click();
  }
  await expect(
    page.locator('li[data-module="Documents"].active').first()
  ).toBeVisible({ timeout: 15000 });

  // Web コンポーネントは関連リストの HTML 挿入後に非同期でマウントされるため、
  // 追加ボタンが出るまで待つ(出た時点で一覧の取得も終わっている)。
  await expect(relatedAddButton(page)).toBeVisible({ timeout: 30000 });
}

/** 関連リストの「ドキュメントの追加」ボタン */
function relatedAddButton(page: Page) {
  return page.locator('[data-testid="documents-related-add"]:visible').first();
}

/** 開いているモーダル(登録・編集) */
function openModal(page: Page) {
  return page.locator('[data-testid="document-title-input"]:visible').first();
}

/** レコードのドキュメント関連リストへファイルをアップロードし、一覧に出ることを確認。 */
export async function uploadDocumentToRecord(
  page: Page,
  module: string,
  recordId: string,
  filePath: string,
  title: string,
  app = "MARKETING"
): Promise<void> {
  await gotoDetail(page, module, recordId, app);
  await openDocumentsTab(page);

  await relatedAddButton(page).click();
  const titleInput = openModal(page);
  await expect(titleInput).toBeVisible({ timeout: 15000 });
  await titleInput.fill(title);

  // ファイル入力は display:none のためクリック不可。setInputFiles は
  // 非表示要素にも設定できるので、そのままファイルを渡す。
  await page
    .locator('[data-testid="document-file-input"]')
    .first()
    .setInputFiles(filePath);
  // 選択したファイル名が表示されてから保存する(未選択のまま保存すると弾かれる)。
  await expect(page.getByText(path.basename(filePath)).first()).toBeVisible({
    timeout: 10000,
  });

  await page.locator('[data-testid="document-save-button"]:visible').click();
  await expect(titleInput).toBeHidden({ timeout: 30000 });

  // 保存後は関連一覧が再取得されるが、確実性のため再遷移して確認する。
  await gotoDetail(page, module, recordId, app);
  await openDocumentsTab(page);
  await expect(page.getByText(title).first()).toBeVisible({ timeout: 15000 });
}

/** レコードのドキュメント関連一覧からファイルをダウンロードし、保存先パスを返す。 */
export async function downloadDocumentFromRecord(
  page: Page,
  module: string,
  recordId: string,
  title: string,
  app = "MARKETING"
): Promise<string> {
  await gotoDetail(page, module, recordId, app);
  await openDocumentsTab(page);

  const row = page.locator("tr").filter({ hasText: title }).first();
  const [download] = await Promise.all([
    page.waitForEvent("download", { timeout: 15000 }),
    row.locator('[data-testid="documents-related-download"]').first().click(),
  ]);
  const dest = path.join(
    "test-results",
    `dl-${module}-${await download.suggestedFilename()}`
  );
  await download.saveAs(dest);
  return dest;
}
