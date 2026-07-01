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
 * 共通CRUDの対象外としている。
 *
 * 理由: 保存に必須の明細(productid)を登録するには商品検索オートコンプリートが
 * 必要だが、この環境では機能しない。
 *  - 連番検索(searchRecordsOnSequenceNumber)はSELECTにsetypeが無く
 *    isPermittedが常にfalseになり0件を返す(F-RevoCRM本体側の不具合)。
 *  - 名称検索(getSearchResult)もこの環境では0件。
 * 明細フィールドを直接JSで設定する方法も、F-RevoCRMの明細JSモデルに登録
 * されず合計が0のまま保存ボタンが無効化されるため不可。
 * 商品検索が機能する環境であれば、明細登録処理を追加して対象に戻せる。
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
