import { test, expect } from "../../fixtures/isolated";
import { createAccount, gotoDetail, deleteViaDetail } from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: 所有者変更（Transfer Ownership） — TEST_COVERAGE の保留（別ユーザー必要）
 *
 * 拡充ベースラインで非 admin ユーザーが揃ったので実施可能になった。admin で使い捨ての
 * 顧客企業を作り、詳細画面の「担当の変更」で `e2e_director`（姓=部長）へ所有者を移し、
 * 詳細（全項目タブ）の担当が新所有者に変わることを検証して後始末する。
 *
 * 一覧の検索・選択は並列負荷で不安定になりやすいため、作成した record id を使って
 * 詳細画面から直接操作・検証する（決定論的）。
 *
 * セレクタ（実 DOM 確認済み）:
 *  - 詳細「その他」: `.detailViewButtoncontainer button.dropdown-toggle`
 *  - 担当の変更: `#Accounts_detailView_moreAction_LBL_TRANSFER_OWNERSHIP`
 *  - モーダル `form#changeOwner` / 新所有者 `#transferOwnerId` /
 *    関連モジュール(必須) `#related_modules` / 保存 `button[name="saveButton"]`
 *  - 担当の値（詳細タブ）: `#Accounts_detailView_fieldValue_assigned_user_id`
 */

const NEW_OWNER_LABEL = "部長"; // e2e_director の姓

async function openDetailsTab(page: import("@playwright/test").Page) {
  await page
    .locator("li.tab-item")
    .filter({ hasText: "詳細" })
    .first()
    .locator("a")
    .first()
    .click();
}

test.describe("共通: 所有者変更 (Transfer Ownership)", () => {
  test("レコードの所有者を別ユーザーへ変更できる", async ({ page }) => {
    test.setTimeout(90000);
    const name = `E2Emto${generateRandomString(8)}`;
    const recordId = await createAccount(page, name); // admin 所有で作成
    try {
      await gotoDetail(page, "Accounts", recordId);
      await page
        .locator(".detailViewButtoncontainer button.dropdown-toggle")
        .first()
        .click();
      await page
        .locator("#Accounts_detailView_moreAction_LBL_TRANSFER_OWNERSHIP")
        .click();

      const modal = page.locator("form#changeOwner");
      await expect(modal).toBeVisible({ timeout: 15000 });

      // 新所有者（表示名に "部長" を含む e2e_director）を value で確実に選ぶ
      const ownerVal = await modal
        .locator("#transferOwnerId option", { hasText: NEW_OWNER_LABEL })
        .first()
        .getAttribute("value");
      expect(ownerVal).toBeTruthy();
      await modal.locator("#transferOwnerId").selectOption(ownerVal as string);

      // 関連モジュールは必須。先頭の実オプションを選ぶ。
      const relVal = await modal
        .locator("#related_modules option")
        .first()
        .getAttribute("value");
      if (relVal) {
        await modal.locator("#related_modules").selectOption(relVal);
      }

      await modal.locator('button[name="saveButton"]').click();
      await page.waitForLoadState("networkidle").catch(() => {});

      // 反映確認: 詳細(全項目タブ)の担当が新所有者になる
      await expect(async () => {
        await gotoDetail(page, "Accounts", recordId);
        await openDetailsTab(page);
        await expect(
          page.locator("#Accounts_detailView_fieldValue_assigned_user_id")
        ).toContainText(NEW_OWNER_LABEL, { timeout: 3000 });
      }).toPass({ timeout: 25000 });
    } finally {
      await deleteViaDetail(page, "Accounts", recordId);
    }
  });
});
