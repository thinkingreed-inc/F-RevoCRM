import type { Page } from "@playwright/test";
import * as path from "path";
import { test, expect } from "../../fixtures/isolated";
import { BASE_URL, generateRandomString } from "../../utils/util";

/**
 * ドキュメント(Documents)の一覧・詳細 — 専用画面
 *
 * ドキュメントは一覧・詳細ともに React の Web コンポーネントに置き換わっており、
 * vtiger 標準の一覧 DOM(`input.listSearchContributor` による列検索、行リンク、
 * インライン編集)を持たない。そのため汎用マトリクス(2-1)の対象外にしてあり、
 * 画面操作の検証はこの spec が受け持つ。
 *
 * 要素の特定はコンポーネント側の `data-testid` で行う。React 側はインラインスタイルで
 * クラス名を持たず、ラベルは翻訳で変わるため、見た目や文言に依存しない目印を使う。
 *
 *   documents-add            … 一覧の「ドキュメントの追加」ボタン
 *   documents-search-input   … 一覧の検索入力
 *   documents-row            … 一覧の行(data-record-id にドキュメントID)
 *   document-title-input     … 登録・編集モーダルのタイトル入力
 *   document-file-input      … 登録・編集モーダルのファイル入力(非表示)
 *   document-save-button     … 登録・編集モーダルの保存ボタン
 *   document-detail-edit     … 詳細モーダルの編集ボタン
 *   document-detail-delete   … 詳細モーダルの削除ボタン
 */

/** 一覧を開き、Web コンポーネントのマウント完了まで待つ */
async function gotoDocumentsList(page: Page): Promise<void> {
  await page.goto(`${BASE_URL}index.php?module=Documents&view=List`, {
    waitUntil: "domcontentloaded",
  });
  // 一覧は Web コンポーネントのマウント後に非同期で取得されるため、
  // 追加ボタンが出るまで待つ(出た時点で初回の取得は終わっている)。
  await expect(page.locator('[data-testid="documents-add"]')).toBeVisible({
    timeout: 30000,
  });
}

/** アップロードに使うファイル(リポジトリ同梱) */
const SAMPLE_FILE = path.resolve(__dirname, "../../fixtures/upload/sample.txt");

/**
 * 「ドキュメントの追加」からドキュメントを1件作る
 *
 * 既定のファイル種別は「システム内部」で、新規作成時はファイルが必須
 * (DocumentCreateEditModal の handleSave)。同梱のサンプルを添付する。
 */
async function createDocument(page: Page, title: string): Promise<void> {
  await page.locator('[data-testid="documents-add"]').click();

  const titleInput = page
    .locator('[data-testid="document-title-input"]:visible')
    .first();
  await expect(titleInput).toBeVisible({ timeout: 15000 });
  await titleInput.fill(title);

  // ファイル入力は display:none のためクリック不可。
  // setInputFiles は非表示要素にも設定できるのでそのまま渡す。
  await page
    .locator('[data-testid="document-file-input"]')
    .first()
    .setInputFiles(SAMPLE_FILE);
  // 選択したファイル名が出てから保存する(未選択のまま保存すると弾かれる)
  await expect(
    page.getByText(path.basename(SAMPLE_FILE)).first()
  ).toBeVisible({ timeout: 10000 });

  await page.locator('[data-testid="document-save-button"]:visible').click();
  await expect(titleInput).toBeHidden({ timeout: 30000 });
}

/** タイトルで一覧を絞り込む */
async function searchDocuments(page: Page, keyword: string): Promise<void> {
  const search = page.locator('[data-testid="documents-search-input"]');
  await search.fill(keyword);
  await search.press("Enter");
}

/** 指定タイトルの行 */
function rowOf(page: Page, title: string) {
  return page
    .locator('[data-testid="documents-row"]')
    .filter({ hasText: title })
    .first();
}

test.describe("ドキュメント: 一覧・詳細(専用画面)", () => {
  test("新規作成すると一覧に表示される", async ({ page }) => {
    const title = `E2Edoc${generateRandomString(6)}`;
    await gotoDocumentsList(page);

    await createDocument(page, title);

    // 保存後は一覧が再取得される
    await expect(rowOf(page, title)).toBeVisible({ timeout: 30000 });
  });

  test("検索で絞り込め、リセットで戻る", async ({ page }) => {
    const title = `E2Edoc${generateRandomString(6)}`;
    const other = `E2Eother${generateRandomString(6)}`;
    await gotoDocumentsList(page);
    await createDocument(page, title);
    await createDocument(page, other);

    await searchDocuments(page, title);
    await expect(rowOf(page, title)).toBeVisible({ timeout: 15000 });
    await expect(rowOf(page, other)).toBeHidden({ timeout: 15000 });

    // 検索語を消すと両方戻る
    await searchDocuments(page, "");
    await expect(rowOf(page, title)).toBeVisible({ timeout: 15000 });
    await expect(rowOf(page, other)).toBeVisible({ timeout: 15000 });
  });

  test("行を開くと詳細が表示される", async ({ page }) => {
    const title = `E2Edoc${generateRandomString(6)}`;
    await gotoDocumentsList(page);
    await createDocument(page, title);
    await searchDocuments(page, title);

    await rowOf(page, title).click();

    // 詳細はモーダルで開く。編集ボタンが出れば取得完了
    await expect(
      page.locator('[data-testid="document-detail-edit"]:visible').first()
    ).toBeVisible({ timeout: 20000 });
    await expect(page.getByText(title).first()).toBeVisible();
  });

  test("詳細から編集すると一覧に反映される", async ({ page }) => {
    const title = `E2Edoc${generateRandomString(6)}`;
    const renamed = `${title}x`;
    await gotoDocumentsList(page);
    await createDocument(page, title);
    await searchDocuments(page, title);
    await rowOf(page, title).click();

    await page
      .locator('[data-testid="document-detail-edit"]:visible')
      .first()
      .click();

    const titleInput = page
      .locator('[data-testid="document-title-input"]:visible')
      .first();
    await expect(titleInput).toBeVisible({ timeout: 15000 });
    await titleInput.fill(renamed);
    await page.locator('[data-testid="document-save-button"]:visible').click();
    await expect(titleInput).toBeHidden({ timeout: 30000 });

    await gotoDocumentsList(page);
    await searchDocuments(page, renamed);
    await expect(rowOf(page, renamed)).toBeVisible({ timeout: 20000 });
  });

  test("詳細から削除すると一覧から消える", async ({ page }) => {
    const title = `E2Edoc${generateRandomString(6)}`;
    await gotoDocumentsList(page);
    await createDocument(page, title);
    await searchDocuments(page, title);
    await rowOf(page, title).click();

    // 削除は window.confirm で確認する
    page.once("dialog", (dialog) => dialog.accept());
    await page
      .locator('[data-testid="document-detail-delete"]:visible')
      .first()
      .click();

    await gotoDocumentsList(page);
    await searchDocuments(page, title);
    await expect(rowOf(page, title)).toBeHidden({ timeout: 20000 });
  });
});
