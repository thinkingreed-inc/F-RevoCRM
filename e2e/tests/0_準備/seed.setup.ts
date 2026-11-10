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
 * 通知ブロック（#1965）の E2E 専用モジュール。発注先（Vendors。CI のほかの spec は参照用のレコードに
 * 使うだけで、作成・編集画面を操作しない）に通知ブロックを置き、「作成時に送信」を「はい」にする
 * （3-37_通知ブロック.spec.ts が使う）。通知先を選ばない保存では何も送らないので、ほかの spec には影響しない。
 * 設定は管理者の項目設定の画面から（CSRF トークン付き）行う。何度実行しても同じ状態になる。
 */
const NOTIFICATION_BLOCK_E2E_MODULE = "Vendors";

setup("add a notification block to the E2E module", async ({ browser }) => {
  const context = await browser.newContext({ storageState: authFile });
  const page = await context.newPage();
  try {
    const layoutUrl = url(
      `index.php?module=LayoutEditor&parent=Settings&view=Index&sourceModule=${NOTIFICATION_BLOCK_E2E_MODULE}`
    );
    await page.goto(layoutUrl);
    await page.locator("#moduleBlocks").waitFor();
    const post = async (params: Record<string, string>) =>
      page.evaluate(async (params) => {
        const body = new URLSearchParams(params);
        const w = window as unknown as { csrfMagicName: string; csrfMagicToken: string };
        body.append(w.csrfMagicName, w.csrfMagicToken);
        const res = await fetch("index.php", { method: "POST", body, credentials: "same-origin" });
        return res.json();
      }, params);

    const notificationBlock = page.locator('div.editFieldsTable[data-notification-block="1"]');
    if ((await notificationBlock.count()) === 0) {
      const added = await post({
        module: "LayoutEditor",
        parent: "Settings",
        action: "Block",
        mode: "save",
        sourceModule: NOTIFICATION_BLOCK_E2E_MODULE,
        blockType: "notification",
        sendOnCreate: "1",
      });
      if (!added || added.success !== true) {
        throw new Error(`通知ブロックを追加できませんでした: ${JSON.stringify(added)}`);
      }
      await page.goto(layoutUrl);
      await notificationBlock.waitFor();
    }
    const tabid = await notificationBlock
      .locator('input[name="notificationSendOnCreate"]')
      .getAttribute("data-tabid");
    const saved = await post({
      module: "NotificationBlock",
      parent: "Settings",
      api: "SaveSendOnCreate",
      tabid: tabid || "",
      enabled: "1",
    });
    if (!saved || saved.enabled !== true) {
      throw new Error(`「作成時に送信」を「はい」にできませんでした: ${JSON.stringify(saved)}`);
    }
  } finally {
    await context.close();
  }
});
