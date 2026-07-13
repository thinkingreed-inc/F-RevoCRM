import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * D-10 申請フィールド (モジュール管理 > 申請フィールド)
 *
 * 一覧CRUD型。Settings 標準の一覧ビュー(ListViewContents.tpl)を土台とし、
 * データ行は tr.listViewEntries、行アクションは div.table-actions 内の
 * アイコン(a:has(i[title="編集"/"削除"]))で特定する。
 * テストで作成した申請フィールドのみを対象に、追加→編集→削除を直列で検証し、
 * 削除で後始末して永続的な残りを残さない。
 */
test.describe.skip("管理: 申請フィールド (RecordField)", () => {
  // 本環境には RecordField モジュールが未導入のためスキップ
  const listParams = { module: "RecordField", view: "List" };
  const token = generateRandomString(5);
  const nameAdd = `テスト${token}`;
  const nameEdit = `テスト編集${token}`;

  // 表示名で申請フィールドのデータ行を特定する
  const row = (page: Page, text: string) =>
    page
      .locator("tr.listViewEntries")
      .filter({ has: page.locator("td.listViewEntryValue.textOverflowEllipsis") })
      .filter({ hasText: text });

  test("申請フィールドの追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 「追加」ボタンから編集フォームを開く
    await page.getByText("追加", { exact: false }).first().click();

    const nameInput = page.locator('input[name="recordfieldname"]');
    await expect(nameInput).toBeVisible();
    await nameInput.fill(nameAdd);

    await saveAndSettle(page, page.getByText("保存", { exact: false }).first());

    // 一覧に追加した申請フィールドが現れること
    await gotoSettings(page, listParams);
    await expect(row(page, nameAdd)).toBeVisible();
  });

  test("申請フィールドの編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 追加した行を開いて編集する
    await row(page, nameAdd).first().click();

    const nameInput = page.locator('input[name="recordfieldname"]');
    await expect(nameInput).toBeVisible();
    await nameInput.fill(nameEdit);

    await saveAndSettle(page, page.getByText("保存", { exact: false }).first());

    // 編集後の名称が現れ、元の名称は消えていること
    await gotoSettings(page, listParams);
    await expect(row(page, nameEdit)).toBeVisible();
    await expect(row(page, nameAdd)).toHaveCount(0);
  });

  test("申請フィールドの削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = row(page, nameEdit).first();
    const deleteButton = target.locator('a:has(i[title="削除"])');
    await deleteButton.waitFor({ state: "visible" });
    await deleteButton.click();

    // 確認ダイアログの「はい」を押す
    await confirmYes(page);
    await page.waitForLoadState("networkidle").catch(() => {});

    // 一覧から削除した申請フィールドが消えていること
    await gotoSettings(page, listParams);
    await expect(row(page, nameEdit)).toHaveCount(0);
  });
});
