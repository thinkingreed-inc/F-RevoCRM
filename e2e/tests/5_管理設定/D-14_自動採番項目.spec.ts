import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString, BASE_URL } from "../../utils/util";
import { gotoSettings, confirmYes } from "../../utils/settings";
import { createRecordViaApi, deleteRecordViaApi, type CreatedRecord } from "../../utils/record";
import { frQuery } from "../../model/fetcher";
import { apiSession } from "../../utils/api";

/**
 * D-14 自動採番項目（uitype 997）
 *
 * レイアウトエディタで「自動採番」項目を Faq に追加し、以下を直列で検証する:
 *   1. 項目の追加（設定は「レコード番号」の画面で行う旨が出る。データ型は string）
 *   2. 「レコード番号」の画面で書式を設定する。不正な書式は保存できない
 *   3. 新規レコードに書式どおりの番号が付き、詳細に出る（DB でも確かめる）
 *   4. 編集画面では入力できず（送信もしない）、保存しても番号は変わらない
 *   5. 「レコード番号」の画面に遡及採番の欄が出て、「対象を確認」で件数が出る（開始はしない）
 *   6. 同じモジュールの項目を切り替えると、その項目の設定（書式など）に入れ替わる
 *   7. リセット・開始番号・基準日・有効の保存が永続し、停止中は新規レコードに番号が付かない
 *   8. 遡及採番の開始・一時停止・再開・中止（番号は付けずに中止する）
 * 後始末（レコード・項目の削除）は afterAll で必ず行う。
 */
test.describe.serial("管理: 自動採番項目", () => {
  const MODULE = "Faq";
  const layoutParams = {
    module: "LayoutEditor",
    view: "Index",
    sourceModule: MODULE,
    mode: "showFieldLayout",
  };

  const token = generateRandomString(6).toUpperCase();
  const label = `e2eno${token}`;
  const prefix = `E2E${token}-`;

  let fieldName = "";
  let record: CreatedRecord | null = null;
  let firstNumber = "";

  const layoutRow = (page: Page, text: string) =>
    page.locator("#moduleBlocks li").filter({ has: page.locator(".fieldLabel b", { hasText: text }) });

  const detailValue = (page: Page) => page.locator(`#${MODULE}_detailView_fieldValue_${fieldName}`);

  const detailUrl = () =>
    `${BASE_URL}index.php?module=${MODULE}&view=Detail&record=${record!.recordId}&mode=showDetailViewByMode&requestMode=full`;

  async function openAddModal(page: Page) {
    await gotoSettings(page, layoutParams);
    await page.waitForLoadState("networkidle").catch(() => {});
    await page.locator("button.addCustomField").first().click();
    const modal = page.locator(".createCustomFieldForm:visible");
    await modal.waitFor({ state: "visible", timeout: 10000 });
    await modal.locator('select[name="fieldType"]').selectOption("AutoNumber");
    return modal;
  }

  const numberingUrl = () =>
    `${BASE_URL}index.php?module=Vtiger&parent=Settings&view=CustomRecordNumbering&sourceModule=${MODULE}&field=${fieldName}`;

  async function saveNumbering(page: Page, format: string) {
    await page.goto(numberingUrl());
    const input = page.locator('[data-test-id="autonumber-format"]');
    await expect(input).toBeVisible({ timeout: 20000 });
    await input.fill(format);
    await page.locator('[name="reset_type"]').selectOption("none");
    const [response] = await Promise.all([
      page.waitForResponse(
        (r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("saveModuleCustomNumberingData"),
        { timeout: 20000 },
      ),
      page.locator('[data-test-id="autonumber-save"]').click(),
    ]);
    return (await response.json()) as { success: boolean };
  }

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

  test("自動採番項目を追加できる", async ({ page }) => {
    const modal = await openAddModal(page);

    // 設定は「レコード番号」の画面で行う旨が出て、既定値の入力欄は出ない
    await expect(modal.locator('[data-test-id="autonumber-settings"]')).toBeVisible();
    await expect(modal.locator('[name="autonumber_format"]')).toHaveCount(0);
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
    // データ型は string のまま（検索・条件・並べ替えは文字の項目として使える）
    expect(json.result.type).toBe("string");
    expect(json.result.uitype).toBe("997");
    fieldName = json.result.name;
  });

  test("「レコード番号」の画面で、不正な書式は保存できず、正しい書式は保存できる", async ({ page }) => {
    // 連番が無い書式
    expect((await saveNumbering(page, "NG-{YYYY}")).success).toBe(false);
    expect((await saveNumbering(page, `${prefix}{0000}`)).success).toBe(true);
  });

  test("新規レコードに番号が付き、詳細に出る", async ({ page }) => {
    record = await createRecordViaApi(MODULE, { question: `e2eq${token}` });

    const rows = await frQuery(record.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${record.wsId}';`);
    firstNumber = String(rows[0][fieldName]);
    expect(firstNumber).toMatch(new RegExp(`^${prefix}\\d{4}$`));

    await page.goto(detailUrl());
    await expect(detailValue(page)).toContainText(firstNumber, { timeout: 20000 });
    // インライン編集の対象にしない
    await detailValue(page).hover();
    await expect(detailValue(page).locator(".editAction")).toHaveCount(0);
  });

  test("編集画面では入力できず、保存しても番号は変わらない", async ({ page }) => {
    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=Edit&record=${record!.recordId}`);
    await expect(page.locator("#EditView")).toBeVisible({ timeout: 20000 });
    // 値はサーバーが付けるため、送信する入力欄にはしない
    await expect(page.locator(`#EditView [name="${fieldName}"]`)).toHaveCount(0);
    // 「読み取り専用項目を編集画面に表示」が有効なモジュールでは、無効の入力欄として番号を出す
    const readonlyInput = page.locator(`input[data-fieldname="${fieldName}"]`);
    if ((await readonlyInput.count()) > 0) {
      await expect(readonlyInput).toBeDisabled();
      await expect(readonlyInput).toHaveValue(firstNumber);
    }

    await Promise.all([
      page.waitForURL(/view=Detail/, { timeout: 30000 }),
      page.locator('#EditView button[type="submit"]').first().click(),
    ]);

    const rows = await frQuery(record!.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${record!.wsId}';`);
    expect(String(rows[0][fieldName])).toBe(firstNumber);
  });

  test("「レコード番号」の画面に遡及採番の欄が出て、対象の件数を確認できる", async ({ page }) => {
    await page.goto(numberingUrl());
    await expect(page.locator('[data-test-id="autonumber-format"]')).toHaveValue(`${prefix}{0000}`, { timeout: 20000 });
    const backfill = page.locator('[data-test-id="autonumber-backfill"]');
    await expect(backfill).toBeVisible();

    // 件数の確認だけ行う（開始すると他のレコードに番号を付けてしまうため、ここでは開始しない）
    await backfill.locator(".js-backfill-preview").click();
    await expect(backfill.locator(".js-backfill-preview-text")).toContainText(/\d+/, { timeout: 20000 });
  });

  const fieldOptions = (page: Page) =>
    page.locator('#EditView select[name="field"] option').evaluateAll((options) => options.map((o) => (o as HTMLOptionElement).value));

  test("同じモジュールの項目を切り替えると、その項目の設定に入れ替わる", async ({ page }) => {
    await page.goto(`${BASE_URL}index.php?module=Vtiger&parent=Settings&view=CustomRecordNumbering&sourceModule=${MODULE}`);
    await expect(page.locator('[data-test-id="autonumber-format"]')).toBeVisible({ timeout: 20000 });
    const fields = await fieldOptions(page);
    expect(fields).toContain(fieldName);
    expect(fields.length).toBeGreaterThan(1);
    const other = fields.find((name) => name !== fieldName)!;

    // 追加した項目を選ぶ → 書式・遡及採番の対象が追加した項目のものになる
    await Promise.all([
      page.waitForURL(new RegExp(`field=${fieldName}`), { timeout: 20000 }),
      page.locator('#EditView select[name="field"]').selectOption(fieldName, { force: true }),
    ]);
    await expect(page.locator('[data-test-id="autonumber-format"]')).toHaveValue(`${prefix}{0000}`, { timeout: 20000 });
    await expect(page.locator('[data-test-id="autonumber-backfill"]')).toHaveAttribute("data-field", fieldName);

    // もう一つの項目に変える → 別の書式、遡及採番の対象も変わる
    await Promise.all([
      page.waitForURL(new RegExp(`field=${other}`), { timeout: 20000 }),
      page.locator('#EditView select[name="field"]').selectOption(other, { force: true }),
    ]);
    await expect(page.locator('[data-test-id="autonumber-format"]')).not.toHaveValue(`${prefix}{0000}`, { timeout: 20000 });
    await expect(page.locator('[data-test-id="autonumber-backfill"]')).toHaveAttribute("data-field", other);
    // モジュールは変わらない
    await expect(page.locator('#EditView select[name="sourceModule"]')).toHaveValue(MODULE);
  });

  test("リセット・開始番号・基準日の保存が永続し、書式の変更が次の番号に効く", async ({ page }) => {
    await page.goto(numberingUrl());
    await expect(page.locator('[data-test-id="autonumber-format"]')).toBeVisible({ timeout: 20000 });
    await page.locator('[data-test-id="autonumber-format"]').fill(`${prefix}{YYYY}{MM}-{000}`);
    await page.locator('[name="reset_type"]').selectOption("month");
    await page.locator('[name="start_number"]').fill("50");
    const baseDate = page.locator('[name="base_date"]');
    const dateOptions = await baseDate.locator("option").evaluateAll((options) => options.map((o) => (o as HTMLOptionElement).value));
    const chosenBaseDate = dateOptions.find((value) => value !== "") ?? "";
    if (chosenBaseDate) {
      await baseDate.selectOption(chosenBaseDate);
    }
    const [response] = await Promise.all([
      page.waitForResponse(
        (r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("saveModuleCustomNumberingData"),
        { timeout: 20000 },
      ),
      page.locator('[data-test-id="autonumber-save"]').click(),
    ]);
    expect(((await response.json()) as { success: boolean }).success).toBe(true);

    await page.goto(numberingUrl());
    await expect(page.locator('[data-test-id="autonumber-format"]')).toHaveValue(`${prefix}{YYYY}{MM}-{000}`, { timeout: 20000 });
    await expect(page.locator('[name="reset_type"]')).toHaveValue("month");
    await expect(page.locator('[name="start_number"]')).toHaveValue("50");
    await expect(page.locator('[name="base_date"]')).toHaveValue(chosenBaseDate);

    // 次に作るレコードの番号は、書式の年月と開始番号以降になる
    const created = await createRecordViaApi(MODULE, { question: `e2eq2${token}` });
    try {
      const rows = await frQuery(created.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${created.wsId}';`);
      expect(String(rows[0][fieldName])).toMatch(new RegExp(`^${prefix}\\d{6}-\\d{3}$`));
      expect(Number(String(rows[0][fieldName]).slice(-3))).toBeGreaterThanOrEqual(50);
    } finally {
      await deleteRecordViaApi(created.session, created.wsId);
    }
    // 元の書式に戻す
    expect((await saveNumbering(page, `${prefix}{0000}`)).success).toBe(true);
  });

  test("停止すると新規レコードに番号が付かず、再開すると付く", async ({ page }) => {
    await page.goto(numberingUrl());
    const active = page.locator('[data-test-id="autonumber-active"]');
    await expect(active).toBeVisible({ timeout: 20000 });
    const save = () =>
      Promise.all([
        page.waitForResponse(
          (r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("saveModuleCustomNumberingData"),
          { timeout: 20000 },
        ),
        page.locator('[data-test-id="autonumber-save"]').click(),
      ]);

    await active.setChecked(false);
    await save();
    const stopped = await createRecordViaApi(MODULE, { question: `e2eq3${token}` });
    try {
      const rows = await frQuery(stopped.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${stopped.wsId}';`);
      expect(String(rows[0][fieldName] ?? "")).toBe("");
    } finally {
      await deleteRecordViaApi(stopped.session, stopped.wsId);
    }

    await page.goto(numberingUrl());
    await expect(active).not.toBeChecked({ timeout: 20000 });
    await active.setChecked(true);
    await save();
    const resumed = await createRecordViaApi(MODULE, { question: `e2eq4${token}` });
    try {
      const rows = await frQuery(resumed.session, `SELECT id, ${fieldName} FROM ${MODULE} WHERE id = '${resumed.wsId}';`);
      expect(String(rows[0][fieldName])).toMatch(new RegExp(`^${prefix}\\d{4}$`));
    } finally {
      await deleteRecordViaApi(resumed.session, resumed.wsId);
    }
  });

  test("遡及採番を開始し、一時停止・再開・中止できる", async ({ page }) => {
    // 値が空のレコードを作るため、いったん採番を停止してレコードを作る（遡及採番の対象にする）
    await page.goto(numberingUrl());
    const active = page.locator('[data-test-id="autonumber-active"]');
    await expect(active).toBeVisible({ timeout: 20000 });
    const saveSettings = () =>
      Promise.all([
        page.waitForResponse(
          (r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("saveModuleCustomNumberingData"),
          { timeout: 20000 },
        ),
        page.locator('[data-test-id="autonumber-save"]').click(),
      ]);
    await active.setChecked(false);
    await saveSettings();
    const blank = await createRecordViaApi(MODULE, { question: `e2eq5${token}` });
    try {
      await page.goto(numberingUrl());
      const backfill = page.locator('[data-test-id="autonumber-backfill"]');
      await expect(backfill).toBeVisible({ timeout: 20000 });

      await backfill.locator(".js-backfill-preview").click();
      await expect(backfill.locator(".js-backfill-preview-text")).toContainText(/[1-9]\d*/, { timeout: 20000 });
      const start = backfill.locator(".js-backfill-start");
      await expect(start).toBeVisible();
      await start.click();
      await expect(backfill.locator(".js-backfill-status")).not.toHaveText("", { timeout: 20000 });
      await expect(backfill.locator(".js-backfill-cancel")).toBeVisible();

      // 一時停止 → 再開 → 中止（作ったレコードは新しいため、静止期間が過ぎるまで番号は付かない）
      await backfill.locator(".js-backfill-pause").click();
      await expect(backfill.locator(".js-backfill-resume")).toBeVisible({ timeout: 20000 });
      await backfill.locator(".js-backfill-resume").click();
      await expect(backfill.locator(".js-backfill-pause")).toBeVisible({ timeout: 20000 });
      await backfill.locator(".js-backfill-cancel").click();
      await expect(backfill.locator(".js-backfill-cancel")).toBeHidden({ timeout: 20000 });
      await expect(backfill.locator(".js-backfill-preview")).toBeVisible();
    } finally {
      await deleteRecordViaApi(blank.session, blank.wsId);
      await page.goto(numberingUrl());
      await expect(active).toBeVisible({ timeout: 20000 });
      await active.setChecked(true);
      await saveSettings();
    }
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
