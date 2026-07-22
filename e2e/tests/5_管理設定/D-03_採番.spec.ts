import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * D-03 レコード番号の設定 (モジュール管理 > レコード番号の設定)
 *
 * 設定編集型。対象モジュールの接頭子(prefix)を編集し、AJAX 保存後にリロードして
 * 値が永続化されていることを検証する。番号採番はモジュール横断の永続設定のため、
 * 元の接頭子を退避 → テスト値へ変更 → 検証 → 原状復帰し、後続へ影響を残さない。
 *
 * 画面は #EditView フォーム(モーダルではない)。保存ボタン(button.saveButton)は
 * 初期状態 disabled で、prefix/sequenceNumber/sourceModule の change イベントで
 * 有効化される。fill 後に blur を発火させてから保存する。
 * 保存は CustomRecordNumberingAjax への POST で画面遷移しないため saveAndSettle を使う。
 *
 * なお JS 側のガード: 接頭子が未変更のまま採番番号を小さくすると弾かれるが、
 * 本テストは接頭子のみ変更し採番番号は据え置くため、このガードには掛からない。
 */
test.describe("管理: レコード番号の設定 (CustomRecordNumbering)", () => {
  const params = { module: "Vtiger", view: "CustomRecordNumbering" };

  // 対象モジュール(sourceModule select の既定選択)の接頭子入力
  const prefixInput = (page: Page) =>
    page.locator('#EditView input[name="prefix"]');

  test("接頭子を編集して保存が永続化され、元に戻せる", async ({ page }) => {
    const testPrefix = `E2E${generateRandomString(5)}`;

    // 設定画面を開き、現在の対象モジュール名と元の接頭子を退避する
    await gotoSettings(page, params);
    const input = prefixInput(page);
    await expect(input).toBeVisible();
    const sourceModule = await page
      .locator('#EditView select[name="sourceModule"]')
      .inputValue();
    const originalPrefix = await input.inputValue();

    // テスト値に更新 → change を発火させて保存ボタンを有効化 → AJAX 保存
    await input.fill(testPrefix);
    await input.blur();
    const saveButton = page.locator("#EditView button.saveButton");
    await expect(saveButton).toBeEnabled();
    await saveAndSettle(page, saveButton);

    // リロードして同一モジュールの接頭子が更新後の値で永続化されていること
    await gotoSettings(page, params);
    await expect(
      page.locator('#EditView select[name="sourceModule"]')
    ).toHaveValue(sourceModule);
    await expect(prefixInput(page)).toHaveValue(testPrefix);

    // 原状復帰: 元の接頭子に戻して保存
    const restoreInput = prefixInput(page);
    await restoreInput.fill(originalPrefix);
    await restoreInput.blur();
    const restoreSave = page.locator("#EditView button.saveButton");
    await expect(restoreSave).toBeEnabled();
    await saveAndSettle(page, restoreSave);

    // 元の接頭子に戻っていること
    await gotoSettings(page, params);
    await expect(page.locator('#EditView select[name="sourceModule"]')).toHaveValue(
      sourceModule
    );
    await expect(prefixInput(page)).toHaveValue(originalPrefix);
  });
});
