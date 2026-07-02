import { test, expect } from "@playwright/test";
import { url } from "../../utils/util";
import { gotoList, firstRow } from "../../utils/listview";

/**
 * 共通機能: フォロー(スター)  — 機能一覧 3-1
 *
 * ・一覧画面: 行の☆(a.markStar)クリックでフォロー ON/OFF が切り替わる
 *   (List.js registerStarToggle。クリックで active クラスを楽観的にトグルし、
 *    action=SaveStar を POST する)
 * ・詳細画面: #starToggle ボタンで同様にトグルする(Detail.js)
 *
 * レコードが存在する Accounts を対象にする(seed.setup で最低1件保証)。
 * トグルは元に戻して後始末する(状態を変えっぱなしにしない)。
 */
test.describe("共通: フォロー(スター)", () => {
  test("一覧画面: 行のスターでフォロー ON/OFF が切り替わる", async ({
    page,
  }) => {
    await gotoList(page, "Accounts");

    const star = firstRow(page).locator("a.markStar");
    await expect(star).toBeVisible();
    const wasActive = await star.evaluate((el) =>
      el.classList.contains("active")
    );

    // 1回目クリック: 状態が反転する
    await star.click();
    await expect(star).toHaveClass(
      wasActive ? /fa-star-o/ : /\bactive\b/
    );

    // 2回目クリック: 元の状態へ戻す(後始末)
    await star.click();
    if (wasActive) {
      await expect(star).toHaveClass(/\bactive\b/);
    } else {
      await expect(star).toHaveClass(/fa-star-o/);
    }
  });

  test("詳細画面: starToggle でフォロー ON/OFF が切り替わる", async ({
    page,
  }) => {
    // 一覧の先頭行から詳細URLを取得して開く
    await gotoList(page, "Accounts");
    const href = await firstRow(page)
      .locator('a[href*="view=Detail"]')
      .first()
      .getAttribute("href");
    expect(href).toBeTruthy();
    const recordId = href!.match(/record=(\d+)/)?.[1];
    expect(recordId).toBeTruthy();

    await page.goto(
      url(
        `index.php?module=Accounts&view=Detail&record=${recordId}&app=MARKETING`
      )
    );
    await page.waitForLoadState("networkidle");

    const toggle = page.locator("#starToggle");
    await expect(toggle).toBeVisible();
    const wasActive = await toggle.evaluate((el) =>
      el.classList.contains("active")
    );

    await toggle.click();
    // 反転を待つ
    await expect
      .poll(async () =>
        toggle.evaluate((el) => el.classList.contains("active"))
      )
      .toBe(!wasActive);

    // 元へ戻す
    await toggle.click();
    await expect
      .poll(async () =>
        toggle.evaluate((el) => el.classList.contains("active"))
      )
      .toBe(wasActive);
  });
});
