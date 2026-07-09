import { expect, type Page } from "@playwright/test";
import * as path from "path";
import { gotoDetail } from "./listview";

/**
 * ドキュメント関連(Documents)のファイルアップロード/ダウンロード共通ヘルパ。
 *
 * 実機確認結果(Task 6):
 * - Documents 関連タブには単純な「追加」ボタン/テキストは無い。
 *   代わりに「新しいドキュメント▼」ドロップダウン(`.relatedContainer .dropdown-toggle`)を開き、
 *   「システム内部」リンク(`#VtigerAction a`, `Documents_Index_Js.uploadTo('Vtiger',...)`)を
 *   クリックするとアップロード用モーダル(UploadDocument.tpl)が開く。
 * - Accounts 詳細の「概要」タブには Documents サマリーウィジェットが常駐しており、
 *   同じ `#VtigerAction a` / `.dropdown-toggle` 相当の要素が非表示のまま DOM に存在するため、
 *   `:visible` で現在表示中の要素に絞る必要がある(絞らないと strict mode 違反になる)。
 * - モーダル内のタイトル欄(`input[name="notes_title"]`)・ファイル欄
 *   (`input[type="file"][name="filename"]`)は、関連一覧の列検索入力や他機能の
 *   非表示モーダルテンプレートと同名のため、開いている `.modal-content:visible` に
 *   スコープしないと誤った(非表示の)要素にアクセスしてしまう。
 * - 保存ボタンは `button.saveButton`(class)ではなく `button[name="saveButton"]`
 *   (ModalFooter.tpl は name 属性でマークしており class は "btn btn-success")。
 * - ダウンロードリンクの実体は `a[name="downloadfile"]`
 *   (href は `action=DownloadFile&record=...&fileid=...&name=...`)。
 */

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
  // タブ切替後、関連コンテンツ(ドキュメント関連リスト)の読み込み完了を待つ。
  await page.waitForLoadState("networkidle");
  await expect(
    page.locator('li[data-module="Documents"].active').first()
  ).toBeVisible({ timeout: 15000 });
}

/**
 * 「新しいドキュメント▼」→「システム内部」でファイルアップロードモーダルを開く。
 *
 * モーダル HTML は `app.helper.showModal()` により同期的に DOM へ挿入されるが、
 * ファイル選択の change イベント登録(Documents_Index_Js.registerFileHandlingEvents)は
 * Bootstrap の `shown.bs.modal`(フェードイン完了後)に紐づくコールバックで非同期に行われる
 * (layouts/v7/resources/helper.js の showModal 実装を確認)。フォーム要素の出現だけを待つと
 * 上記コールバック未実行のタイミングでファイルを設定してしまい、change イベントの listener が
 * 無く選択が反映されない(必須バリデーションで保存が止まる)ことがある。
 * フェードイン所要時間ぶんの待ちを追加する。
 */
async function openUploadModal(page: Page): Promise<void> {
  await page
    .locator(".relatedContainer .dropdown-toggle:visible")
    .first()
    .click();
  await page.locator("#VtigerAction a:visible").first().click();
  await page.waitForSelector('.modal-content form[name="upload"]', {
    timeout: 15000,
  });
  // shown.bs.modal (フェードイン完了) を待って JS イベント登録の完了を確保する。
  await page.waitForTimeout(500);
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
  await openUploadModal(page);

  // 開いているモーダルに厳密にスコープする(列検索入力・他の非表示モーダルテンプレートと
  // 同名 name 属性が衝突するため)。
  const modal = page.locator(".modal-content:visible");
  await modal.locator('input[name="notes_title"]').fill(title);
  await modal
    .locator('input[type="file"][name="filename"]')
    .setInputFiles(filePath);
  // ファイル選択の change イベントハンドラ(Documents_Index_Js.registerFileChangeEvent)は
  // モーダル表示直後の AJAX コールバックで非同期に登録されるため、即座に保存を押すと
  // ハンドラ未登録でファイル未選択のまま送信され、必須バリデーションで保存が止まることがある。
  // ファイル名がプレビュー表示される(.fileDetails 更新)のを待ってから保存する。
  await expect(modal.locator(".fileDetails")).toContainText(
    path.basename(filePath),
    { timeout: 10000 }
  );
  await modal.locator('button[name="saveButton"]').click();
  await expect(modal).toBeHidden({ timeout: 15000 });

  // 保存後は AJAX で関連一覧が更新されるが、確実性のため再遷移して確認する。
  await gotoDetail(page, module, recordId, app);
  await openDocumentsTab(page);
  await expect(
    page.locator(".relatedContents").getByText(title).first()
  ).toBeVisible({ timeout: 15000 });
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
  const row = page
    .locator(".relatedContents tr")
    .filter({ hasText: title })
    .first();
  const [download] = await Promise.all([
    page.waitForEvent("download", { timeout: 15000 }),
    row.locator('a[name="downloadfile"]').first().click(),
  ]);
  const dest = path.join(
    "test-results",
    `dl-${module}-${await download.suggestedFilename()}`
  );
  await download.saveAs(dest);
  return dest;
}
