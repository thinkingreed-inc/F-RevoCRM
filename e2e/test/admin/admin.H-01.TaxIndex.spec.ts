import { test, expect, type Page, type Locator } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * H-01 税の管理 (販売管理 > 税の管理)
 *
 * モーダル追加型。税一覧に対し「新規税金の追加」→「編集」を直列で行い、
 * 追加した税がリロード後の一覧に現れること・編集後の値が反映されることを検証する。
 *
 * 税は削除できず「有効/無効(deleted フラグ)」の切り替えのみのため(Tax.js の
 * updateTaxStatus 参照)、削除テストは設けず追加＋編集のみとする。
 *
 * 追加/編集フォームはモーダル(#editTax / .modal-content)で開き、保存は AJAX
 * (action=TaxAjax)で行われ画面遷移しない。モーダル操作は非表示の雛形と
 * 混在しないよう表示中(:visible)へスコープする。
 *
 * 【重要・失敗の根本原因】
 * Tax.js の saveTaxDetails は「重複チェック(mode=checkDuplicateTaxName)」→
 * 「保存(action=TaxAjax)」の 2 本の POST を jQuery Deferred で直列に投げる。
 * saveAndSettle は最初の POST 応答 + networkidle しか待たないため、2 本目の
 * 保存 POST が確定する前に gotoSettings(リロード)へ進み得る。DB には後追いで
 * 行が入る(=作成自体は成功)一方、直後のリロード一覧には現れないという競合が
 * 起きていた(error-context では前回トークンの行だけが残り今回分が無かった)。
 *
 * 対策: 保存クリック後は「モーダルが閉じる(hideModal は保存 POST 応答後に
 * 実行される)」かつ「Tax.js が DOM へ追加/更新した行がライブ一覧に現れる」ことを
 * 明示的に待ってからリロードする。これにより保存の往復完了を保証したうえで、
 * リロード後の永続化を検証する。
 */
test.describe.serial("管理: 税の管理 (TaxIndex)", () => {
  const listParams = { module: "Vtiger", view: "TaxIndex" };
  const token = generateRandomString(8);
  const taxName = `e2etax${token}`;
  const editedName = `e2etaxedit${token}`;
  const addPercentage = "10";
  const editedPercentage = "15";

  // 税一覧の行(data-taxid を持つ tr)を税名で特定。
  // TaxIndex.tpl の product/service 税は table.inventoryTaxTable、既定タブ
  // (#taxes)に描画される。行構造は
  //   <span class="taxLabel">名前</span> ... <span class="taxPercentage">10.000%</span>
  // で、Tax.js の addTaxDetails/updateTaxDetails も同じ構造で append/更新する。
  const row = (page: Page, name: string): Locator =>
    page.locator("table.inventoryTaxTable tr[data-taxid]").filter({
      has: page.locator("span.taxLabel", { hasText: name }),
    });

  // 表示中のモーダル
  const modal = (page: Page): Locator => page.locator(".modal-content:visible");

  // 税率は DB の DECIMAL(percentage)由来で "10.000%" のように小数3桁付きで
  // 描画される(TaxRecord::getTax → percentage カラム、TaxIndex.tpl の
  // <span class="taxPercentage">{...}%</span>)。入力した "10" に対し末尾の
  // ".000" が付くため、末尾ゼロを許容する正規表現で突き合わせる。
  const percentagePattern = (value: string): RegExp =>
    new RegExp(`^\\s*${value}(?:\\.0+)?%\\s*$`);

  // 保存クリック → 重複チェック/保存の 2 本 POST → hideModal を待つ。
  // saveAndSettle で POST を蹴った後、モーダルが確実に閉じるまで待機し、
  // 保存往復の完了を担保する。
  const saveTaxModal = async (page: Page): Promise<void> => {
    await saveAndSettle(
      page,
      modal(page).locator('button.btn-success[name="saveButton"]')
    );
    // hideModal() は保存 POST 応答後に呼ばれる。閉じるまで待つことで
    // 「保存が返ってきた」ことを保証する(networkidle の競合対策)。
    await expect(page.locator("#editTax")).toHaveCount(0, { timeout: 15000 });
  };

  test("新規税金の追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 「新規税金の追加」ボタン(LBL_ADD_NEW_TAX)を押してモーダルを開く
    await page.locator("button.addTax").first().click();
    await expect(modal(page).locator("#editTax")).toBeVisible();

    // 税名と税額を入力(既定で「単純」「固定」が選択されているため税率のみ)
    await modal(page).locator('input[name="taxlabel"]').fill(taxName);
    await modal(page).locator('input[name="percentage"]').fill(addPercentage);

    // 保存(AJAX)。モーダルが閉じるまで待って保存往復の完了を担保する。
    await saveTaxModal(page);

    // Tax.js の addTaxDetails がライブ一覧へ行を追加していること(=保存反映)を
    // リロード前に確認する。これで DB 永続化の往復が完了していると確定できる。
    await expect(row(page, taxName)).toBeVisible();

    // リロード後の一覧に追加した税が現れ、税率が反映されていること
    await gotoSettings(page, listParams);
    const target = row(page, taxName);
    await expect(target).toBeVisible();
    await expect(target.locator("span.taxPercentage")).toHaveText(
      percentagePattern(addPercentage)
    );
  });

  test("税金の編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 対象行の編集アイコンからモーダルを開く
    await expect(row(page, taxName)).toBeVisible();
    await row(page, taxName).locator("a.editTax").click();
    await expect(modal(page).locator("#editTax")).toBeVisible();

    // 税名・税額を編集して保存
    await modal(page).locator('input[name="taxlabel"]').fill(editedName);
    await modal(page)
      .locator('input[name="percentage"]')
      .fill(editedPercentage);
    await saveTaxModal(page);

    // updateTaxDetails が同一行の taxLabel を編集後名へ更新していること(=反映)を
    // リロード前に確認してから永続化を検証する。
    await expect(row(page, editedName)).toBeVisible();

    // リロード後、編集後の税名・税率が反映され、元の税名は消えていること
    await gotoSettings(page, listParams);
    const edited = row(page, editedName);
    await expect(edited).toBeVisible();
    await expect(edited.locator("span.taxPercentage")).toHaveText(
      percentagePattern(editedPercentage)
    );
    await expect(row(page, taxName)).toHaveCount(0);
  });

  // 削除テストなし: 税は無効化(status トグル)のみで削除できないため。
});
