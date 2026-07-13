import { test, expect } from "../../../fixtures/isolated";
import { seedSpec } from "../../../fixtures/seedSpec";
import { createRecordViaApi, deleteRecordViaApi } from "../../../utils/record";
import { deleteViaDetail } from "../../../utils/listview";
import { generateRandomString } from "../../../utils/util";
import * as li from "../../../utils/lineitem";

/**
 * 在庫系(Invoice/Quotes/SalesOrder/PurchaseOrder)の E2E。
 *
 * 従来これらは「明細(productid)の登録に商品検索オートコンプリートが必要だが本環境で
 * 0 件を返す」ため CRUD 対象外だった(TEST_COVERAGE.md P2)。真因は 名称検索
 * Products_Record_Model::getSearchResult() が discontinued=1(有効)を要求するのに、
 * API 最小シードの商品が discontinued=0 だったこと。拡充ベースライン dump に
 * 有効・価格付きの [E2E-INV] 商品/サービス を焼き込む(seed-spec.inventory)ことで
 * 明細登録が成立し、以下を検証できる:
 *   - CRUD(作成→編集→削除)
 *   - 割引/税/合計の監査(明細の計算ロジック): これが本 spec の主眼。
 *
 * 4 モジュールは実装(layouts/v7/modules/Inventory, Edit.js)を共有するため、
 * 明細ドライバ(utils/lineitem.ts)1 本でパラメータ化する。
 */

// 単価既知の商品(合計/割引の検算に使う)。seed-spec.inventory が唯一の出所。
const productA = seedSpec.inventory.products[0]; // [E2E-INV] 商品A / 単価 1000

/** 通貨端数を許容して数値の一致を確認する(円は整数, 端数設定でも 0.5 以内)。 */
function expectClose(actual: number, expected: number, tol = 0.5): void {
  expect(
    Math.abs(actual - expected),
    `expected ${actual} ≈ ${expected} (±${tol})`
  ).toBeLessThanOrEqual(tol);
}

interface ModuleCfg {
  module: string;
  /** 参照必須項目とその参照先モジュール。 */
  refField: string;
  refModule: string;
  refNameField: string;
  /** 既定が空の必須ピックリスト(保存前に実値を入れる)。 */
  picklists: string[];
}

const MODULES: ModuleCfg[] = [
  { module: "Invoice", refField: "account_id", refModule: "Accounts", refNameField: "accountname", picklists: [] },
  { module: "Quotes", refField: "account_id", refModule: "Accounts", refNameField: "accountname", picklists: ["quotestage"] },
  { module: "SalesOrder", refField: "account_id", refModule: "Accounts", refNameField: "accountname", picklists: ["sostatus", "invoicestatus"] },
  { module: "PurchaseOrder", refField: "vendor_id", refModule: "Vendors", refNameField: "vendorname", picklists: ["postatus"] },
];

for (const cfg of MODULES) {
  test.describe(`在庫: ${cfg.module}`, () => {
    test("CRUD + 割引/税/合計の監査", async ({ page }) => {
      const suffix = generateRandomString(6);
      const refName = `[E2E-INV-REF] ${cfg.refModule} ${suffix}`;
      // 参照レコード(取引先/仕入先)は API で用意する(ヘッダの足場であり検証対象ではない)。
      const ref = await createRecordViaApi(cfg.refModule, {
        [cfg.refNameField]: refName,
      });

      let recordId = "";
      try {
        // === 作成画面 + 明細 ===
        await li.gotoInventoryEdit(page, cfg.module);
        await li.fillInventoryHeader(page, {
          subject: `[E2E-INV] ${cfg.module} ${suffix}`,
          reference: {
            field: cfg.refField,
            recordId: ref.recordId,
            displayName: refName,
          },
          picklists: cfg.picklists,
        });
        await li.fillProductLine(page, {
          searchKey: productA.searchKey,
          qty: 2,
          listPrice: productA.unitPrice,
        });

        const gross = productA.unitPrice * 2; // 1000 * 2 = 2000

        // === 監査 1: 合計計算(割引前) ===
        await test.step("合計計算(割引前)", async () => {
          expectClose(await li.readLineCell(page, "productTotal"), gross);
          const t = await li.readTotals(page);
          expectClose(t.netTotal, gross);
          expectClose(t.preTaxTotal, gross);
          // グループ税モード: 総計 = 税抜合計 + 税額(調整 0)。税が無くても 0 で成立する。
          expectClose(t.grandTotal, t.preTaxTotal + t.taxFinal);
        });

        // === 監査 2: 行割引 10% ===
        await test.step("行割引 10% の反映", async () => {
          await li.setLineDiscount(page, 1, { type: "percentage", value: 10 });
          expectClose(await li.readLineCell(page, "discountTotal"), gross * 0.1); // 200
          expectClose(
            await li.readLineCell(page, "totalAfterDiscount"),
            gross * 0.9
          ); // 1800
          const t = await li.readTotals(page);
          expectClose(t.netTotal, gross * 0.9); // 1800
          expectClose(t.grandTotal, t.preTaxTotal + t.taxFinal);
        });

        // === CRUD: 保存(作成) ===
        await li.saveButton(page).click();
        await page.waitForURL(/[?&]record=\d+/, { timeout: 20000 });
        recordId = page.url().match(/record=(\d+)/)?.[1] ?? "";
        expect(recordId, "作成後に record ID が取得できること").not.toBe("");

        // === CRUD: 編集(数量変更で再計算) ===
        await test.step("編集: 数量変更で再計算", async () => {
          await li.gotoInventoryEditRecord(page, cfg.module, recordId);
          await li.setQty(page, 1, 3);
          expectClose(
            await li.readLineCell(page, "productTotal"),
            productA.unitPrice * 3
          ); // 3000
          await li.saveButton(page).click();
          await page.waitForURL(/[?&]record=\d+/, { timeout: 20000 });
        });
      } finally {
        // === CRUD: 削除 + 後始末 ===
        if (recordId) {
          await deleteViaDetail(page, cfg.module, recordId);
        }
        await deleteRecordViaApi(ref.session, ref.wsId);
      }
    });
  });
}
