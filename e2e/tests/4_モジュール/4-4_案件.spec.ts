import { test, expect, type Page } from "../../fixtures/isolated";
import { gotoDetail } from "../../utils/listview";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";

/**
 * 案件の固有アクション(詳細画面) — 機能一覧 19-2
 *
 * 詳細の「メールを送る」がメール作成モーダルを、「プロジェクトの作成」が案件コンバート
 * モーダルを起動すること、および「見積の作成」「受注の作成」が各在庫モジュールの編集
 * 画面へ遷移することを検証する(生成・送信自体は副作用のため起動/遷移確認まで)。
 *
 * 対象レコードは API で用意して並行安全にする(related_to は seed 済み Accounts を充当)。
 */
test.describe("案件の固有アクション", () => {
  const visibleModal = (page: Page, title: string) =>
    page.locator(".modal-content:visible").filter({ hasText: title }).first();

  test("メール作成 / プロジェクト変換 の各モーダルと 見積・受注 作成画面が起動する", async ({
    page,
  }) => {
    const { recordId, wsId, session } = await createRecordViaApi("Potentials");
    try {
      // メール作成モーダル(DETAILVIEWBASIC)
      await gotoDetail(page, "Potentials", recordId);
      await page
        .locator("#Potentials_detailView_basicAction_LBL_SEND_EMAIL")
        .click();
      await expect(visibleModal(page, "メールの作成")).toBeVisible();

      // プロジェクト変換モーダル(案件のコンバート。AJAX でフォームを読み込みモーダル表示)
      await gotoDetail(page, "Potentials", recordId);
      await page.getByRole("button", { name: "プロジェクトの作成" }).click();
      await expect(visibleModal(page, "案件のコンバート")).toBeVisible();

      // 見積 / 受注 の作成(「その他」メニュー → 対象モジュールの編集画面へ遷移)
      for (const target of ["Quotes", "SalesOrder"]) {
        await gotoDetail(page, "Potentials", recordId);
        await page
          .locator(".detailViewButtoncontainer button.dropdown-toggle")
          .first()
          .click();
        await page
          .locator(
            `.detailViewButtoncontainer .dropdown-menu a[href*="module=${target}"][href*="view=Edit"]`
          )
          .first()
          .click();
        await page.waitForURL(new RegExp(`[?&]module=${target}&view=Edit`), {
          timeout: 15000,
        });
      }
    } finally {
      await deleteRecordViaApi(session, wsId);
    }
  });
});
