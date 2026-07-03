import { Page } from "@playwright/test";
import { url } from "./util";

/** 重複処理の種類(merge_type)。1=スキップ, 2=上書き, 3=マージ。 */
export type MergeType = "1" | "2" | "3";

export interface AccountsImportOptions {
  /** アップロードする CSV 本文 */
  csv: string;
  /** ヘッダ行の有無(既定 true) */
  hasHeader?: boolean;
  /** 重複処理の種類(既定 "1"=スキップ)。selected_merge_fields は既定で accountname。 */
  mergeType?: MergeType;
  /** CSV 列順に対応する割当先フィールド(値)。例: ["accountname","phone"] */
  mappings: string[];
}

/**
 * 顧客企業(Accounts)の CSV インポートウィザードを一気通貫で実行する。
 *
 * ウィザードは 3 ステップ:
 *   1) アップロード → #importStep2
 *   2) 重複処理(merge_type) → #uploadAndParse
 *   3) 項目マッピング(列順に明示割当) → #importButton
 *
 * ヘッダは自動マッピングされないため、mappings で列順に割当先を指定する。
 * インポート完了(結果ページ)まで待って戻る。
 */
export async function runAccountsImport(
  page: Page,
  opts: AccountsImportOptions
): Promise<void> {
  const { csv, hasHeader = true, mergeType = "1", mappings } = opts;

  await page.goto(url("index.php?module=Accounts&view=Import"));
  await page.waitForLoadState("networkidle");

  // ファイル入力はドロップゾーンで視覚的に隠れているため attached を待つ。
  const fileInput = page.locator('input[type="file"][name="import_file"]');
  await fileInput.waitFor({ state: "attached", timeout: 20000 });
  await fileInput.setInputFiles({
    name: "import.csv",
    mimeType: "text/csv",
    buffer: Buffer.from(csv, "utf-8"),
  });

  // ヘッダ有無
  const headerBox = page.locator("#has_header");
  if (hasHeader) {
    if (!(await headerBox.isChecked())) await headerBox.check();
  } else {
    if (await headerBox.isChecked()) await headerBox.uncheck();
  }

  // 1) → 2)
  await page.locator("#importStep2").click();
  const next2 = page.locator("#uploadAndParse");
  await next2.waitFor({ state: "visible", timeout: 15000 });

  // 2) 重複処理の種類を選択して進む
  await page.locator('select[name="merge_type"]').selectOption(mergeType);
  await next2.click();

  // 3) マッピング(列順に割当)
  const mapSelects = page.locator('select[name="mapped_fields"]');
  await mapSelects.first().waitFor({ state: "visible", timeout: 15000 });
  for (let i = 0; i < mappings.length; i++) {
    await mapSelects.nth(i).selectOption(mappings[i]);
  }

  // インポート実行 → 結果ページ。複数行の取り込み確定に時間がかかることがある
  // ため、結果ページの遷移(networkidle)後に確定待ちの猶予を置く。
  await page.locator("#importButton").click();
  await page.waitForLoadState("networkidle");
  await page.waitForTimeout(2500);
}
