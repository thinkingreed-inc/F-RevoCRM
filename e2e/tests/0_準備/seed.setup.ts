import { test as setup } from "@playwright/test";
import { frgetDescribe, frCount, frCreate } from "../../model/fetcher";
import { getStoredSessionName, getStoredUserId } from "../../model/session";
import { getFieldValue } from "../../utils/field";
import { authFile, generateRandomString, url } from "../../utils/util";

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
  // auth.setup が先に取得済みの sessionName / userId を使い回す(再 login しない)。
  const sessionName = getStoredSessionName();
  const ownerWsId = getStoredUserId();

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

/**
 * 版管理（#1964）の E2E 専用モジュール。ほかの spec が使わない契約（ServiceContracts）を
 * 版管理「はい」にし、版に含める関連にドキュメントを選んでおく（3-36_版管理.spec.ts が使う）。
 * 設定の保存は管理者の画面の API（CSRF トークン付き）で行う。何度実行しても同じ状態になる。
 */
const RECORD_VERSION_E2E_MODULE = "ServiceContracts";

setup("enable record versioning for the E2E module", async ({ browser }) => {
  const context = await browser.newContext({ storageState: authFile });
  const page = await context.newPage();
  try {
    await page.goto(url("index.php?module=RecordVersion&parent=Settings&view=Index"));
    const row = page.locator(
      `tr.recordVersionModuleRow[data-module="${RECORD_VERSION_E2E_MODULE}"]`
    );
    const tabid = await row.getAttribute("data-tabid");
    if (!tabid) {
      throw new Error("版管理の設定画面に E2E 用のモジュールがありません");
    }
    // 版に含める関連の候補から、ドキュメントの関連を探す
    await page.goto(
      url(`index.php?module=RecordVersion&parent=Settings&view=EditRelations&tabid=${tabid}`)
    );
    const relationIds = await page
      .locator('input[name="relation_ids[]"]')
      .evaluateAll((inputs) =>
        inputs
          .filter((input) => (input.parentElement?.textContent || "").includes("ドキュメント"))
          .map((input) => (input as HTMLInputElement).value)
      );
    await page.goto(url("index.php?module=RecordVersion&parent=Settings&view=Index"));
    const save = async (params: Record<string, string | string[]>) =>
      page.evaluate(async (params) => {
        const body = new URLSearchParams();
        for (const [key, value] of Object.entries(params)) {
          for (const v of Array.isArray(value) ? value : [value]) {
            body.append(key, v);
          }
        }
        const w = window as unknown as { csrfMagicName: string; csrfMagicToken: string };
        body.append(w.csrfMagicName, w.csrfMagicToken);
        const res = await fetch("index.php", { method: "POST", body, credentials: "same-origin" });
        return res.json();
      }, params);
    const base = { module: "RecordVersion", parent: "Settings", api: "Save", tabid };
    const enabled = await save({ ...base, mode: "module", enabled: "1" });
    if (!enabled || enabled.enabled !== true) {
      throw new Error(`版管理を「はい」にできませんでした: ${JSON.stringify(enabled)}`);
    }
    await save({ ...base, mode: "relations", "relation_ids[]": relationIds });
  } finally {
    await context.close();
  }
});
