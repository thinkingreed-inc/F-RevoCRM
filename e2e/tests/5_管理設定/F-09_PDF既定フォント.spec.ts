import { test, expect } from "../../fixtures/isolated";
import type { Locator, Page } from "@playwright/test";
import { readFileSync } from "fs";
import { resolve } from "path";
import { gotoSettings } from "../../utils/settings";
import { seedSpec } from "../../fixtures/seedSpec";
import { deleteRecordViaApi } from "../../utils/record";
import { deleteViaDetail, gotoDetail } from "../../utils/listview";
import { generateRandomString } from "../../utils/util";
import * as li from "../../utils/lineitem";

/**
 * F-09 PDF の既定フォント（システム変数 PDF_DEFAULT_FONT、thinkingreed-inc#1901）
 *
 * PDF_DEFAULT_FONT は font 型で、編集ダイアログでは libraries/tcpdf/fonts/ のフォントから選び、
 * TrueType フォント（.ttf）をアップロードして追加できる。
 * 追加したフォントを保存すると、PDF の出力に使われる（PDF に埋め込まれる）ことまで確認する。
 *
 * PDF_DEFAULT_FONT はグローバル設定のため、テストの最後に必ず元の値へ戻す。
 * 追加したフォントは削除する手段がないため、名前を e2e で始めて .gitignore で除外している
 * （CI は使い捨ての環境なので残っても影響しない）。
 */
test.describe.serial("管理: PDF の既定フォント (PDF_DEFAULT_FONT)", () => {
  const listParams = { module: "Parameters", view: "List" };
  const KEY = "PDF_DEFAULT_FONT";

  /** アップロードに使う TrueType フォント（リポジトリ同梱の OpenSans） */
  const FONT_TTF = readFileSync(
    resolve(__dirname, "../../../public/resources/fonts/OpenSans-Regular-webfont.ttf"),
  );

  const rowOf = (page: Page) =>
    page
      .locator("#listview-table tr.listViewEntries")
      .filter({ has: page.getByText(KEY, { exact: true }) });

  /** 一覧の「値」セル(列は キー / 値 / 備考 の順) */
  const valueCellOf = (page: Page) =>
    rowOf(page).locator("td.listViewEntryValue").nth(1);

  const openEditDialog = async (page: Page): Promise<Locator> => {
    await rowOf(page).locator("a.parameter-edit-btn").click();
    const opened = page.locator('[role="dialog"]:visible');
    await expect(opened.getByRole("heading", { name: KEY })).toBeVisible();
    // フォントの一覧を読み込み終わると選択できるようになる
    await expect(fontSelect(opened)).toBeEnabled();
    return opened;
  };

  const fontSelect = (opened: Locator) =>
    opened.getByRole("combobox", { name: "値" });

  const saveDialog = async (page: Page, opened: Locator): Promise<void> => {
    await opened.getByRole("button", { name: "保存" }).click();
    await expect(opened).toBeHidden({ timeout: 15000 });
    await page.waitForLoadState("networkidle").catch(() => {});
  };

  const cancelDialog = async (opened: Locator): Promise<void> => {
    await opened.getByRole("button", { name: "キャンセル" }).click();
    await expect(opened).toBeHidden();
  };

  /** 「フォントを追加」でファイルを選ぶ */
  const uploadFont = async (opened: Locator, name: string, buffer: Buffer) => {
    await opened
      .locator('input[type="file"]')
      .setInputFiles({ name, mimeType: "font/ttf", buffer });
  };

  /** 今の値を読み、テストの最後に戻せるようにする */
  const currentValue = async (page: Page): Promise<string> => {
    await gotoSettings(page, listParams);
    return (await valueCellOf(page).innerText()).trim();
  };

  const restoreValue = async (page: Page, value: string): Promise<void> => {
    await gotoSettings(page, listParams);
    const opened = await openEditDialog(page);
    await fontSelect(opened).selectOption(value);
    await saveDialog(page, opened);
  };

  test("フォントの一覧から選ぶ入力になり、シークレットの設定欄は出ない", async ({ page }) => {
    await gotoSettings(page, listParams);
    const opened = await openEditDialog(page);

    const select = fontSelect(opened);
    // 先頭は未設定（既定のフォント）
    await expect(select.locator("option").first()).toHaveText("未設定（ume-tgo4）");
    await expect(select.locator('option[value="dejavusans"]')).toHaveCount(1);
    await expect(select.locator('option[value="cid0jp"]')).toHaveCount(1);
    // 文字コード表や太字用のファイルは選ばせない
    await expect(select.locator('option[value="uni2cid_aj16"]')).toHaveCount(0);
    await expect(select.locator('option[value="dejavusansb"]')).toHaveCount(0);

    await expect(opened.getByRole("button", { name: "フォントを追加" })).toBeVisible();
    await expect(opened.getByText("シークレット", { exact: true })).toHaveCount(0);
    await cancelDialog(opened);
  });

  test("フォントを追加して保存すると、PDF の出力にそのフォントが使われる", async ({ page }) => {
    test.setTimeout(180000);
    const originalValue = await currentValue(page);
    const suffix = generateRandomString(6).toLowerCase().replace(/[^a-z0-9]/g, "");
    const fontName = `e2efont${suffix}`;
    const cfg = li.INVENTORY_MODULES.find((m) => m.module === "Invoice")!;
    let created: li.CreatedInventoryRecord | null = null;

    try {
      const opened = await openEditDialog(page);
      await uploadFont(opened, `E2EFont${suffix}.ttf`, FONT_TTF);

      // 追加したフォントが選ばれた状態になる（保存はまだ）
      await expect(opened.getByText(`フォント「${fontName}」を追加しました。`)).toBeVisible({
        timeout: 30000,
      });
      await expect(fontSelect(opened)).toHaveValue(fontName);
      await saveDialog(page, opened);

      await gotoSettings(page, listParams);
      await expect(valueCellOf(page)).toHaveText(fontName);

      // 請求の PDF に追加したフォントが埋め込まれる
      created = await li.createInventoryRecord(page, cfg, {
        suffix,
        product: seedSpec.inventory.products[0],
        qty: 1,
      });
      await gotoDetail(page, cfg.module, created.recordId);
      await page.click("text=その他");
      const [download] = await Promise.all([
        page.waitForEvent("download", { timeout: 30000 }),
        page.locator("a", { hasText: /PDFにエクスポート/ }).first().click(),
      ]);
      const pdf = readFileSync((await download.path())!).toString("latin1");
      expect(pdf.startsWith("%PDF-")).toBe(true);
      // サブセット化されたフォントは「6 文字の英大文字 + フォント名」で埋め込まれる
      expect(pdf).toMatch(/\/BaseFont \/[A-Z]{6}\+OpenSans/);
    } finally {
      if (created) {
        await deleteViaDetail(page, cfg.module, created.recordId);
        await deleteRecordViaApi(created.ref.session, created.ref.wsId);
      }
      await restoreValue(page, originalValue);
    }
  });

  test("同じ名前のフォントは追加できない", async ({ page }) => {
    await gotoSettings(page, listParams);
    const opened = await openEditDialog(page);
    const before = await fontSelect(opened).inputValue();

    // dejavusans は同梱のフォント。置き換えられないこと
    await uploadFont(opened, "DejaVuSans.ttf", FONT_TTF);

    await expect(opened.getByRole("alert")).toHaveText(
      "同じ名前のフォントがすでにあります。一覧から選択してください。",
    );
    await expect(fontSelect(opened)).toHaveValue(before);
    await cancelDialog(opened);
  });

  test("TrueType 以外のファイルは追加できない", async ({ page }) => {
    await gotoSettings(page, listParams);
    const opened = await openEditDialog(page);

    // 拡張子が .ttf でないものは送る前に止める
    await uploadFont(opened, "Font.otf", FONT_TTF);
    await expect(opened.getByRole("alert")).toHaveText(
      "追加できるのはTrueTypeフォント（.ttf）だけです。",
    );

    // 拡張子が .ttf でも中身が TrueType でなければサーバーが断る
    await uploadFont(opened, "e2enotfont.ttf", Buffer.from("<?php echo 1;"));
    await expect(opened.getByRole("alert")).toContainText(
      "TrueTypeフォントとして読み込めませんでした。",
    );
    await cancelDialog(opened);
  });
});
