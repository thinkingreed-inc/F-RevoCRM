import { test, expect, type Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * I-02 カレンダー設定 (Calendar Settings)
 *
 * 個人設定 > カレンダー設定 (module=Users&view=Calendar&parent=Settings&record=1) を検証する。
 *
 * 注意: このテストはログイン中の管理者「自身」のカレンダー設定を編集する。共有セッションが
 * 参照する設定であるため、セッションに影響しうる項目は避け、無害かつ値が固定されている
 * 「カレンダー共有 (sharedtype)」を対象とする。sharedtype の選択肢は
 * private(非公開) / public(公開) / selectedusers(選択されたユーザー) と非ローカライズ値で安定しており、
 * 詳細画面には翻訳済みラベルが表示されるため反映確認がしやすい。
 *
 * 元の値を退避 → 安全な値へ変更 → リロード後に反映を確認 → 原状復帰する。
 */
test.describe("管理: カレンダー設定 (CalendarSettings)", () => {
  // temp URL は parent=Settings を含むため gotoSettings を利用する。
  const detailParams = { module: "Users", view: "Calendar", record: "1" };
  // 詳細画面で共有タイプが表示されるセル。
  const detailCell = "#Users_detailView_fieldValue_calendarsharedtype span.value";
  // 共有タイプの選択肢(value → 詳細画面に表示される翻訳ラベル)。
  const typeLabel: Record<string, string> = {
    private: "非公開",
    public: "公開",
    selectedusers: "選択されたユーザー",
  };

  /**
   * 編集画面を開き、共有タイプ select(#sharedType)を選択して保存する。
   * #sharedType は select2 で装飾されたネイティブ select のため、
   * selectOption でネイティブ側を変更すれば change イベントで select2 も同期する。
   * 保存はフォーム送信 + リダイレクトのため saveAndSettle で待つ。
   */
  async function changeSharedType(page: Page, value: string): Promise<void> {
    await gotoSettings(page, { ...detailParams, mode: "Edit" });
    const select = page.locator("#sharedType");
    await expect(select).toBeAttached();
    await select.selectOption(value);
    await saveAndSettle(page, page.locator("button.saveButton"));
  }

  test("カレンダー共有タイプを編集して反映され、元に戻せる", async ({ page }) => {
    // 詳細を開き、現在の共有タイプ(ラベル)を退避する
    await gotoSettings(page, detailParams);
    const detailValue = page.locator(detailCell).first();
    await expect(detailValue).toBeVisible();
    const originalLabel = (await detailValue.innerText()).trim();

    // 現在の value を特定し、別の安全な value を選ぶ(選択ユーザー破壊を避けるため
    // 変更先は private / public のみを使う)
    const originalValue =
      Object.keys(typeLabel).find((v) => typeLabel[v] === originalLabel) ??
      "private";
    const targetValue = originalValue === "public" ? "private" : "public";

    // 対象の共有タイプへ変更して保存する
    await changeSharedType(page, targetValue);

    // リロード後、詳細に変更後のラベルが反映されていること
    await gotoSettings(page, detailParams);
    await expect(page.locator(detailCell).first()).toHaveText(
      typeLabel[targetValue]
    );

    // 原状復帰: 元の共有タイプ(private/public)に戻す
    // 元が selectedusers の場合は個々の選択ユーザーを復元できないため、
    // 破壊を避けて既定の private に寄せる(後続テストへの影響を残さない)。
    const restoreValue = originalValue === "selectedusers" ? "private" : originalValue;
    await changeSharedType(page, restoreValue);

    // リロード後、共有タイプが復帰していること
    await gotoSettings(page, detailParams);
    await expect(page.locator(detailCell).first()).toHaveText(
      typeLabel[restoreValue]
    );
  });
});
