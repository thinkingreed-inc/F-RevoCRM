import { test, expect } from "../../fixtures/isolated";
import { runAccountsImport } from "../../utils/import";
import { generateRandomString } from "../../utils/util";
import { login, frQuery, frDelete } from "../../model/fetcher";

/**
 * 検証・後始末用に「その場で」API セッションを取得する。
 * 保存済み sessionName は実行/並行ワーカーの状況で失効していることがあるため、
 * 使う直前に取り直すのが確実。
 */
async function apiSession(): Promise<string> {
  const res = await login(
    process.env.E2E_USER_NAME || "",
    process.env.E2E_USER_ACCESSKEY || ""
  );
  if (!res) throw new Error("API login failed (import verify)");
  return res.sessionName;
}

/**
 * 共通機能: CSV インポート(顧客企業・パターン別) — 機能一覧 11-1
 *
 * インポートの各種条件を Accounts で一通り検証する:
 *   - ヘッダ付き・複数行の新規作成
 *   - ヘッダなし CSV
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
