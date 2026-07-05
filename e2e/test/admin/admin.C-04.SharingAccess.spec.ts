import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings } from "../../utils/settings";

/**
 * C-04 共有ルール (ユーザー管理 > 共有ルール)
 *
 * 組織全体の共有ルール(全体設定)を変更するため、対象モジュール(発注)の元の権限を
 * 退避 → 別の値に変更して反映を検証 → 元に戻す、と原状復帰する。
 *
 * ※スキップ理由: 共有ルールの保存は全レコードの共有権限の再計算を伴う重い全体操作で、
 *   保存反映が遅い環境(CI)では原状復帰の確定前に検証がレースし、さらに直列実行の
 *   後続テスト(標準モジュールCRUD等)へ影響(レコード操作のタイムアウト)を波及させる。
 *   共有された1回のテストランで安全に検証しづらいため、本パイロットの対象外とする。
 */
test.describe.skip("管理: 共有ルール (SharingAccess)", () => {
  const params = { module: "SharingAccess", view: "Index" };

  // 権限ラジオを持つ行のうち「発注」を含むもの
  const targetRow = (page: Page) =>
    page
      .locator('tr:has(input[type="radio"])')
      .filter({ hasText: "発注" })
      .first();

  test("発注の共有ルールを変更して反映され、元に戻せる", async ({ page }) => {
    await gotoSettings(page, params);

    const original = await targetRow(page)
      .locator('input[type="radio"]:checked')
      .getAttribute("value");
    expect(original).not.toBeNull();

    // 元と異なる値を選ぶ(非公開=3 と 公開/読み取り専用=0 を入れ替え)
    const changed = original === "3" ? "0" : "3";

    await targetRow(page)
      .locator(`input[type="radio"][value="${changed}"]`)
      .check();
    await page.locator('button.saveButton[name="saveButton"]').click();

    // 変更が反映されていること
    await gotoSettings(page, params);
    await expect(
      targetRow(page).locator('input[type="radio"]:checked')
    ).toHaveValue(changed);

    // 原状復帰
    await targetRow(page)
      .locator(`input[type="radio"][value="${original}"]`)
      .check();
    await page.locator('button.saveButton[name="saveButton"]').click();

    await gotoSettings(page, params);
    await expect(
      targetRow(page).locator('input[type="radio"]:checked')
    ).toHaveValue(original as string);
  });
});
