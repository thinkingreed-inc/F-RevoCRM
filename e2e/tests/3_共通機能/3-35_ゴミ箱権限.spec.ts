import { test, expect } from "@playwright/test";
import type { Page } from "@playwright/test";
import { loginInIsolatedContext } from "../../utils/settings";
import { url } from "../../utils/util";
import { seedSpec, passwordFor } from "../../fixtures/seedSpec";

/**
 * 共通機能: ごみ箱のモジュール権限 — issue #1205
 *
 * ごみ箱はサイドバーのモジュール一覧から対象モジュールを選ぶ画面で、
 * sourceModule クエリにより対象モジュールを URL から直接指定できる。
 * モジュールアクセス権限の無いモジュールは次の 2 経路で塞ぐ:
 *   - 一覧に出さない        (RecycleBin_Module_Model::getAllModuleList)
 *   - 直接指定しても拒否する (RecycleBin_List_View::checkPermission)
 * 後者が無いと、一覧から消えていても URL 直接指定で列見出しや
 * ピックリスト値といったモジュール構成の情報が読めてしまう。
 *
 * 拡充ベースラインの権限ペルソナ e2e_p_hidden(= Accounts モジュール非表示)と
 * admin を対比して検証する。ペルソナの定義は seed-spec.json が唯一の出所。
 *
 * セレクタ(実コード確認済み):
 *  - モジュール一覧 : ul.lists-menu a.filterName[href*="sourceModule=<Module>"]
 *                     (layouts/v7/modules/RecycleBin/partials/SidebarEssentials.tpl)
 *  - 権限拒否画面   : img[src*="denied.gif"] + span.genHeaderSmall
 *                     (Vtiger/OperationNotPermitted.tpl。AppException の表示先)
 */

/** 権限を外す対象モジュール(ペルソナ定義と同じ Accounts)。 */
const HIDDEN_MODULE = seedSpec.actionPerm.module;
/**
 * 対照用の「権限があるモジュール」。制限プロファイルは Sales Profile を複製して
 * Accounts の権限だけを書き換えたものなので、Contacts は両ユーザーとも見える。
 */
const VISIBLE_MODULE = "Contacts";

const hiddenPersona = seedSpec.actionPerm.personas.find(
  (p) => p.restriction === "module_hidden"
);
if (!hiddenPersona) {
  throw new Error(
    "seed-spec.json に restriction=module_hidden のペルソナがありません"
  );
}

const binModuleLink = (page: Page, module: string) =>
  page.locator(`ul.lists-menu a.filterName[href*="sourceModule=${module}"]`);

const gotoBin = async (page: Page, sourceModule?: string) => {
  const query = sourceModule ? `&sourceModule=${sourceModule}` : "";
  await page.goto(url(`index.php?module=RecycleBin&view=List${query}`));
  await page.waitForLoadState("domcontentloaded");
};

const expectPermissionDenied = async (page: Page) => {
  await expect(page.locator('img[src*="denied.gif"]')).toBeVisible();
  await expect(page.locator("span.genHeaderSmall")).toContainText(
    /アクセスが拒否されました|権限がありません/
  );
};

test.describe("共通: ごみ箱のモジュール権限", () => {
  test(`${hiddenPersona.userName}: 権限の無いモジュールは一覧に出ず URL 直接指定も拒否される`, async ({
    browser,
  }) => {
    test.setTimeout(60000);
    const { context, page } = await loginInIsolatedContext(
      browser,
      hiddenPersona.userName,
      passwordFor(hiddenPersona.userName)
    );
    try {
      // 一覧自体は描画される(権限のあるモジュールは出る)が、権限の無いモジュールは出ない
      await gotoBin(page);
      await expect(binModuleLink(page, VISIBLE_MODULE)).toHaveCount(1);
      await expect(binModuleLink(page, HIDDEN_MODULE)).toHaveCount(0);

      // sourceModule を URL で直接指定しても拒否される
      await gotoBin(page, HIDDEN_MODULE);
      await expectPermissionDenied(page);

      // 権限のあるモジュールは直接指定でも従来どおり開ける
      await gotoBin(page, VISIBLE_MODULE);
      await expect(page.locator('img[src*="denied.gif"]')).toHaveCount(0);
      await expect(binModuleLink(page, VISIBLE_MODULE)).toHaveCount(1);
    } finally {
      await context.close();
    }
  });

  test("admin: 権限のあるモジュールは一覧に出て URL 直接指定でも開ける", async ({
    browser,
  }) => {
    test.setTimeout(60000);
    const { context, page } = await loginInIsolatedContext(
      browser,
      "admin",
      passwordFor("admin")
    );
    try {
      await gotoBin(page);
      await expect(binModuleLink(page, HIDDEN_MODULE)).toHaveCount(1);

      await gotoBin(page, HIDDEN_MODULE);
      await expect(page.locator('img[src*="denied.gif"]')).toHaveCount(0);
      await expect(binModuleLink(page, HIDDEN_MODULE)).toHaveCount(1);
    } finally {
      await context.close();
    }
  });
});
