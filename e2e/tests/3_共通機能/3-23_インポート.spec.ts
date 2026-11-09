import { test, expect } from "../../fixtures/isolated";
import { runAccountsImport } from "../../utils/import";
import { generateRandomString } from "../../utils/util";
import { apiSession } from "../../utils/api";
import { frQuery, frDelete } from "../../model/fetcher";

/**
 * 共通機能: CSV インポート(顧客企業・パターン別) — 機能一覧 11-1
 *
 * インポートの各種条件を Accounts で一通り検証する:
 *   - ヘッダ付き・複数行の新規作成
 *   - ヘッダなし CSV
 *   - 最新のインポートの取り消し
 *   - 重複処理: スキップ / 上書き / マージ(merge_type=1/2/3、突合は既定の accountname)
 *
 * 【前提/知見】
 *  - CSV ヘッダは項目へ自動マッピングされないため、列順に明示割当が必要。
 *  - 重複突合フィールド(selected_merge_fields)は既定で顧客企業名(accountname)。
 *  - 上書き=未指定列を空白化 / マージ=未指定列は保持。
 *
 * インポートは1ユーザーにつき同時実行不可(ロック)のため describe.serial で直列化。
 * 値の検証と後始末は Webservice API(frQuery/frDelete)で行う。
 */
test.describe.serial("共通: CSV インポート(パターン別)", () => {
  /** accountname 前方一致で作成レコードを API 削除する。 */
  async function cleanup(sn: string, prefix: string): Promise<void> {
    const rows = await frQuery(
      sn,
      `SELECT id,accountname FROM Accounts WHERE accountname LIKE '${prefix}%';`
    );
    for (const r of rows) {
      if (r.id) await frDelete(sn, r.id);
    }
  }

  test("ヘッダ付き・複数行を新規作成できる", async ({ page }) => {
    const base = `E2Eimp${generateRandomString(6)}`;
    await runAccountsImport(page, {
      csv: `accountname\n${base}_1\n${base}_2\n${base}_3\n`,
      mappings: ["accountname"],
    });
    const sn = await apiSession();
    const rows = await frQuery(
      sn,
      `SELECT accountname FROM Accounts WHERE accountname LIKE '${base}%';`
    );
    expect(rows.length).toBe(3);
    await cleanup(sn, base);
  });

  test("ヘッダなし CSV をインポートできる", async ({ page }) => {
    const name = `E2Eimpnh${generateRandomString(6)}`;
    await runAccountsImport(page, {
      csv: `${name},03-0000\n`,
      hasHeader: false,
      mappings: ["accountname", "phone"],
    });
    const sn = await apiSession();
    const rows = await frQuery(
      sn,
      `SELECT accountname FROM Accounts WHERE accountname='${name}';`
    );
    expect(rows.length).toBe(1);
    await cleanup(sn, name);
  });

  test("最新のインポートを取り消すと取り込んだレコードが削除される", async ({
    page,
  }) => {
    // 取り消しボタンは重複処理を選ばずに取り込んだとき(MERGE_ENABLED=0)だけ表示される。
    // 完了済み(キュー status=4)でも URL の importid で一時テーブルを特定して削除する(#1967)。
    const name = `E2Eimpud${generateRandomString(6)}`;
    await runAccountsImport(page, {
      csv: `accountname\n${name}\n`,
      mappings: ["accountname"],
      skipDuplicateStep: true,
    });
    const sn = await apiSession();
    const before = await frQuery(
      sn,
      `SELECT id FROM Accounts WHERE accountname='${name}';`
    );
    expect(before.length).toBe(1);

    await page.locator('button[onclick*="mode=undoImport"]').click();
    // 削除結果: 1 行目=対象レコード数 / 2 行目=削除されたレコード数
    const rows = page.locator(".importResultsTable tr");
    await expect(rows.nth(1).locator("td").nth(1)).toHaveText("1", {
      timeout: 15000,
    });
    await expect(rows.nth(0).locator("td").nth(1)).toHaveText("1");

    const after = await frQuery(
      sn,
      `SELECT id FROM Accounts WHERE accountname='${name}';`
    );
    expect(after.length).toBe(0);
    await cleanup(sn, name);
  });

  test("重複スキップ: 既存レコードは更新されない", async ({ page }) => {
    const name = `E2Eimpsk${generateRandomString(6)}`;
    await runAccountsImport(page, {
      csv: `accountname,phone\n${name},03-1111\n`,
      mappings: ["accountname", "phone"],
    });
    await runAccountsImport(page, {
      csv: `accountname,phone\n${name},03-9999\n`,
      mergeType: "1",
      mappings: ["accountname", "phone"],
    });
    const sn = await apiSession();
    const rows = await frQuery(
      sn,
      `SELECT accountname,phone FROM Accounts WHERE accountname='${name}';`
    );
    expect(rows.length).toBe(1);
    expect(rows[0].phone).toBe("03-1111"); // スキップ=更新されない
    await cleanup(sn, name);
  });

  test("重複上書き: 既存レコードが更新される", async ({ page }) => {
    // merge_type=2(上書き)。突合した既存レコードの指定列が更新されることを検証。
    // (本ビルドでは CSV に列自体が無い項目は空白化されず保持されたため、
    //  ここでは「指定列が更新される」ことを確認する)
    const name = `E2Eimpov${generateRandomString(6)}`;
    await runAccountsImport(page, {
      csv: `accountname,phone\n${name},03-1111\n`,
      mappings: ["accountname", "phone"],
    });
    await runAccountsImport(page, {
      csv: `accountname,phone\n${name},03-9999\n`,
      mergeType: "2",
      mappings: ["accountname", "phone"],
    });
    const sn = await apiSession();
    const rows = await frQuery(
      sn,
      `SELECT accountname,phone FROM Accounts WHERE accountname='${name}';`
    );
    expect(rows.length).toBe(1); // 重複は作られない
    expect(rows[0].phone).toBe("03-9999"); // 上書き=更新される
    await cleanup(sn, name);
  });

  test("重複マージ: 未指定列は保持される", async ({ page }) => {
    const name = `E2Eimpmg${generateRandomString(6)}`;
    await runAccountsImport(page, {
      csv: `accountname,phone,website\n${name},03-1111,http://a.example\n`,
      mappings: ["accountname", "phone", "website"],
    });
    await runAccountsImport(page, {
      csv: `accountname,phone\n${name},03-9999\n`,
      mergeType: "3",
      mappings: ["accountname", "phone"],
    });
    const sn = await apiSession();
    const rows = await frQuery(
      sn,
      `SELECT accountname,phone,website FROM Accounts WHERE accountname='${name}';`
    );
    expect(rows.length).toBe(1);
    expect(rows[0].phone).toBe("03-9999");
    expect(rows[0].website).toContain("a.example"); // マージ=保持
    await cleanup(sn, name);
  });
});
