import { test, expect } from "../../../fixtures/isolated";
import { url, generateRandomString } from "../../../utils/util";
import {
  createEventViaApi,
  deleteAllEventsBySubject,
  findEventBySubjectOrNull,
  dayStr,
  resolveUserWsId,
} from "../../../utils/calendar";
import { writeFileSync, readFileSync } from "fs";
import { join } from "path";
import { tmpdir } from "os";

/**
 * カレンダーの iCal 連携(エクスポート / インポート) — TEST_COVERAGE P0-B / B4
 *
 * カレンダーは共通のエクスポート / インポートとは別に **iCal(.ics) 固有実装** を持つ:
 *   modules/Calendar/actions/ExportData.php — `type != 'csv'` のとき
 *     Content-Type: text/calendar を返し、iCalendar コンポーネントを組み立てる
 *     (`setEventFieldsForExport` / `setTodoFieldsForExport` で Events と Calendar を統合)
 *   modules/Calendar/views/Import.php — `importResult` / `undoIcalImport` を expose し、
 *     ics をパースして Events(予定) と Calendar(ToDo) に振り分けて登録する
 *     (`iCalLastImport` に登録分を記録し、取り消しできるようにする)
 *
 * 共通機能側の 3-22_エクスポート / 3-23_インポート は CSV 前提のため、
 * この iCal 固有実装は誰も通していなかった(2026-08-26 の棚卸しで判明)。
 *
 * なお `modules/Calendar/views/Export.php` は空クラス(`Vtiger_Export_View` そのまま)
 * なので、エクスポート画面自体の固有実装は無い。
 *
 * インポートの導線(実機で確認):
 *   一覧の「インポート」(#Calendar_basicAction_LBL_IMPORT)
 *     → ランディング画面(mode=landing)で CSV / ICS を選ぶ
 *     → #icsImport で ICS 用の 1 ステップ画面(mode=importBasicStep&fileFormat=ics)
 *     → #importButton で取り込み(mode=importResult)
 *     → 結果画面に「最後のインポートの結果を取り消す」(mode=undoIcalImport)がある
 */

/** インポート用の最小 iCalendar(VEVENT 1 件)を組み立てる。 */
function buildIcs(subject: string, uid: string): string {
  const d = dayStr(1).replace(/-/g, ""); // 翌日(YYYYMMDD)
  return [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//F-RevoCRM E2E//iCal//JA",
    "CALSCALE:GREGORIAN",
    "BEGIN:VEVENT",
    `UID:${uid}`,
    `DTSTAMP:${d}T000000Z`,
    `DTSTART:${d}T010000Z`,
    `DTEND:${d}T020000Z`,
    `SUMMARY:${subject}`,
    "DESCRIPTION:E2E iCal import",
    "END:VEVENT",
    "END:VCALENDAR",
    "",
  ].join("\r\n");
}

test.describe("カレンダー: iCal 連携", () => {
  test("予定を iCal(.ics)形式でエクスポートできる", async ({ page }) => {
    test.setTimeout(120000);
    const subject = `E2Eical出${generateRandomString(6)}`;

    try {
      await createEventViaApi({
        subject,
        date: dayStr(1),
        ownerWsId: await resolveUserWsId("admin"),
      });

      await page.goto(url("index.php?module=Calendar&view=List&app=SALES"));
      await page.waitForLoadState("networkidle");

      // 一括操作の「その他」→ エクスポート(共通導線)
      await page
        .locator(".listViewMassActions button.dropdown-toggle")
        .first()
        .click();
      await page
        .locator("#Calendar_listView_advancedAction_LBL_EXPORT")
        .click();

      // カレンダーだけ形式選択(csv / ics)がある。ics を選ぶと固有実装を通る。
      const modal = page.locator(".modal-content:visible").first();
      await expect(modal).toBeVisible({ timeout: 20000 });
      await modal.locator("#ics").check();

      const [download] = await Promise.all([
        page.waitForEvent("download", { timeout: 60000 }),
        modal.locator("button.btn-success.btn-lg").first().click(),
      ]);

      const filePath = await download.path();
      expect(filePath).toBeTruthy();
      const body = readFileSync(filePath!, "utf8");
      // iCalendar として成立していること(固有実装が組み立てた形式)
      expect(body, "iCalendar のヘッダで始まること").toContain(
        "BEGIN:VCALENDAR"
      );
      expect(body).toContain("BEGIN:VEVENT");
      expect(body, "作成した予定の件名が含まれること").toContain(subject);
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });

  test("iCal(.ics)をインポートすると予定が作成される", async ({ page }) => {
    test.setTimeout(120000);
    const token = generateRandomString(6);
    const subject = `E2Eical入${token}`;
    const icsPath = join(tmpdir(), `e2e-ical-${token}.ics`);
    writeFileSync(icsPath, buildIcs(subject, `e2e-${token}@example.com`));

    try {
      expect(
        await findEventBySubjectOrNull(subject, 1),
        "インポート前は同名の予定が無いこと"
      ).toBeNull();

      // カレンダーのインポートは「ランディング画面」で CSV / iCal を選ばせる。
      // view=Import を直に開くと CSV ウィザードになるので、
      // mode=landing → #icsImport(ICS ファイル)の導線を通す。
      await page.goto(url("index.php?module=Calendar&view=List&app=SALES"));
      await page.waitForLoadState("networkidle");
      await page.locator("#Calendar_basicAction_LBL_IMPORT").click();
      const icsCard = page.locator("#icsImport");
      await expect(icsCard, "ICS ファイルの導線があること").toBeVisible({
        timeout: 20000,
      });
      await icsCard.click();

      // ICS 用のインポート画面(1 ステップのみ)。実行は #importButton
      // (`Calendar_Edit_Js.uploadAndParse()` → mode=importResult)。
      const importFile = page.locator('input[name="import_file"]');
      await expect(importFile).toBeAttached({ timeout: 20000 });
      await importFile.setInputFiles(icsPath);
      await page.locator("#importButton").click();
      await page.waitForLoadState("networkidle");

      // 取り込み結果画面(ImportResult.tpl)が出ること
      // 結果はオーバーレイページとして描画される(モーダルではない)
      const undoButton = page
        .locator("button.btn-danger")
        .filter({ hasText: "取り消す" })
        .first();
      await expect(undoButton, "インポート結果画面が出ること").toBeVisible({
        timeout: 30000,
      });
      await expect(
        page.locator("body"),
        "取り込んだ予定の件数が出ること"
      ).toContainText("No. of Events Successfully Imported");

      // 取り込んだ予定が API から引けること(Events として登録される)
      await expect
        .poll(async () => !!(await findEventBySubjectOrNull(subject, 3)), {
          timeout: 30000,
        })
        .toBe(true);

      // --- 取り消し(undoIcalImport): iCalLastImport に記録した分が消える ---
      await undoButton.click();
      await page.waitForLoadState("networkidle");
      await expect
        .poll(async () => !!(await findEventBySubjectOrNull(subject, 3)), {
          timeout: 30000,
        })
        .toBe(false);
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });
});
