import { test, expect } from "@playwright/test";
import type { Page } from "@playwright/test";
import { loginInIsolatedContext } from "../../utils/settings";
import { gotoList, listRows, firstRecordId, gotoDetail } from "../../utils/listview";
import { url } from "../../utils/util";
import { seedSpec, passwordFor } from "../../fixtures/seedSpec";

/**
 * 共通機能: プロファイル(役割)による アクション権限 — TEST_COVERAGE 権限ギャップ
 *
 * 拡充ベースラインは Sales Profile を複製し対象モジュール(Accounts)の権限だけ書き換えた
 * 制限プロファイルを、専用ロール+ユーザー(ペルソナ)に割り当てている:
 *   - e2e_p_hidden   : Accounts モジュール非表示(見えない)
 *   - e2e_p_readonly : 閲覧のみ(作成/編集/削除ボタンが出ない)
 *   - e2e_p_nodelete : 削除のみ不可(編集/追加は可、削除だけ出ない)
 *
 * Accounts は Public 共有なので「レコードが見える/見えない(sharing)」の影響を受けず、
 * 差はプロファイルの アクション権限 だけに出る。制限された操作の UI 要素はサーバ側で
 * DOM から除外される(CSS で隠すのではない)ため toHaveCount(0) で検証できる。
 * 期待可否は seed-spec.json の personas[].expect が唯一の出所(dump ビルド時に isPermitted で一致確認済み)。
 *
 * セレクタ(実コード確認済み):
 *  - 一覧「追加」  : #Accounts_listView_basicAction_LBL_ADD_RECORD (CreateView 権限で出没)
 *  - 詳細「編集」  : #Accounts_detailView_basicAction_LBL_EDIT      (EditView 権限)
 *  - 詳細「削除」  : a[href*="Vtiger_Detail_Js.deleteRecord"]        (Delete 権限)
 *  - 権限拒否画面  : span.genHeaderSmall(「アクセスが拒否されました。」/「権限がありません」)+ img[src*="denied.gif"]
 */

const MODULE = seedSpec.actionPerm.module; // "Accounts"

const createBtn = (p: Page) =>
  p.locator(`#${MODULE}_listView_basicAction_LBL_ADD_RECORD`);
const editBtn = (p: Page) =>
  p.locator(`#${MODULE}_detailView_basicAction_LBL_EDIT`);
const deleteLink = (p: Page) =>
  p.locator('a[href*="Vtiger_Detail_Js.deleteRecord"]');

test.describe("共通: アクション権限 (プロファイル/役割)", () => {
  for (const persona of seedSpec.actionPerm.personas) {
    test(`${persona.userName} (${persona.restriction}) の操作可否が期待どおり`, async ({
      browser,
    }) => {
      test.setTimeout(60000);
      const { context, page } = await loginInIsolatedContext(
        browser,
        persona.userName,
        passwordFor(persona.userName)
      );
      try {
        const exp = persona.expect;

        if (!exp.moduleVisible) {
          // 見えない: 一覧アクセスが権限拒否画面になる(モジュール非表示)
          await page.goto(
            url(`index.php?module=${MODULE}&view=List&app=MARKETING`)
          );
          await page.waitForLoadState("domcontentloaded");
          await expect(page.locator('img[src*="denied.gif"]')).toBeVisible();
          await expect(page.locator("span.genHeaderSmall")).toContainText(
            /アクセスが拒否されました|権限がありません/
          );
          // 一覧の明細行は描画されない
          await expect(listRows(page)).toHaveCount(0);
          return;
        }

        // 見える: 一覧が開けて明細行がある
        await gotoList(page, MODULE);
        await expect(listRows(page).first()).toBeVisible();

        // 「追加」ボタンの有無 = CreateView 権限
        await expect(createBtn(page)).toHaveCount(exp.canCreate ? 1 : 0);

        // 任意の 1 レコードの詳細を開く(Public 共有なので閲覧可)
        const recordId = await firstRecordId(page);
        await gotoDetail(page, MODULE, recordId);

        // 「編集」ボタンの有無 = EditView 権限
        await expect(editBtn(page)).toHaveCount(exp.canEdit ? 1 : 0);
        // 「削除」リンクの有無 = Delete 権限(その他メニュー内。DOM 上の有無で判定)
        await expect(deleteLink(page)).toHaveCount(exp.canDelete ? 1 : 0);
      } finally {
        await context.close();
      }
    });
  }
});
