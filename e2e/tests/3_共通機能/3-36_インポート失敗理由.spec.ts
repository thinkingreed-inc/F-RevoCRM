import { Page } from "@playwright/test";
import { test, expect } from "../../fixtures/isolated";
import { runImport } from "../../utils/import";
import { generateRandomString } from "../../utils/util";
import { apiSession } from "../../utils/api";
import { frQuery } from "../../model/fetcher";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";

/**
 * 共通機能: インポートの失敗・スキップ理由の表示(issue #1905)
 *
 * インポート結果画面の「詳細」(失敗/スキップ)に「理由」列が増え、行ごとに
 * 失敗・スキップの原因が表示されることを、理由の種類ごとに検証する:
 *   - 案件(Potentials): 必須項目が空 / 選択肢に無い値 / 日付不正 / 参照先が複数一致 / 重複スキップ
 *   - 活動(Calendar): 開始日が空 / 選択肢に無い値
 *   - 受注(SalesOrder): 有効な明細行が無い / 明細行の製品が見つからない
 * あわせて、既存の列の後ろに「理由」列が増えること、成功した行が作成されること、
 * CSV の値に含めた HTML がタグとして解釈されないことを確認する。
 *
 * インポートは1ユーザーにつき同時実行不可(ロック)のため describe.serial で直列化。
 * 前提レコード(顧客企業・製品)は API で作成し、作成したものは API で削除する。
 */
test.describe.serial("共通: インポートの失敗・スキップ理由", () => {
  const base = `E2Eimprsn${generateRandomString(6)}`;
  const single = `${base}_顧客単一`;
  const dup = `${base}_顧客重複`;
  const product = `${base}_製品`;
  const created: string[] = [];

  /** 結果画面の「詳細」を開き、見出しと各行のセルを返す。 */
  async function openDetails(
    page: Page,
    type: "failed" | "skipped"
  ): Promise<{ headers: string[]; rows: string[][] }> {
    await page.locator(`a[onclick*="type=${type}"]`).click();
    const table = page.locator(".import-details-container table");
    await expect(table).toBeVisible({ timeout: 15000 });
    // CSV の値に含めた HTML がタグとして解釈されていない（onerror の発火を待たずに DOM で判定する）
    await expect(table.locator("td img")).toHaveCount(0);
    const headers = (await table.locator("thead th").allInnerTexts()).map((s) =>
      s.trim()
    );
    const rows: string[][] = [];
    for (const tr of await table.locator("tr.listViewEntries").all()) {
      rows.push((await tr.locator("td").allInnerTexts()).map((s) => s.trim()));
    }
    await page.locator(".modal-dialog .close").first().click();
    return { headers, rows };
  }

  /** 先頭列(名前)が name の行の最終列(理由)を返す。 */
  function reasonOf(rows: string[][], name: string): string | undefined {
    return rows.find((r) => r[0] === name)?.at(-1);
  }

  async function deleteBy(sn: string, query: string): Promise<void> {
    for (const r of await frQuery(sn, query)) {
      if (r.id) await deleteRecordViaApi(sn, r.id);
    }
  }

  test.beforeAll(async ({ browser, workerStorageState }) => {
    test.setTimeout(90000);
    // 同じ名前の顧客企業は API・画面では重複チェックで作れないため、インポートで作る
    const page = await browser.newPage({ storageState: workerStorageState });
    await runImport(page, {
      module: "Accounts",
      skipDuplicateStep: true,
      csv: `accountname\n${single}\n${dup}\n${dup}\n`,
      mappings: ["accountname"],
    });
    await page.close();
    const prd = await createRecordViaApi("Products", {
      productname: product,
      unit_price: "1000",
      discontinued: "1",
    });
    created.push(prd.wsId);
  });

  test.afterAll(async () => {
    const sn = await apiSession();
    await deleteBy(sn, `SELECT id FROM Potentials WHERE potentialname LIKE '${base}%';`);
    await deleteBy(sn, `SELECT id FROM Calendar WHERE subject LIKE '${base}%';`);
    await deleteBy(sn, `SELECT id FROM SalesOrder WHERE subject LIKE '${base}%';`);
    await deleteBy(sn, `SELECT id FROM Accounts WHERE accountname LIKE '${base}%';`);
    for (const id of created) await deleteRecordViaApi(sn, id);
  });

  test("案件: 失敗とスキップの詳細に理由が表示される", async ({ page }) => {
    test.setTimeout(120000);
    const xss = `"><img src=x onerror=alert(1)>`;
    let dialogs = 0;
    page.on("dialog", (d) => {
      dialogs++;
      void d.dismiss();
    });
    await runImport(page, {
      module: "Potentials",
      skipDuplicateStep: true,
      csv:
        `potentialname,related_to,closingdate,sales_stage\n` +
        `${base}_正常,${single},2026-12-31,Prospecting\n` +
        `${base}_営業ステージ空,${single},2026-12-31,\n` +
        `${base}_選択肢なし,${single},2026-12-31,存在しない段階\n` +
        `${base}_日付不正,${single},日付ではない,Prospecting\n` +
        `${base}_参照複数,${dup},2026-12-31,Prospecting\n` +
        `${base}_XSS,${single},2026-12-31,"${xss.replace(/"/g, '""')}"\n`,
      mappings: ["potentialname", "related_to", "closingdate", "sales_stage"],
    });

    const failed = await openDetails(page, "failed");
    // 既存の列の後ろに「理由」列が1列だけ増える
    expect(failed.headers.at(-1)).toBe("理由");
    expect(failed.headers.slice(0, -1)).toEqual([
      "案件名",
      "顧客企業名",
      "完了予定日",
      "営業ステージ",
    ]);
    expect(failed.rows.length).toBe(4);
    expect(reasonOf(failed.rows, `${base}_営業ステージ空`)).toBe(
      "必須項目「営業ステージ」が未入力です"
    );
    expect(reasonOf(failed.rows, `${base}_選択肢なし`)).toBe(
      "「営業ステージ」の値が選択肢に存在しません"
    );
    expect(reasonOf(failed.rows, `${base}_日付不正`)).toBe(
      "必須項目「完了予定日」が未入力です"
    );
    expect(reasonOf(failed.rows, `${base}_XSS`)).toBe(
      "「営業ステージ」の値が選択肢に存在しません"
    );
    // CSV の値に含めた HTML はタグとして解釈されない
    expect(dialogs).toBe(0);

    const skipped = await openDetails(page, "skipped");
    expect(skipped.rows.length).toBe(1);
    expect(reasonOf(skipped.rows, `${base}_参照複数`)).toBe(
      "「顧客企業名」の値が複数のレコードに一致しました"
    );

    const sn = await apiSession();
    const rows = await frQuery(
      sn,
      `SELECT potentialname FROM Potentials WHERE potentialname LIKE '${base}%';`
    );
    expect(rows.map((r) => r.potentialname)).toEqual([`${base}_正常`]);
  });

  test("案件: 重複でスキップした行に理由が表示される", async ({ page }) => {
    test.setTimeout(90000);
    // 直前のテストで作成された ${base}_正常 と案件名で重複させる
    await runImport(page, {
      module: "Potentials",
      mergeType: "1",
      csv:
        `potentialname,related_to,closingdate,sales_stage\n` +
        `${base}_正常,${single},2026-12-31,Prospecting\n`,
      mappings: ["potentialname", "related_to", "closingdate", "sales_stage"],
    });
    const skipped = await openDetails(page, "skipped");
    expect(reasonOf(skipped.rows, `${base}_正常`)).toBe(
      "重複するレコードが存在するためスキップしました"
    );
  });

  test("活動: 失敗の詳細に理由が表示される", async ({ page }) => {
    test.setTimeout(90000);
    await runImport(page, {
      module: "Calendar",
      skipDuplicateStep: true,
      csv:
        `subject,activitytype,date_start,time_start,due_date,taskstatus\n` +
        `${base}_TODO正常,Call,2026-12-01,10:00,2026-12-01,Not Started\n` +
        `${base}_TODO開始日空,Call,,10:00,2026-12-01,Not Started\n` +
        `${base}_TODO選択肢なし,Call,2026-12-01,10:00,2026-12-01,存在しないステータス\n`,
      mappings: [
        "subject",
        "activitytype",
        "date_start",
        "time_start",
        "due_date",
        "taskstatus",
      ],
    });
    const failed = await openDetails(page, "failed");
    expect(failed.rows.length).toBe(2);
    expect(reasonOf(failed.rows, `${base}_TODO開始日空`)).toBe(
      "必須項目「開始日時」が未入力です"
    );
    expect(reasonOf(failed.rows, `${base}_TODO選択肢なし`)).toBe(
      "「ステータス」の値が選択肢に存在しません"
    );
  });

  test("受注: 明細行の不備で失敗した行に理由が表示される", async ({ page }) => {
    test.setTimeout(90000);
    const head = (name: string) =>
      `${base}_${name},${single},Approved,テスト番地1,テスト番地1,individual`;
    await runImport(page, {
      module: "SalesOrder",
      csv:
        `subject,account_id,sostatus,bill_street,ship_street,hdnTaxType,productid,quantity,listprice\n` +
        `${head("受注正常")},${product},1,1000\n` +
        `${head("受注明細不正")},,1,1000\n` +
        `${head("受注製品なし")},${base}_存在しない製品,1,1000\n`,
      mappings: [
        "subject",
        "account_id",
        "sostatus",
        "bill_street",
        "ship_street",
        "hdnTaxType",
        "productid",
        "quantity",
        "listprice",
      ],
    });
    const failed = await openDetails(page, "failed");
    expect(failed.rows.length).toBe(2);
    expect(reasonOf(failed.rows, `${base}_受注明細不正`)).toBe(
      "有効な明細行がありません（製品・数量・単価を確認してください）"
    );
    expect(reasonOf(failed.rows, `${base}_受注製品なし`)).toBe(
      "明細行の製品が見つからないか、参照する権限がありません"
    );
  });
});
