import { test, expect, type Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * D-02 レイアウトエディタ (モジュール管理 > 項目・ブロックの設定)
 *
 * カスタムブロック／カスタム項目は永続的な設定変更のため、追加したものは
 * 必ず後始末(削除)する。安全なモジュール Faq に対し、以下を直列で検証する:
 *   1. カスタムブロックの追加 → 一覧に現れること
 *   2. そのブロックへカスタム項目(テキスト単数行)の追加 → 一覧に現れること
 *   3. カスタム項目の削除 → 一覧から消えること
 *   4. カスタムブロックの削除 → 一覧から消えること
 * コア項目・コアブロックには一切触れない(自分で作成したものだけを操作する)。
 *
 * モーダルは showModal で .myModal 内に差し込まれ、非表示の雛形も DOM に残るため、
 * モーダル操作は必ず表示中のもの(:visible)へスコープする。
 * ブロック/項目の追加・削除は画面遷移しない AJAX 保存のため saveAndSettle で待つ。
 * 削除確認は app.helper.showConfirmationBox(.confirm-box-ok)のため confirmYes を使う。
 */
test.describe.serial("管理: レイアウトエディタ (LayoutEditor)", () => {
  // 安全な対象モジュール。カスタム項目/ブロックの追加が許可されている。
  const listParams = {
    module: "LayoutEditor",
    view: "Index",
    sourceModule: "Faq",
    mode: "showFieldLayout",
  };

  // 特殊文字はブロック名バリデーションで弾かれるため英数字のみのトークンにする。
  const token = generateRandomString(8);
  const blockLabel = `e2eblk${token}`;
  const fieldLabel = `e2efld${token}`;

  // 一覧上のブロック(見出しラベルで特定)
  const block = (page: Page, label: string) =>
    page.locator("div.editFieldsTable").filter({
      has: page.locator(".blockLabel strong", { hasText: label }),
    });

  // ブロック内の項目行(項目ラベルで特定)
  const field = (page: Page, label: string) =>
    page
      .locator("#moduleBlocks li")
      .filter({ has: page.locator(".fieldLabel b", { hasText: label }) });

  test("カスタムブロックの追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 画面上部の「ブロックの追加」ボタン
    await page.locator("button.addCustomBlock").click();

    // 表示中の追加モーダルへスコープしてブロック名を入力
    const modal = page.locator(".addBlockModal:visible");
    await modal.locator('input[name="label"]').fill(blockLabel);

    // AJAX 保存(画面遷移しない)
    await saveAndSettle(
      page,
      modal.locator('button.btn-success[name="saveButton"]')
    );

    // リロード後も追加したブロックが一覧に現れること
    await gotoSettings(page, listParams);
    await expect(block(page, blockLabel)).toHaveCount(1);
  });

  test("カスタム項目の追加(テキスト単数行)", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 追加したブロックのヘッダにある「カスタム項目の追加」ボタン
    await block(page, blockLabel).locator("button.addCustomField").click();

    // 表示中の項目作成モーダルへスコープ。項目タイプの既定は Text(単数行)。
    const modal = page.locator(".createCustomFieldForm:visible");
    await modal.locator('input[name="fieldLabel"]').fill(fieldLabel);
    // Text 型は桁数(必須)入力が必要
    await modal.locator('input[name="fieldLength"]').fill("100");

    await saveAndSettle(
      page,
      modal.locator('button.btn-success[name="saveButton"]')
    );

    // リロード後も追加した項目が一覧(=作成したブロック内)に現れること
    await gotoSettings(page, listParams);
    await expect(field(page, fieldLabel)).toHaveCount(1);
    // 作成したブロック配下に存在することも確認
    await expect(
      block(page, blockLabel).locator(".fieldLabel b", { hasText: fieldLabel })
    ).toBeVisible();
  });

  test("カスタム項目の削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = field(page, fieldLabel);
    await expect(target).toHaveCount(1);

    // 行内の削除アイコン(a.deleteCustomField)→ 確認ダイアログで「はい」
    await Promise.all([
      page
        .waitForResponse((r) => r.request().method() === "POST", {
          timeout: 15000,
        })
        .catch(() => {}),
      (async () => {
        await target.locator("a.deleteCustomField").click();
        await confirmYes(page);
      })(),
    ]);
    await page.waitForLoadState("networkidle").catch(() => {});

    // リロード後、項目が一覧から消えていること
    await gotoSettings(page, listParams);
    await expect(field(page, fieldLabel)).toHaveCount(0);
  });

  test("カスタムブロックの削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = block(page, blockLabel);
    await expect(target).toHaveCount(1);

    // ブロックヘッダの「ブロックの削除」→ 確認ダイアログで「はい」
    await Promise.all([
      page
        .waitForResponse((r) => r.request().method() === "POST", {
          timeout: 15000,
        })
        .catch(() => {}),
      (async () => {
        await target.locator("button.deleteCustomBlock").click();
        await confirmYes(page);
      })(),
    ]);
    await page.waitForLoadState("networkidle").catch(() => {});

    // リロード後、ブロックが一覧から消えていること(後始末完了)
    await gotoSettings(page, listParams);
    await expect(block(page, blockLabel)).toHaveCount(0);
  });
});
