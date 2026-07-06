import { test } from "@playwright/test";
import { FrTest } from "../model/FrTest";
import { readFileSync } from "fs";
import { sessionNameFile } from "../utils/util";

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
 * 理由: 保存に必須の明細(productid)を登録するには商品検索オートコンプリートが
 * 必要で、明細を含む作成/編集は汎用ドライバでは表現できない。
 * → 明細を扱う専用ドライバ(utils/lineitem.ts)を用意し、CRUD と 割引/税/合計の監査を
 *    test/module/inventory.spec.ts で検証している。
 *
 * かつて「商品検索が0件で明細登録できない」ためブロックされていたが、真因は
 * 名称検索 Products_Record_Model::getSearchResult() が discontinued=1(有効)を要求する
 * のに、API最小シードの商品が discontinued=0 だったこと。拡充ベースライン dump に
 * 有効・価格付きの [E2E-INV] 商品/サービス を焼き込む(seed-spec.inventory)ことで解消した。
 *
 * 別件(未修正・報告のみ): 連番検索 searchRecordsOnSequenceNumber
 * (modules/Products/models/Module.php)は SELECT に setype を含めず
 * isPermitted($row['setype'],…) を呼ぶため常に0件を返す本体バグがある。名称検索で
 * 明細を引けるため E2E のブロッカーではないが、連番(商品番号)での明細検索は効かない。
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
