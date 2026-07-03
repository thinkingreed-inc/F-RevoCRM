import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listSearch,
  listRows,
  clearListSearch,
  firstRecordId,
  deleteViaDetail,
} from "../../utils/listview";
import { url, generateRandomString } from "../../utils/util";
import { readFileSync } from "fs";
import * as path from "path";

/**
 * 共通機能: CSV インポート(顧客企業) — 機能一覧 11-1
 *
 * Accounts のインポート(view=Import)は 3 ステップ:
 *   1) アップロード(import_file, has_header 既定 ON, UTF-8) → #importStep2(次へ)
 *   2) 重複処理の選択(merge_type 既定「スキップ」) → #uploadAndParse(次へ)
 *   3) 項目マッピング → #importButton(インポート)
 *
 * 【重要】CSV のヘッダ名は項目へ自動マッピングされない(mapped_fields は既定
 * 「オプションの選択」=未割当)。そのため 3) で先頭列を顧客企業名(accountname)へ
 * 明示的にマッピングする必要がある。
 *
 * CSV は最小テンプレート(fixtures/import_accounts.csv)を用い、{{TOKEN}} を
 * 実行毎の一意値へ置換して並行実行でも衝突しないようにする。取り込んだレコードは
 * 後始末で削除する。
 */
const CSV_TEMPLATE = readFileSync(
  path.resolve(__dirname, "../../fixtures/import_accounts.csv"),
  "utf-8"
);

test.describe("共通: CSV インポート", () => {
  test("CSV から顧客企業を 1 件インポートできる(項目マッピング)", async ({
    page,
  }) => {
    const token = generateRandomString(6);
    const name = `E2E_IMPORT_${token}`;
    const csv = CSV_TEMPLATE.replace("{{TOKEN}}", token);

    await page.goto(url("index.php?module=Accounts&view=Import"));
    await page.waitForLoadState("networkidle");

    // ファイル入力はドロップゾーンのスタイルで視覚的に隠れているため、
    // 可視ではなく attached を待って setInputFiles する(隠し input でも動く)。
    const fileInput = page.locator('input[type="file"][name="import_file"]');
    await fileInput.waitFor({ state: "attached", timeout: 20000 });
    await fileInput.setInputFiles({
      name: "import_accounts.csv",
      mimeType: "text/csv",
      buffer: Buffer.from(csv, "utf-8"),
    });

    // 1) → 2) 重複処理 → 3) マッピング(各ステップは AJAX 遷移のため待つ)
    await page.locator("#importStep2").click();
    const next2 = page.locator("#uploadAndParse");
    await next2.waitFor({ state: "visible", timeout: 15000 });
    await next2.click();

    // 3) 先頭列(accountname)を顧客企業名へマッピング
    const mapSelect = page.locator('select[name="mapped_fields"]').first();
    await expect(mapSelect).toBeVisible();
    await mapSelect.selectOption("accountname");

    // インポート実行 → 結果ページ
    await page.locator("#importButton").click();
    await page.waitForLoadState("networkidle");

    // 取り込んだレコードが一覧に現れる
    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    await expect(
      listRows(page).filter({ hasText: name }).first()
    ).toBeVisible();

    // 後始末
    const id = await firstRecordId(page);
    await clearListSearch(page);
    await deleteViaDetail(page, "Accounts", id);
  });
});
