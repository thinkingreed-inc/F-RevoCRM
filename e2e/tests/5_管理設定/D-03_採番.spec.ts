import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString, BASE_URL } from "../../utils/util";
import { gotoSettings } from "../../utils/settings";

/**
 * D-03 レコード番号の設定 (設定 > レコード番号 = CustomRecordNumbering)
 *
 * 連番項目はすべて自動採番項目(uitype 997)に移行済みで、この画面が設定の入口。
 * 画面は #EditView フォーム(モーダルではない)。保存は CustomRecordNumberingAjax への POST で、
 * 画面遷移しない。保存ボタン(button.saveButton)は初期状態 disabled で、入力の変更で有効になる。
 *
 * 検証:
 *   1. 画面に、選んだモジュールの自動採番項目と設定(書式・リセット・開始番号・基準日・有効)が出る
 *   2. モジュールを切り替えると、項目の選択肢・書式・遡及採番の対象が、そのモジュールのものに変わる
 *   3. 書式・リセット・有効を保存すると永続化され、元に戻せる
 *   4. 不正な書式はエラーが出て保存されない
 *
 * 番号の設定はモジュール横断の永続設定のため、変更は元の値を退避してから行い、最後に必ず戻す。
 * 他のテストが同時に使わない ServiceContracts で行う（書式を変えている間に作られるレコードの番号が変わるため）。
 */
test.describe("管理: レコード番号の設定 (CustomRecordNumbering)", () => {
  const params = { module: "Vtiger", view: "CustomRecordNumbering" };
  const MODULE = "ServiceContracts";

  // 同じモジュールの設定を書き換えるため、テスト同士も並列にしない
  test.describe.configure({ mode: "serial" });

  const moduleSelect = (page: Page) => page.locator('#EditView select[name="sourceModule"]');
  const fieldSelect = (page: Page) => page.locator('#EditView select[name="field"]');
  const formatInput = (page: Page) => page.locator('#EditView [data-test-id="autonumber-format"]');
  const saveButton = (page: Page) => page.locator('#EditView [data-test-id="autonumber-save"]');

  const numberingUrl = (query: Record<string, string> = {}) => {
    const search = new URLSearchParams({ module: "Vtiger", parent: "Settings", view: "CustomRecordNumbering", ...query });
    return `${BASE_URL}index.php?${search.toString()}`;
  };

  async function open(page: Page, query: Record<string, string> = {}) {
    await page.goto(numberingUrl(query));
    await expect(formatInput(page)).toBeVisible({ timeout: 20000 });
  }

  /** select2 の元の select は隠れているため、値を直接変えて change を発火させる */
  async function choose(page: Page, select: ReturnType<typeof moduleSelect>, value: string) {
    await Promise.all([
      page.waitForURL(/view=CustomRecordNumbering/, { timeout: 20000 }),
      select.selectOption(value, { force: true }),
    ]);
    await expect(formatInput(page)).toBeVisible({ timeout: 20000 });
  }

  async function save(page: Page) {
    const [response] = await Promise.all([
      page.waitForResponse(
        (r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("saveModuleCustomNumberingData"),
        { timeout: 20000 },
      ),
      saveButton(page).click(),
    ]);
    return (await response.json()) as { success: boolean; error?: { message: string } };
  }

  async function moduleValues(page: Page): Promise<string[]> {
    return moduleSelect(page).locator("option").evaluateAll((options) => options.map((o) => (o as HTMLOptionElement).value));
  }

  test("画面に自動採番項目の設定が出る", async ({ page }) => {
    await gotoSettings(page, params);
    await expect(formatInput(page)).toBeVisible({ timeout: 20000 });
    await expect(moduleSelect(page)).not.toHaveValue("");
    await expect(fieldSelect(page).locator("option")).not.toHaveCount(0);
    await expect(formatInput(page)).not.toHaveValue("");
    for (const testId of ["autonumber-reset", "autonumber-start", "autonumber-base-date", "autonumber-effective-from", "autonumber-active"]) {
      await expect(page.locator(`#EditView [data-test-id="${testId}"]`)).toHaveCount(1);
    }
    // 変更するまでは保存できない
    await expect(saveButton(page)).toBeDisabled();
    // 遡及採番の欄が選んだ項目で出る
    const backfill = page.locator('#EditView [data-test-id="autonumber-backfill"]');
    await expect(backfill).toHaveAttribute("data-module", await moduleSelect(page).inputValue());
    await expect(backfill).toHaveAttribute("data-field", await fieldSelect(page).inputValue());
  });

  test("モジュールを切り替えると、項目と書式が切り替わる", async ({ page }) => {
    await open(page);
    const modules = await moduleValues(page);
    expect(modules.length).toBeGreaterThan(1);

    // モジュールごとの項目・書式を集める
    const seen: Record<string, { fields: string[]; format: string; backfillModule: string; backfillField: string }> = {};
    for (const name of modules.slice(0, 4)) {
      await open(page, { sourceModule: name });
      await expect(moduleSelect(page)).toHaveValue(name);
      const backfill = page.locator('#EditView [data-test-id="autonumber-backfill"]');
      seen[name] = {
        fields: await fieldSelect(page).locator("option").evaluateAll((options) => options.map((o) => (o as HTMLOptionElement).value)),
        format: await formatInput(page).inputValue(),
        backfillModule: (await backfill.getAttribute("data-module")) ?? "",
        backfillField: (await backfill.getAttribute("data-field")) ?? "",
      };
      expect(seen[name].fields.length).toBeGreaterThan(0);
      expect(seen[name].backfillModule).toBe(name);
      expect(seen[name].backfillField).toBe(seen[name].fields[0]);
    }

    // 画面上でモジュールを変えると、別のモジュールの項目と書式に入れ替わる（画面の再読み込みで）
    const [first, second] = modules;
    await open(page, { sourceModule: first });
    await choose(page, moduleSelect(page), second);
    await expect(moduleSelect(page)).toHaveValue(second);
    expect(page.url()).toContain(`sourceModule=${second}`);
    const switchedFields = await fieldSelect(page).locator("option").evaluateAll((options) => options.map((o) => (o as HTMLOptionElement).value));
    expect(switchedFields).toEqual(seen[second].fields);
    await expect(formatInput(page)).toHaveValue(seen[second].format);
    await expect(page.locator('#EditView [data-test-id="autonumber-backfill"]')).toHaveAttribute("data-module", second);

    // 戻すと元の書式に戻る
    await choose(page, moduleSelect(page), first);
    await expect(moduleSelect(page)).toHaveValue(first);
    await expect(formatInput(page)).toHaveValue(seen[first].format);
  });

  test("自動採番項目を持たないモジュールを指定したときは、先頭のモジュールを開く", async ({ page }) => {
    await page.goto(numberingUrl({ sourceModule: "Home" }));
    await expect(formatInput(page)).toBeVisible({ timeout: 20000 });
    const modules = await moduleValues(page);
    await expect(moduleSelect(page)).toHaveValue(modules[0]);
  });

  test("モジュールの一覧には自動採番項目を持つモジュールだけが出る", async ({ page }) => {
    await open(page);
    const modules = await moduleValues(page);
    for (const name of [MODULE, "Accounts", "Leads"]) {
      expect(modules).toContain(name);
    }
    expect(modules).not.toContain("Home");
    expect(modules).not.toContain("Calendar");
  });

  test("書式・リセット・有効を保存すると永続化され、元に戻せる", async ({ page }) => {
    const token = generateRandomString(5).toUpperCase();
    const testFormat = `E2E${token}-{YYYY}-{000}`;

    await open(page, { sourceModule: MODULE });
    const originalFormat = await formatInput(page).inputValue();
    const originalReset = await page.locator('#EditView [name="reset_type"]').inputValue();
    const originalStart = await page.locator('#EditView [name="start_number"]').inputValue();
    const originalActive = await page.locator('#EditView [data-test-id="autonumber-active"]').isChecked();
    const fieldName = await fieldSelect(page).inputValue();

    try {
      await formatInput(page).fill(testFormat);
      await page.locator('#EditView [name="reset_type"]').selectOption("year");
      await expect(saveButton(page)).toBeEnabled();
      expect((await save(page)).success).toBe(true);

      // 再読み込みしても、保存した値が出る
      await open(page, { sourceModule: MODULE, field: fieldName });
      await expect(formatInput(page)).toHaveValue(testFormat);
      await expect(page.locator('#EditView [name="reset_type"]')).toHaveValue("year");
      await expect(page.locator('#EditView [name="start_number"]')).toHaveValue(originalStart);
      await expect(saveButton(page)).toBeDisabled();

      // 有効のチェックも保存される
      await page.locator('#EditView [data-test-id="autonumber-active"]').setChecked(!originalActive);
      expect((await save(page)).success).toBe(true);
      await open(page, { sourceModule: MODULE, field: fieldName });
      expect(await page.locator('#EditView [data-test-id="autonumber-active"]').isChecked()).toBe(!originalActive);
    } finally {
      // 原状復帰
      await open(page, { sourceModule: MODULE, field: fieldName });
      await formatInput(page).fill(originalFormat);
      await page.locator('#EditView [name="reset_type"]').selectOption(originalReset);
      await page.locator('#EditView [data-test-id="autonumber-active"]').setChecked(originalActive);
      await saveButton(page).click({ force: true });
      await page.waitForResponse((r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("saveModuleCustomNumberingData"), { timeout: 20000 });
    }

    await open(page, { sourceModule: MODULE, field: fieldName });
    await expect(formatInput(page)).toHaveValue(originalFormat);
    await expect(page.locator('#EditView [name="reset_type"]')).toHaveValue(originalReset);
    expect(await page.locator('#EditView [data-test-id="autonumber-active"]').isChecked()).toBe(originalActive);
  });

  test("不正な書式はエラーが出て保存されない", async ({ page }) => {
    await open(page, { sourceModule: MODULE });
    const originalFormat = await formatInput(page).inputValue();

    // 連番が無い書式
    await formatInput(page).fill("NG-{YYYY}");
    const result = await save(page);
    expect(result.success).toBe(false);
    expect(result.error?.message ?? "").not.toBe("");

    await open(page, { sourceModule: MODULE });
    await expect(formatInput(page)).toHaveValue(originalFormat);
  });

  test("書式が空のときは送信されない", async ({ page }) => {
    await open(page, { sourceModule: MODULE });
    const posted: string[] = [];
    page.on("request", (r) => {
      if (r.method() === "POST" && (r.postData() ?? "").includes("saveModuleCustomNumberingData")) posted.push(r.url());
    });
    await formatInput(page).fill("");
    await saveButton(page).click({ force: true });
    await page.waitForTimeout(500);
    expect(posted).toHaveLength(0);
  });
});
