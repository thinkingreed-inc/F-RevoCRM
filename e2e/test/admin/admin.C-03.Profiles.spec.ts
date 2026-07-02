import { test, expect, type Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * C-03 プロファイル (ユーザー管理 > プロファイル)
 *
 * 一覧CRUD型。行アクションは複製アイコン(a[title="複製"])と、その他メニュー
 * (span.dropdown-toggle)内の編集/削除。テストで作成したプロファイルのみを対象に
 * 作成→編集→複製→削除を直列で検証する。
 */
test.describe.serial("管理: プロファイル (Profiles)", () => {
  const params = { module: "Profiles", view: "List" };
  const token = generateRandomString(8);
  const nameAdd = `e2eprofadd${token}`;
  const nameEdit = `e2eprofedit${token}`;
  const nameCopy = `e2eprofcopy${token}`;

  // データ行(アクションセルを持つ tr)を名前で特定
  const row = (page: Page, text: string) =>
    page
      .locator("tr")
      .filter({ has: page.locator(".table-actions") })
      .filter({ hasText: text });

  // プロファイル削除は所属ユーザーの移譲先(別プロファイル)を選ぶモーダル
  // (#DeleteModal / select#transfer_record)が開くため、先頭の移譲先を選んで保存する。
  const deleteViaTransferModal = async (page: Page) => {
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
    await saveAndSettle(page, modal.locator('button[name="saveButton"]'), {
      force: true,
    });
  };

  test("プロファイルの追加", async ({ page }) => {
    await gotoSettings(page, params);
    await page.getByText("プロファイルの追加").first().click();
    await page.locator('input[name="profilename"]').fill(nameAdd);
    await saveAndSettle(page, page.locator("button.saveButton"));

    await gotoSettings(page, params);
    await expect(row(page, nameAdd)).toBeVisible();
  });

  test("プロファイルの編集", async ({ page }) => {
    await gotoSettings(page, params);
    const target = row(page, nameAdd);
    await target.locator("span.dropdown-toggle").click();
    await target.locator('a[title="編集"]').click();

    await page.locator('input[name="profilename"]').fill(nameEdit);
    await saveAndSettle(page, page.locator("button.saveButton"));

    await gotoSettings(page, params);
    await expect(row(page, nameEdit)).toBeVisible();
    await expect(row(page, nameAdd)).toHaveCount(0);
  });

  test("プロファイルの複製", async ({ page }) => {
    await gotoSettings(page, params);
    await row(page, nameEdit).locator('a[title="複製"]').click();

    await page.locator('input[name="profilename"]').fill(nameCopy);
    await saveAndSettle(page, page.locator("button.saveButton"));

    await gotoSettings(page, params);
    await expect(row(page, nameCopy)).toBeVisible();
  });

  test("プロファイルの削除", async ({ page }) => {
    // 作成した2件(複製/編集後)を削除して後始末する
    for (const name of [nameCopy, nameEdit]) {
      await gotoSettings(page, params);
      const target = row(page, name);
      await target.locator("span.dropdown-toggle").click();
      await target.locator('a[title="削除"]').click();
      await deleteViaTransferModal(page);
      await gotoSettings(page, params);
      await expect(row(page, name)).toHaveCount(0);
    }
  });
});
