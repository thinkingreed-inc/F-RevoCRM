import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import { apiSession } from "../../utils/api";
import { frQuery, frDelete } from "../../model/fetcher";

/**
 * 活動 / ToDo (Calendar) の作成 — 機能一覧 39 / 40
 *
 * Calendar は共通 CRUD(`fr.common`)の対象外(専用フォーム)。専用の編集画面
 * (module=Calendar&view=Edit)で件名を入力して保存し、レコードが作成される
 * ことを検証する(日付/時刻/ステータス等の必須項目は既定値を利用)。
 * 検証・後始末は Webservice API。
 */
test.describe("活動/ToDo (Calendar)", () => {
  test("Calendar レコードを新規作成できる", async ({ page }) => {
    const subject = `E2Ecal${generateRandomString(6)}`;

    await page.goto(url("index.php?module=Calendar&view=Edit&app=SALES"));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="subject"]', subject);
    // 日付/時刻は既定値。ステータス(taskstatus)のみ未選択なので設定する。
    await page
      .locator('select[name="taskstatus"]')
      .selectOption("Not Started");
    await page.locator("button.saveButton").first().click();
    await page.waitForLoadState("networkidle");

    // 作成を API で検証
    const sn = await apiSession();
    const rows = await frQuery(
      sn,
      `SELECT id,subject FROM Calendar WHERE subject='${subject}';`
    );
    expect(rows.length).toBeGreaterThanOrEqual(1);

    // 後始末
    for (const r of rows) {
      if (r.id) await frDelete(sn, r.id);
    }
  });
});
