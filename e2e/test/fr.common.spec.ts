import { test } from "@playwright/test";
import { FrTest } from "../model/FrTest";
import { readFileSync } from "fs";
import { sessionNameFile } from "../auth.setup";

const moduleMap: Record<string, typeof FrTest> = {
  Accounts: FrTest,
  Contacts: FrTest,
  Potentials: FrTest,
  Leads: FrTest,
  Products: FrTest,
  Assets:FrTest,
  Campaigns:FrTest,
  Dailyreports:FrTest,
  Faq:FrTest,
  HelpDesk:FrTest,
  Invoice:FrTest,
  PriceBooks:FrTest,
  Project:FrTest,
  ProjectMilestone:FrTest,
  ProjectTask:FrTest,
  PurchaseOrder:FrTest,
  Quotes:FrTest,
  SalesOrder:FrTest,
  ServiceContracts:FrTest,
  Services:FrTest,
  Vendors:FrTest,
};

test.beforeAll(async () => {});

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
