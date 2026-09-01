import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * 6-0 項目作成 UI 検証
 *
 * LayoutEditor(モジュール管理 > 項目・ブロックの設定)の UI を実際に操作して、
 * 使い捨てのカスタム項目(テキスト単数行)を作成 → 表示を確認 → 削除する。
 * 「項目バリデーション」シリーズの土台として、項目作成 UI 自体が動くことを保証する。
 *
 * 永続的な設定変更のため作成した項目は finally で必ず削除する。さらに、前回実行が
 * 途中で落ちて項目が残っていても緑で始められるよう、作成前にも防御的に削除する
 * (冪等性: 何度連続実行しても pass する)。コア項目・コアブロックには触れない。
 *
 * セレクタは D-02 レイアウト編集テストで実証済みのものを踏襲する:
 *   追加ボタン    : block header の button.addCustomField
 *   作成モーダル  : .createCustomFieldForm:visible(型 select[name=fieldType] 既定 Text)
 *   ラベル入力    : input[name="fieldLabel"] / 桁数 input[name="fieldLength"](Text は必須)
 *   保存          : button.btn-success[name="saveButton"]
 *   削除          : 項目行の a.deleteCustomField → 確認ダイアログ .confirm-box-ok
 */
const MODULE = "Faq";
const THROWAWAY_LABEL = "E2E使い捨て項目";

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
      .waitForResponse((r) => r.request().method() === "POST", {
        timeout: 15000,
      })
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

test.describe("項目作成 UI", () => {
  test("LayoutEditor で項目を作成→表示→削除できる", async ({ page }) => {
    // 防御的な事前後始末(前回実行の残骸があっても緑で始める)
    await deleteThrowawayIfExists(page);

    try {
      // 先頭の「カスタム項目の追加」可能なブロックに、テキスト単数行の項目を追加
      await gotoSettings(page, listParams);
      await page.waitForLoadState("networkidle").catch(() => {});
      await page.locator("button.addCustomField").first().click();

      const modal = page.locator(".createCustomFieldForm:visible");
      await modal.waitFor({ state: "visible", timeout: 10000 });
      await modal.locator('select[name="fieldType"]').selectOption("Text");
      await modal.locator('input[name="fieldLabel"]').fill(THROWAWAY_LABEL);
      // Text 型は桁数(必須)入力が必要
      await modal.locator('input[name="fieldLength"]').fill("100");
      await saveAndSettle(
        page,
        modal.locator('button.btn-success[name="saveButton"]')
      );

      // リロード後もレイアウト一覧に現れること
      await gotoSettings(page, listParams);
      await expect(fieldRow(page)).toHaveCount(1);

      // 作成した項目が Faq の作成フォームにも現れること
      await page.goto(
        `${page.url().split("index.php")[0]}index.php?module=${MODULE}&view=Edit`
      );
      await page.waitForLoadState("domcontentloaded");
      await expect(
        page.getByText(THROWAWAY_LABEL, { exact: true }).first()
      ).toBeVisible();
    } finally {
      // 後始末: 使い捨て項目を UI で削除(複数回実行の安定性を担保)
      await deleteThrowawayIfExists(page);
    }
  });
});
