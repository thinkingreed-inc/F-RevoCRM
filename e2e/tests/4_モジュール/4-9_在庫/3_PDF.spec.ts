import { test, expect } from "../../../fixtures/isolated";
import { seedSpec } from "../../../fixtures/seedSpec";
import { deleteRecordViaApi } from "../../../utils/record";
import { deleteViaDetail, gotoDetail } from "../../../utils/listview";
import { generateRandomString } from "../../../utils/util";
import { readFileSync } from "fs";
import * as li from "../../../utils/lineitem";

/**
 * 在庫系(Invoice/Quotes/SalesOrder/PurchaseOrder)の PDF エクスポート — TEST_COVERAGE P0-2
 *
 * 詳細画面の DETAILVIEW リンク(`Vtiger_DetailView_Model::getDetailViewLinks`)は
 * `vtiger_pdftemplates` の該当モジュール行ごとに「PDFにエクスポート(テンプレート名)」を
 * 生成する(`LBL_EXPORT_TO_PDF`)。リンク先は
 *   index.php?module=<M>&action=ExportPDF&record=<id>&template=<templateId>
 * で、`PDF_helper` が `Content-Disposition: attachment` を返すため
 * `page.waitForEvent("download")` で受け取れる。
 *
 * ベースライン dump には在庫 4 モジュールそれぞれに PDF テンプレートが 1 件ずつ
 * 入っている(`vtiger_pdftemplates`)ので、テンプレート作成は不要。
 *
 * レコードは明細(productid)が必須で Webservice API では作れないため、
 * `utils/lineitem.ts::createInventoryRecord` で UI 経由で 1 件作って使う。
 */

// 単価既知の商品(明細を 1 行だけ載せる)。seed-spec.inventory が唯一の出所。
const productA = seedSpec.inventory.products[0];

/** PDF のマジックナンバー。先頭 5 バイトが "%PDF-" であることを確認する。 */
const PDF_MAGIC = "%PDF-";

for (const cfg of li.INVENTORY_MODULES) {
  test.describe(`在庫PDF: ${cfg.module}`, () => {
    test("詳細画面から PDF をエクスポートできる", async ({ page }) => {
      const suffix = generateRandomString(6);
      let created: li.CreatedInventoryRecord | null = null;

      try {
        created = await li.createInventoryRecord(page, cfg, {
          suffix,
          product: productA,
          qty: 1,
        });

        await gotoDetail(page, cfg.module, created.recordId);

        // PDF リンクは詳細のアクションバー「その他」ドロップダウン内に出る。
        await page.click("text=その他");
        const pdfLink = page
          .locator("a", { hasText: /PDFにエクスポート/ })
          .first();
        await expect(
          pdfLink,
          "PDF テンプレートがある module では PDF エクスポートのリンクが出ること"
        ).toBeVisible();

        // PDF 生成は TCPDF のレンダリングを伴うため待ちを長めに取る。
        const [download] = await Promise.all([
          page.waitForEvent("download", { timeout: 30000 }),
          pdfLink.click(),
        ]);

        expect(download.suggestedFilename()).toMatch(/\.pdf$/i);

        // 中身が本当に PDF であることまで確認する(エラー HTML が
        // attachment で降ってくるケースを検出するため)。
        const filePath = await download.path();
        expect(filePath, "ダウンロードファイルのパスが取れること").toBeTruthy();
        const buf = readFileSync(filePath!);
        expect(buf.length, "PDF が空でないこと").toBeGreaterThan(1000);
        expect(buf.subarray(0, PDF_MAGIC.length).toString("latin1")).toBe(
          PDF_MAGIC
        );
      } finally {
        if (created) {
          await deleteViaDetail(page, cfg.module, created.recordId);
          await deleteRecordViaApi(created.ref.session, created.ref.wsId);
        }
      }
    });
  });
}
