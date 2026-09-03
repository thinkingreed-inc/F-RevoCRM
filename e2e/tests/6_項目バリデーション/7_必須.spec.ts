import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * 6-7 必須項目 UI 検証(Excel ⑮⑯ 必須項目)
 *
 * 既存の Faq 項目(question 等)を直接必須解除するとスキーマを恒久的に汚すため、
 * 使い捨てのカスタム項目(テキスト単数行)を LayoutEditor で作成し、その場で
 * 必須(mandatory)に設定して検証する:
 *   (1) 使い捨て必須項目を空のまま保存 → ブロックされる(record= が発行されない、
 *       可視のバリデーションエラーがある)
 *   (2) 有効値を入れて保存 → 保存される(record= が発行される)
 *
 * 永続的な設定変更のため、作成した使い捨て項目は finally で必ず削除する。さらに、
 * 前回実行が途中で落ちて項目が残っていても緑で始められるよう、作成前にも防御的に
 * 削除する(冪等性: 何度連続実行しても pass する)。コア項目・コアブロックには触れない。
 *
 * セレクタは 0_項目作成.spec.ts(Task 6)で実証済みのものを踏襲する:
 *   追加ボタン    : button.addCustomField(.first())
 *   作成モーダル  : .createCustomFieldForm:visible(型 select[name=fieldType])
 *   ラベル入力    : input[name="fieldLabel"] / 桁数 input[name="fieldLength"](Text は必須)
 *   保存          : button.btn-success[name="saveButton"]
 *   削除          : 項目行の a.deleteCustomField → 確認ダイアログ .confirm-box-ok
 *
 * 必須トグルは実 DOM 調査(本タスクで実施)により判明した挙動:
 *   項目行内 span.mandatory をクリックするとポップアップ等なしに即 AJAX
 *   (index.php への POST)で切り替わり、レスポンスに mandatory:true が返る。
 *   ラベル横に <span class="redColor">*</span> が付き、以後 Faq 作成フォームで
 *   data-rule-required="true" として描画される。
 */
const MODULE = "Faq";
const THROWAWAY_LABEL = "E2E使い捨て必須";

const listParams = {
  module: "LayoutEditor",
  view: "Index",
  sourceModule: MODULE,
  mode: "showFieldLayout",
};

// 一覧上の対象項目行(項目ラベルで特定)
const fieldRow = (page: Page) =>
  page
    .locator("#moduleBlocks li")
    .filter({ has: page.locator(".fieldLabel b", { hasText: THROWAWAY_LABEL }) });

/** 使い捨て項目が一覧に残っていれば UI で削除する(存在しなければ何もしない)。 */
async function deleteThrowawayIfExists(page: Page): Promise<void> {
  await gotoSettings(page, listParams);
  await page.waitForLoadState("networkidle").catch(() => {});
  const target = fieldRow(page);
  if ((await target.count()) === 0) return;

  await Promise.all([
    page
      .waitForResponse((r) => r.request().method() === "POST", { timeout: 15000 })
      .catch(() => {}),
    (async () => {
      await target.first().locator("a.deleteCustomField").click();
      await confirmYes(page);
    })(),
  ]);
  await page.waitForLoadState("networkidle").catch(() => {});

  // 削除完了をリロードで確認(残っていれば以降のアサーションで検知される)
  await gotoSettings(page, listParams);
  await expect(fieldRow(page)).toHaveCount(0);
}

/** 使い捨て項目を作成し、内部項目名(name属性値。例: cf_890)を返す。 */
async function createThrowawayField(page: Page): Promise<string> {
  await gotoSettings(page, listParams);
  await page.waitForLoadState("networkidle").catch(() => {});
  await page.locator("button.addCustomField").first().click();

  const modal = page.locator(".createCustomFieldForm:visible");
  await modal.waitFor({ state: "visible", timeout: 10000 });
  await modal.locator('select[name="fieldType"]').selectOption("Text");
  await modal.locator('input[name="fieldLabel"]').fill(THROWAWAY_LABEL);
  await modal.locator('input[name="fieldLength"]').fill("100"); // Text 型は桁数(必須)
  await saveAndSettle(page, modal.locator('button.btn-success[name="saveButton"]'));

  await gotoSettings(page, listParams);
  await expect(fieldRow(page)).toHaveCount(1);

  const fieldName = await fieldRow(page)
    .first()
    .locator(".editFields")
    .getAttribute("data-field-name");
  if (!fieldName) throw new Error("作成した使い捨て項目の内部項目名が取得できません");
  return fieldName;
}

/** 一覧上で対象項目行の「必須」トグル(span.mandatory)をクリックして必須化する。 */
async function makeMandatory(page: Page): Promise<void> {
  await gotoSettings(page, listParams);
  await page.waitForLoadState("networkidle").catch(() => {});
  const row = fieldRow(page).first();

  await Promise.all([
    page
      .waitForResponse(
        (r) => r.url().includes("index.php") && r.request().method() === "POST",
        { timeout: 15000 }
      )
      .catch(() => {}),
    row.locator("span.mandatory").click(),
  ]);
  await page.waitForLoadState("networkidle").catch(() => {});

  // 必須化されたこと(ラベル横の * 表示)をリロードして確認する
  await gotoSettings(page, listParams);
  await page.waitForLoadState("networkidle").catch(() => {});
  await expect(
    fieldRow(page).first().locator(".fieldLabel .redColor", { hasText: "*" })
  ).toBeVisible();
}

/** Faq 作成フォームを開き、既存の必須項目(質問/回答/ステータス)を有効値で埋める。 */
async function openFaqCreateAndFillCoreMandatory(page: Page): Promise<void> {
  await page.goto(`${page.url().split("index.php")[0]}index.php?module=${MODULE}&view=Edit&app=MARKETING`);
  await page.waitForLoadState("domcontentloaded");

  // ステータス(選択肢・必須): 先頭の空オプション以外を選択
  await page.locator('select[name="faqstatus"]').selectOption("Draft");

  // 質問/回答(必須・リッチテキスト Jodit): 項目セル内の .jodit-wysiwyg に入力する
  for (const name of ["question", "faq_answer"]) {
    const cell = page
      .locator(`#Faq_editView_fieldName_${name}`)
      .locator('xpath=ancestor::*[contains(@class,"fieldValue")][1]');
    const editor = cell.locator(".jodit-wysiwyg").first();
    await editor.click();
    await editor.fill(`E2E必須検証_${name}`);
    await editor.blur();
  }
}

test.describe("項目バリデーション: 必須項目", () => {
  test("使い捨て必須項目は空だと保存できず、有効値だと保存できる", async ({ page }) => {
    // 防御的な事前後始末(前回実行の残骸があっても緑で始める)
    await deleteThrowawayIfExists(page);

    try {
      const fieldName = await createThrowawayField(page);
      await makeMandatory(page);

      // --- (1) 必須項目を空のまま保存 → ブロックされる ---
      await openFaqCreateAndFillCoreMandatory(page);
      // 使い捨て必須項目は未入力のまま保存
      await page.locator("button.saveButton").first().click();
      await page.waitForTimeout(1500);

      expect(page.url()).not.toMatch(/[?&]record=\d+/);
      const errorCount = await page
        .locator(
          [
            "label.error:visible",
            "span.error:visible",
            `#${MODULE}_editView_fieldName_${fieldName}.input-error:visible`,
            `#${MODULE}_editView_fieldName_${fieldName}[aria-invalid="true"]`,
          ].join(", ")
        )
        .count();
      expect(errorCount).toBeGreaterThan(0);

      // --- (2) 有効値を入れて保存 → 保存される ---
      await page.locator(`#${MODULE}_editView_fieldName_${fieldName}`).fill("有効な必須値");
      await page.locator("button.saveButton").first().click();
      await page.waitForURL(/[?&]record=\d+/, { timeout: 12000 });
      expect(page.url()).toMatch(/[?&]record=\d+/);
    } finally {
      // 後始末: 使い捨て必須項目を UI で削除(複数回実行の安定性を担保)
      await deleteThrowawayIfExists(page);
    }
  });
});
