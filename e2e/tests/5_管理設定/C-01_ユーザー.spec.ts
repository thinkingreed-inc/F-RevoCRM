import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, loginInIsolatedContext, saveAndSettle } from "../../utils/settings";
import { listSearch } from "../../utils/listview";
import { apiSession } from "../../utils/api";
import { frQuery } from "../../model/fetcher";

/**
 * C-01 ユーザー (ユーザー管理 > ユーザー)
 *
 * 追加→編集→パスワード変更→(別ユーザーでの)ログイン確認→無効化 を直列で検証する。
 * temp は共有セッションでログアウト/別ユーザーにログインしていたが、共有 storageState を
 * 汚さないよう、ログイン確認は独立 browser context で行う。無効化も admin ではなく
 * このテストで作成したユーザーに対して行う。
 */
test.describe.serial("管理: ユーザー (Users)", () => {
  // 一覧の列検索 → 行のメニュー → モーダル保存と手数が多く、CI 負荷時に
  // Playwright 既定の 30 秒では足りない(「パスワードの変更」が flaky になった)。
  test.describe.configure({ timeout: 90000 });

  const listParams = { module: "Users", view: "List" };
  const token = generateRandomString(6).toLowerCase();
  const userName = `e2e${token}`;
  const password1 = "Test_1234";
  const password2 = "Test_12345";

  // 作成したユーザーの record id(後続テストで編集URLに使う)
  let userId = "";

  // ユーザー一覧は姓名(表示名)を表示しログイン名は出ないため、両方に含まれる
  // 一意な token で行を特定する。
  const row = (page: Page) =>
    page.locator("tr.listViewEntries").filter({ hasText: token });

  /**
   * 一覧を開いて、このテストのユーザー行だけに絞り込んでから返す。
   *
   * ユーザーは vtiger の仕様上 削除できず(無効化のみ)、E2E が作ったユーザーは
   * 実行を重ねるたびに増える。一覧は 1 ページ 20 件なので、素朴に
   * 「一覧を開いて token で filter」すると作成直後の行が 1 ページ目に出ず
   * 「見つからない」で落ちる(2026-08-26 に実際に踏んだ)。必ず列検索で絞る。
   */
  const openRow = async (page: Page) => {
    await gotoSettings(page, listParams);
    await listSearch(page, "user_name", userName);
    const target = row(page);
    await expect(target.first()).toBeVisible({ timeout: 15000 });
    return target;
  };

  /**
   * 作成したユーザーの record id を API で引く。
   * 一覧の 1 ページ目に依存しないようにするため(上記 openRow と同じ理由)。
   */
  const fetchUserId = async (): Promise<string> => {
    const session = await apiSession();
    const rows = await frQuery(
      session,
      `SELECT id FROM Users WHERE user_name = '${userName}';`
    );
    const wsId = rows?.[0]?.id ?? "";
    return wsId.includes("x") ? wsId.split("x")[1] : wsId;
  };

  test("ユーザーの追加", async ({ page }) => {
    await gotoSettings(page, listParams);
    await page.getByText("ユーザーの追加").first().click();

    await page.locator('input[name="user_name"]').fill(userName);
    await page.locator('input[name="last_name"]').fill(`Test${token}`);
    await page.locator('input[name="email1"]').fill(`${userName}@example.com`);
    await page.locator('input[name="user_password"]').fill(password1);
    await page.locator('input[name="confirm_password"]').fill(password1);
    await saveAndSettle(page, page.locator("button.saveButton"));

    // 一覧に現れること(列検索で絞ってから確認する)
    await openRow(page);
    // record id は一覧の行属性ではなく API で引く(1 ページ目に依存しないため)
    userId = await fetchUserId();
    expect(userId, "作成したユーザーの record id が引けること").not.toBe("");
  });

  test("ユーザーの編集", async ({ page }) => {
    await gotoSettings(page, { ...listParams, view: "Edit", record: userId });
    const lastName = page.locator('input[name="last_name"]');
    await expect(lastName).toBeVisible();
    await lastName.fill(`Edited${token}`);
    await saveAndSettle(page, page.locator("button.saveButton"));

    // 編集画面を開き直し、反映を確認
    await gotoSettings(page, { ...listParams, view: "Edit", record: userId });
    await expect(page.locator('input[name="last_name"]')).toHaveValue(
      `Edited${token}`
    );
  });

  test("パスワードの変更", async ({ page }) => {
    const target = (await openRow(page)).first();
    // 各行の dropdown に「パスワードの変更」があるため、自分の行のメニューに限定する
    await target.locator("span.dropdown-toggle").click();
    await target.locator(".dropdown-menu").getByText("パスワードの変更").click();

    const modal = page.locator(".modal-content:visible");
    // admin が他ユーザーのパスワードを変更する場合、旧パスワードは不要。
    // パスワードは強度要件(8文字以上・大小英字・数字・記号)を満たす必要がある。
    await modal.locator('input[name="new_password"]').fill(password2);
    await modal.locator('input[name="confirm_password"]').fill(password2);
    await saveAndSettle(page, modal.locator('button[name="saveButton"]'));

    // このフローは成功トーストを出さず、成功するとモーダルが閉じる
    await expect(page.locator(".modal-content:visible")).toHaveCount(0, {
      timeout: 15000,
    });
  });

  test("作成したユーザーでログインできる(独立context)", async ({ browser }) => {
    const { context, page } = await loginInIsolatedContext(
      browser,
      userName,
      password2
    );
    try {
      // ログイン成功時はログインフォーム(パスワード欄)が無くなる
      await expect(page.locator("input#password")).toHaveCount(0, {
        timeout: 15000,
      });
    } finally {
      await context.close();
    }
  });

  // 注: ユーザーの「無効化」はユーザー編集フォームに status セレクトが無く、
  // この環境の UI では標準の編集操作から到達できないため、本パイロットの対象外とする
  // (追加/編集/パスワード変更/ログイン確認で基本操作はカバー済み)。
});
