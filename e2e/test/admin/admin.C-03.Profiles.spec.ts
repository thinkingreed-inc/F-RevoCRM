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

  // Profiles の追加/編集/複製の保存は AJAX。保存ボタン押下後に
  //   1) EditAjax(mode=checkDuplicate) の POST
  //   2) 実際の action=Save の POST
  // が直列で走り、成功後に window.history.back() で一覧へ戻る。
  // saveAndSettle は「最初の POST」で解決するため、遅い CI では checkDuplicate 完了
  // 時点で先へ進み、直後の gotoSettings で本命の Save POST が中断され保存されない。
  // ここでは body に action=Save を含む POST の応答(2xx/3xx)を明示的に待つ。
  const saveProfileForm = async (page: Page) => {
    const saveResponse = page.waitForResponse(
      (r) => {
        if (r.request().method() !== "POST") return false;
        const body = r.request().postData() ?? "";
        return /(^|&)action=Save(&|$)/.test(body) && r.status() < 400;
      },
      { timeout: 30000 }
    );
    await page.locator("button.saveButton").click();
    await saveResponse;
    await page.waitForLoadState("networkidle").catch(() => {});
  };

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
    await saveProfileForm(page);

    await gotoSettings(page, params);
    await expect(row(page, nameAdd)).toBeVisible({ timeout: 30000 });
  });

  test("プロファイルの編集", async ({ page }) => {
    await gotoSettings(page, params);
    const target = row(page, nameAdd);
    // ドロップダウンを開いてから編集リンク(フルページ遷移)へ。遷移完了(編集フォームの
    // profilename 表示)を待ってから入力する。
    await target.locator("span.dropdown-toggle").click();
    const editLink = target.locator('a[title="編集"]');
    await expect(editLink).toBeVisible();
    await editLink.click();

    const nameInput = page.locator('input[name="profilename"]');
    await expect(nameInput).toBeVisible();
    await expect(nameInput).toHaveValue(nameAdd);
    await nameInput.fill(nameEdit);
    await saveProfileForm(page);

    await gotoSettings(page, params);
    await expect(row(page, nameEdit)).toBeVisible({ timeout: 30000 });
    await expect(row(page, nameAdd)).toHaveCount(0);
  });

  test("プロファイルの複製", async ({ page }) => {
    await gotoSettings(page, params);
    await row(page, nameEdit).locator('a[title="複製"]').click();

    await page.locator('input[name="profilename"]').fill(nameCopy);
    await saveProfileForm(page);

    await gotoSettings(page, params);
    await expect(row(page, nameCopy)).toBeVisible({ timeout: 30000 });
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
      await expect(row(page, name)).toHaveCount(0, { timeout: 30000 });
    }
  });
});
