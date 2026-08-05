import { test, expect, type Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * H-02 諸条件の編集 (Settings > 販売管理 > 諸条件)
 *
 * 全体設定に相当する諸条件テキスト(TermsAndConditions)を編集する型。
 * 諸条件は在庫モジュール共通のグローバル設定のため、元のテキストを退避 →
 * テスト値へ変更 → リロードして永続化を検証 → 原状復帰し、後続テストへ影響を残さない。
 *
 * 画面はモーダルではなく Settings ページ内にインラインで表示される全画面ビュー。
 * 保存は画面遷移しない AJAX(action=TermsAndConditionsAjax, mode=save)のため
 * saveAndSettle で完了を待つ。保存ボタン(.saveTC)は textarea の keyup で表示される
 * 仕様のため、fill 後に keyup を発火させてボタンを可視化する。
 */
test.describe("管理: 諸条件の編集 (TermsAndConditionsEdit)", () => {
  const editParams = { module: "Vtiger", view: "TermsAndConditionsEdit" };

  /**
   * 諸条件入力欄の Locator を返す。テンプレート上のクラス `TCContent form-control`。
   */
  const termsTextarea = (page: Page) =>
    page.locator("textarea.TCContent.form-control");

  test("諸条件テキストを編集して永続化され、元に戻せる", async ({ page }) => {
    const testTerms = `E2Eテスト諸条件 ${generateRandomString(20)}`;

    // 編集画面を開き、既定モジュールの元テキストを退避する
    await gotoSettings(page, editParams);
    const textarea = termsTextarea(page);
    await expect(textarea).toBeVisible();
    const originalTerms = await textarea.inputValue();

    // テスト値へ変更し、keyup で保存ボタンを可視化してから保存する
    await textarea.fill(testTerms);
    await textarea.dispatchEvent("keyup");
    const saveButton = page.locator("button.saveButton.saveTC");
    await expect(saveButton).toBeVisible();
    await saveAndSettle(page, saveButton);

    // リロードしてテスト値が永続化されていること
    await gotoSettings(page, editParams);
    await expect(termsTextarea(page)).toHaveValue(testTerms);

    // 原状復帰: 元の諸条件テキストに戻す
    const restoreTextarea = termsTextarea(page);
    await expect(restoreTextarea).toBeVisible();
    await restoreTextarea.fill(originalTerms);
    await restoreTextarea.dispatchEvent("keyup");
    const restoreButton = page.locator("button.saveButton.saveTC");
    await expect(restoreButton).toBeVisible();
    await saveAndSettle(page, restoreButton);

    // 元の諸条件テキストに戻っていること
    await gotoSettings(page, editParams);
    await expect(termsTextarea(page)).toHaveValue(originalTerms);
  });
});
