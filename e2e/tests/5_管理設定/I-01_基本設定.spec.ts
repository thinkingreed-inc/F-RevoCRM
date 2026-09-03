import { test, expect } from "../../fixtures/isolated";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * I-01 個人設定 (My Preferences / PreferenceDetail)
 *
 * 個人設定の詳細/編集画面(module=Users&view=PreferenceDetail&parent=Settings)を検証する。
 *
 * 注意: このテストはログイン中の管理者「自身」の個人設定を編集する。共有セッションが
 * 参照する設定であるため、ロケール/日付書式/パスワード/アクセスキー等の
 * セッションに影響しうる項目は絶対に触らない。ここでは無害な自由入力項目である
 * 「FAX (phone_fax)」を対象とし、元の値を退避 → 変更 → 反映確認 → 原状復帰する。
 */
test.describe("管理: 個人設定 (PreferenceDetail)", () => {
  // temp URL は parent=Settings を含むため gotoSettings を利用する。
  const detailParams = { module: "Users", view: "PreferenceDetail", record: "1" };
  // 対象は無害な自由入力項目(FAX)。セッションに影響する項目は使わない。
  const fieldName = "phone_fax";

  test("FAX を編集して反映され、元に戻せる", async ({ page }) => {
    const testValue = `03-0000-${generateRandomString(4)}`;

    // 個人設定の詳細を開き、編集画面へ遷移する
    await gotoSettings(page, detailParams);
    await page.getByRole("button", { name: "編集", exact: true }).first().click();

    // 編集画面で元の FAX 値を退避する
    const faxInput = page.locator(`input[name="${fieldName}"]`);
    await expect(faxInput).toBeVisible();
    const originalValue = await faxInput.inputValue();

    // テスト値に更新して保存する(保存はフォーム送信で画面遷移する)
    await faxInput.fill(testValue);
    await saveAndSettle(page, page.locator("button.saveButton"));

    // 詳細に更新後の FAX が反映されていること
    await gotoSettings(page, detailParams);
    const faxDetailValue = page
      .locator(`#Users_detailView_fieldValue_${fieldName}`)
      .locator("span.value");
    await expect(faxDetailValue).toHaveText(testValue);

    // 原状復帰: 元の FAX 値に戻す
    await page.getByRole("button", { name: "編集", exact: true }).first().click();
    await expect(faxInput).toBeVisible();
    await faxInput.fill(originalValue);
    await saveAndSettle(page, page.locator("button.saveButton"));

    // 元の値に戻っていること(元が空なら表示テキストは空になる)
    await gotoSettings(page, detailParams);
    await expect(faxDetailValue).toHaveText(originalValue);
  });
});
