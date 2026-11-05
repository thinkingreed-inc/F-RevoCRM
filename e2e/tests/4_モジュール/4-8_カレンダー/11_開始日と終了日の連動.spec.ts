import { test, expect } from "../../../fixtures/isolated";
import { url } from "../../../utils/util";
import type { Page } from "@playwright/test";

/**
 * 予定(Events)で開始日を変えると終了日が連動すること。
 * - クイック作成は終日オンのときだけ連動を打ち切っていた。
 * - 編集画面は終日でも隠れた開始時刻＋既定の時間で計算していたため、遅い時刻だと翌日になり、
 *   複数日の終日は幅が失われていた。
 * 終日は日数の差を保ち、終日オフは開始＋既定の時間になる。レコードは保存しない。
 */
test.describe("カレンダー: 開始日を変えると終了日が連動する", () => {
  /** ユーザーの日付書式で、基準日から days 日後の文字列を返す(画面の moment を使う) */
  async function shiftDate(page: Page, base: string, days: number) {
    return page.evaluate(
      ([b, d]) => {
        // @ts-ignore - 画面のグローバル
        const f = vtUtils.getMomentDateFormat();
        // @ts-ignore
        return moment(b, f).add(d, "days").format(f);
      },
      [base, days] as const
    );
  }

  test.describe("編集画面（活動の追加）", () => {
    const cases = [
      { name: "終日オフ", allday: false, startTime: "10:00", span: 0 },
      { name: "終日オン", allday: true, startTime: "10:00", span: 0 },
      // 隠れた開始時刻に既定の時間を足すと翌日になっていた
      {
        name: "終日オン・開始時刻23:30",
        allday: true,
        startTime: "23:30",
        span: 0,
      },
      // 幅が失われ、終了日が開始日と同じ日になっていた
      { name: "終日オン・複数日", allday: true, startTime: "10:00", span: 2 },
    ];
    for (const c of cases) {
      test(`${c.name}: 開始日を変えると終了日が連動する`, async ({ page }) => {
        await page.goto(
          url("index.php?module=Calendar&view=Edit&mode=Events&app=SALES")
        );
        await page.waitForLoadState("networkidle");
        const form = page.locator("form#EditView");
        const field = (name: string) => form.locator(`[name="${name}"]`);
        await field("time_start").evaluate(
          (e: HTMLInputElement, v) => (e.value = v),
          c.startTime
        );
        if (c.allday) await page.locator("#alldayEvent").check();
        const changeStart = async (value: string) => {
          await field("date_start").evaluate((e, v) => {
            // @ts-ignore - 日付ピッカーで選んだときと同じ change を起こす
            jQuery(e).val(v).trigger("change");
          }, value);
        };

        const today = await field("date_start").inputValue();
        const start = await shiftDate(page, today, 35);
        await changeStart(start);
        if (c.span) {
          await field("due_date").evaluate(
            (e: HTMLInputElement, v) => (e.value = v),
            await shiftDate(page, start, c.span)
          );
        }
        const moved = await shiftDate(page, start, 10);
        await changeStart(moved);
        await expect(field("due_date")).toHaveValue(
          await shiftDate(page, moved, c.span)
        );
      });
    }
  });

  // 終日オフは開始時刻（作成時の現在時刻）次第で翌日になるため、単体テスト（useCalendarDateTimeSync.test.ts）で確認する
  test("クイック作成・終日オン: 開始日を変えると終了日が連動し、複数日は幅を保つ", async ({
    page,
  }) => {
    await page.goto(url("index.php?module=Calendar&view=Calendar&app=SALES"));
    await page.waitForLoadState("networkidle");
    await page.waitForTimeout(1200);
    await page.evaluate(() => {
      // @ts-ignore - グローバルのカレンダーJS
      Calendar_Calendar_Js.showCreateEventModal();
    });
    const dialog = page.locator('[role="dialog"]:has(input[type="date"])');
    await dialog.waitFor({ state: "visible", timeout: 15000 });
    const dates = dialog.locator('input[type="date"]');
    await dialog
      .locator("label", { hasText: /^終日$/ })
      .locator("..")
      .locator('button[role="checkbox"]')
      .click();

    await dates.nth(0).focus();
    await dates.nth(0).fill("2026-12-10");
    await expect(dates.nth(1)).toHaveValue("2026-12-10");

    await dates.nth(1).focus();
    await dates.nth(1).fill("2026-12-12");
    await dates.nth(0).focus();
    await dates.nth(0).fill("2026-12-20");
    await expect(dates.nth(1)).toHaveValue("2026-12-22");
  });
});
