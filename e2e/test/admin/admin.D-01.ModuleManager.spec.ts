import { test, expect, type Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * D-01 モジュール管理 (モジュール管理 > モジュール管理)
 *
 * モジュールの有効/無効を切り替える設定画面。切り替えはチェックボックス
 * (input[name="moduleStatus"][data-module=...]) の click で AJAX 保存され、
 * 画面遷移はしない。保存後にリロードしても checked 状態が永続することを検証する。
 *
 * これは全体設定に相当するグローバルな変更のため、
 *   元の状態を退避 → 反転 → 反映を検証 → 原状復帰
 * の順で行い、後続テストへ影響を残さない。
 *
 * 対象モジュールはコア/他テスト依存を避け、低リスクな「Portal」を使う
 * (Accounts/Contacts/Users 等は選ばない)。data-module は内部モジュール名で
 * ラベル訳語に依存しないため安定したセレクタになる。
 */
test.describe.serial("管理: モジュール管理 (ModuleManager)", () => {
  const params = { module: "ModuleManager", view: "List" };
  const TARGET_MODULE = "Portal";

  // 対象モジュールの有効/無効トグル(チェックボックス)
  const toggle = (page: Page) =>
    page.locator(`input[name="moduleStatus"][data-module="${TARGET_MODULE}"]`);

  test("モジュールの有効/無効を切り替えて永続し、元に戻せる", async ({
    page,
  }) => {
    await gotoSettings(page, params);

    // 対象トグルが表示されていること(非表示/制限モジュールでない)
    await expect(toggle(page)).toBeVisible();

    // 元の状態を退避する
    const originalChecked = await toggle(page).isChecked();

    // 状態を反転して AJAX 保存する(遷移しないため saveAndSettle で待つ)
    await saveAndSettle(page, toggle(page));

    // 画面上でトグルが反転していること
    // (設定ボタン .actions は設定リンクを持つモジュールにしか描画されないため検証しない)
    await expect(toggle(page)).toBeChecked({ checked: !originalChecked });

    // リロードして反転後の状態が永続していること
    await gotoSettings(page, params);
    await expect(toggle(page)).toBeChecked({ checked: !originalChecked });

    // 原状復帰: 元の状態へ戻す
    await saveAndSettle(page, toggle(page));
    await expect(toggle(page)).toBeChecked({ checked: originalChecked });

    // リロードして元の状態に戻っていることを最終確認する
    await gotoSettings(page, params);
    await expect(toggle(page)).toBeChecked({ checked: originalChecked });
  });
});
