import { test, expect } from "../../fixtures/isolated";
import { url } from "../../utils/util";

/**
 * 共通機能: カレンダー表示モード切替 — 機能一覧 14-1
 *
 * カレンダー画面(FullCalendar)の 月/週/日/概要 ボタンで表示モードを切り替えられる
 * ことを、ボタンのアクティブ状態(fc-state-active)で検証する。
 * (イベントの作成・編集は module/calendar.spec.ts で別途検証済み)
 *
 * 主要セレクタ(実画面で確定): .fc-toolbar / .fc-month-button / .fc-agendaWeek-button /
 * .fc-agendaDay-button / .fc-vtAgendaList-button、アクティブ = fc-state-active
 */
test.describe("共通: カレンダー表示切替", () => {
  test("月/週/日/概要 の表示モードを切り替えられる", async ({ page }) => {
    await page.goto(url("index.php?module=Calendar&view=Calendar&app=MARKETING"));
    await page.waitForLoadState("networkidle");
    await expect(page.locator(".fc-toolbar").first()).toBeVisible({
      timeout: 15000,
    });

    const modes: Array<[string, string]> = [
      ["月", ".fc-month-button"],
      ["週", ".fc-agendaWeek-button"],
      ["日", ".fc-agendaDay-button"],
      ["概要", ".fc-vtAgendaList-button"],
    ];
    for (const [label, sel] of modes) {
      const btn = page.locator(sel).first();
      await expect(btn, `${label}ボタンが表示される`).toBeVisible();
      await btn.click();
      await expect(
        btn,
        `${label}ボタンがアクティブになる`
      ).toHaveClass(/fc-state-active/, { timeout: 10000 });
    }
  });
});
