import { test, expect } from "../../../fixtures/isolated";
import { seedSpec } from "../../../fixtures/seedSpec";
import { createRecordViaApi, deleteRecordViaApi } from "../../../utils/record";
import { deleteViaDetail, gotoDetail } from "../../../utils/listview";
import { generateRandomString } from "../../../utils/util";
import { apiSession } from "../../../utils/api";
import { frQuery } from "../../../model/fetcher";
import * as li from "../../../utils/lineitem";

/**
 * 在庫系の相互生成(見積 → 請求 / 受注 / 発注) — TEST_COVERAGE P0-3
 *
 * 販売フローの本体だが E2E が無かった領域。
 * `Quotes_Record_Model` の `getCreateInvoiceUrl` / `getCreateSalesOrderUrl` /
 * `getCreatePurchaseOrderUrl` が詳細画面の DETAILVIEW リンク
 * (ラベル「生成 <モジュール単数名>」)として出力され、遷移先は
 *   index.php?module=<生成先>&view=Edit&quote_id=<見積ID>
 * で、見積の明細とヘッダが引き継がれた編集画面が開く。
 *
 * 検証の要点は「引き継ぎ」: 生成画面に見積の明細(商品行)が載っていること、
 * そして保存後のレコードに明細が実在すること(API で確認)。
 *
 * 発注(PurchaseOrder)は必須の参照が仕入先(vendor_id)で見積からは引き継がれないため、
 * テスト側で API 作成した仕入先を設定してから保存する。
 */

const productA = seedSpec.inventory.products[0];

/** 見積を作る際の設定(在庫モジュール表から取得)。 */
const QUOTES = li.INVENTORY_MODULES.find((m) => m.module === "Quotes")!;

interface TargetCfg {
  /** 生成先モジュール */
  module: string;
  /** 保存前に実値を入れる必須ピックリスト */
  picklists: string[];
  /** 見積から引き継がれない必須参照(発注のみ) */
  reference?: { field: string; module: string; nameField: string };
  /**
   * 明細の金額(定価)まで引き継がれるか。
   * 発注は「定価 = 仕入原価」で評価される仕様のため、商品行は引き継がれても
   * 単価が 0 になる(既存 spec 1_在庫 でも PO だけ定価を明示設定している)。
   */
  inheritsPrice: boolean;
}

const TARGETS: TargetCfg[] = [
  { module: "Invoice", picklists: [], inheritsPrice: true },
  {
    module: "SalesOrder",
    picklists: ["sostatus", "invoicestatus"],
    inheritsPrice: true,
  },
  {
    module: "PurchaseOrder",
    picklists: ["postatus"],
    reference: { field: "vendor_id", module: "Vendors", nameField: "vendorname" },
    inheritsPrice: false,
  },
];

for (const target of TARGETS) {
  test.describe(`在庫の相互生成: 見積 → ${target.module}`, () => {
    test("明細を引き継いで生成・保存できる", async ({ page }) => {
      const suffix = generateRandomString(6);
      let quote: li.CreatedInventoryRecord | null = null;
      let generatedId = "";
      let extraRef: Awaited<ReturnType<typeof createRecordViaApi>> | null = null;

      try {
        // === 元になる見積(明細 1 行) ===
        quote = await li.createInventoryRecord(page, QUOTES, {
          suffix,
          product: productA,
          qty: 2,
        });

        // === 詳細の「生成 …」リンクから生成画面へ ===
        await gotoDetail(page, "Quotes", quote.recordId);
        await page.click("text=その他");
        const generateLink = page
          .locator(
            `a[href*="module=${target.module}"][href*="quote_id=${quote.recordId}"]`
          )
          .first();
        await expect(
          generateLink,
          `見積の詳細に ${target.module} の生成リンクが出ること`
        ).toBeVisible();
        await generateLink.click();

        // === 明細が引き継がれていること(本 spec の主眼) ===
        await expect(page.locator("#productName1")).toHaveValue(
          new RegExp(productA.searchKey)
        );
        expect(
          await page.locator("#hdnProductId1").inputValue(),
          "引き継いだ明細に商品 ID が入っていること"
        ).not.toBe("");
        if (target.inheritsPrice) {
          // 数量・金額も引き継がれる
          expect(await li.readLineCell(page, "productTotal")).toBeGreaterThan(0);
        } else {
          // 発注は定価 = 仕入原価(0)で入るため、保存できるよう定価を明示設定する
          const listPrice = page.locator("#listPrice1");
          await listPrice.fill(String(productA.unitPrice));
          await listPrice.blur();
          expect(await li.readLineCell(page, "productTotal")).toBeGreaterThan(0);
        }

        // === 生成先固有の必須項目を埋めて保存 ===
        if (target.reference) {
          const refName = `[E2E-GEN-REF] ${target.reference.module} ${suffix}`;
          extraRef = await createRecordViaApi(target.reference.module, {
            [target.reference.nameField]: refName,
          });
          await li.setReference(
            page,
            target.reference.field,
            extraRef.recordId,
            refName
          );
        }
        for (const name of target.picklists) {
          await li.setPicklistFirstValue(page, name);
        }

        await li.saveButton(page).click();
        await page.waitForURL(/[?&]record=\d+/, { timeout: 20000 });
        generatedId = page.url().match(/record=(\d+)/)?.[1] ?? "";
        expect(generatedId, "生成レコードが保存できること").not.toBe("");

        // === 保存されたレコードに明細が実在すること(API で確認) ===
        const session = await apiSession();
        const idRows = await frQuery(
          session,
          `SELECT id FROM ${target.module} WHERE subject = '${quote.subject}' LIMIT 1;`
        );
        const wsId = idRows?.[0]?.id;
        expect(wsId, "生成レコードが API から引けること").toBeTruthy();
        const rows = await frQuery(
          session,
          `SELECT hdnGrandTotal FROM ${target.module} WHERE id = '${wsId}';`
        );
        // 明細が載っていれば総計は 0 より大きい(単価 1000 × 数量 2)
        expect(
          Number(rows?.[0]?.hdnGrandTotal ?? 0),
          "生成レコードの総計が明細から算出されていること"
        ).toBeGreaterThan(0);
      } finally {
        if (generatedId) {
          await deleteViaDetail(page, target.module, generatedId);
        }
        if (quote) {
          await deleteViaDetail(page, "Quotes", quote.recordId);
          await deleteRecordViaApi(quote.ref.session, quote.ref.wsId);
        }
        if (extraRef) {
          await deleteRecordViaApi(extraRef.session, extraRef.wsId);
        }
      }
    });
  });
}
