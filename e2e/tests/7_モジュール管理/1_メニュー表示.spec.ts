import { test, expect } from "../../fixtures/isolated";
import type { Locator, Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { BASE_URL } from "../../utils/util";
import {
  MODULES,
  MODULE_NAMES,
  type ManagedModule,
} from "./fixtures/modules";
import { forceRestoreAll, findStillDisabled, getPresence } from "./support/db";

/**
 * 7 モジュール管理: ON/OFF でアプリメニューの表示が切り替わることを検証する。
 *
 * 各モジュールについて Excel「3_〇_OSS版_モジュール管理.xlsx」の判定に従い、
 *   ON  → メニューにリンクが出る
 *   OFF → メニューにリンクが出ない
 * を確認する。トグルは ModuleManager の UI(input[name="moduleStatus"])で行う
 * (CSRF・サーバ側メニューキャッシュ再生成込み。実証済み=D-01)。
 *
 * これは共有 CRM のグローバル設定変更のため、安全に最大限配慮する:
 *  - 各テストは try/finally で必ず有効へ戻す。
 *  - describe.serial + workers=1 で直列実行(トグル競合と共有モジュール
 *    (Contacts/Accounts 等)無効化中の他テスト巻き込みを防ぐ)。
 *  - afterAll で全対象を DB で presence=0 に強制復帰し、RecreateUserFiles で
 *    キャッシュを再生成する最終防波堤を張る。
 *  - 実行後に「無効のまま残った対象が無い」ことを DB で検証する。
 */

const MODULE_MANAGER = { module: "ModuleManager", view: "List" };
const TEST_TIMEOUT = 90_000;

/** ModuleManager 一覧上の有効/無効トグル(内部名で特定、訳語非依存)。 */
const toggle = (page: Page, name: string): Locator =>
  page.locator(`input[name="moduleStatus"][data-module="${name}"]`);

/** モジュールを目的の有効状態にする(必要な時だけクリックして AJAX 保存)。 */
async function setModuleEnabled(
  page: Page,
  name: string,
  enabled: boolean
): Promise<void> {
  await gotoSettings(page, MODULE_MANAGER);
  const cb = toggle(page, name);
  await expect(cb, `${name} のトグルが一覧に存在しない`).toBeVisible();
  if ((await cb.isChecked()) !== enabled) {
    await saveAndSettle(page, cb);
  }
  await expect(cb).toBeChecked({ checked: enabled });
}

/** そのモジュールのメニューリンク locator(描画場所により選択子を切替)。 */
function menuLocator(page: Page, mod: ManagedModule): Locator {
  const marker = `module=${mod.name}&`;
  switch (mod.menu) {
    case "appmenu":
      return page.locator(`#app-menu a[href*="${marker}"]`);
    case "misc":
      return page.locator(`#app-menu [data-default-url*="${marker}"]`);
    case "topbar":
      return page.locator(`nav.app-fixed-navbar a[href*="${marker}"]`);
  }
}

/** アプリを再読込し、メニューにリンクが在る/無いを検証する。 */
async function assertMenu(
  page: Page,
  mod: ManagedModule,
  shouldExist: boolean
): Promise<void> {
  await page.goto(`${BASE_URL}index.php?module=Home&view=DashBoard`);
  await page.waitForLoadState("domcontentloaded");
  // ヘッダ(メニュー)は本文より先に描画される。本文でエラーが出ても
  // メニュー判定は成立するよう、ナビが DOM に付くまで待つ。
  await page
    .locator("nav.app-fixed-navbar")
    .first()
    .waitFor({ state: "attached", timeout: 15_000 });

  const loc = menuLocator(page, mod);
  if (shouldExist) {
    await expect
      .poll(async () => loc.count(), {
        timeout: 10_000,
        message: `${mod.label}(${mod.name}) のメニューリンクが出ていない`,
      })
      .toBeGreaterThan(0);
  } else {
    await expect(
      loc,
      `${mod.label}(${mod.name}) 無効化後もメニューリンクが残っている`
    ).toHaveCount(0, { timeout: 10_000 });
  }
}

test.describe.serial("モジュール管理: ON/OFF でメニュー表示切替", () => {
  test.afterAll(() => {
    // 最終防波堤: 全対象を有効へ強制復帰 + キャッシュ再生成 + 検証。
    forceRestoreAll(MODULE_NAMES);
    const left = findStillDisabled(MODULE_NAMES);
    if (left.length > 0) {
      throw new Error(`無効のまま残った対象モジュール: ${left.join(", ")}`);
    }
    // eslint-disable-next-line no-console
    console.log(
      "SAFETY_NET_OK 全対象を presence=0 へ復帰:",
      JSON.stringify(getPresence(MODULE_NAMES))
    );
  });

  for (const mod of MODULES) {
    test(`${mod.label} (${mod.name})`, async ({ page }) => {
      test.setTimeout(TEST_TIMEOUT);
      try {
        // 1) 有効化 → メニューに出る(ON チェック)
        await setModuleEnabled(page, mod.name, true);
        await assertMenu(page, mod, true);

        // 2) 無効化 → メニューから消える(OFF チェック)
        await setModuleEnabled(page, mod.name, false);
        await assertMenu(page, mod, false);

        // 3) 再有効化(復帰) → 再びメニューに出る
        await setModuleEnabled(page, mod.name, true);
        await assertMenu(page, mod, true);
      } finally {
        // 失敗しても必ず有効へ戻す(afterAll の DB 復帰と二重の保険)。
        await setModuleEnabled(page, mod.name, true).catch(() => {});
      }
    });
  }
});
