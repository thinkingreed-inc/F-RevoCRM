import { test as setup } from "@playwright/test";
import { login, frgetDescribe, frCount, frCreate } from "./model/fetcher";
import { getFieldValue } from "./utils/field";
import { generateRandomString } from "./utils/util";

/**
 * 関連項目(reference)で「参照される側」になるモジュール。
 * これらは他モジュールのテストが既存レコードを選択するために最低1件必要だが、
 * 自身のCRUDテストの削除で0件になり得るため、テスト開始前にAPIでシードしておく。
 * (CRUDの削除は最新レコードを対象にするため、先に作るシードは生き残る)
 */
const REFERENCEABLE_MODULES = [
  "Accounts",
  "Products",
  "Services",
  "Project",
  "Vendors",
];

setup("seed referenceable records", async () => {
  const response = await login(
    process.env.E2E_USER_NAME || "",
    process.env.E2E_USER_ACCESSKEY || ""
  );
  if (!response) {
    throw new Error("API login failed (seed)");
  }
  const sessionName = response.sessionName;
  const ownerWsId = response.userId;

  for (const moduleName of REFERENCEABLE_MODULES) {
    const count = await frCount(sessionName, moduleName);
    if (count > 0) {
      continue;
    }

    const describe = await frgetDescribe(sessionName, moduleName);
    if (!describe) {
      continue;
    }

    // 必須項目だけを埋めた最小レコードを組み立てる
    const hash = generateRandomString(8);
    const element: Record<string, string> = {};
    for (const field of describe.fields) {
      if (!field.mandatory || field.editable === false) {
        continue;
      }
      if (field.type.name === "owner") {
        element[field.name] = ownerWsId;
        continue;
      }
      if (field.type.name === "reference") {
        // シード対象モジュールに必須の関連項目は無い想定。あればスキップ。
        continue;
      }
      const value = await getFieldValue(
        { moduleName, ...field } as any,
        hash
      );
      element[field.name] = value || `E2Eシード_${hash}`;
    }

    const created = await frCreate(sessionName, moduleName, element);
    if (!created) {
      throw new Error(`シード作成に失敗しました: ${moduleName}`);
    }
  }
});
