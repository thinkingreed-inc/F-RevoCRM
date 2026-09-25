import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listRows,
  listSearch,
  clearListSearch,
  createAccount,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: 一覧からの検索(列検索) — 機能一覧 2-4
 *
 * ヘッダ直下の検索行(input.listSearchContributor[name=<field>])に値を入れ、
 * Enter(または検索ボタン)で AJAX 絞り込みが行われる。
 *
 * 並行実行(DB 共有)でも安定するよう、一意名の専用レコードを作り、その名前で
 * accountname 列を検索して該当レコードだけに絞り込めることを検証する。
 */
test.describe("共通: 一覧の列検索", () => {
  test("名前列で検索すると該当レコードに絞り込まれる", async ({ page }) => {
    const name = `E2Esearch${generateRandomString(8)}`;
    const recordId = await createAccount(page, name);

    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);

    // 一意名なので、該当行が表示され、結果は 1 件に絞り込まれる
    await expect(
      listRows(page).filter({ hasText: name }).first()
    ).toBeVisible();
    await expect(listRows(page)).toHaveCount(1);

    // 後始末: 検索条件をクリア(セッションに残るため)し、レコードを削除
    await clearListSearch(page);
    await deleteViaDetail(page, "Accounts", recordId);
  });
});
