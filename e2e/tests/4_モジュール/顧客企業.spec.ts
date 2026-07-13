import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import {
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";

test.describe("顧客企業モジュールのテスト", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(
      url("index.php?module=Accounts&view=List&viewname=4&app=MARKETING")
    );
  });

  test("顧客企業リストに表示されているべき要素のテスト", async ({ page }) => {
    await expect(page.getByText("顧客企業の追加").first()).toBeVisible();
    await expect(page.getByText("インポート").first()).toBeVisible();
    await expect(page.getByText("カスタマイズ").first()).toBeVisible();
    await expect(page.getByText("個人リスト").first()).toBeVisible();
    await expect(page.getByText("共有リスト").first()).toBeVisible();

    // カスタマイズを押したときの動作確認
    await page.getByText("カスタマイズ").first().click();
    await expect(page.getByText("顧客企業 項目の編集").first()).toBeVisible();
    await expect(
      page.getByText("顧客企業 ワークフローの編集").first()
    ).toBeVisible();
    await page.getByText("カスタマイズ").first().click(); //閉じる
  });
});

/**
 * 顧客企業の固有アクション(詳細画面) — 機能一覧 18-2
 *
 * 詳細の「メールを送る」「組織階層」「SMSを送る」がそれぞれのモーダルを起動する
 * ことを検証する(送信自体は副作用があるため起動確認まで)。専用レコードで並行安全。
 */
test.describe("顧客企業の固有アクション", () => {
  const visibleModal = (page: import("@playwright/test").Page, title: string) =>
    page.locator(".modal-content:visible").filter({ hasText: title }).first();

  test("メール作成 / 組織階層 / SMS送信 の各モーダルが起動する", async ({
    page,
  }) => {
    const recordId = await createAccount(
      page,
      `E2Eacc${generateRandomString(6)}`
    );

    // メール作成モーダル
    await gotoDetail(page, "Accounts", recordId);
    await page
      .locator("#Accounts_detailView_basicAction_LBL_SEND_EMAIL")
      .click();
    await expect(visibleModal(page, "メールの作成")).toBeVisible();

    // 組織階層モーダル(「その他」メニュー内)
    await gotoDetail(page, "Accounts", recordId);
    await page
      .locator(".detailViewButtoncontainer button.dropdown-toggle")
      .first()
      .click();
    await page
      .locator("#Accounts_detailView_moreAction_LBL_SHOW_ACCOUNT_HIERARCHY")
      .click();
    await expect(visibleModal(page, "組織階層")).toBeVisible();

    // SMS送信モーダル(「その他」メニュー内)
    await gotoDetail(page, "Accounts", recordId);
    await page
      .locator(".detailViewButtoncontainer button.dropdown-toggle")
      .first()
      .click();
    await page
      .locator("#Accounts_detailView_moreAction_LBL_SEND_SMS")
      .click();
    await expect(visibleModal(page, "SMSを送る")).toBeVisible();

    // 予定の登録(活動) / TODOの登録 → カレンダーの編集画面へ遷移
    for (const action of ["LBL_ADD_EVENT", "LBL_ADD_TASK"]) {
      await gotoDetail(page, "Accounts", recordId);
      await page
        .locator(".detailViewButtoncontainer button.dropdown-toggle")
        .first()
        .click();
      await page
        .locator(`#Accounts_detailView_moreAction_${action} a`)
        .click();
      await page.waitForURL(/[?&]module=Calendar&view=/, { timeout: 15000 });
    }

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
