import { expect, type Page } from "@playwright/test";
import { url } from "./util";
import { apiSession } from "./api";
import { frQuery, frRetrieve, frDelete, frCreate } from "../model/fetcher";

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

/**
 * 件名で予定(WSレコード)を引く。見つからなければ undefined。
 *
 * F-RevoCRM の予定は Webservice 上 2 モジュールに分かれる:
 *  - Calendar = ToDo(activitytype=Task)
 *  - Events   = 活動(activitytype=Call/Meeting 等、時間区切り/終日)
 * モーダルの既定は時間区切り活動(Call)のため Events 側に入る。標準 Edit フォームの
 * ToDo は Calendar 側。どちらに入るかは作成経路で変わるため、両モジュールを引く。
 */
async function queryEvent(
  sn: string,
  subject: string
): Promise<CreatedCalendarEvent | undefined> {
  for (const mod of ["Events", "Calendar"]) {
    const rows = await frQuery(
      sn,
      `SELECT id FROM ${mod} WHERE subject='${subject}';`
    );
    if (rows?.length) {
      const wsId = rows[0].id;
      return { recordId: wsId.split("x")[1], wsId };
    }
  }
  return undefined;
}

/** 件名で予定を引き、recordId/wsId を返す(Calendar/Events 両対応)。 */
export async function findEventBySubject(
  subject: string
): Promise<CreatedCalendarEvent> {
  const sn = await apiSession();
  const rec = await queryEvent(sn, subject);
  if (!rec) {
    throw new Error(`予定が見つかりません(作成失敗?): ${subject}`);
  }
  return rec;
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

/** 件名に一致する予定を(繰り返しで複数生成された分も含め)全て API 削除する後始末。 */
export async function deleteAllEventsBySubject(subject: string): Promise<void> {
  const sn = await apiSession();
  for (const mod of ["Events", "Calendar"]) {
    const rows = await frQuery(
      sn,
      `SELECT id FROM ${mod} WHERE subject='${subject}';`
    ).catch(() => [] as any[]);
    for (const r of rows ?? []) {
      if (r?.id) await frDelete(sn, r.id).catch(() => {});
    }
  }
}

/**
 * 詳細画面から予定を複製する。複製フォームで件名を新件名に変更して保存し、
 * 新レコード(recordId/wsId)を返す。保存後はカレンダービューへ遷移するため
 * 新レコードは新件名で API 検索して特定する。
 */
export async function duplicateEventViaDetail(
  page: Page,
  recordId: string,
  newSubject: string
): Promise<CreatedCalendarEvent> {
  await page.goto(
    url(`index.php?module=Calendar&view=Detail&record=${recordId}&app=SALES`)
  );
  await page.waitForLoadState("networkidle");
  await page
    .locator(".detailViewButtoncontainer button.dropdown-toggle")
    .first()
    .click();
  await page
    .locator("#Calendar_detailView_moreAction_LBL_DUPLICATE a")
    .click();
  await page.waitForURL(/view=Edit/, { timeout: 15000 });
  await page.waitForLoadState("domcontentloaded");
  // 件名を新件名に上書き(元と同一だと重複防止で弾かれ得るため)
  await page.fill("#Calendar_editView_fieldName_subject", newSubject);
  await page.locator("button.saveButton").first().click();
  await page.waitForLoadState("networkidle");
  return findEventBySubject(newSubject);
}

/** 詳細画面の「その他 → 削除」で予定を削除する。 */
export async function deleteEventViaDetail(
  page: Page,
  recordId: string
): Promise<void> {
  await page.goto(
    url(`index.php?module=Calendar&view=Detail&record=${recordId}&app=SALES`)
  );
  await page.waitForLoadState("networkidle");
  await page.click("text=その他");
  // moreAction ドロップダウン内の削除リンク(ラベルは 活動/TODO で異なるため text で拾う)
  await page
    .locator(".detailViewButtoncontainer")
    .getByText("削除", { exact: false })
    .first()
    .click();
  // 確認ボックス「削除しますか？」の可視 Yes ボタンをクリックする。
  const yes = page
    .getByRole("button", { name: "Yes", exact: true })
    .and(page.locator(":visible"));
  await yes.first().click({ timeout: 8000 });
  // 削除(AJAX/遷移)完了を待つ。カレンダービューへ遷移することが多い。
  await page.waitForLoadState("networkidle");
  await page.waitForTimeout(1500);
}

/**
 * 件名で Calendar レコードを引く。無ければ null。
 * 新規作成モーダルの保存は「POST(200) → 画面表示 → 再フェッチ」と反映が遅いため、
 * 既定で寛大にリトライ(attempts 回 × 1s)して反映待ちを吸収する。
 */
export async function findEventBySubjectOrNull(
  subject: string,
  attempts = 20
): Promise<CreatedCalendarEvent | null> {
  const sn = await apiSession();
  for (let i = 0; i < attempts; i++) {
    const rec = await queryEvent(sn, subject);
    if (rec) return rec;
    await new Promise((r) => setTimeout(r, 1000));
  }
  return null;
}

export interface ModalEventInput {
  subject: string;
  /** 終日にする(is_allday)。 */
  allDay?: boolean;
  /** 共有メモ(common_memo, #1191)。 */
  commonMemo?: string;
  /** 招待するユーザーの検索語(名前の一部)。候補ドロップダウンから一致を選ぶ。 */
  invitees?: string[];
}

/** モーダルの招待者検索に語を入れ、候補ドロップダウンから一致ユーザーを選択する。 */
async function addInvitees(page: Page, terms: string[]): Promise<void> {
  const search = page
    .getByPlaceholder("ユーザーを検索して追加...")
    .first();
  await search.waitFor({ state: "visible", timeout: 8000 });
  for (const term of terms) {
    await search.click();
    await search.fill(term);
    await page.waitForTimeout(600);
    // 候補は Portal で body 直下 [data-rwc-dropdown="invitee"]。一致名をクリック。
    await page
      .locator('[data-rwc-dropdown="invitee"]')
      .getByText(term, { exact: false })
      .first()
      .click();
    await page.waitForTimeout(300);
    await search.fill("");
  }
  // 候補ドロップダウン(body直下・position:fixed・高z-index)は 保存 ボタンに重なって
  // クリックを妨げるため、確実に閉じる(Escape → blur → 消えるまで待つ)。
  await search.press("Escape").catch(() => {});
  await search.blur().catch(() => {});
  await page
    .locator('[data-rwc-dropdown="invitee"]')
    .waitFor({ state: "detached", timeout: 5000 })
    .catch(() => {});
  await page.waitForTimeout(300);
}

/**
 * カレンダーの新規作成モーダル(React QuickCreate = CalendarForm.tsx)で予定を作成する。
 *
 * 標準 Edit フォームに無い 共有メモ(common_memo)/終日(is_allday)はこのモーダルでのみ設定できる。
 * モーダルはカレンダービュー上で `Calendar_Calendar_Js.showCreateEventModal()` を実行して開く
 * (追加ボタンは #messageBar に干渉されるため JS 起動が安定)。日時は既定(当日+現在時刻)を使う。
 *
 * 【保存の反映が遅い】保存 POST(module=Events&api=Save)は 200 でも、レコードが WS クエリ・
 * 一覧・詳細に反映されるまで数秒のラグがある(保存→画面表示→再フェッチ)。そのため保存後は
 * モーダルの閉じ(=保存受理)を待ってから、件名クエリを寛大にリトライして特定する。
 */
export async function createEventViaModal(
  page: Page,
  input: ModalEventInput
): Promise<CreatedCalendarEvent> {
  await page.goto(url("index.php?module=Calendar&view=Calendar&app=SALES"));
  await page.waitForLoadState("networkidle");
  await page.waitForTimeout(1200);
  await page.evaluate(() => {
    // @ts-ignore - グローバルのカレンダーJS
    Calendar_Calendar_Js.showCreateEventModal();
  });
  const subjectInput = page.locator("#field_subject");
  await subjectInput.waitFor({ state: "visible", timeout: 15000 });
  await subjectInput.fill(input.subject);
  if (input.commonMemo) {
    await page.locator("#field_common_memo").fill(input.commonMemo);
  }
  if (input.allDay) {
    await page
      .locator("label")
      .filter({ hasText: "終日" })
      .first()
      .click({ force: true });
    await page.waitForTimeout(400);
  }
  if (input.invitees && input.invitees.length) {
    await addInvitees(page, input.invitees);
  }
  await page.getByRole("button", { name: "保存", exact: true }).first().click();
  // 保存受理(モーダルが閉じる)を待つ
  await subjectInput.waitFor({ state: "hidden", timeout: 15000 }).catch(() => {});
  // 反映が遅いので件名クエリを寛大にリトライして特定する
  const rec = await findEventBySubjectOrNull(input.subject, 20);
  if (!rec) {
    throw new Error(
      `カレンダー新規作成モーダル: 保存後に予定が見つかりません(反映遅延を超過?): ${input.subject}`
    );
  }
  return rec;
}

/** 繰り返し種別(スプレッドシートの 日/週/月/年)。 */
export type RecurringType = "Daily" | "Weekly" | "Monthly" | "Yearly";
const RECUR_LABEL: Record<RecurringType, string> = {
  Daily: "日",
  Weekly: "週",
  Monthly: "月",
  Yearly: "年",
};

/**
 * 繰り返し予定を作成する。新規作成モーダル → 「詳細入力」でフル編集画面
 * (view=Edit&mode=Events)へ遷移し、繰り返し(recurringcheck)を ON にして
 * 繰り返し種別(recurringtype: 日/週/月/年)を設定して保存する。
 * (繰り返し UI はモーダルには無く、このフル編集画面にのみ存在する)
 */
export async function createRecurringEvent(
  page: Page,
  subject: string,
  recurringType: RecurringType,
  allDay = false
): Promise<CreatedCalendarEvent> {
  // モーダル→詳細入力→フルフォームの一連は重く、高並列時に UI 操作が競合して
  // まれに失敗する。作成できていなければ 1 度だけやり直す(冪等: 件名で検索して確認)。
  try {
    return await attemptCreateRecurringEvent(page, subject, recurringType, allDay);
  } catch {
    const existing = await findEventBySubjectOrNull(subject, 3);
    if (existing) return existing;
    return await attemptCreateRecurringEvent(page, subject, recurringType, allDay);
  }
}

async function attemptCreateRecurringEvent(
  page: Page,
  subject: string,
  recurringType: RecurringType,
  allDay = false
): Promise<CreatedCalendarEvent> {
  await page.goto(url("index.php?module=Calendar&view=Calendar&app=SALES"));
  await page.waitForLoadState("networkidle");
  await page.waitForTimeout(1200);
  await page.evaluate(() => {
    // @ts-ignore
    Calendar_Calendar_Js.showCreateEventModal();
  });
  await page.locator("#field_subject").waitFor({ state: "visible", timeout: 15000 });
  await page.locator("#field_subject").fill(subject);
  // 「詳細入力」でフル編集画面へ(POST 遷移)
  await page
    .getByRole("button", { name: "詳細入力", exact: false })
    .first()
    .click();
  await page.waitForURL(/view=Edit/, { timeout: 15000 });
  await page.waitForLoadState("networkidle");
  await page.waitForTimeout(800);
  if (allDay) {
    const allDayCheck = page.locator("#alldayEvent").first();
    if (!(await allDayCheck.isChecked().catch(() => false))) {
      await allDayCheck.check({ force: true });
    }
    await page.waitForTimeout(300);
  }
  // 繰り返しを ON にして種別を選択
  const recurCheck = page.locator('input[name="recurringcheck"]').first();
  if (!(await recurCheck.isChecked().catch(() => false))) {
    await recurCheck.check({ force: true });
  }
  await page.waitForTimeout(400);
  await page
    .locator('select[name="recurringtype"]')
    .selectOption({ label: RECUR_LABEL[recurringType] });
  await page.waitForTimeout(300);
  await page.locator("button.saveButton").first().click();
  await page.waitForLoadState("networkidle");
  const rec = await findEventBySubjectOrNull(subject, 20);
  if (!rec) {
    throw new Error(`繰り返し予定の作成に失敗(反映遅延を超過?): ${subject}`);
  }
  return rec;
}

/** 当日から days 日後の日付(yyyy-mm-dd)。0 で当日。 */
export function dayStr(days = 0): string {
  const d = new Date();
  d.setDate(d.getDate() + days);
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `${d.getFullYear()}-${mm}-${dd}`;
}

/** userName から webservice ユーザーID(例 "19x7")を引く。 */
export async function resolveUserWsId(userName: string): Promise<string> {
  const sn = await apiSession();
  const rows = await frQuery(
    sn,
    `SELECT id FROM Users WHERE user_name='${userName}';`
  );
  const id = rows?.[0]?.id;
  if (!id) throw new Error(`ユーザーが見つかりません: ${userName}`);
  return id;
}

export interface ApiEventInput {
  subject: string;
  /** 所有者(assigned_user_id)。webservice ユーザーID(例 "19x7")。 */
  ownerWsId: string;
  /** 開始=終了日(yyyy-mm-dd)。 */
  date: string;
  timeStart?: string;
  timeEnd?: string;
  visibility?: "Public" | "Private";
  activitytype?: string;
}

/**
 * Events を Webservice API で作成し wsId を返す。
 * 他ユーザー所有・可視性(公開/非公開)を検証するため assigned_user_id を明示指定できる。
 * Events は duration_hours/duration_minutes が必須(MANDATORY_FIELDS_MISSING 対策)。
 */
export async function createEventViaApi(input: ApiEventInput): Promise<string> {
  const sn = await apiSession();
  const res = await frCreate(sn, "Events", {
    subject: input.subject,
    assigned_user_id: input.ownerWsId,
    date_start: input.date,
    due_date: input.date,
    time_start: input.timeStart ?? "10:00",
    time_end: input.timeEnd ?? "11:00",
    duration_hours: "1",
    duration_minutes: "0",
    eventstatus: "Planned",
    activitytype: input.activitytype ?? "Meeting",
    visibility: input.visibility ?? "Public",
  });
  const id = (res as { id?: string } | false) && (res as { id?: string }).id;
  if (!id) throw new Error(`Events API 作成に失敗: ${input.subject}`);
  return id;
}

export interface CalendarFeedItem {
  id: string;
  title: string;
  url: string;
  visibility: string;
  start: string;
  end: string;
  userid: string;
}

/**
 * 指定ユーザーの予定フィード(FullCalendar 用 JSON)を「現在ログイン中ユーザーの視点」で取得する。
 * 共有カレンダー(他者予定の閲覧)や非公開マスク(予定あり*)の検証に使う。
 * page はフィードを見る側のユーザーでログイン済みであること(page.request がその Cookie を使う)。
 */
export async function fetchCalendarFeed(
  page: Page,
  ownerUserId: string,
  start: string,
  end: string
): Promise<CalendarFeedItem[]> {
  const uid = ownerUserId.includes("x")
    ? ownerUserId.split("x")[1]
    : ownerUserId;
  const feedUrl = url(
    `index.php?module=Calendar&action=Feed&type=Events&userid=${uid}&start=${start}&end=${end}&color=%233366cc&textColor=white`
  );
  const resp = await page.request.get(feedUrl);
  if (!resp.ok()) throw new Error(`Feed 取得失敗: ${resp.status()}`);
  return JSON.parse(await resp.text()) as CalendarFeedItem[];
}

/** 指定件名の予定が存在しない(削除済み)ことを API で確認する(削除の非同期を数回リトライ)。 */
export async function expectEventDeleted(subject: string): Promise<void> {
  const sn = await apiSession();
  let count = 1;
  for (let i = 0; i < 5; i++) {
    const rec = await queryEvent(sn, subject);
    count = rec ? 1 : 0;
    if (count === 0) return;
    await new Promise((r) => setTimeout(r, 1000));
  }
  expect(count).toBe(0);
}
