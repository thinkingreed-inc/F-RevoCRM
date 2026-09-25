import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * F-06 メニュー設定 (システム構成 > メニュー設定 / MenuEditor)
 *
 * グローバルナビ(全体設定)を書き換える画面のため、原状復帰が必須。
 * ドラッグ&ドロップの並び替えはフレーキーなので避け、
 * 「アプリ内モジュールの非表示(削除) → 反映確認 → 再表示(追加)で復帰」
 * のサイクルで検証する。
 *
 * 仕組み(modules/Settings/MenuEditor/actions/SaveAjax.php):
 *   - removeModule: vtiger_app2tab.visible = 0 (アプリ内メニューから外す)
 *   - addModule   : vtiger_app2tab.visible = 1 (元に戻す=removeの逆操作)
 * よって remove→add は可逆で、テスト後に元の表示状態へ戻せる。
 *
 * 主なセレクタ(layouts/v7/modules/Settings/MenuEditor/Index.tpl, AddModule.tpl):
 *   - アプリ枠:   .appContainer[data-appname="APP"]
 *   - モジュール: .modules[data-module="MODULE"] (アプリ枠内)
 *   - 削除アイコン: .menuEditorRemoveItem (.modules 内)
 *   - 追加トリガ: .menuEditorAddItem[data-appname="APP"]
 *   - 追加モーダル: .addModuleContainer 内 .addModule[data-module="MODULE"] と submit ボタン
 */
test.describe.serial("管理: メニュー設定 (MenuEditor)", () => {
  const params = { module: "MenuEditor", view: "Index" };

  // テスト対象(remove→add する)アプリ名・モジュール名。beforeAll で DOM から採取する。
  let appName = "";
  let moduleName = "";

  const moduleItem = (page: Page, app: string, mod: string) =>
    page.locator(`.appContainer[data-appname="${app}"] .modules[data-module="${mod}"]`);

  test.beforeAll(async ({ browser }) => {
    // 表示中のモジュールを 1 件、DOM から採取して対象を決める(環境非依存)。
    const page = await browser.newPage();
    try {
      await gotoSettings(page, params);
      const firstModule = page.locator(".appContainer .modules[data-module]").first();
      await expect(firstModule).toBeVisible();
      moduleName = (await firstModule.getAttribute("data-module")) ?? "";
      appName =
        (await firstModule
          .locator("xpath=ancestor::div[contains(@class,'appContainer')]")
          .getAttribute("data-appname")) ?? "";
      expect(moduleName).not.toBe("");
      expect(appName).not.toBe("");
    } finally {
      await page.close();
    }
  });

  test("アプリのメニューからモジュールを非表示にできる(削除)", async ({ page }) => {
    await gotoSettings(page, params);

    const target = moduleItem(page, appName, moduleName);
    await expect(target).toBeVisible();

    // 削除アイコン押下 = removeModule(AJAX)。POST 完了・networkidle を待つ。
    await saveAndSettle(page, target.locator(".menuEditorRemoveItem"));

    // リロード後、対象モジュールがそのアプリ枠から消えていること(永続化の確認)。
    await gotoSettings(page, params);
    await expect(moduleItem(page, appName, moduleName)).toHaveCount(0);
  });

  test("非表示にしたモジュールを再表示して原状復帰する(追加)", async ({ page }) => {
    await gotoSettings(page, params);

    // 対象アプリの「非表示モジュールを選択」を開く(AddModule モーダル)。
    await page
      .locator(`.menuEditorAddItem[data-appname="${appName}"]`)
      .click();

    // モーダル内に限定してモジュールボタンを選択(トグルで .selectedModule 付与)。
    const modal = page.locator(".modal-content:visible");
    await expect(modal).toBeVisible();
    const addButton = modal.locator(`.addModule[data-module="${moduleName}"]`);
    await expect(addButton).toBeVisible();
    await addButton.click();
    await expect(addButton).toHaveClass(/selectedModule/);

    // 保存(addModule AJAX)。成功後 window.location.reload() される。
    await saveAndSettle(page, modal.locator('button[type="submit"]'));

    // リロード後、対象モジュールがアプリ枠に戻っていること(復帰の確認)。
    await gotoSettings(page, params);
    await expect(moduleItem(page, appName, moduleName)).toBeVisible();
  });
});
