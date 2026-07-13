import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * C-05 グループ (ユーザー管理 > グループ)
 *
 * 一覧CRUD型。作成→編集→削除を直列で検証する。メンバーは select2(#memberList)から
 * 1件選ぶ。作成したグループのみを対象にする。
 */
test.describe.serial("管理: グループ (Groups)", () => {
  const params = { module: "Groups", view: "List" };
  const token = generateRandomString(8);
  const nameAdd = `e2egrpadd${token}`;
  const nameEdit = `e2egrpedit${token}`;

  // グループ一覧のデータ行(行クラスに依存せずアクションセルの有無で特定)
  const row = (page: Page, text: string) =>
    page
      .locator("tr")
      .filter({ has: page.locator(".table-actions, .listViewEntryValue") })
      .filter({ hasText: text });

  // メンバーは必須。select2 のドロップダウン/マスクが保存ボタンを覆い操作が不安定なため、
  // 元の select(#memberList)で先頭の実メンバーを選択し change を発火させて select2 に同期する。
  const pickFirstMember = async (page: Page) => {
    await page.locator("#memberList").evaluate((el) => {
      const sel = el as HTMLSelectElement;
      for (const opt of Array.from(sel.options)) {
        if (opt.value) {
          opt.selected = true;
          break;
        }
      }
      sel.dispatchEvent(new Event("change", { bubbles: true }));
    });
  };

  test("グループの追加", async ({ page }) => {
    await gotoSettings(page, params);
    await page.getByText("グループの追加").first().click();

    await page.locator('input[name="groupname"]').fill(nameAdd);
    await pickFirstMember(page);
    await saveAndSettle(page, page.locator("button.saveButton"), { force: true });

    await gotoSettings(page, params);
    await expect(row(page, nameAdd)).toBeVisible();
  });

  test("グループの編集", async ({ page }) => {
    await gotoSettings(page, params);
    await row(page, nameAdd).locator("i.fa.fa-pencil").click();

    await page.locator('input[name="groupname"]').fill(nameEdit);
    await saveAndSettle(page, page.locator("button.saveButton"), { force: true });

    await gotoSettings(page, params);
    await expect(row(page, nameEdit)).toBeVisible();
    await expect(row(page, nameAdd)).toHaveCount(0);
  });

  test("グループの削除", async ({ page }) => {
    await gotoSettings(page, params);
    const target = row(page, nameEdit);
    await target.locator("i.fa.fa-trash").click();

    // グループ削除は単純な確認ボックスではなく「所属レコードの移譲先」を選ぶ
    // モーダル(#DeleteModal / DeleteTransferForm.tpl)が開く。役割(C-02)と同じ移譲型だが、
    // 移譲先は隠しフィールドではなく select2(select#transfer_record: ユーザー/グループ)のため、
    // 元の select で自分以外の先頭候補を選び change を発火させて select2 に同期する。
    const modal = page.locator(".modal-content:visible");
    await expect(modal.locator("#transfer_record")).toBeVisible();
    await modal.locator("#transfer_record").evaluate((el) => {
      const sel = el as HTMLSelectElement;
      for (const opt of Array.from(sel.options)) {
        if (opt.value) {
          opt.selected = true;
          break;
        }
      }
      sel.dispatchEvent(new Event("change", { bubbles: true }));
    });
    await saveAndSettle(
      page,
      modal.locator('button[name="saveButton"]'),
      { force: true }
    );

    await gotoSettings(page, params);
    await expect(row(page, nameEdit)).toHaveCount(0);
  });
});
