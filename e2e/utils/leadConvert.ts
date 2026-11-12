import * as fs from "fs";
import * as os from "os";
import * as path from "path";
import { expect, type Locator, type Page } from "@playwright/test";
import { gotoDetail } from "./listview";
import { frDelete, frQuery } from "../model/fetcher";

/**
 * リード昇格（Web コンポーネント <lead-convert>）と、その設定
 * （<lead-mapping-settings> / Settings_Leads_ConvertSettings_Api）のテスト用ヘルパ。
 *
 * 昇格画面の設定（表示項目・昇格時デフォルト値・判定条件）は CRM 全体で 1 つの共有状態。
 * 設定を読み書きする処理は withConvertSettingsLock で直列化し、並列のワーカー同士で
 * 「読んでから保存する」間に他方の変更を消さないようにする。
 */

export type ConvertModule = "Accounts" | "Contacts" | "Potentials";

/** 設定 API（mode=info）の応答のうちテストで使う部分 */
export type ConvertSettingsInfo = {
  modules: Record<
    ConvertModule,
    {
      label: string;
      inputFields: { name: string; label: string; mandatory: boolean }[];
      matchableFields: { name: string; label: string; mandatory: boolean }[];
      layout: string[];
      defaults: Record<string, string>;
      matchRules: string[][];
    }
  >;
};

const LOCK_DIR = path.join(os.tmpdir(), "frevo-e2e-lead-convert-settings.lock");
const STALE_MS = 120_000;
const ACQUIRE_TIMEOUT_MS = 180_000;

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

/** 昇格画面の設定を読み書きする処理をロックで包んで実行する */
export async function withConvertSettingsLock<T>(
  fn: () => Promise<T>
): Promise<T> {
  const startedAt = Date.now();
  for (;;) {
    try {
      fs.mkdirSync(LOCK_DIR);
      break;
    } catch {
      try {
        if (Date.now() - fs.statSync(LOCK_DIR).mtimeMs > STALE_MS) {
          fs.rmSync(LOCK_DIR, { recursive: true, force: true });
          continue;
        }
      } catch {
        // 待っている間に解放された場合は取り直す
      }
      if (Date.now() - startedAt > ACQUIRE_TIMEOUT_MS) {
        throw new Error(`昇格設定のロックを取得できませんでした: ${LOCK_DIR}`);
      }
      await sleep(200);
    }
  }
  try {
    return await fn();
  } finally {
    fs.rmSync(LOCK_DIR, { recursive: true, force: true });
  }
}

/**
 * 設定 API を呼ぶ。ログイン済みの CRM 画面上で、画面と同じ CSRF トークンを付けて送る
 * （page は CRM のいずれかの画面を開いていること）。
 */
async function callSettingsApi<T>(
  page: Page,
  mode: string,
  params: Record<string, string> = {}
): Promise<T> {
  return page.evaluate(
    async ({ mode, params }) => {
      const body = new URLSearchParams({
        module: "Leads",
        parent: "Settings",
        api: "ConvertSettings",
        mode,
        ...params,
      });
      const w = window as unknown as { csrfMagicName?: string; csrfMagicToken?: string };
      if (w.csrfMagicName && w.csrfMagicToken) {
        body.append(w.csrfMagicName, w.csrfMagicToken);
      }
      const res =
        mode === "info"
          ? await fetch(`index.php?${body.toString()}`, { credentials: "same-origin" })
          : await fetch("index.php", {
              method: "POST",
              credentials: "same-origin",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: body.toString(),
            });
      const data = await res.json();
      if (data && (data.success === false || data.error)) {
        throw new Error(`ConvertSettings ${mode}: ${JSON.stringify(data.error)}`);
      }
      return (data.result ?? data) as never;
    },
    { mode, params }
  ) as Promise<T>;
}

export async function getConvertSettings(page: Page): Promise<ConvertSettingsInfo> {
  return callSettingsApi<ConvertSettingsInfo>(page, "info");
}

async function saveOrThrow(page: Page, mode: string, params: Record<string, string>) {
  const result = await callSettingsApi<{ saved: boolean; errors: unknown[] }>(page, mode, params);
  if (!result.saved) {
    throw new Error(`ConvertSettings ${mode} の保存に失敗: ${JSON.stringify(result.errors)}`);
  }
}

export async function saveLayout(page: Page, target: ConvertModule, fields: string[]) {
  await saveOrThrow(page, "saveLayout", { target, fields: JSON.stringify(fields) });
}

export async function saveDefaults(page: Page, target: ConvertModule, defaults: Record<string, string>) {
  await saveOrThrow(page, "saveDefaults", { target, defaults: JSON.stringify(defaults) });
}

export async function saveMatchRules(page: Page, target: "Accounts" | "Contacts", rules: string[][]) {
  await saveOrThrow(page, "saveMatchRules", { target, rules: JSON.stringify(rules) });
}

/**
 * 昇格テストが前提にする設定を「足りなければ足す」（既存の設定は消さない）。
 *  - 判定条件: 企業は会社名一致、担当者はメール一致
 *  - 昇格時デフォルト値: 案件の営業ステージ（案件を新規作成するとき必須を埋めるため）
 */
export async function ensureConvertPrerequisites(page: Page): Promise<void> {
  await withConvertSettingsLock(async () => {
    const info = await getConvertSettings(page);
    const hasRule = (rules: string[][], rule: string[]) =>
      rules.some((r) => r.length === rule.length && r.every((f, i) => f === rule[i]));

    const accountRules = info.modules.Accounts.matchRules;
    if (!hasRule(accountRules, ["accountname"])) {
      await saveMatchRules(page, "Accounts", [...accountRules, ["accountname"]]);
    }
    const contactRules = info.modules.Contacts.matchRules;
    if (!hasRule(contactRules, ["email"])) {
      await saveMatchRules(page, "Contacts", [...contactRules, ["email"]]);
    }
    const potentialDefaults = info.modules.Potentials.defaults;
    if (!potentialDefaults.sales_stage) {
      await saveDefaults(page, "Potentials", { ...potentialDefaults, sales_stage: "Prospecting" });
    }
  });
}

/** リード詳細から「リードを昇格」で昇格画面を開き、読み込み完了を待つ */
export async function openLeadConvert(page: Page, leadId: string): Promise<void> {
  await gotoDetail(page, "Leads", leadId);
  await page.locator("#Leads_detailView_basicAction_LBL_CONVERT_LEAD").click();
  await expect(page.getByTestId("lead-convert-section-Contacts")).toBeVisible({ timeout: 20000 });
}

/** 昇格画面の 1 セクション */
export function convertSection(page: Page, module: ConvertModule): Locator {
  return page.getByTestId(`lead-convert-section-${module}`);
}

/** セクションの扱い（新しく作成する／既存のレコードに紐づける／作成しない）のボタン */
export function modeButton(page: Page, module: ConvertModule, label: string): Locator {
  return convertSection(page, module).getByRole("radio", { name: label });
}

/** 「昇格する」を押し、昇格先（企業か担当者）の詳細へ遷移した record ID を返す */
export async function submitConvert(
  page: Page,
  expectedModule: "Accounts" | "Contacts" = "Accounts"
): Promise<string> {
  await page.getByTestId("lead-convert-submit").click();
  const pattern = new RegExp(`[?&]module=${expectedModule}&view=Detail&record=(\\d+)`);
  await page.waitForURL(pattern, { timeout: 30000 });
  return page.url().match(pattern)![1];
}

/** 名前の一部が一致する企業・担当者・案件を消す（後始末。失敗は無視） */
export async function deleteConvertedRecords(session: string, token: string): Promise<void> {
  const targets: [string, string][] = [
    ["Potentials", "potentialname"],
    ["Contacts", "lastname"],
    ["Accounts", "accountname"],
  ];
  for (const [module, field] of targets) {
    const rows = await frQuery(session, `SELECT id FROM ${module} WHERE ${field} LIKE '%${token}%';`).catch(() => []);
    for (const row of rows ?? []) {
      await frDelete(session, row.id).catch(() => {});
    }
  }
}
