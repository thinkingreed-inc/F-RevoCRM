import { test, expect } from "../../fixtures/isolated";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { url, generateRandomString } from "../../utils/util";
import { readFileSync } from "fs";

/**
 * レポート(Reports) — TEST_COVERAGE P0-1
 *
 * 機能一覧 41-1 の正式機能だが spec が 1 本も無かった領域。
 * ベースライン dump には標準レポートが焼き込まれている(`vtiger_report`)ので、
 * レポートの新規作成を待たずに「一覧 → 実行 → エクスポート」を検証できる。
 *
 * 画面構造(実機で確認):
 *  - 一覧:   index.php?module=Reports&view=List
 *            行は標準 ListView と同じ tr.listViewEntries[data-recordurl]
 *            左に フォルダ一覧(ListViewFolders.tpl)
 *  - 実行:   index.php?module=Reports&view=Detail&record=<id>
 *            レコード数は #countValue、明細表は #reportDetails table
 *  - 出力:   button.reportActions[data-href*="mode=GetCSV"] / mode=GetXLS
 *            (`Reports_Record_Model` が Content-Disposition: attachment を返すため
 *             page.waitForEvent("download") で受け取れる)
 *
 * 検証に使う固定レポート: record=1 = "Contacts by Accounts"
 * (顧客企業に紐づく顧客担当者の一覧。dump 固定なのでレポート名も併せて assert し、
 *  dump が入れ替わった場合に気付けるようにする)
 */

/** dump に焼き込まれた標準レポート(顧客企業別の顧客担当者)。 */
const FIXED_REPORT = { id: "1", name: "Contacts by Accounts" };

/** レポート一覧へ遷移する。 */
async function gotoReportList(page: import("@playwright/test").Page) {
  await page.goto(url("index.php?module=Reports&view=List"));
  await page.waitForLoadState("networkidle");
}

/** レポートの実行結果(詳細)へ遷移する。 */
async function gotoReportDetail(
  page: import("@playwright/test").Page,
  reportId: string
) {
  await page.goto(
    url(`index.php?module=Reports&view=Detail&record=${reportId}`)
  );
  await page.waitForLoadState("networkidle");
}

test.describe("レポート(Reports)", () => {
  test("一覧にフォルダと既存レポートが表示される", async ({ page }) => {
    await gotoReportList(page);

    // フォルダ一覧(既定は「すべてのレポート」が選択状態)
    await expect(page.getByText("すべてのレポート").first()).toBeVisible();

    // dump の標準レポートが行として並ぶ
    const rows = page.locator("tr.listViewEntries");
    await expect(rows.first()).toBeVisible();
    expect(await rows.count()).toBeGreaterThan(0);

    // 行から詳細 URL が取れる(レポートを開く導線)
    const recordUrl = await rows.first().getAttribute("data-recordurl");
    expect(recordUrl).toContain("module=Reports");
    expect(recordUrl).toContain("view=Detail");
  });

  test("レポートを開くと集計結果が表示される", async ({ page }) => {
    const token = generateRandomString(8);
    // レポートは「顧客企業に紐づく顧客担当者」。結合先の顧客企業と、
    // それに紐づく顧客担当者を API で用意し、結果に現れることを確認する。
    const account = await createRecordViaApi("Accounts", {
      accountname: `[E2E-RPT] Account ${token}`,
    });
    const contact = await createRecordViaApi("Contacts", {
      lastname: `RPT${token}`,
      account_id: account.wsId,
    });

    try {
      await gotoReportDetail(page, FIXED_REPORT.id);

      // 想定どおりのレポートを開いているか(dump 差し替え検知)
      await expect(page.getByText(FIXED_REPORT.name).first()).toBeVisible();

      // レコード数が 1 件以上になっている
      const count = page.locator("#countValue");
      await expect(count).toBeVisible();
      await expect
        .poll(async () => Number((await count.textContent())?.trim() || "0"), {
          timeout: 15000,
        })
        .toBeGreaterThan(0);

      // 明細表に作成した顧客担当者が現れる(集計が実データを拾っていること)
      const details = page.locator("#reportDetails");
      await expect(details).toBeVisible();
      await expect(details).toContainText(`RPT${token}`);
    } finally {
      await deleteRecordViaApi(contact.session, contact.wsId);
      await deleteRecordViaApi(account.session, account.wsId);
    }
  });

  test("CSV エクスポートでファイルがダウンロードされる", async ({ page }) => {
    await gotoReportDetail(page, FIXED_REPORT.id);

    const csvButton = page.locator(
      'button.reportActions[data-href*="mode=GetCSV"]'
    );
    await expect(csvButton).toBeVisible();

    const [download] = await Promise.all([
      page.waitForEvent("download", { timeout: 30000 }),
      csvButton.click(),
    ]);

    expect(download.suggestedFilename()).toMatch(/\.csv$/i);
    const filePath = await download.path();
    expect(filePath).toBeTruthy();
    // 列見出し行があるので、レコード 0 件でも空にはならない
    expect(readFileSync(filePath!).length).toBeGreaterThan(0);
  });

  test("Excel エクスポートでファイルがダウンロードされる", async ({ page }) => {
    await gotoReportDetail(page, FIXED_REPORT.id);

    const xlsButton = page.locator(
      'button.reportActions[data-href*="mode=GetXLS"]'
    );
    await expect(xlsButton).toBeVisible();

    const [download] = await Promise.all([
      page.waitForEvent("download", { timeout: 30000 }),
      xlsButton.click(),
    ]);

    expect(download.suggestedFilename()).toMatch(/\.xlsx?$/i);
    const filePath = await download.path();
    expect(filePath).toBeTruthy();
    // xlsx は ZIP コンテナ。先頭 2 バイトが "PK" であることを確認する
    const buf = readFileSync(filePath!);
    expect(buf.length).toBeGreaterThan(0);
    expect(buf.subarray(0, 2).toString("latin1")).toBe("PK");
  });
});
