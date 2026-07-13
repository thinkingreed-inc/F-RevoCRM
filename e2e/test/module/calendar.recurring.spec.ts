import { test, expect } from "../../fixtures/isolated";
import { generateRandomString } from "../../utils/util";
import {
  createRecurringEvent,
  retrieveEvent,
  deleteAllEventsBySubject,
  type RecurringType,
} from "../../utils/calendar";

/**
 * カレンダー 繰り返し予定 — 元スプレッドシート `_カレンダー基本パターン` 5.x
 *
 * 新規作成モーダル → 「詳細入力」でフル編集画面(view=Edit&mode=Events)へ遷移し、
 * 「繰り返し」(recurringcheck)を ON、繰り返し種別(recurringtype: 日/週/月/年)を設定して保存。
 * 保存値(recurringtype)が入力通りであることを検証する(繰り返しは複数インスタンスを生成する)。
 */
test.describe.serial("カレンダー: 繰り返し予定(日/週/月/年)", () => {
  const cases: { label: string; type: RecurringType }[] = [
    { label: "5.1 日", type: "Daily" },
    { label: "5.2 週", type: "Weekly" },
    { label: "5.5 月", type: "Monthly" },
    { label: "5.6 年", type: "Yearly" },
  ];

  for (const c of cases) {
    test(`${c.label}: 繰り返しの予定を登録でき recurringtype=${c.type}`, async ({
      page,
    }) => {
      test.setTimeout(120000);
      const subject = `E2Erec${c.type}${generateRandomString(5)}`;
      try {
        const rec = await createRecurringEvent(page, subject, c.type);
        const stored = await retrieveEvent(rec.wsId);
        expect(stored.recurringtype).toBe(c.type);
      } finally {
        await deleteAllEventsBySubject(subject);
      }
    });
  }

  test("6.1 終日の繰り返しの予定を登録でき recurringtype=Daily", async ({ page }) => {
    test.setTimeout(120000);
    const subject = `E2ErecAllday${generateRandomString(5)}`;
    try {
      const rec = await createRecurringEvent(page, subject, "Daily", true);
      const stored = await retrieveEvent(rec.wsId);
      expect(stored.recurringtype).toBe("Daily");
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });
});
