import { test, expect } from "../../fixtures/isolated";
import { generateRandomString } from "../../utils/util";
import {
  createCalendarEvent,
  editCalendarEvent,
  retrieveEvent,
  expectEventOnCalendar,
  deleteCalendarEvent,
  duplicateEventViaDetail,
  deleteEventViaDetail,
  expectEventDeleted,
} from "../../utils/calendar";

/**
 * カレンダー基本パターン(単一ユーザー分) — 元スプレッドシート `_カレンダー基本パターン`
 *
 * 標準 Edit フォームで扱える範囲(時間区切り予定の 登録 / タイトル変更 / 時刻変更 /
 * 非公開)を、入力値通りに保存されること(API 保存値)と、カレンダービューに表示される
 * ことで検証する。終日 / 繰り返し / 招待 / 複製 / 他者予定の削除(#864)/ 共有カレンダーの
 * 別ユーザー表示は FullCalendar オーバーレイ UI・複数ユーザー設定が必要なため本 spec の
 * 範囲外(TEST_COVERAGE の次段)。
 */
test.describe.serial("カレンダー基本パターン(単一ユーザー)", () => {
  /** 当日(yyyy-mm-dd)。既定のカレンダー表示範囲に出るよう当日を使う。 */
  function today(): string {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${d.getFullYear()}-${mm}-${dd}`;
  }

  test("1.1 時間区切り予定を登録でき、入力値通りに保存されカレンダーに表示される", async ({
    page,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Ecal${generateRandomString(6)}`;
    let wsId = "";
    try {
      const rec = await createCalendarEvent(page, {
        subject,
        date: today(),
        timeStart: "10:00",
        visibility: "Public",
      });
      wsId = rec.wsId;
      const stored = await retrieveEvent(wsId);
      expect(stored.subject).toBe(subject);
      expect(stored.date_start).toContain(today());
      expect(stored.time_start).toContain("10:00");
      expect(stored.visibility).toBe("Public");
      await expectEventOnCalendar(page, subject);
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("1.5 予定のタイトルを変更でき、変更後がカレンダーに表示される", async ({
    page,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Ecal${generateRandomString(6)}`;
    const renamed = `${subject}_r`;
    let wsId = "";
    try {
      const rec = await createCalendarEvent(page, {
        subject,
        date: today(),
        timeStart: "10:00",
      });
      wsId = rec.wsId;
      await editCalendarEvent(page, rec.recordId, { subject: renamed });
      const stored = await retrieveEvent(wsId);
      expect(stored.subject).toBe(renamed);
      await expectEventOnCalendar(page, renamed);
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("1.2 予定の開始/終了時刻を変更でき、入力値通りに保存される", async ({
    page,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Ecal${generateRandomString(6)}`;
    let wsId = "";
    try {
      const rec = await createCalendarEvent(page, {
        subject,
        date: today(),
        timeStart: "10:00",
      });
      wsId = rec.wsId;
      await editCalendarEvent(page, rec.recordId, {
        subject,
        date: today(),
        timeStart: "14:00",
      });
      const stored = await retrieveEvent(wsId);
      expect(stored.time_start).toContain("14:00");
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("2.1 非公開の予定を登録でき、非公開(Private)で保存される", async ({
    page,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Ecalp${generateRandomString(6)}`;
    let wsId = "";
    try {
      const rec = await createCalendarEvent(page, {
        subject,
        date: today(),
        timeStart: "16:00",
        visibility: "Private",
      });
      wsId = rec.wsId;
      const stored = await retrieveEvent(wsId);
      expect(stored.subject).toBe(subject);
      expect(stored.visibility).toBe("Private");
      await expectEventOnCalendar(page, subject);
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("2.4 非公開の予定を公開に変更でき、公開(Public)で保存される", async ({
    page,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Ecalpp${generateRandomString(6)}`;
    let wsId = "";
    try {
      const rec = await createCalendarEvent(page, {
        subject,
        date: today(),
        timeStart: "16:00",
        visibility: "Private",
      });
      wsId = rec.wsId;
      await editCalendarEvent(page, rec.recordId, {
        subject,
        visibility: "Public",
      });
      const stored = await retrieveEvent(wsId);
      expect(stored.visibility).toBe("Public");
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("1.7 予定を複製(件名変更)でき、複製がカレンダーに表示される", async ({
    page,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Ecal${generateRandomString(6)}`;
    const dupSubject = `${subject}_dup`;
    let wsId = "";
    let dupWsId = "";
    try {
      const rec = await createCalendarEvent(page, {
        subject,
        date: today(),
        timeStart: "10:00",
      });
      wsId = rec.wsId;
      const dup = await duplicateEventViaDetail(page, rec.recordId, dupSubject);
      dupWsId = dup.wsId;
      const stored = await retrieveEvent(dupWsId);
      expect(stored.subject).toBe(dupSubject);
      await expectEventOnCalendar(page, dupSubject);
    } finally {
      if (dupWsId) await deleteCalendarEvent(dupWsId);
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("1.9 予定を削除でき、カレンダーに表示されなくなる", async ({ page }) => {
    test.setTimeout(90000);
    const subject = `E2Ecal${generateRandomString(6)}`;
    let wsId = "";
    try {
      const rec = await createCalendarEvent(page, {
        subject,
        date: today(),
        timeStart: "10:00",
      });
      wsId = rec.wsId;
      await deleteEventViaDetail(page, rec.recordId);
      await expectEventDeleted(subject);
      wsId = ""; // 削除済み
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });
});
