import { test, expect } from "../../fixtures/isolated";
import type { Locator, Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { BASE_URL } from "../../utils/util";

/**
 * D-01 モジュール管理 (ModuleManager)
 *
 * モジュールの有効/無効を切り替えると、
 *   - 一覧のトグル状態が永続する
 *   - アプリメニューのリンクが 出る / 消える
 * ことを検証する。トグルは訳語に依存しない
 * `input[name="moduleStatus"][data-module="<内部名>"]` で特定する。
 *
 * 【対象は 1 モジュールに絞る (2026-08-28)】
 * 以前は `tests/7_モジュール管理/` で 38 モジュールを総当たりしていたが、
 * 検証しているのは「ModuleManager のトグル → presence 更新 → メニュー再生成」という
 * **モジュール非依存の共通経路**であり、モジュールごとに繰り返す価値が薄い
 * (実行コストは 2 段目の直列実行で 1.7 分)。代表 1 モジュール(顧客企業)に集約した。
 * メニューの描画場所がモジュールによって違う点(appmenu / トップバー / その他メニュー)は
 * 各モジュールの spec が一覧を開けることで担保される。
 *
 * 【このテストは 1 段目(並列)では走らせない】
 * 顧客企業を一時的に無効化するため、並列実行中の他 spec を巻き込む。
 * CI では `CI_SERIAL_SPECS` に入れて workers=1 の 2 段目で実行する。
 */
test.describe.serial("管理: モジュール管理 (ModuleManager)", () => {
  const params = { module: "ModuleManager", view: "List" };

  /** 代表モジュール。アプリメニュー(#app-menu)にリンクが出るもの。 */
  const TARGET = { name: "Accounts", label: "顧客企業" };

  /** 一覧の有効/無効トグル(内部名で特定、訳語非依存)。 */
  const toggle = (page: Page): Locator =>
    page.locator(`input[name="moduleStatus"][data-module="${TARGET.name}"]`);

  /** アプリメニュー内の対象モジュールへのリンク。 */
  const menuLink = (page: Page): Locator =>
    page.locator(`#app-menu a[href*="module=${TARGET.name}&"]`);

  /** 目的の有効状態にする(必要な時だけクリックして AJAX 保存)。 */
  async function setEnabled(page: Page, enabled: boolean): Promise<void> {
    await gotoSettings(page, params);
    const cb = toggle(page);
    await expect(cb, `${TARGET.name} のトグルが一覧に無い`).toBeVisible({
      timeout: 20000,
    });
    if ((await cb.isChecked()) !== enabled) {
      await saveAndSettle(page, cb);
    }
    await expect(cb).toBeChecked({ checked: enabled });
  }

  /** アプリを開き直して、メニューにリンクが在る/無いを検証する。 */
  async function expectMenu(page: Page, shouldExist: boolean): Promise<void> {
    await page.goto(`${BASE_URL}index.php?module=Home&view=DashBoard`);
    await page.waitForLoadState("domcontentloaded");
    // ヘッダ(メニュー)は本文より先に描画される。本文でエラーが出ても
    // メニュー判定は成立するようナビが DOM に付くまで待つ。
    await page
      .locator("nav.app-fixed-navbar")
      .first()
      .waitFor({ state: "attached", timeout: 20000 });

    if (shouldExist) {
      await expect
        .poll(async () => menuLink(page).count(), {
          timeout: 20000,
          message: `${TARGET.label} のメニューリンクが出ていない`,
        })
        .toBeGreaterThan(0);
    } else {
      await expect(
        menuLink(page),
        `${TARGET.label} 無効化後もメニューリンクが残っている`
      ).toHaveCount(0, { timeout: 20000 });
    }
  }

  test("モジュールを無効化するとメニューから消え、再有効化で戻る", async ({
    page,
  }) => {
    test.setTimeout(120000);
    try {
      // 1) 有効 → メニューに出る
      await setEnabled(page, true);
      await expectMenu(page, true);

      // 2) 無効化 → 一覧のトグルが永続し、メニューから消える
      await setEnabled(page, false);
      await gotoSettings(page, params);
      await expect(
        toggle(page),
        "無効の状態がリロード後も保持されること"
      ).toBeChecked({ checked: false });
      await expectMenu(page, false);

      // 3) 再有効化 → 永続し、メニューに戻る
      await setEnabled(page, true);
      await gotoSettings(page, params);
      await expect(
        toggle(page),
        "有効の状態がリロード後も保持されること"
      ).toBeChecked({ checked: true });
      await expectMenu(page, true);
    } finally {
      // 失敗しても必ず有効へ戻す(共有 CRM のグローバル設定のため)。
      // ここでも戻せない場合はテスト自体が失敗して検知できる
      // (CI は毎回クリーンな dump なので次の実行へは影響しない)。
      await setEnabled(page, true).catch(() => {});
    }
  });
});
