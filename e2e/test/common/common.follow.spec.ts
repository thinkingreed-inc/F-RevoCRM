import { test, expect } from "../../fixtures/isolated";
import {
  gotoList,
  listRows,
  listSearch,
  clearListSearch,
  createAccount,
  gotoDetail,
  deleteViaDetail,
} from "../../utils/listview";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: フォロー(スター)  — 機能一覧 3-1
 *
 * ・一覧画面: 行の☆(a.markStar)クリックでフォロー ON/OFF が切り替わる
 *   (List.js registerStarToggle。クリックで active クラスを楽観的にトグル)
 * ・詳細画面: #starToggle ボタンで同様にトグルする(Detail.js)
 *
 * 並行実行(DB 共有)でも安全なよう、専用の顧客企業を作って操作・後始末する。
 * (「先頭の任意レコード」だと他テストの使い捨てレコードを掴む恐れがある)
 */
test.describe("共通: フォロー(スター)", () => {
  test("一覧画面: 行のスターでフォロー ON/OFF が切り替わる", async ({
    page,
  }) => {
    const name = `E2Efollow${generateRandomString(6)}`;
    const recordId = await createAccount(page, name);

    // 自分のレコードを一覧で絞り込み、その行のスターを操作する
    await gotoList(page, "Accounts");
    await listSearch(page, "accountname", name);
    const star = listRows(page)
      .filter({ hasText: name })
      .first()
      .locator("a.markStar");
    await expect(star).toBeVisible();

    // 作成直後は未フォロー(fa-star-o)。クリックで active になる。
    await star.click();
    await expect(star).toHaveClass(/\bactive\b/);

    // もう一度で解除
    await star.click();
    await expect(star).toHaveClass(/fa-star-o/);

    await clearListSearch(page);
    await deleteViaDetail(page, "Accounts", recordId);
  });

  test("詳細画面: starToggle でフォロー ON/OFF が切り替わる", async ({
    page,
  }) => {
    const recordId = await createAccount(
      page,
      `E2Efollowd${generateRandomString(6)}`
    );
    await gotoDetail(page, "Accounts", recordId);

    const toggle = page.locator("#starToggle");
    await expect(toggle).toBeVisible();
    const wasActive = await toggle.evaluate((el) =>
      el.classList.contains("active")
    );

    await toggle.click();
    await expect
      .poll(async () =>
        toggle.evaluate((el) => el.classList.contains("active"))
      )
      .toBe(!wasActive);

    await toggle.click();
    await expect
      .poll(async () =>
        toggle.evaluate((el) => el.classList.contains("active"))
      )
      .toBe(wasActive);

    await deleteViaDetail(page, "Accounts", recordId);
  });
});
