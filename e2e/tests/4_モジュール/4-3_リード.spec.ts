import { test, expect, type Page } from "../../fixtures/isolated";
import { gotoDetail } from "../../utils/listview";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { frQuery, frDelete } from "../../model/fetcher";
import { generateRandomString } from "../../utils/util";

/**
 * リードの固有アクション(詳細画面) — 機能一覧 16-2 / 16-3
 *
 * 詳細の「メールを送る」「SMSを送る」が各モーダルを、「リードを昇格」が昇格画面
 * (Web コンポーネント <lead-convert>)を起動すること、および「予定の登録」「TODOの登録」が
 * カレンダーの編集画面へ遷移することを検証する。
 *
 * 1本目は各アクションの「起動」確認(昇格は昇格画面の各セクションの表示まで)。
 * 2本目で昇格の実行(ConvertLeadSave API)まで検証する(下の test を参照)。
 * 判定条件・既存レコードへの紐づけ・検証エラー等の詳細は 4-12_リード昇格.spec.ts(ローカル用)。
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

      // リード昇格画面(DETAILVIEWBASIC。<lead-convert> がダイアログで開き、API で情報を読み込む)
      await gotoDetail(page, "Leads", recordId);
      await page
        .locator("#Leads_detailView_basicAction_LBL_CONVERT_LEAD")
        .click();
      await expect(
        page.getByTestId("lead-convert-section-Contacts")
      ).toBeVisible();

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
   * リード昇格の実行（<lead-convert> → ConvertLeadSave API）— 機能一覧 16-2
   *
   * 昇格が成立する条件:
   *  - リードに company があると顧客企業、lastname があると顧客担当者が既定で「新しく作成する」に
   *    なり、各モジュールの必須項目はリードの値（マッピング）から埋まる。
   *  - 担当（assigned_user_id）はリードの担当が既定で入る。
   *  → company+lastname を持つ fresh リードなら追加入力なしで昇格でき、作成された
   *    顧客企業の詳細へ遷移する（ConvertLeadSave の redirectUrl）。
   *  - 会社名は毎回ランダムにして、昇格済み判定（既定は会社名一致）に当たらないようにする。
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
      // 顧客企業・顧客担当者が既定で「新しく作成する」（company/lastname 由来）
      for (const module of ["Accounts", "Contacts"]) {
        await expect(
          page
            .getByTestId(`lead-convert-section-${module}`)
            .getByRole("radio", { name: "新しく作成する" })
        ).toBeChecked();
      }

      // 昇格 → 作成された顧客企業の詳細へ遷移する
      await page.getByTestId("lead-convert-submit").click();
      await page.waitForURL(/[?&]module=Accounts&view=Detail&record=\d+/, {
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
