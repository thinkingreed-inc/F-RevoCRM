import { test, expect } from "@playwright/test";
import type { Page } from "@playwright/test";
import { loginInIsolatedContext } from "../../utils/settings";
import { gotoList, openMassMore } from "../../utils/listview";
import { url } from "../../utils/util";
import { seedSpec, passwordFor } from "../../fixtures/seedSpec";

/**
 * 共通機能: エクスポート/インポート権限（プロファイル utility 権限）
 *
 * Sales Profile(id2) は既定で Export/Import を拒否している。拡充ベースラインは、
 * 複製プロファイルで Accounts の Export(activityid 6)/Import(5) を **許可** したペルソナ
 * `e2e_p_export` を持ち、既定拒否の Sales ユーザー `e2e_director` と対比する。
 *
 * 検証（実コード確認済み）:
 *  - Export リンク: 一覧「その他」ドロップダウン内 `#Accounts_listView_advancedAction_LBL_EXPORT`。
 *    許可なら存在、拒否なら DOM から除外。
 *  - Import: 一覧メニューには項目が無い（テンプレートで除外）ため、`view=Import` へ直接遷移し、
 *    許可ならインポート画面（`input[type="file"]`）、拒否なら権限拒否画面（`権限がありません`）。
 */

const M = seedSpec.exportImportPerm.module; // Accounts
const exportLink = (p: Page) =>
  p.locator(`#${M}_listView_advancedAction_LBL_EXPORT`);

async function openMoreAndCheckExport(page: Page): Promise<number> {
  await gotoList(page, M);
  await openMassMore(page).catch(() => {});
  return exportLink(page).count();
}

test.describe("共通: エクスポート/インポート権限", () => {
  test(`${seedSpec.exportImportPerm.userName}: Export リンクが出て Import 画面に入れる（許可）`, async ({
    browser,
  }) => {
    test.setTimeout(60000);
    const eip = seedSpec.exportImportPerm;
    const { context, page } = await loginInIsolatedContext(
      browser,
      eip.userName,
      passwordFor(eip.userName)
    );
    try {
      // Export 許可 → リンクが存在
      expect(await openMoreAndCheckExport(page)).toBe(1);
      // Import 許可 → インポート画面のファイル入力が出る
      await page.goto(url(`index.php?module=${M}&view=Import&app=MARKETING`));
      await page.waitForLoadState("domcontentloaded");
      await expect(page.locator('input[type="file"]')).toHaveCount(1);
    } finally {
      await context.close();
    }
  });

  test(`${seedSpec.exportImportPerm.negativeUserName}: Export リンクが無く Import が権限拒否（既定拒否）`, async ({
    browser,
  }) => {
    test.setTimeout(60000);
    const eip = seedSpec.exportImportPerm;
    const { context, page } = await loginInIsolatedContext(
      browser,
      eip.negativeUserName,
      passwordFor(eip.negativeUserName)
    );
    try {
      // Export 拒否 → リンクが DOM に無い
      expect(await openMoreAndCheckExport(page)).toBe(0);
      // Import 拒否 → 権限拒否画面
      await page.goto(url(`index.php?module=${M}&view=Import&app=MARKETING`));
      await page.waitForLoadState("domcontentloaded");
      await expect(page.locator('input[type="file"]')).toHaveCount(0);
      await expect(page.locator("span.genHeaderSmall")).toContainText(
        /権限がありません|アクセスが拒否されました/
      );
    } finally {
      await context.close();
    }
  });
});
