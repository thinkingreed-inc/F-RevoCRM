import { Page } from "@playwright/test";
import * as fs from "fs";
import * as os from "os";
import * as path from "path";
import { url } from "./util";

/**
 * インポートは 1 ユーザーにつき同時実行不可(サーバ側ロック)。マトリクスは
 * モジュールごとの describe を複数ワーカーで並列実行するため、同一 admin ユーザーの
 * インポートが衝突し「0 件作成/マッピング画面に到達しない」等の不整合を起こす。
 * ワーカー横断のファイルロックでインポートウィザードを直列化する(stale 検知付き)。
 */
const IMPORT_LOCK_FILE = path.join(os.tmpdir(), "frevo-e2e-import.lock");
const IMPORT_LOCK_STALE_MS = 5 * 60 * 1000;
const IMPORT_LOCK_WAIT_MS = 4 * 60 * 1000;

/** インポートウィザードをワーカー横断で直列実行する(取得待ち→実行→解放)。 */
export async function withImportLock<T>(fn: () => Promise<T>): Promise<T> {
  const deadline = Date.now() + IMPORT_LOCK_WAIT_MS;
  for (;;) {
    try {
      const fd = fs.openSync(IMPORT_LOCK_FILE, "wx");
      fs.writeSync(fd, String(Date.now()));
      fs.closeSync(fd);
      break;
    } catch {
      // 既にロック中。stale(クラッシュで残置)なら奪取して継続。
      try {
        const st = fs.statSync(IMPORT_LOCK_FILE);
        if (Date.now() - st.mtimeMs > IMPORT_LOCK_STALE_MS) {
          fs.rmSync(IMPORT_LOCK_FILE, { force: true });
          continue;
        }
      } catch {
        // 直前に解放された(race)。即リトライ。
      }
      if (Date.now() > deadline) {
        throw new Error("インポートロックの取得がタイムアウトしました");
      }
      await new Promise((r) => setTimeout(r, 400 + Math.random() * 600));
    }
  }
  try {
    return await fn();
  } finally {
    fs.rmSync(IMPORT_LOCK_FILE, { force: true });
  }
}

/** 重複処理の種類(merge_type)。1=スキップ, 2=上書き, 3=マージ。 */
export type MergeType = "1" | "2" | "3";

/**
 * 重複処理(merge_type)ステップを持たないモジュール。
 * `modules/Vtiger/views/Import.php::getUnsupportedDuplicateHandlingModules()`
 * = array('PriceBooks','Users') + getInventoryModules()(Invoice/Quotes/SalesOrder/PurchaseOrder)。
 * これらは step2(merge_type 選択)が描画されず、step1 の #skipDuplicateMerge
 * (uploadAndParse('0'))でマッピング(step3)へ直行する。
 */
const UNSUPPORTED_DUPLICATE_HANDLING_MODULES = new Set<string>([
  "PriceBooks",
  "Users",
  "Invoice",
  "Quotes",
  "SalesOrder",
  "PurchaseOrder",
]);

/** 重複処理(merge_type)ステップを持たないモジュールか。 */
export function isDuplicateHandlingUnsupported(moduleName: string): boolean {
  return UNSUPPORTED_DUPLICATE_HANDLING_MODULES.has(moduleName);
}

export interface ImportOptions {
  /** インポート対象モジュール(例: "Accounts", "Contacts")。 */
  module: string;
  /** アップロードする CSV 本文 */
  csv: string;
  /** ヘッダ行の有無(既定 true) */
  hasHeader?: boolean;
  /**
   * 重複処理の種類(既定 "1"=スキップ)。selected_merge_fields は既定で名前列。
   * 重複処理非対応モジュール(インベントリ/PriceBooks/Users)では無視される。
   */
  mergeType?: MergeType;
  /** CSV 列順に対応する割当先フィールド(値)。例: ["accountname","phone"] */
  mappings: string[];
  /**
   * 重複処理ステップを「この手順をスキップ」でスキップする(既定 false)。
   * 重複が起きない一意レコードの新規作成では merge_type/突合項目の選択が不要なうえ、
   * 既定の突合項目が無いモジュール(HelpDesk 等)では merge_type の「次へ」が
   * 突合項目未選択で進めないため、スキップして step3(マッピング)へ直行する。
   * 重複処理そのものを検証する 3-23 は false のまま merge_type 経路を使う。
   */
  skipDuplicateStep?: boolean;
}

/** 後方互換用: module を除いた Accounts 専用オプション。 */
export type AccountsImportOptions = Omit<ImportOptions, "module">;

/**
 * 任意モジュールの CSV インポートウィザードを一気通貫で実行する。
 *
 * ウィザードは 3 ステップ:
 *   1) アップロード
 *      - 重複処理対応モジュール   → #importStep2 で step2 へ
 *      - 重複処理非対応モジュール → #skipDuplicateMerge(uploadAndParse('0'))で step3 へ直行
 *   2) 重複処理(merge_type) → #uploadAndParse  ※対応モジュールのみ
 *   3) 項目マッピング(列順に明示割当) → #importButton
 *
 * ヘッダは自動マッピングされないため、mappings で列順に割当先を指定する。
 * インポート完了(結果ページ)まで待って戻る。
 *
 * インポートはユーザー単位でサーバ側直列のため、ウィザード全体を withImportLock で
 * ワーカー横断に直列化する(全呼び出し元 = マトリクス / 3-23 / 3-24 が並列実行でも衝突しない)。
 */
export async function runImport(page: Page, opts: ImportOptions): Promise<void> {
  const {
    module,
    csv,
    hasHeader = true,
    mergeType = "1",
    mappings,
    skipDuplicateStep = false,
  } = opts;

  await withImportLock(async () => {
  await page.goto(url(`index.php?module=${module}&view=Import`));
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

  if (isDuplicateHandlingUnsupported(module)) {
    // 重複処理ステップ無し: step1 の #skipDuplicateMerge で step3(マッピング)へ直行。
    // step2 用の隠し #skipDuplicateMerge も DOM に存在するため step1 側にスコープする。
    await page
      .locator("#importStepOneButtonsDiv #skipDuplicateMerge")
      .click();
  } else if (skipDuplicateStep) {
    // 重複処理対応だが「この手順をスキップ」で step3 へ直行する。
    // step1 の #importStep2 で step2 を出し、step2 側の #skipDuplicateMerge を押す
    // (merge_type/突合項目の選択が不要になる)。
    await page.locator("#importStep2").click();
    const skipBtn = page.locator("#importStepTwoButtonsDiv #skipDuplicateMerge");
    await skipBtn.waitFor({ state: "visible", timeout: 15000 });
    await skipBtn.click();
  } else {
    // 1) → 2)
    await page.locator("#importStep2").click();
    const next2 = page.locator("#uploadAndParse");
    await next2.waitFor({ state: "visible", timeout: 15000 });
    // 2) 重複処理の種類を選択して進む
    await page.locator('select[name="merge_type"]').selectOption(mergeType);
    await next2.click();
  }

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
  });
}

/**
 * 顧客企業(Accounts)の CSV インポートを実行する後方互換ラッパ。
 * 既存の共通機能 spec(3-23/3-24)から利用される。実体は {@link runImport}。
 */
export async function runAccountsImport(
  page: Page,
  opts: AccountsImportOptions
): Promise<void> {
  return runImport(page, { module: "Accounts", ...opts });
}
