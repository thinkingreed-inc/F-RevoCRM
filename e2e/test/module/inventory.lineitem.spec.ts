import { test, expect } from "../../fixtures/isolated";
import { seedSpec } from "../../fixtures/seedSpec";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { deleteViaDetail } from "../../utils/listview";
import { generateRandomString } from "../../utils/util";
import * as li from "../../utils/lineitem";

/**
 * 在庫明細の「品目名インライン検索(オートコンプリート)」と「製品追加 / サービス追加」の E2E。
 *
 * 既存 inventory.spec.ts は 4 モジュールの CRUD + 監査を **商品ポップアップ(箱アイコン)ダイアログ**で
 * 検証している。本 spec はもう一方の入力経路である **インライン検索** と **行追加ボタン** を検証する:
 *   1. 製品追加(#addProduct)      2. サービス追加(#addService)
 *   3. 製品のインライン検索        4. サービスのインライン検索
 * インライン検索は #1704(getSearchResult の label 曖昧)修正後に動作する。明細UIは4モジュール共通
 * のため代表として Invoice で検証する(dump に 有効・単価付き [E2E-INV] 商品/サービスを投入済み)。
 */

const productA = seedSpec.inventory.products[0]; // [E2E-INV] 商品A / 1000
const productB = seedSpec.inventory.products[1]; // [E2E-INV] 商品B / 2500
const serviceA = seedSpec.inventory.services[0]; // [E2E-INV] サービスA / 5000

function expectClose(actual: number, expected: number, tol = 0.5): void {
  expect(
    Math.abs(actual - expected),
    `expected ${actual} ≈ ${expected} (±${tol})`
  ).toBeLessThanOrEqual(tol);
}

test.describe("在庫明細: インライン検索 + 製品追加/サービス追加", () => {
  test("製品インライン検索 / 製品追加 / サービス追加(サービスもインライン検索)", async ({
    page,
  }) => {
    const suffix = generateRandomString(6);
    const refName = `[E2E-INV-REF] Accounts ${suffix}`;
    const ref = await createRecordViaApi("Accounts", { accountname: refName });

    let recordId = "";
    try {
      await li.gotoInventoryEdit(page, "Invoice");
      await li.fillInventoryHeader(page, {
        subject: `[E2E-INV-LI] ${suffix}`,
        reference: {
          field: "account_id",
          recordId: ref.recordId,
          displayName: refName,
        },
        picklists: [],
      });

      // ③ 製品のインライン検索: 事前描画の row1(Products)に 商品A を補完で選択
      await test.step("製品をインライン検索で選択(row1)", async () => {
        await li.fillLineByAutocomplete(page, {
          rowNo: 1,
          searchKey: productA.searchKey,
          qty: 2,
          listPrice: productA.unitPrice,
        });
        expectClose(await li.readLineCell(page, "productTotal", 1), productA.unitPrice * 2);
      });

      // ① 製品追加: #addProduct → 新行(Products)に 商品B をインライン検索
      await test.step("製品追加 → インライン検索(商品B)", async () => {
        const rowB = await li.addLineRow(page, "Products");
        await li.fillLineByAutocomplete(page, {
          rowNo: rowB,
          searchKey: productB.searchKey,
          qty: 1,
          listPrice: productB.unitPrice,
        });
        expectClose(await li.readLineCell(page, "productTotal", rowB), productB.unitPrice);
      });

      // ②④ サービス追加 + サービスのインライン検索: #addService → 新行(Services)に サービスA
      await test.step("サービス追加 → サービスをインライン検索", async () => {
        const rowS = await li.addLineRow(page, "Services");
        await li.fillLineByAutocomplete(page, {
          rowNo: rowS,
          searchKey: serviceA.searchKey,
          qty: 1,
          listPrice: serviceA.unitPrice,
        });
        expectClose(await li.readLineCell(page, "productTotal", rowS), serviceA.unitPrice);
      });

      // 3 行の小計合算 = 1000*2 + 2500 + 5000 = 9500(税抜)
      const gross = productA.unitPrice * 2 + productB.unitPrice + serviceA.unitPrice;
      const t = await li.readTotals(page);
      expectClose(t.netTotal, gross);
      expectClose(t.grandTotal, t.preTaxTotal + t.taxFinal);

      // 保存(CRUD)
      await li.saveButton(page).click();
      await page.waitForURL(/[?&]record=\d+/, { timeout: 20000 });
      recordId = page.url().match(/record=(\d+)/)?.[1] ?? "";
      expect(recordId, "保存後に record ID が取れること").not.toBe("");
    } finally {
      if (recordId) {
        await deleteViaDetail(page, "Invoice", recordId);
      }
      await deleteRecordViaApi(ref.session, ref.wsId);
    }
  });
});
