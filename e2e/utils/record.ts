import {
  frgetDescribe,
  frCreate,
  frDelete,
  frQuery,
} from "../model/fetcher";
import { apiSession } from "./api";
import { getStoredUserId } from "../model/session";
import { getFieldValue } from "./field";
import { generateRandomString } from "./util";
import type { FRDescribeFieldsType } from "../model/types/frBase";

/**
 * 詳細画面の固有アクション(メール/変換など)を検証するテスト向けに、対象レコードを
 * Webservice API で用意する共通ヘルパ。
 *
 * UI の新規作成フォームは必須の関連項目(reference)選択モーダル等が不安定なため、
 * 「レコードを用意する」こと自体が目的の固有アクションテストでは API 作成の方が堅牢。
 * 必須項目のみを型に応じて自動で埋め、必須の関連項目は参照先の既存レコードを充てる
 * (参照先が空なら Accounts 等の被参照モジュールが seed 済みである前提)。
 */

export type CreatedRecord = {
  /** 数値の record ID(詳細/編集 URL の record= に使う)。 */
  recordId: string;
  /** Webservice ID(`<prefix>x<id>`。API 削除に使う)。 */
  wsId: string;
  /** 作成に使った API セッション(後始末の削除で使い回す)。 */
  session: string;
};

/**
 * 指定モジュールのレコードを必須項目だけ埋めて API 作成する。
 * @param moduleName 対象モジュール
 * @param overrides 明示的に上書きしたい項目値(項目名→値)
 */
export async function createRecordViaApi(
  moduleName: string,
  overrides: Record<string, string> = {}
): Promise<CreatedRecord> {
  const session = await apiSession();
  const describe = await frgetDescribe(session, moduleName);
  if (!describe) {
    throw new Error(`describe 取得に失敗しました: ${moduleName}`);
  }

  const ownerWsId = getStoredUserId();
  const hash = generateRandomString(8);
  const element: Record<string, string> = {};

  for (const field of describe.fields as FRDescribeFieldsType[]) {
    if (!field.mandatory || field.editable === false) {
      continue;
    }
    if (field.name in overrides) {
      continue; // 上書き指定があるものは後でまとめて入れる
    }
    if (field.type.name === "owner") {
      element[field.name] = ownerWsId;
      continue;
    }
    if (field.type.name === "reference") {
      // 必須の関連項目は参照先の既存レコードを1件充てる
      const refModule = field.type.refersTo?.[0];
      if (refModule) {
        const rows = await frQuery(
          session,
          `SELECT id FROM ${refModule} LIMIT 1;`
        );
        const refId = rows?.[0]?.id;
        if (refId) {
          element[field.name] = refId;
        }
      }
      continue;
    }
    const value = await getFieldValue(
      { moduleName, ...field } as any,
      hash
    );
    element[field.name] = value || `E2E_${hash}`;
  }

  Object.assign(element, overrides);

  const created = await frCreate(session, moduleName, element);
  if (!created) {
    throw new Error(
      `レコード作成に失敗しました: ${moduleName} / ${JSON.stringify(element)}`
    );
  }

  const wsId = created.id; // `<prefix>x<id>`
  const recordId = wsId.split("x")[1];
  return { recordId, wsId, session };
}

/** API 作成したレコードを削除する(後始末用。失敗は握りつぶす)。 */
export async function deleteRecordViaApi(
  session: string,
  wsId: string
): Promise<void> {
  await frDelete(session, wsId).catch(() => {});
}
