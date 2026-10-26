import { test, expect } from "../../fixtures/isolated";
import type { Page, Locator } from "@playwright/test";
import * as fs from "fs";
import * as path from "path";
import { generateRandomString, BASE_URL } from "../../utils/util";
import { gotoSettings, confirmYes } from "../../utils/settings";
import { createRecordViaApi, deleteRecordViaApi, type CreatedRecord } from "../../utils/record";
import { frDelete, frQuery } from "../../model/fetcher";
import { apiSession } from "../../utils/api";

/**
 * D-12 ファイルアップロード項目（uitype 998）
 *
 * レイアウトエディタで「ファイルアップロード」項目（通常・手書き）を Faq に追加し、
 * 以下を直列で検証する:
 *   1. 項目の追加（手書き入力の指定を含む）
 *   2. 編集画面でファイルを選び、手書き項目に書いて保存 → 詳細にサムネイルが出る
 *   3. 一覧にファイルであることが分かる印とファイル名が出る
 *   4. アップロードしたファイルがドキュメントに登録されている
 *   5. 詳細画面のインライン編集でファイルを外せる
 * 後始末（レコード・ドキュメント・リスト・項目の削除）は afterAll で必ず行う。
 *
 * 入力欄は Web コンポーネント <fr-field-fileupload> で、data-testid で操作する。
 * 項目・ドキュメント・リストは永続的なデータのため、途中で失敗しても削除する。
 */
test.describe.serial("管理: ファイルアップロード項目", () => {
  const MODULE = "Faq";
  const layoutParams = {
    module: "LayoutEditor",
    view: "Index",
    sourceModule: MODULE,
    mode: "showFieldLayout",
  };

  const token = generateRandomString(8);
  const fileLabel = `e2efile${token}`;
  const signLabel = `e2esign${token}`;
  const imageTitle = `e2eimg${token}`;
  const imageName = `${imageTitle}.png`;
  const filterName = `e2ecv${token}`;
  const imageBuffer = fs.readFileSync(path.join(__dirname, "../../fixtures/upload/sample.png"));

  let fileFieldName = "";
  let signFieldName = "";
  let record: CreatedRecord | null = null;
  let filterId = "";

  /** レイアウト一覧上の項目行（項目ラベルで特定） */
  const layoutRow = (page: Page, label: string) =>
    page.locator("#moduleBlocks li").filter({ has: page.locator(".fieldLabel b", { hasText: label }) });

  /** 編集フォーム上の項目（Web コンポーネントを含む枠） */
  const editField = (page: Page, fieldName: string): Locator =>
    page.locator(`.fr-wc-field[data-fieldname="${fieldName}"]`);

  /** 詳細画面の項目の値 */
  const detailValue = (page: Page, fieldName: string): Locator =>
    page.locator(`#${MODULE}_detailView_fieldValue_${fieldName}`);

  const detailUrl = () =>
    `${BASE_URL}index.php?module=${MODULE}&view=Detail&record=${record!.recordId}&mode=showDetailViewByMode&requestMode=full`;

  /** レイアウトエディタでファイルアップロード項目を追加し、項目名（cf_NNN）を返す */
  async function addFileField(page: Page, label: string, handwriting: boolean): Promise<string> {
    await gotoSettings(page, layoutParams);
    await page.waitForLoadState("networkidle").catch(() => {});
    await page.locator("button.addCustomField").first().click();

    const modal = page.locator(".createCustomFieldForm:visible");
    await modal.waitFor({ state: "visible", timeout: 10000 });
    await modal.locator('select[name="fieldType"]').selectOption("FileUpload");

    // ファイルには既定値が無く、手書き入力の指定が出る
    const handwritingRow = modal.locator(".handwritingSupported");
    await expect(handwritingRow).toBeVisible();
    await expect(modal.locator(".defaultValueUi").first()).toBeHidden();

    await modal.locator('input[name="fieldLabel"]').fill(label);
    if (handwriting) {
      await handwritingRow.locator('input[type="checkbox"][name="handwriting"]').check();
    }

    const [response] = await Promise.all([
      page.waitForResponse(
        (r) => r.request().method() === "POST" && (r.request().postData() ?? "").includes("mode=add"),
        { timeout: 20000 },
      ),
      modal.locator('button.btn-success[name="saveButton"]').click(),
    ]);
    const json = (await response.json()) as { success: boolean; result: { name: string; type: string } };
    expect(json.success).toBe(true);
    expect(json.result.type).toBe("fileupload");
    return json.result.name;
  }

  /** レイアウトエディタで項目を削除する（無ければ何もしない） */
  async function deleteFieldIfExists(page: Page, label: string): Promise<void> {
    await gotoSettings(page, layoutParams);
    await page.waitForLoadState("networkidle").catch(() => {});
    const target = layoutRow(page, label);
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
    await expect(layoutRow(page, label)).toHaveCount(0);
  }

  /** vtiger の app.request.post で送る（CSRF トークンが付く） */
  async function postViaApp(page: Page, data: Record<string, unknown>): Promise<unknown> {
    return page.evaluate(
      (payload) =>
        new Promise((resolve, reject) => {
          // eslint-disable-next-line @typescript-eslint/no-explicit-any
          (window as any).app.request
            .post({ data: payload })
            .then((err: unknown, result: unknown) => (err ? reject(err) : resolve(result)));
        }),
      data,
    );
  }

  test("ファイルアップロード項目を追加できる（通常・手書き）", async ({ page }) => {
    fileFieldName = await addFileField(page, fileLabel, false);
    signFieldName = await addFileField(page, signLabel, true);

    await gotoSettings(page, layoutParams);
    await expect(layoutRow(page, fileLabel)).toHaveCount(1);
    await expect(layoutRow(page, signLabel)).toHaveCount(1);
  });

  test("編集画面でファイルを選び手書きして保存すると、詳細にサムネイルが出る", async ({ page }) => {
    record = await createRecordViaApi(MODULE, { question: `e2eq${token}` });

    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=Edit&record=${record.recordId}`);

    // 通常の項目: ファイルを選ぶとその場でドキュメントとして登録され、サムネイルが出る
    const fileField = editField(page, fileFieldName);
    await expect(fileField.locator('[data-testid="file-field-select"]')).toBeVisible({ timeout: 20000 });
    await expect(fileField.locator('[data-testid="handwriting-canvas"]')).toHaveCount(0);
    await fileField
      .locator('[data-testid="file-field-input"]')
      .setInputFiles({ name: imageName, mimeType: "image/png", buffer: imageBuffer });
    await expect(fileField.locator('[data-testid="file-field-thumbnail"]')).toBeVisible({ timeout: 20000 });
    await expect(fileField.locator('[data-testid="file-field-filename"]')).toContainText(imageName);
    // 保存するまではドキュメントを作らず、一時ファイルを指す値を入れる
    await expect(fileField.locator(`input[name="${fileFieldName}"]`)).toHaveValue(/^draft:[0-9a-f]{32}$/);

    // 手書きの項目: ファイル選択の代わりに書く欄が出る。書き終わると確定ボタンなしで自動で画像として登録する
    const signField = editField(page, signFieldName);
    const canvas = signField.locator('[data-testid="handwriting-canvas"]');
    await expect(canvas).toBeVisible();
    await expect(signField.locator('[data-testid="file-field-input"]')).toHaveCount(0);
    await canvas.scrollIntoViewIfNeeded();
    const box = await canvas.boundingBox();
    if (!box) throw new Error("手書き欄の位置が取れません");
    await page.mouse.move(box.x + 20, box.y + box.height / 2);
    await page.mouse.down();
    for (let i = 1; i <= 15; i++) {
      await page.mouse.move(box.x + 20 + i * 15, box.y + box.height / 2 + Math.sin(i / 2) * 30);
    }
    await page.mouse.up();
    await expect(signField.locator('[data-testid="handwriting-saved"]')).toBeVisible({ timeout: 20000 });
    await expect(signField.locator(`input[name="${signFieldName}"]`)).toHaveValue(/^draft:[0-9a-f]{32}$/);
    // 続けて書けるよう、書く欄は出したまま
    await expect(canvas).toBeVisible();

    // 保存
    await Promise.all([
      page.waitForURL(/view=Detail/, { timeout: 30000 }),
      page.locator('#EditView button[type="submit"]').first().click(),
    ]);

    // 詳細: 両方の項目にサムネイルとファイル名が出る
    await page.goto(detailUrl());
    const fileDetail = detailValue(page, fileFieldName);
    await expect(fileDetail.locator('[data-testid="file-field-thumbnail"]')).toBeVisible({ timeout: 20000 });
    await expect(fileDetail.locator('[data-testid="file-field-filename"]')).toContainText(imageName);
    const signDetail = detailValue(page, signFieldName);
    await expect(signDetail.locator('[data-testid="file-field-thumbnail"]')).toBeVisible({ timeout: 20000 });
    await expect(signDetail.locator('[data-testid="file-field-filename"]')).toContainText(`${signLabel}_`);
  });

  test("手書きを書き直して保存すると、同じドキュメントのまま版が増える", async ({ page }) => {
    await page.goto(detailUrl());
    const before = detailValue(page, signFieldName).locator('[data-testid="file-field-value"]');
    await expect(before).toBeVisible({ timeout: 20000 });
    const documentId = await before.getAttribute("data-document-id");
    expect(documentId).toMatch(/^[1-9][0-9]*$/);

    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=Edit&record=${record!.recordId}`);
    const signField = editField(page, signFieldName);
    await signField.locator('[data-testid="file-field-change"]').click({ timeout: 20000 });
    const canvas = signField.locator('[data-testid="handwriting-canvas"]');
    await canvas.scrollIntoViewIfNeeded();
    const box = await canvas.boundingBox();
    if (!box) throw new Error("手書き欄の位置が取れません");
    // 何度か書き足しても（自動保存が何回走っても）版は保存時の 1 回分だけ増える
    for (let stroke = 0; stroke < 3; stroke++) {
      await page.mouse.move(box.x + 30, box.y + 30 + stroke * 40);
      await page.mouse.down();
      await page.mouse.move(box.x + 200, box.y + 40 + stroke * 40, { steps: 10 });
      await page.mouse.up();
      await expect(signField.locator('[data-testid="handwriting-saved"]')).toBeVisible({ timeout: 20000 });
    }
    await Promise.all([
      page.waitForURL(/view=Detail/, { timeout: 30000 }),
      page.locator('#EditView button[type="submit"]').first().click(),
    ]);

    await page.goto(detailUrl());
    const after = detailValue(page, signFieldName).locator('[data-testid="file-field-value"]');
    await expect(after).toHaveAttribute("data-document-id", documentId ?? "", { timeout: 20000 });
    // 手書きのドキュメントは 1 件のまま（途中の内容でドキュメントを増やさない）
    const rows = await frQuery(
      record!.session,
      `SELECT id, fileversion FROM Documents WHERE notes_title LIKE '${signLabel}%';`,
    );
    expect(rows).toHaveLength(1);
    expect(String(rows[0].fileversion)).toBe("2");
  });

  test("一覧にはファイルの印とファイル名が出る", async ({ page }) => {
    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=List`);
    await page.waitForLoadState("networkidle").catch(() => {});

    // 一覧に項目を出すリストを用意する（列の選択はこのテストの対象外のため直接保存する）
    const result = (await postViaApp(page, {
      module: "CustomView",
      action: "Save",
      source_module: MODULE,
      viewname: filterName,
      status: 1,
      setdefault: 0,
      setmetrics: 0,
      columnslist: [
        "vtiger_faq:question:question:Faq_Question:V",
        `vtiger_faqcf:${fileFieldName}:${fileFieldName}:Faq_${fileLabel}:I`,
      ],
    })) as { id: string | number };
    filterId = String(result.id);

    await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=List&viewname=${filterId}`);
    const row = page.locator(`tr.listViewEntries[data-id="${record!.recordId}"]`);
    await expect(row).toBeVisible({ timeout: 20000 });
    const cell = row.locator(".fr-file-field-list");
    await expect(cell).toContainText(imageName);
    await expect(cell.locator("i.fa-paperclip")).toHaveCount(1);
  });

  test("アップロードしたファイルはドキュメントに登録されている", async ({ page }) => {
    await page.goto(`${BASE_URL}index.php?module=Documents&view=List`);
    const search = page.locator('[data-testid="documents-search-input"]');
    await expect(search).toBeVisible({ timeout: 30000 });
    await search.fill(imageTitle);
    await search.press("Enter");
    await expect(page.locator('[data-testid="documents-row"]').filter({ hasText: imageTitle }).first()).toBeVisible({
      timeout: 20000,
    });
  });

  test("詳細画面のインライン編集でファイルを差し替えると、再読み込みせずに表示され続けて編集できる", async ({ page }) => {
    await page.goto(detailUrl());
    const cell = detailValue(page, fileFieldName);
    await expect(cell.locator('[data-testid="file-field-thumbnail"]')).toBeVisible({ timeout: 20000 });
    const documentId = await cell.locator('[data-testid="file-field-value"]').getAttribute("data-document-id");

    await cell.hover();
    await cell.locator(".editAction").first().click({ force: true });
    const editor = cell.locator(".editElement fr-field-fileupload");
    await editor.locator('[data-testid="file-field-change"]').click({ timeout: 20000 });
    await editor
      .locator('[data-testid="file-field-input"]')
      .setInputFiles({ name: `${imageTitle}-v2.png`, mimeType: "image/png", buffer: imageBuffer });
    await expect(cell.locator(`input[name="${fileFieldName}"]`)).toHaveValue(/^draft:/, { timeout: 20000 });
    await Promise.all([
      page.waitForResponse((r) => r.request().method() === "POST", { timeout: 15000 }),
      cell.locator(".inlineAjaxSave").click(),
    ]);

    // 保存後は同じドキュメント（版が増える）を再読み込みなしで表示する（編集欄は隠れて残るため表示側を見る）
    const shown = cell.locator(".value");
    await expect(shown.locator('[data-testid="file-field-value"]')).toHaveAttribute("data-document-id", documentId ?? "", {
      timeout: 20000,
    });
    await expect(shown.locator('[data-testid="file-field-filename"]')).toContainText(`${imageTitle}-v2.png`);
    await expect(cell.locator(".fieldBasicData")).toHaveAttribute("data-value", documentId ?? "");

    // 続けてインライン編集しても、保存済みのファイルとして扱う
    await cell.hover();
    await cell.locator(".editAction").first().click({ force: true });
    await expect(cell.locator(`input[name="${fileFieldName}"]`)).toHaveValue(documentId ?? "");
    await cell.locator(".inlineAjaxCancel").click();
  });

  test("詳細画面のインライン編集でファイルを外せる", async ({ page }) => {
    await page.goto(detailUrl());
    const cell = detailValue(page, fileFieldName);
    await expect(cell.locator('[data-testid="file-field-thumbnail"]')).toBeVisible({ timeout: 20000 });

    await cell.hover();
    await cell.locator(".editAction").first().click({ force: true });
    const editor = cell.locator(".editElement fr-field-fileupload");
    // 直前のテストでインライン編集から差し替えたファイル（${imageTitle}-v2.png）が入っている
    await expect(editor.locator('[data-testid="file-field-filename"]')).toContainText(imageTitle, { timeout: 20000 });

    await editor.locator('[data-testid="file-field-remove"]').click();
    await expect(cell.locator(`input[name="${fileFieldName}"]`)).toHaveValue("");
    await Promise.all([
      page.waitForResponse((r) => r.request().method() === "POST", { timeout: 15000 }),
      cell.locator(".inlineAjaxSave").click(),
    ]);

    // 保存後は値が空になり、再表示してもファイルは出ない
    await page.goto(detailUrl());
    await expect(detailValue(page, fileFieldName).locator('[data-testid="file-field-filename"]')).toHaveCount(0);
    await expect(detailValue(page, signFieldName).locator('[data-testid="file-field-thumbnail"]')).toBeVisible({
      timeout: 20000,
    });
  });

  // 途中で失敗しても後始末するよう afterAll で行う（serial は失敗以降のテストを飛ばすため）
  test.afterAll(async ({ browser, workerStorageState }) => {
    const page = await browser.newPage({ storageState: workerStorageState });
    try {
      const session = record?.session ?? (await apiSession());
      // レコード
      if (record) {
        await deleteRecordViaApi(session, record.wsId);
      }
      // ドキュメント（アップロードしたファイル・手書きの画像）
      for (const title of [imageTitle, signLabel]) {
        const rows = await frQuery(session, `SELECT id FROM Documents WHERE notes_title LIKE '${title}%';`);
        for (const row of rows) {
          await frDelete(session, row.id).catch(() => false);
        }
      }
      // リスト
      if (filterId) {
        await page.goto(`${BASE_URL}index.php?module=${MODULE}&view=List`);
        await postViaApp(page, { module: "CustomView", action: "Delete", record: filterId }).catch(() => {});
      }
      // 項目
      await deleteFieldIfExists(page, fileLabel);
      await deleteFieldIfExists(page, signLabel);
    } finally {
      await page.close();
    }
  });
});
