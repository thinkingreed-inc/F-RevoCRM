import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * D-11 承認フロー (モジュール管理 > 承認フロー)
 *
 * 一覧CRUD＋詳細内リストの代表。承認フローを作成し、詳細画面で承認ステップを
 * 追加(編集相当)し、最後に承認フローを削除して後始末する流れを直列で検証する。
 * temp 由来の block=/fieldid= や無駄なメニュー経由ナビは廃止し、gotoSettings に一本化。
 *
 * 追加名と(未使用の)別名は互いに部分文字列にならないよう token で一意化する。
 * モーダル操作は表示中の土台(.modal-content:visible)へスコープする。
 */
test.describe.skip("管理: 承認フロー (ApprovalFlow)", () => {
  // 本環境には ApprovalFlow モジュールが未導入のためスキップ
  const listParams = { module: "ApprovalFlow", view: "List" };
  const token = generateRandomString(8);
  const flowName = `e2e承認add${token}`;
  const stepName = `e2eステップ${token}`;
  const approvedStatus = `e2e一次OK${token}`;

  // 承認フローの一覧行を名前で特定する
  const row = (page: Page, text: string) =>
    page.locator("tr.listViewEntries").filter({ hasText: text });

  // 表示中のモーダル
  const modal = (page: Page) => page.locator(".modal-content:visible");

  test("承認フローの追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 「追加」ボタンから承認フロー作成画面へ
    await page.getByText("追加").first().click();
    await page.waitForLoadState("networkidle").catch(() => {});

    // 承認フロー名を入力して保存
    await page.locator('input[name="name"]').fill(flowName);
    await saveAndSettle(page, page.getByText("保存").first());

    // 一覧に作成した承認フローが現れること
    await gotoSettings(page, listParams);
    await expect(row(page, flowName)).toBeVisible();
  });

  test("承認フローの編集(承認ステップ追加)", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 追加した承認フローの詳細画面へ
    await row(page, flowName).first().click();
    await page.waitForLoadState("networkidle").catch(() => {});

    // 「承認ステップの追加」を押してモーダルを開く
    await page.locator("#ApprovalStep_listView_basicAction_LBL_ADD_RECORD").click();
    await page.waitForLoadState("networkidle").catch(() => {});

    // 承認ステップ名を入力
    await modal(page).locator('input[name="name"]').fill(stepName);

    // 承認後の申請ステータスを入力
    await modal(page).locator('input[name="approvedstatus"]').first().fill(approvedStatus);

    // 割当先(select2)を選択する
    const dropdownInput = modal(page).locator("input.select2-input").first();
    await dropdownInput.click();
    await page.locator(".select2-results > li").nth(2).click();

    // リストを閉じるため sequence 欄へフォーカスを移す
    const sequence = modal(page).locator('input[name="sequence"]');
    await sequence.waitFor({ state: "visible" });
    await sequence.click();

    // 保存(AJAX 保存の完了を待つ)
    await saveAndSettle(
      page,
      modal(page).locator('button[name="saveButton"]').first()
    );

    // 詳細を再表示し、追加した承認ステップが反映されていること
    await gotoSettings(page, listParams);
    await row(page, flowName).first().click();
    await page.waitForLoadState("networkidle").catch(() => {});
    await expect(page.getByText(stepName).first()).toBeVisible();
  });

  test("承認フローの削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 作成した承認フローの行から削除アイコンを押す
    const target = row(page, flowName).first();
    const deleteButton = target.locator('a[title="削除"]');
    await deleteButton.waitFor({ state: "visible" });
    await deleteButton.click();

    // 確認ダイアログの「はい」を押す
    await confirmYes(page);
    await page.waitForLoadState("networkidle").catch(() => {});

    // 一覧から削除した承認フローが消えていること
    await gotoSettings(page, listParams);
    await expect(row(page, flowName)).toHaveCount(0);
  });
});
