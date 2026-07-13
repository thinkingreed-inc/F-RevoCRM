import { test, expect } from "../../../fixtures/isolated";
import { url, generateRandomString } from "../../../utils/util";
import { apiSession } from "../../../utils/api";
import { frQuery, frDelete } from "../../../model/fetcher";

/**
 * 活動 / ToDo (Calendar) の作成・編集 — 機能一覧 39 / 40
 *
 * Calendar は共通 CRUD(`fr.common`)の対象外(専用フォーム)。専用の編集画面
 * (module=Calendar&view=Edit)で 作成 → 編集 を検証する。日付/時刻は既定値、
 * ステータス(taskstatus)のみ選択。値検証・後始末は Webservice API。
 *
 * ※保存後はカレンダービューへ遷移し標準の詳細(その他→削除)が無いため、
 *   UI からの削除は別フロー(カレンダー上の操作)。ここでは作成・編集に限定する。
 */
test.describe("活動/ToDo (Calendar)", () => {
  test("Calendar レコードの作成・編集ができる", async ({ page }) => {
    const subject = `E2Ecal${generateRandomString(6)}`;
    const edited = `${subject}_edited`;

    // --- 作成 ---
    await page.goto(url("index.php?module=Calendar&view=Edit&app=SALES"));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="subject"]', subject);
    await page.locator('select[name="taskstatus"]').selectOption("Not Started");
    await page.locator("button.saveButton").first().click();
    await page.waitForLoadState("networkidle");

    const sn = await apiSession();
    let rows = await frQuery(
      sn,
      `SELECT id,subject FROM Calendar WHERE subject='${subject}';`
    );
    expect(rows.length).toBeGreaterThanOrEqual(1);
    const wsId = rows[0].id; // 例: 9x123
    const recordId = wsId.split("x")[1];

    // --- 編集(件名を変更) ---
    await page.goto(
      url(`index.php?module=Calendar&view=Edit&record=${recordId}&app=SALES`)
    );
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="subject"]', edited);
    await page.locator("button.saveButton").first().click();
    await page.waitForLoadState("networkidle");

    rows = await frQuery(
      sn,
      `SELECT id FROM Calendar WHERE subject='${edited}';`
    );
    expect(rows.length).toBe(1);

    // 後始末(API 削除)
    const leftovers = await frQuery(
      sn,
      `SELECT id FROM Calendar WHERE subject='${subject}' OR subject='${edited}';`
    );
    for (const r of leftovers) if (r.id) await frDelete(sn, r.id);
  });
});
