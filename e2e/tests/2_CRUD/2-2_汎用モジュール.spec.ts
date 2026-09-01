import { test } from "@playwright/test";
import { FrTest } from "../../model/FrTest";
import { readFileSync } from "fs";
import { sessionNameFile } from "../../utils/util";

const moduleMap: Record<string, typeof FrTest> = {
  Accounts: FrTest,
  Contacts: FrTest,
  Potentials: FrTest,
  Leads: FrTest,
  Products: FrTest,
  Assets: FrTest,
  Campaigns: FrTest,
  Dailyreports: FrTest,
  Faq: FrTest,
  HelpDesk: FrTest,
  PriceBooks: FrTest,
  Project: FrTest,
  ProjectMilestone: FrTest,
  ProjectTask: FrTest,
  ServiceContracts: FrTest,
  Services: FrTest,
  Vendors: FrTest,
};

/**
 * インベントリ系モジュール(Invoice/Quotes/SalesOrder/PurchaseOrder)は
 * この汎用CRUDドライバ(FrTest)の対象外としている。
 *
 * 理由: 明細(productid)を含む作成/編集は汎用ドライバでは表現できない。
 * → 明細を扱う専用ドライバ(utils/lineitem.ts)を用意し、CRUD と 割引/税/合計の監査を
 *    test/module/inventory.spec.ts(商品ポップアップ経路) /
 *    test/module/inventory.lineitem.spec.ts(インライン検索 + 製品追加/サービス追加) で検証している。
 * いずれも 有効(discontinued=1)・価格付き の [E2E-INV] 商品/サービスを dump に焼き込むこと
 * (seed-spec.inventory)が前提(API最小シードは discontinued=0 で検索に出ない)。
 *
 * 補足: 品目名インライン検索は以前 getSearchResult の label 曖昧(ERROR 1052)で全滅していたが
 * main #1704 で修正済み。連番検索 searchRecordsOnSequenceNumber は setype 未SELECT の別バグが
 * 未修正だが、名称検索/ダイアログで明細を引けるため E2E のブロッカーではない。
 */


for (const module of Object.keys(moduleMap)) {
  test.describe.serial(`モジュール: ${module}`, async () => {
    let testModuleModel: FrTest | null;

    test.beforeAll(async () => {
      const ModuleClass = moduleMap[module];
      const sessionName = readFileSync(sessionNameFile, "utf-8");
      testModuleModel = await ModuleClass.init(module, sessionName);
      if (!testModuleModel) {
        console.log("testModuleModel", testModuleModel);
        throw new Error("Module initialization failed");
      }
    });

    test.describe(`レコードへの追加・変更・削除`, async () => {
      test(`レコード新規作成`, async ({ page }) => {
        // ここではすでに初期化されているため、nullチェックは不要
        await testModuleModel!.testRecordCreate(page);
      });

      test("レコード編集", async ({ page }) => {
        // ここではすでに初期化されているため、nullチェックは不要
        await testModuleModel!.testRecordEdit(page);
      });

      test("レコード削除", async ({ page }) => {
        // ここではすでに初期化されているため、nullチェックは不要
        await testModuleModel!.testRecordDelete(page);
      });
    });
  });
}
