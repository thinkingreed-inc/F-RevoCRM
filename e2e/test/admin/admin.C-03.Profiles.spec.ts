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
    // ドロップダウンを開いてから編集リンクへ遷移する。
    // 編集リンクはフルページ遷移(a[href])なので、遷移完了(=編集フォームの
    // profilename が表示)を待ってから入力する。遅い CI では
    // ドロップダウン表示→クリックが競合しやすいため明示的に待つ。
    await target.locator("span.dropdown-toggle").click();
    const editLink = target.locator('a[title="編集"]');
    await expect(editLink).toBeVisible();
    await editLink.click();

    const nameInput = page.locator('input[name="profilename"]');
    await expect(nameInput).toBeVisible();
    await expect(nameInput).toHaveValue(nameAdd);
    await nameInput.fill(nameEdit);

    // Profiles の保存は AJAX。保存ボタン押下後に
    //   1) EditAjax(mode=checkDuplicate) の POST
    //   2) 実際の action=Save の POST
    // が直列で走り、成功後に window.history.back() で一覧へ戻る。
    // saveAndSettle は「最初の POST」で解決するため、遅い CI では
    // checkDuplicate 完了時点で先に進んでしまい、直後の gotoSettings で
    // 本命の Save POST が中断され保存がコミットされない。
    // ここでは body に action=Save を含む POST の応答(2xx/3xx)を明示的に
    // 待ってから次へ進む。
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
    // Save 後の history.back() による一覧再描画も落ち着かせる。
    await page.waitForLoadState("networkidle").catch(() => {});

    // 一覧を再取得し、遅い反映に備えて寛容なタイムアウトでリネーム後の行を待つ。
    await gotoSettings(page, params);
    await expect(row(page, nameEdit)).toBeVisible({ timeout: 30000 });
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
