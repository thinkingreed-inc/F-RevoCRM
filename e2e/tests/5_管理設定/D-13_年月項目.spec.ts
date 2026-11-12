import { test, expect } from "../../fixtures/isolated";
import type { Page, Locator } from "@playwright/test";
import { generateRandomString, BASE_URL } from "../../utils/util";
import { gotoSettings, confirmYes } from "../../utils/settings";
import { createRecordViaApi, deleteRecordViaApi, type CreatedRecord } from "../../utils/record";
import { frQuery } from "../../model/fetcher";
import { apiSession } from "../../utils/api";

/**
 * D-13 年月項目（uitype 996）
 *
 * レイアウトエディタで「年月」項目を Faq に追加し、以下を直列で検証する:
 *   1. 項目の追加（既定値の入力欄が出ない・データ型は date）
 *   2. 編集画面のピッカーで月を選んで保存 → DB には月初日で入り、詳細には「YYYY年M月」で出る
 *   3. 詳細画面のインライン編集で別の月に変えられる
 *   4. 別の項目のインライン編集では年月の値が変わらない
 *   5. 年を切り替えて月を選べる
 *   6. 編集画面で「クリア」すると空で保存でき、詳細は空欄になる
 * 後始末（レコード・項目の削除）は afterAll で必ず行う。
 *
 * 入力欄は Web コンポーネント <fr-field-yearmonth> で、data-testid / data-month で操作する。
 */
test.describe.serial("管理: 年月項目", () => {
  const MODULE = "Faq";
  const layoutParams = {
    module: "LayoutEditor",
    view: "Index",
    sourceModule: MODULE,
    mode: "showFieldLayout",
  };

  const token = generateRandomString(8);
  const label = `e2eym${token}`;

  let fieldName = "";
  let record: CreatedRecord | null = null;

  const layoutRow = (page: Page, text: string) =>
    page.locator("#moduleBlocks li").filter({ has: page.locator(".fieldLabel b", { hasText: text }) });

  const editField = (page: Page): Locator => page.locator(`.fr-wc-field[data-fieldname="${fieldName}"]`);

  const detailValue = (page: Page): Locator => page.locator(`#${MODULE}_detailView_fieldValue_${fieldName}`);

  const detailUrl = () =>
    `${BASE_URL}index.php?module=${MODULE}&view=Detail&record=${record!.recordId}&mode=showDetailViewByMode&requestMode=full`;

  async function deleteFieldIfExists(page: Page, text: string): Promise<void> {
    await gotoSettings(page, layoutParams);
    await page.waitForLoadState("networkidle").catch(() => {});
    const target = layoutRow(page, text);
    if ((await target.count()) === 0) return;
    await Promise.all([
      page.waitForResponse((r) => r.request().method() === "POST", { timeout: 15000 }).catch(() => {}),
      (async () => {
        await target.first().locator("a.deleteCustomField").click();
        await confirmYes(page);
      })(),
    ]);
    await page.waitForLoadState("networkidle").catch(() => {});
    await gotoSettings(page, layoutParams);
    await expect(layoutRow(page, text)).toHaveCount(0);
  }

  test("年月項目を追加できる", async ({ page }) => {
    await gotoSettings(page, layoutParams);
    await page.waitForLoadState("networkidle").catch(() => {});
    await page.locator("button.addCustomField").first().click();

    const modal = page.locator(".createCustomFieldForm:visible");
    await modal.waitFor({ state: "visible", timeout: 10000 });
    await modal.locator('select[name="fieldType"]').selectOption("YearMonth");

    // 年月には既定値が無い
    await expect(modal.locator(".defaultValueUi").first()).toBeHidden();
    await modal.locator('input[name="fieldLabel"]').fill(label);

    const [response] = await Promise.all([
      page.waitForResponse(
        (r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("mode=add"),
        { timeout: 20000 },
      ),
      modal.locator('button.btn-success[name="saveButton"]').click(),
    ]);
    const json = (await response.json()) as { success: boolean; result: { name: string; type: string; uitype: string } };
    expect(json.success).toBe(true);
    // データ型は date のまま（日付の検索・条件が使える）。専用の入力欄は uitype で見分ける
    expect(json.result.type).toBe("date");
    expect(json.result.uitype).toBe("996");
    fieldName = json.result.name;

    await gotoSettings(page, layoutParams);
    await expect(layoutRow(page, label)).toHaveCount(1);
  });

  test("編集画面のピッカーで月を選んで保存すると、月初日で保存され「YYYY年M月」で出る", async ({ page }) => {
    record = await createRecordViaApi(MODULE, { question: `e2eq${token}` });
    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=Edit&record=${record.recordId}`);

    const field = editField(page);
    const trigger = field.locator('[data-testid="yearmonth-trigger"]');
    await expect(trigger).toBeVisible({ timeout: 20000 });
    await trigger.click();
    await page.locator('button[data-month="3"]').click();

    // 送信する値は月初日（DB の形式）
    await expect(field.locator(`input[name="${fieldName}"]`)).toHaveValue(/^\d{4}-03-01$/);

    await Promise.all([
      page.waitForURL(/view=Detail/, { timeout: 30000 }),
      page.locator('#EditView button[type="submit"]').first().click(),
    ]);

    await page.goto(detailUrl());
    await expect(detailValue(page)).toContainText(/^\s*\d{4}年3月\s*$/, { timeout: 20000 });

    const rows = await frQuery(record.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${record.wsId}';`);
    expect(String(rows[0][fieldName])).toMatch(/^\d{4}-03-01$/);
  });

  test("詳細画面のインライン編集で別の月に変えられる", async ({ page }) => {
    await page.goto(detailUrl());
    const cell = detailValue(page);
    await expect(cell).toContainText(/\d{4}年3月/, { timeout: 20000 });

    await cell.hover();
    await cell.locator(".editAction").first().click({ force: true });
    const editor = cell.locator(".editElement fr-field-yearmonth");
    await editor.locator('[data-testid="yearmonth-trigger"]').click({ timeout: 20000 });
    await page.locator('button[data-month="11"]').click();
    await expect(cell.locator(`input[name="${fieldName}"]`)).toHaveValue(/^\d{4}-11-01$/);

    await Promise.all([
      page.waitForResponse((r) => r.request().method() === "POST", { timeout: 15000 }),
      cell.locator(".inlineAjaxSave").click(),
    ]);
    await expect(cell.locator(".value")).toContainText(/\d{4}年11月/, { timeout: 20000 });

    await page.goto(detailUrl());
    await expect(detailValue(page)).toContainText(/\d{4}年11月/, { timeout: 20000 });
  });

  test("別の項目をインライン編集しても、年月の値は変わらない", async ({ page }) => {
    const before = await frQuery(record!.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${record!.wsId}';`);
    const expected = String(before[0][fieldName]);
    expect(expected).toMatch(/^\d{4}-11-01$/);

    await page.goto(detailUrl());
    const cell = page.locator(`#${MODULE}_detailView_fieldValue_question`);
    await expect(cell).toBeVisible({ timeout: 20000 });
    await cell.hover();
    await cell.locator(".editAction").first().click({ force: true });
    const input = cell.locator(".editElement").locator("textarea, input[type='text']").first();
    await input.fill(`e2eq-inline-${token}`);
    await Promise.all([
      page.waitForResponse((r) => r.request().method() === "POST", { timeout: 15000 }),
      cell.locator(".inlineAjaxSave").click(),
    ]);
    await expect(cell.locator(".value")).toContainText(`e2eq-inline-${token}`, { timeout: 20000 });

    // 年月の値は消えず、表示も年月のまま
    const after = await frQuery(record!.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${record!.wsId}';`);
    expect(String(after[0][fieldName])).toBe(expected);
    await page.goto(detailUrl());
    await expect(detailValue(page)).toContainText(/\d{4}年11月/, { timeout: 20000 });
  });

  test("年を切り替えて月を選べる", async ({ page }) => {
    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=Edit&record=${record!.recordId}`);
    const field = editField(page);
    await field.locator('[data-testid="yearmonth-trigger"]').click({ timeout: 20000 });
    // ピッカーはポータルに出るため、入力欄の外から探す
    const year = await page.locator('[data-testid="yearmonth-year"]').first().textContent();
    const current = Number(String(year).replace(/\D/g, ""));
    expect(current).toBeGreaterThan(1900);

    // 1 つ前の年に切り替えて 6 月を選ぶ
    await page.getByRole("button", { name: "前の年" }).click();
    await page.locator('button[data-month="6"]').click();
    await expect(field.locator(`input[name="${fieldName}"]`)).toHaveValue(new RegExp(`^${current - 1}-06-01$`));

    await Promise.all([
      page.waitForURL(/view=Detail/, { timeout: 30000 }),
      page.locator('#EditView button[type="submit"]').first().click(),
    ]);
    const rows = await frQuery(record!.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${record!.wsId}';`);
    expect(String(rows[0][fieldName])).toBe(`${current - 1}-06-01`);
  });

  test("クリアして保存すると空になり、詳細は空欄になる", async ({ page }) => {
    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=Edit&record=${record!.recordId}`);
    const field = editField(page);
    await field.locator('[data-testid="yearmonth-trigger"]').click({ timeout: 20000 });
    await field.locator('[data-testid="yearmonth-clear"]').click();
    await expect(field.locator(`input[name="${fieldName}"]`)).toHaveValue("");

    await Promise.all([
      page.waitForURL(/view=Detail/, { timeout: 30000 }),
      page.locator('#EditView button[type="submit"]').first().click(),
    ]);
    const rows = await frQuery(record!.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${record!.wsId}';`);
    expect(String(rows[0][fieldName] ?? "")).toBe("");

    await page.goto(detailUrl());
    await expect(detailValue(page)).toBeVisible({ timeout: 20000 });
    await expect(detailValue(page)).not.toContainText(/\d{4}年/);
  });

  // 途中で失敗しても後始末するよう afterAll で行う（serial は失敗以降のテストを飛ばすため）
  test.afterAll(async ({ browser, workerStorageState }) => {
    const page = await browser.newPage({ storageState: workerStorageState });
    try {
      const session = record?.session ?? (await apiSession());
      if (record) {
        await deleteRecordViaApi(session, record.wsId);
      }
      await deleteFieldIfExists(page, label);
    } finally {
      await page.close();
    }
  });
});
