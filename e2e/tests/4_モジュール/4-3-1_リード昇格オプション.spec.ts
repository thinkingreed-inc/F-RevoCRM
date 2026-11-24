import { test, expect } from "../../fixtures/isolated";
import { gotoDetail, gotoList, listRows, listSearch, clearListSearch } from "../../utils/listview";
import { confirmYes, setParameterValue } from "../../utils/settings";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { apiSession } from "../../utils/api";
import { frQuery, frDelete } from "../../model/fetcher";
import { generateRandomString } from "../../utils/util";

/**
 * 昇格済みリードの表示／再昇格オプション (Issue #1854)
 *
 * システム変数で挙動を切り替える:
 *  - SHOW_CONVERTED_LEADS : 昇格済みリードを一覧などに表示するか
 *  - ALLOW_RECONVERT_LEAD : 昇格済みリードを再度昇格できるようにするか
 *
 * どちらもグローバル設定のため、最後のテストで必ず 'false' に戻して原状復帰する。
 * 昇格済みリードを 1 件作って状態を引き継ぐので serial で実行する。
 */
test.describe.serial("リード昇格オプション", () => {
  // 2 つのパラメーターはシステム全体の設定で、書き換えると並列実行中の他テスト
  // (リード一覧の件数や昇格ボタンを見るもの)に影響する。そのため既定では skip し、
  // 明示的にオプトインしたときだけ単独で実行する運用とする。
  //   cd e2e && E2E_LEAD_CONVERT_OPTION=1 npm run test:e2e -- --workers=1 \
  //     "tests/4_モジュール/4-3-1_リード昇格オプション.spec.ts"
  test.skip(
    !process.env.E2E_LEAD_CONVERT_OPTION,
    "グローバル設定を書き換えるため、E2E_LEAD_CONVERT_OPTION 指定時のみ実行する"
  );

  const token = generateRandomString(8);
  const company = `E2E昇格済会社${token}`;
  const lastname = `E2E昇格済姓${token}`;

  let leadRecordId = "";
  let leadWsId = "";
  let session = "";
  /** 1 回目の昇格で作られた顧客企業の record ID(再昇格で別レコードになることの確認に使う) */
  let firstAccountId = "";

  const convertLeadButton = (page: import("@playwright/test").Page) =>
    page.locator("#Leads_detailView_basicAction_LBL_CONVERT_LEAD");

  // 途中で失敗しても必ず原状復帰する。パラメーターはグローバル設定のため、
  // false に戻さないまま終わると後続の実行や他テストを巻き込む。
  test.afterAll(async ({ browser, workerStorageState }) => {
    const context = await browser.newContext({ storageState: workerStorageState });
    try {
      const page = await context.newPage();
      await setParameterValue(page, "SHOW_CONVERTED_LEADS", "false");
      await setParameterValue(page, "ALLOW_RECONVERT_LEAD", "false");
    } finally {
      await context.close();
    }

    // 昇格で作られた顧客企業・顧客担当者と、テスト用リードを削除する
    const cleanupSession = session || (await apiSession());
    for (const [module, field, value] of [
      ["Accounts", "accountname", company],
      ["Contacts", "lastname", lastname],
    ] as const) {
      const rows = await frQuery(
        cleanupSession,
        `SELECT id FROM ${module} WHERE ${field}='${value}';`
      ).catch(() => []);
      for (const r of rows) await frDelete(cleanupSession, r.id).catch(() => {});
    }
    if (leadWsId) {
      await deleteRecordViaApi(cleanupSession, leadWsId);
    }
  });

  test("リードを昇格すると既定では一覧から消え、昇格ボタンも出なくなる", async ({
    page,
  }) => {
    const created = await createRecordViaApi("Leads", { company, lastname });
    leadRecordId = created.recordId;
    leadWsId = created.wsId;
    session = created.session;

    // 昇格前は一覧に出ている
    await gotoList(page, "Leads");
    await listSearch(page, "company", company);
    await expect(listRows(page)).toHaveCount(1);
    await clearListSearch(page);

    // 詳細から昇格する(company/lastname 由来で Accounts/Contacts が既定オン)
    await gotoDetail(page, "Leads", leadRecordId);
    await convertLeadButton(page).click();
    await expect(page.locator("#convertLeadForm")).toBeVisible();
    await page.locator('#convertLeadForm button[name="saveButton"]').click();
    await page.waitForURL(/[?&]module=Accounts&record=\d+/, { timeout: 20000 });
    firstAccountId = page.url().match(/record=(\d+)/)![1];

    // 既定 (SHOW_CONVERTED_LEADS=false) では一覧から消える
    await gotoList(page, "Leads");
    await listSearch(page, "company", company);
    await expect(listRows(page)).toHaveCount(0);
    await clearListSearch(page);

    // 既定 (ALLOW_RECONVERT_LEAD=false) では昇格ボタンも出ない
    await gotoDetail(page, "Leads", leadRecordId);
    await expect(convertLeadButton(page)).toHaveCount(0);
  });

  test("SHOW_CONVERTED_LEADS を true にすると昇格済みリードが一覧に出る", async ({
    page,
  }) => {
    await setParameterValue(page, "SHOW_CONVERTED_LEADS", "true");

    await gotoList(page, "Leads");
    await listSearch(page, "company", company);
    await expect(listRows(page)).toHaveCount(1);
    await clearListSearch(page);

    // 表示しても、再昇格は別のパラメーターなのでまだ許可されない
    await gotoDetail(page, "Leads", leadRecordId);
    await expect(convertLeadButton(page)).toHaveCount(0);
  });

  test("ALLOW_RECONVERT_LEAD を true にすると確認のうえ再昇格できる", async ({
    page,
  }) => {
    await setParameterValue(page, "ALLOW_RECONVERT_LEAD", "true");

    await gotoDetail(page, "Leads", leadRecordId);
    await expect(convertLeadButton(page)).toBeVisible();

    // 昇格済みのため確認ダイアログが出る。「はい」で昇格モーダルが開く
    await convertLeadButton(page).click();
    await expect(page.locator(".confirm-box-ok")).toBeVisible();
    await confirmYes(page);
    await expect(page.locator("#convertLeadForm")).toBeVisible();

    // 保存まで通ること。顧客企業は同名のものが再利用され(ConvertLead.php の
    // accountname 検索)、顧客担当者は毎回新規に作られる。
    const contactsBefore = (
      await frQuery(session, `SELECT id FROM Contacts WHERE lastname='${lastname}';`)
    ) ?? [];
    await page.locator('#convertLeadForm button[name="saveButton"]').click();
    await page.waitForURL(/[?&]module=Accounts&record=\d+/, { timeout: 20000 });
    expect(page.url()).toContain(`record=${firstAccountId}`);

    const contactsAfter = (
      await frQuery(session, `SELECT id FROM Contacts WHERE lastname='${lastname}';`)
    ) ?? [];
    expect(contactsAfter.length).toBe(contactsBefore.length + 1);
  });

  test("パラメーターを false に戻すと従来の動作に戻る", async ({ page }) => {
    await setParameterValue(page, "SHOW_CONVERTED_LEADS", "false");
    await setParameterValue(page, "ALLOW_RECONVERT_LEAD", "false");

    await gotoList(page, "Leads");
    await listSearch(page, "company", company);
    await expect(listRows(page)).toHaveCount(0);
    await clearListSearch(page);

    await gotoDetail(page, "Leads", leadRecordId);
    await expect(convertLeadButton(page)).toHaveCount(0);
  });
});
