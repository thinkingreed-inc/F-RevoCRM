import { test, expect, type Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * F-05 構成エディタ (System > 構成エディタ / ConfigEditor)
 *
 * 設定編集型。構成エディタはアプリ全体に効くグローバル設定を編集するため、
 * ログイン・権限・セッションに影響しない低リスクな項目のみを対象にする。
 *
 * 対象項目: `list_max_entries_per_page`(一覧の 1 ページあたり表示件数)。
 * 表示件数は各モジュールの一覧表示に効くだけで、後続テストの動作や認証には
 * 影響しないため安全。値域は 1〜100(テンプレートの data-rule-range 参照)。
 *
 * 手順: 元の値を退避 → 安全な値へ変更 → リロードして反映を検証 →
 * 元の値へ厳密に復帰する。
 *
 * なお編集・保存はページ遷移せず pjax + AJAX で `#ConfigEditorDetails` を
 * 差し替える方式のため、保存は saveAndSettle(POST 完了待ち)で行う。
 */
test.describe("管理: 構成エディタ (ConfigEditor)", () => {
  const detailParams = { module: "Vtiger", view: "ConfigEditorDetail" };
  const FIELD = "list_max_entries_per_page";

  /** 詳細画面で編集ボタンを押し、インラインの編集フォームを開く。 */
  async function openEditForm(page: Page): Promise<void> {
    await gotoSettings(page, detailParams);
    await page.locator("#ConfigEditorDetails .editButton").click();
    // 編集フォームは pjax で #ConfigEditorDetails 内に差し替わる
    await expect(page.locator(`#ConfigEditorForm input[name="${FIELD}"]`)).toBeVisible();
  }

  test("一覧の表示件数を編集して反映され、元に戻せる", async ({ page }) => {
    // 元の値を退避する
    await openEditForm(page);
    const input = page.locator(`#ConfigEditorForm input[name="${FIELD}"]`);
    const originalValue = (await input.inputValue()).trim();

    // 元の値と衝突しない安全な値(値域 1〜100 の範囲内)を作る。
    // generateRandomString の英数字から数値を導き、20〜79 に収める。
    const seed = generateRandomString(8)
      .split("")
      .reduce((acc, c) => acc + c.charCodeAt(0), 0);
    let candidate = 20 + (seed % 60); // 20〜79
    if (String(candidate) === originalValue) {
      candidate = candidate === 79 ? 20 : candidate + 1;
    }
    const testValue = String(candidate);

    // テスト値へ変更して AJAX 保存する(保存後は詳細へ pjax で戻る)
    await input.fill(testValue);
    await saveAndSettle(page, page.locator("#ConfigEditorForm button.saveButton"));

    // リロードして永続化されていることを検証する
    await gotoSettings(page, detailParams);
    await expect(page.locator("#ConfigEditorDetails")).toContainText(testValue);
    // 編集フォームでも値が反映されていること(確実な確認)
    await openEditForm(page);
    await expect(page.locator(`#ConfigEditorForm input[name="${FIELD}"]`)).toHaveValue(
      testValue
    );

    // 原状復帰: 元の値へ厳密に戻す
    await page.locator(`#ConfigEditorForm input[name="${FIELD}"]`).fill(originalValue);
    await saveAndSettle(page, page.locator("#ConfigEditorForm button.saveButton"));

    // 元の値へ戻っていること
    await openEditForm(page);
    await expect(page.locator(`#ConfigEditorForm input[name="${FIELD}"]`)).toHaveValue(
      originalValue
    );
  });
});
