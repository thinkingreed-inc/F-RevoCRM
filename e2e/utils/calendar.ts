import { expect, type Page } from "@playwright/test";
import { url } from "./util";
import { apiSession } from "./api";
import { frQuery, frRetrieve, frDelete } from "../model/fetcher";

/**
 * カレンダー(活動)予定の作成・検証ヘルパ。
 *
 * F-RevoCRM の予定は共通 CRUD ドライバの対象外(専用フォーム)。標準の編集画面
 * (module=Calendar&view=Edit)は subject / date_start / time_start / due_date /
 * taskstatus / visibility を持つ(終日・繰り返し・招待は FullCalendar オーバーレイ側の
 * 機能で本ヘルパの範囲外)。管理者の日付/時刻書式は yyyy-mm-dd / 24時間。
 *
 * 保存後はカレンダービューへ遷移し record= を含む詳細 URL に来ないため、
 * 作成レコードの ID は Webservice API(件名検索)で取得する。
 */

export interface CalendarEventInput {
  subject: string;
  /** 開始日=終了日(yyyy-mm-dd)。既定の月表示に出したい場合は当日を渡す。 */
  date: string;
  /** 開始時刻(HH:MM, 24時間)。省略時は時間区切りにしない。 */
  timeStart?: string;
  visibility?: "Public" | "Private";
  /** taskstatus の値(例: "Planned")。省略時はフォーム既定。 */
  status?: string;
}

export interface CreatedCalendarEvent {
  recordId: string;
  wsId: string;
}

async function fillEventForm(page: Page, input: CalendarEventInput): Promise<void> {
  await page.fill("#Calendar_editView_fieldName_subject", input.subject);
  await page.fill("#Calendar_editView_fieldName_date_start", input.date);
  await page.fill("#Calendar_editView_fieldName_due_date", input.date);
  if (input.timeStart) {
    await page.fill("#Calendar_editView_fieldName_time_start", input.timeStart);
  }
  if (input.visibility) {
    await page
      .locator('select[name="visibility"]')
      .selectOption(input.visibility);
  }
  // taskstatus は必須(V~M)。フォーム既定が未選択のことがあるため常に明示設定する。
  await page
    .locator('select[name="taskstatus"]')
    .selectOption(input.status ?? "Planned");
}

/** 標準 Edit フォームで予定を作成し、API で採番された ID を返す。 */
export async function createCalendarEvent(
  page: Page,
  input: CalendarEventInput
): Promise<CreatedCalendarEvent> {
  await page.goto(url("index.php?module=Calendar&view=Edit&app=SALES"));
  await page.waitForLoadState("domcontentloaded");
  await fillEventForm(page, input);
  await page.locator("button.saveButton").first().click();
  await page.waitForLoadState("networkidle");
  return findEventBySubject(input.subject);
}

/** 既存予定を編集する(件名を変更する等)。record ID を指定して Edit を開く。 */
export async function editCalendarEvent(
  page: Page,
  recordId: string,
  patch: Partial<CalendarEventInput> & { subject: string }
): Promise<void> {
  await page.goto(
    url(`index.php?module=Calendar&view=Edit&record=${recordId}&app=SALES`)
  );
  await page.waitForLoadState("domcontentloaded");
  await page.fill("#Calendar_editView_fieldName_subject", patch.subject);
  if (patch.date) {
    await page.fill("#Calendar_editView_fieldName_date_start", patch.date);
    await page.fill("#Calendar_editView_fieldName_due_date", patch.date);
  }
  if (patch.timeStart) {
    await page.fill("#Calendar_editView_fieldName_time_start", patch.timeStart);
  }
  if (patch.visibility) {
    await page
      .locator('select[name="visibility"]')
      .selectOption(patch.visibility);
  }
  await page.locator("button.saveButton").first().click();
  await page.waitForLoadState("networkidle");
}

/** 件名で Calendar レコードを引き、recordId/wsId を返す。 */
export async function findEventBySubject(
  subject: string
): Promise<CreatedCalendarEvent> {
  const sn = await apiSession();
  const rows = await frQuery(
    sn,
    `SELECT id FROM Calendar WHERE subject='${subject}';`
  );
  if (!rows.length) {
    throw new Error(`Calendar 予定が見つかりません(作成失敗?): ${subject}`);
  }
  const wsId = rows[0].id;
  return { recordId: wsId.split("x")[1], wsId };
}

/** 保存値(API)を取得して返す(subject/date_start/time_start/visibility 等の検証用)。 */
export async function retrieveEvent(
  wsId: string
): Promise<Record<string, string>> {
  const sn = await apiSession();
  const rec = await frRetrieve(sn, wsId);
  if (!rec) throw new Error(`Calendar 予定の取得に失敗: ${wsId}`);
  return rec;
}

/**
 * 予定が Calendar モジュールに登録・表示されていることを確認する。
 *
 * FullCalendar(v3)ビューはイベント取得が保存済み表示日/AJAX に依存し表示検証が不安定なため、
 * Calendar のリストビュー(件名で列検索)で「登録され一覧に表示される」ことを確認する。
 * (「入力値通りに登録」自体は呼び出し側が API 保存値で別途検証している)
 */
export async function expectEventOnCalendar(
  page: Page,
  subject: string
): Promise<void> {
  await page.goto(
    url(
      `index.php?module=Calendar&view=List&app=SALES&search_key=subject&search_value=${encodeURIComponent(subject)}&operator=e`
    )
  );
  await page.waitForLoadState("networkidle");
  await expect(
    page.getByText(subject, { exact: false }).and(page.locator(":visible")).first()
  ).toBeVisible({ timeout: 20000 });
}

/** API で予定を削除する(後始末)。失敗は握りつぶす。 */
export async function deleteCalendarEvent(wsId: string): Promise<void> {
  const sn = await apiSession();
  await frDelete(sn, wsId).catch(() => {});
}
