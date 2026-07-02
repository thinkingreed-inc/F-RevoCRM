import { test, expect, type Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * E-01 Webフォーム (自動化 > Webフォーム)
 *
 * 一覧CRUD型。作成→編集→削除を直列で検証する。
 *
 * Webフォームの作成には「対象モジュール(targetmodule)」の選択と、少なくとも
 * 1つのフィールドが必要。対象モジュールは picklist(uitype16)で、既定で先頭の
 * サポートモジュール(通常 Contacts)が選択され、対象モジュールの必須フィールドは
 * FieldsEditView 側で自動的に選択済みになる。そのため最小構成では
 *   ・Webフォーム名(name)を入力
 *   ・対象モジュールを明示的に選択(既定でも可だが取りこぼしを避けて明示)
 * だけで保存できる。
 *
 * 名前(name)は編集画面では readonly のため(EditRecordStructure_Model)、
 * 編集テストでは説明(description)を書き換えて更新を検証する。
 *
 * 追加名と編集名は互いに部分文字列にならないようにする(hasText の部分一致で
 * 「更新後の行だけが存在すること」を検証するため)。
 */
test.describe.serial("管理: Webフォーム (Webforms)", () => {
  const listParams = { module: "Webforms", view: "List" };
  const token = generateRandomString(8);
  const name = `e2ewebformadd${token}`;
  const editedDesc = `e2ewebformedit${token}`;

  // 一覧のデータ行(Webフォーム名で特定)
  const row = (page: Page, text: string) =>
    page.locator("tr.listViewEntries").filter({ hasText: text });

  test("Webフォームの追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 「Webフォームの追加」をクリックして作成画面へ
    await page.getByText("Webフォームの追加").first().click();

    // Webフォーム名を入力
    await page.locator('input[name="name"]').fill(name);

    // 対象モジュールを明示的に選択(uitype16 picklist=通常の select)。
    // 既定で先頭のサポートモジュールが選ばれているが、取りこぼしを避けて明示する。
    // Contacts が選べればそれを、無ければ先頭のオプションを選ぶ。
    const targetModule = page.locator('select[name="targetmodule"]');
    await expect(targetModule).toBeVisible();
    const hasContacts =
      (await targetModule.locator('option[value="Contacts"]').count()) > 0;
    if (hasContacts) {
      await targetModule.selectOption("Contacts");
    } else {
      const firstValue = await targetModule
        .locator("option")
        .first()
        .getAttribute("value");
      if (firstValue) await targetModule.selectOption(firstValue);
    }

    // 対象モジュールの必須フィールドが少なくとも1つ選択済みであることを確認。
    // (FieldsEditView 側で mandatory フィールドは自動選択される)
    await expect(
      page.locator('table[name="targetModuleFields"] tr.listViewEntries').first()
    ).toBeVisible();

    // 保存(AJAX保存+重複チェックを挟むため saveAndSettle で完了を待つ)
    await saveAndSettle(page, page.locator("button.saveButton"));

    // 一覧に作成した Webフォームが現れること
    await gotoSettings(page, listParams);
    await expect(row(page, name)).toBeVisible();
  });

  test("Webフォームの編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 対象行の編集アイコン(fa-pencil / title=編集)から編集画面へ
    const target = row(page, name).first();
    await target.locator('i.fa.fa-pencil').click();

    // 名前は readonly のため説明(description)を書き換える
    const description = page.locator('textarea[name="description"]');
    await expect(description).toBeVisible();
    await description.fill(editedDesc);

    await saveAndSettle(page, page.locator("button.saveButton"));

    // 再度編集画面を開き、説明が更新されていることを検証する。
    // (一覧に説明列は無いため、詳細=編集画面で確認する)
    await gotoSettings(page, listParams);
    await row(page, name).first().locator('i.fa.fa-pencil').click();
    await expect(page.locator('textarea[name="description"]')).toHaveValue(
      editedDesc
    );

    // 一覧に対象の Webフォームが依然として存在すること
    await gotoSettings(page, listParams);
    await expect(row(page, name)).toBeVisible();
  });

  test("Webフォームの削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 対象行の削除アイコン(fa-trash / title=削除)をクリック
    const target = row(page, name).first();
    await target.locator("i.fa.fa-trash.icon-trash").click();

    // 確認ダイアログ(showConfirmationBox)の「はい」を押す
    await confirmYes(page);

    // 削除は AJAX で一覧を再読込するため、行が消えるのを待つ
    await page.waitForLoadState("networkidle").catch(() => {});

    // リロード後も一覧から消えていること
    await gotoSettings(page, listParams);
    await expect(row(page, name)).toHaveCount(0);
  });
});
