import { test, expect } from "../../fixtures/isolated";
import { seedSpec } from "../../fixtures/seedSpec";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { gotoDetail, deleteViaDetail } from "../../utils/listview";
import { url, generateRandomString } from "../../utils/util";
import { apiSession } from "../../utils/api";
import { frQuery } from "../../model/fetcher";

/**
 * 価格表(PriceBooks) — TEST_COVERAGE P0-B / B2
 *
 * `modules/PriceBooks/actions/Save.php` は `saveRecord` を override しており、
 * **関連リストからの追加(relationOperation)** のときだけ次の固有処理を行う:
 *   1. 親(sourceModule / sourceRecord)と作成した価格表を関連付ける
 *   2. 親が Products / Services の場合、`updateListPrice()` で
 *      **価格表の一覧価格(vtiger_pricebookproductrel.listprice)を
 *      親の単価(unit_price)で初期化する**
 *
 * 通常の新規作成では通らないコードパスで、かつ 3_共通機能 の関連追加テストは
 * Accounts → Contacts 固定のため、この個別実装は誰も通していなかった
 * (2026-08-26 の「モジュール個別実装」棚卸しで判明)。
 *
 * 【なぜ関連一覧の「追加」ボタンを押す形にしていないか】
 * 現行 UI の関連一覧「追加」は React ダイアログを開き、保存は
 * `modules/Vtiger/apis/Save.php`(`Vtiger_Save_Api`)を通る。これは
 * `PriceBooks_Save_Action`(`Vtiger_Save_Action` 系)とは別クラスなので、
 * React 経由では本 spec が検証したい固有実装を通らない。
 * そのため relationOperation 付きの Edit 画面を直接開いて検証する。
 *
 * 【listprice の確認方法】
 * listprice は Webservice の項目ではないので API では引けない。
 * 製品詳細の価格表関連一覧は行ごとに
 *   <a class="editListPrice" data-list-price="…" data-related-recordid="…">
 * を出力する(layouts/v7/modules/Vtiger/RelatedList.tpl)ので、
 * この属性値を listprice の実測値として使う。
 */

const productA = seedSpec.inventory.products[0]; // [E2E-INV] 商品A / 単価 1000

/** "1,000.00" → 1000 のように通貨表記を数値化する。 */
function toNumber(text: string | null): number {
  return Number(String(text ?? "").replace(/[^\d.-]/g, ""));
}

test.describe("価格表(PriceBooks): 製品の関連からの追加", () => {
  test("製品の関連から価格表を追加すると、関連付けと一覧価格の初期化が行われる", async ({
    page,
  }) => {
    test.setTimeout(120000);
    const token = generateRandomString(6);
    const bookName = `E2E価格表${token}`;
    let productId = "";
    let productRef: Awaited<ReturnType<typeof createRecordViaApi>> | null = null;
    let bookId = "";

    try {
      // 単価が既知の製品を用意する。seed の [E2E-INV] 商品A を API で引き当て、
      // 無ければ単価付きの製品を作る(単価は listprice の期待値になるので必須)。
      const session = await apiSession();
      const found = await frQuery(
        session,
        `SELECT id, unit_price FROM Products WHERE productname = '${productA.name}' LIMIT 1;`
      );
      if (found?.[0]?.id) {
        productId = String(found[0].id).split("x")[1];
      } else {
        productRef = await createRecordViaApi("Products", {
          productname: `E2E製品${token}`,
          unit_price: String(productA.unitPrice),
        });
        productId = productRef.recordId;
      }
      expect(productId, "対象製品の record id が取れること").not.toBe("");

      // === 親製品を source にした relationOperation 経路で価格表を作成する ===
      await page.goto(
        url(
          `index.php?module=PriceBooks&view=Edit&relationOperation=true` +
            `&sourceModule=Products&sourceRecord=${productId}&app=INVENTORY`
        )
      );
      await expect(
        page.locator('input[name="bookname"]'),
        "価格表の編集フォームが開くこと"
      ).toBeVisible({ timeout: 20000 });

      // 必須項目(価格表名 / 通貨)を埋めて保存する。通貨は既定で入るが、
      // 空の場合に備えて先頭の実値を選ぶ。
      await page.locator('input[name="bookname"]').fill(bookName);
      await page.evaluate(() => {
        const sel = document.querySelector(
          'select[name="currency_id"]'
        ) as HTMLSelectElement | null;
        if (!sel || sel.value) return;
        const opt = Array.from(sel.options).find((o) => o.value !== "");
        if (opt) {
          sel.value = opt.value;
          sel.dispatchEvent(new Event("change", { bubbles: true }));
        }
      });
      await page.locator("button.saveButton").first().click();
      // relationOperation の保存後は **親(製品)の詳細** に戻る(価格表の詳細ではない)
      await page.waitForURL(/module=Products.*view=Detail/, { timeout: 30000 });
      await page.waitForLoadState("networkidle");

      // === 製品詳細の価格表関連タブを開く ===
      const tab = page.locator('li.tab-item[data-module="PriceBooks"]').first();
      await tab.locator("a").first().click();
      await expect(tab).toHaveClass(/active/, { timeout: 20000 });

      // 検証 1: 作成した価格表が製品に関連付いていること
      const row = page
        .locator("tr.listViewEntries")
        .filter({ hasText: bookName })
        .first();
      await expect(
        row,
        "作成した価格表が製品の関連一覧に並ぶこと(addRelation)"
      ).toBeVisible({ timeout: 20000 });

      // 検証 2: 一覧価格が製品の単価で初期化されていること(updateListPrice)
      const priceLink = row.locator("a.editListPrice").first();
      await expect(
        priceLink,
        "一覧価格の編集導線があること(listprice のレコードが存在する)"
      ).toBeAttached({ timeout: 20000 });
      const listPrice = toNumber(await priceLink.getAttribute("data-list-price"));
      expect(
        listPrice,
        "一覧価格が製品の単価で初期化されること(updateListPrice)"
      ).toBe(productA.unitPrice);

      // 後始末用に価格表の record id を取る
      bookId = (await priceLink.getAttribute("data-related-recordid")) ?? "";
    } finally {
      if (bookId) await deleteViaDetail(page, "PriceBooks", bookId);
      if (productRef) {
        await deleteRecordViaApi(productRef.session, productRef.wsId);
      }
    }
  });
});
