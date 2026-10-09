import { test, expect } from "../../fixtures/privileges";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { apiSession } from "../../utils/api";
import { frQuery } from "../../model/fetcher";

/**
 * C-07 ユーザー参照項目の候補検索 (#1887)
 *
 * ユーザーの「上司」(reports_to_id) で部下にあたるユーザーが、ユーザー参照項目の
 * 候補(入力補完・ポップアップ)から除外されていた不具合の回帰テスト。
 *
 * 部下ユーザー(上司 = admin)を 1 人作り、次の 2 経路で候補に出ることを確かめる。
 * - 入力補完: 別ユーザーの編集画面の「上司」に名前の一部を入力する
 *   (以前はログイン中ユーザー = admin の部下が除外されていた)
 * - ポップアップ: admin の編集画面の「上司」の検索を開く
 *   (以前は編集中ユーザー = admin の部下が除外されていた)
 *
 * ユーザーは vtiger の仕様上 削除できないため、一意な token を名前に含めて特定する。
 */
test.describe.serial("管理: ユーザー参照項目の候補検索", () => {
  test.describe.configure({ timeout: 90000 });

  const token = generateRandomString(6).toLowerCase();
  const subordinate = { userName: `e2esub${token}`, lastName: `Sub${token}` };
  const other = { userName: `e2eoth${token}`, lastName: `Oth${token}` };
  const password = "Test_1234";

  let adminId = "";
  let otherId = "";

  /** ユーザー名から record id を API で引く(一覧の 1 ページ目に依存しないため)。 */
  const fetchUserId = async (userName: string): Promise<string> => {
    const session = await apiSession();
    const rows = await frQuery(
      session,
      `SELECT id FROM Users WHERE user_name = '${userName}';`
    );
    const wsId = rows?.[0]?.id ?? "";
    return wsId.includes("x") ? wsId.split("x")[1] : wsId;
  };

  /** ユーザーを作成する。reportsTo を渡すと「上司」をその record id にする。 */
  const createUser = async (
    page: Page,
    user: { userName: string; lastName: string },
    reportsTo?: string
  ): Promise<string> => {
    await gotoSettings(page, { module: "Users", view: "Edit" });
    await page.locator('input[name="user_name"]').fill(user.userName);
    await page.locator('input[name="last_name"]').fill(user.lastName);
    await page
      .locator('input[name="email1"]')
      .fill(`${user.userName}@example.com`);
    await page.locator('input[name="user_password"]').fill(password);
    await page.locator('input[name="confirm_password"]').fill(password);
    if (reportsTo) {
      // 「上司」は候補検索で選ぶ項目だが、ここは前提データの準備なので、
      // 検証対象の候補検索を経由せずに hidden の値を直接セットする。
      await page
        .locator('input[name="reports_to_id"]')
        .evaluate((el, id) => ((el as HTMLInputElement).value = id), reportsTo);
    }
    await saveAndSettle(page, page.locator("button.saveButton"));

    const id = await fetchUserId(user.userName);
    expect(id, `${user.userName} の record id が引けること`).not.toBe("");
    return id;
  };

  test("前提: admin の部下と、別のユーザーを作成する", async ({ page }) => {
    adminId = await fetchUserId(process.env.E2E_USER_NAME || "admin");
    expect(adminId, "admin の record id が引けること").not.toBe("");

    await createUser(page, subordinate, adminId);
    otherId = await createUser(page, other);

    const session = await apiSession();
    const rows = await frQuery(
      session,
      `SELECT reports_to_id FROM Users WHERE user_name = '${subordinate.userName}';`
    );
    expect(rows?.[0]?.reports_to_id, "部下の上司が admin であること").toBe(
      `19x${adminId}`
    );
  });

  test("入力補完: ログイン中ユーザーの部下が候補に出る", async ({ page }) => {
    await gotoSettings(page, { module: "Users", view: "Edit", record: otherId });

    const input = page.locator("#reports_to_id_display");
    await expect(input).toBeVisible();
    await input.click();
    await input.pressSequentially(token, { delay: 60 });

    await expect(
      page.locator("ul.ui-autocomplete:visible li.ui-menu-item", {
        hasText: subordinate.lastName,
      })
    ).toBeVisible({ timeout: 15000 });
  });

  test("ポップアップ: 編集中ユーザーの部下が一覧に出る", async ({ page }) => {
    await gotoSettings(page, { module: "Users", view: "Edit", record: adminId });

    await page.locator("#Users_editView_fieldName_reports_to_id_select").click();
    const modal = page.locator("#popupModal");
    await expect(
      modal.locator(".listview-table .listViewEntries").first()
    ).toBeVisible({ timeout: 15000 });

    // 一覧は 1 ページ 20 件のため、作成したユーザーで絞り込んでから確認する
    const searchInput = modal.locator('input[name="last_name"]');
    await searchInput.fill(subordinate.lastName);
    await searchInput.press("Enter");

    await expect(
      modal.locator(".listview-table .listViewEntries", {
        hasText: subordinate.lastName,
      })
    ).toBeVisible({ timeout: 15000 });
  });
});
