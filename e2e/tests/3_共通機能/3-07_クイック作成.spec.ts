import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listSearch,
  listRows,
  clearListSearch,
  firstRecordId,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: クイック作成(＋ボタン) — 機能一覧 12-2
 *
 * ヘッダ右上の ＋ (#menubar_quickCreate) → モジュール選択
 * (#menubar_quickCreate_<Module>) で、React WebコンポーネントのクイックFC作成
 * ダイアログ(<quick-create> 内の [role="dialog"])が開く。必須項目(顧客企業名)を
 * 入力し「保存」で作成できることを検証する。作成したレコードは後始末で削除する。
 */
test.describe("共通: クイック作成(＋)", () => {
  test("ヘッダの＋から顧客企業を作成できる", async ({ page }) => {
    const name = `E2Eqc${generateRandomString(6)}`;
    await gotoList(page, "Accounts");

    await page.locator("#menubar_quickCreate").click();
    // ドロップダウンが開くのを待ってからモジュール項目をクリックする
    await page.waitForTimeout(500);
    await page.locator("#menubar_quickCreate_Accounts").click();

    // クイック作成ダイアログ(WebComponents)は React ポータルで body 直下に
    // dialog ロールとして描画されるため、role で捉える(<quick-create> 内には無い)。
    const dialog = page.getByRole("dialog", { name: /クイック作成/ });
    await expect(dialog).toBeVisible({ timeout: 15000 });

    await dialog
      .getByRole("textbox", { name: "顧客企業名を入力してください" })
      .fill(name);
    await dialog.getByRole("button", { name: "保存" }).click();
    await expect(dialog).toBeHidden({ timeout: 15000 });
    await page.waitForLoadState("networkidle");

    // 作成確認: 一覧で名前検索して 1 件
    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    await expect(
      listRows(page).filter({ hasText: name }).first()
    ).toBeVisible();

    // 後始末: 作成レコードを削除
    const id = await firstRecordId(page);
    await clearListSearch(page);
    await deleteViaDetail(page, "Accounts", id);
  });
});
