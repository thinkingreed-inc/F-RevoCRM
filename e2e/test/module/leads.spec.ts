import { test, expect, type Page } from "../../fixtures/isolated";
import { gotoDetail } from "../../utils/listview";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { frQuery, frDelete } from "../../model/fetcher";
import { generateRandomString } from "../../utils/util";

/**
 * リードの固有アクション(詳細画面) — 機能一覧 16-2 / 16-3
 *
 * 詳細の「メールを送る」「SMSを送る」が各モーダルを、「リードを昇格」が昇格
 * (ConvertLead)モーダルを起動すること、および「予定の登録」「TODOの登録」が
 * カレンダーの編集画面へ遷移することを検証する。
 *
 * 1本目は各アクションの「起動」確認(昇格はフォーム #convertLeadForm の表示まで)。
 * 2本目で昇格の「保存(SaveConvertLead)」まで検証する(下の test を参照)。
 * 対象レコードは API で用意して並行安全にする。
 */
test.describe("リードの固有アクション", () => {
  const visibleModal = (page: Page, title: string) =>
    page.locator(".modal-content:visible").filter({ hasText: title }).first();

  test("メール作成 / SMS / リード昇格 の各モーダルと 予定・TODO 作成画面が起動する", async ({
    page,
  }) => {
    const { recordId, wsId, session } = await createRecordViaApi("Leads");
    try {
      // メール作成モーダル(DETAILVIEWBASIC)
      await gotoDetail(page, "Leads", recordId);
      await page
        .locator("#Leads_detailView_basicAction_LBL_SEND_EMAIL")
        .click();
      await expect(visibleModal(page, "メールの作成")).toBeVisible();

      // リード昇格モーダル(DETAILVIEWBASIC。AJAX でフォームを読み込みモーダル表示)
      await gotoDetail(page, "Leads", recordId);
      await page
        .locator("#Leads_detailView_basicAction_LBL_CONVERT_LEAD")
        .click();
      await expect(page.locator("#convertLeadForm")).toBeVisible();

      // SMS送信モーダル(「その他」メニュー内)
      await gotoDetail(page, "Leads", recordId);
      await page
        .locator(".detailViewButtoncontainer button.dropdown-toggle")
        .first()
        .click();
      await page.locator("#Leads_detailView_moreAction_LBL_SEND_SMS").click();
      await expect(visibleModal(page, "SMSを送る")).toBeVisible();

      // 予定の登録(活動) / TODOの登録 → カレンダーの編集画面へ遷移
      for (const action of ["LBL_ADD_EVENT", "LBL_ADD_TASK"]) {
        await gotoDetail(page, "Leads", recordId);
        await page
          .locator(".detailViewButtoncontainer button.dropdown-toggle")
          .first()
          .click();
        await page
          .locator(`#Leads_detailView_moreAction_${action} a`)
          .click();
        await page.waitForURL(/[?&]module=Calendar&view=/, { timeout: 15000 });
      }
    } finally {
      await deleteRecordViaApi(session, wsId);
    }
  });

  /**
   * リード昇格の保存（ConvertLead → SaveConvertLead）— 機能一覧 16-2
   *
   * 昇格が成立する条件（実機調査で確定）:
   *  - リードに company があると Accounts、lastname があると Contacts の作成チェックが
   *    既定でオンになり、各モジュールの必須項目はリードの値から自動で埋まる。
   *  - 担当（assigned_user_id）はリストの担当者が既定で入る。
   *  → 上記が揃った fresh リードなら、昇格フォームは追加入力なしで保存でき、
   *    作成された顧客企業の詳細へ遷移する（SaveConvertLead.php の header Location）。
   *
   * 過去に未成立だった主因: company 未設定で Accounts 未チェックのまま保存し、
   * jQuery Validation（vtValidate）が必須項目未入力で native submit を止めていた／
   * submitHandler バインド前にクリックし modules 空で SaveConvertLead に届いていた。
   * ここでは company+lastname を持つリードを用意し、モーダル描画（=submit登録）を待って
   * から保存することで昇格を成立させる。
   *
   * 注意: 一度昇格したリードは `isLeadConverted()` で昇格ボタンが消えるため、テストは
   * 毎回 fresh なリードを作る（使い捨て）。作成された顧客企業/顧客担当者は後始末で削除する。
   */
  test("昇格を保存すると顧客企業が作成され詳細へ遷移する", async ({ page }) => {
    const token = generateRandomString(8);
    const company = `E2E昇格会社${token}`;
    const lastname = `E2E昇格姓${token}`;
    const { recordId, wsId, session } = await createRecordViaApi("Leads", {
      company,
      lastname,
    });

    let createdAccountId = "";
    try {
      await gotoDetail(page, "Leads", recordId);
      await page
        .locator("#Leads_detailView_basicAction_LBL_CONVERT_LEAD")
        .click();
      // フォーム描画＝submitHandler(vtValidate)の登録完了を待つ
      await expect(page.locator("#convertLeadForm")).toBeVisible();
      // Accounts/Contacts の作成チェックが既定でオン（company/lastname 由来）
      await expect(
        page.locator("#AccountsModule")
      ).toBeChecked();

      // 保存 → 作成された顧客企業の詳細へ遷移する
      await page
        .locator('#convertLeadForm button[name="saveButton"]')
        .click();
      await page.waitForURL(/[?&]module=Accounts&record=\d+/, {
        timeout: 20000,
      });
      createdAccountId = page.url().match(/record=(\d+)/)![1];
      expect(createdAccountId).toBeTruthy();
    } finally {
      // 後始末: 昇格で作成された顧客企業・顧客担当者、および（昇格済みの）リードを削除
      const accRows = await frQuery(
        session,
        `SELECT id FROM Accounts WHERE accountname='${company}';`
      ).catch(() => []);
      for (const r of accRows) await frDelete(session, r.id).catch(() => {});

      const conRows = await frQuery(
        session,
        `SELECT id FROM Contacts WHERE lastname='${lastname}';`
      ).catch(() => []);
      for (const r of conRows) await frDelete(session, r.id).catch(() => {});

      await deleteRecordViaApi(session, wsId);
    }
  });
});
