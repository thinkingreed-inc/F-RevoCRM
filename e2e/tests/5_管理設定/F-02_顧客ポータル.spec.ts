import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * F-02 顧客ポータル (Settings > CustomerPortal)
 *
 * AJAX 保存型の代表。全体設定であるポータルのアナウンス文を編集し、
 * リロード後もその値が保持されることを検証する。グローバル設定のため、
 * 元のアナウンス文を退避 → 変更 → 検証 → 原状復帰し、後続テストへ影響を残さない。
 *
 * 実装メモ:
 * - 保存ボタン(#savePortalInfo)はフォーム未変更時は disabled。textarea を
 *   編集(input/keyup)すると change イベントが発火し、活性化される。
 * - 保存はページ遷移しない AJAX POST のため saveAndSettle で完了を待つ。
 * - textarea の描画値はテンプレート上、前後に空白・改行が付くため trim して比較する。
 */
test.describe("管理: 顧客ポータル (CustomerPortal)", () => {
  const portalParams = { module: "CustomerPortal", view: "Index" };

  /** アナウンス textarea を取得する(表示待ち込み)。 */
  async function announcementInput(page: Page) {
    const input = page.locator("#portalAnnouncement");
    await expect(input).toBeVisible();
    return input;
  }

  test("アナウンス文を編集して保持され、元に戻せる", async ({ page }) => {
    const testAnnouncement = `E2Eテストお知らせ${generateRandomString(6)}`;

    // 編集画面を開き、元のアナウンス文を退避する
    await gotoSettings(page, portalParams);
    let announcement = await announcementInput(page);
    const originalAnnouncement = (await announcement.inputValue()).trim();

    // テスト値に更新して保存する(AJAX 保存のため完了を待つ)
    await announcement.fill(testAnnouncement);
    const saveButton = page.locator("#savePortalInfo");
    await expect(saveButton).toBeEnabled();
    await saveAndSettle(page, saveButton);

    // リロード後もアナウンス文が保持されていること
    await gotoSettings(page, portalParams);
    announcement = await announcementInput(page);
    expect((await announcement.inputValue()).trim()).toBe(testAnnouncement);

    // 原状復帰: 元のアナウンス文に戻す。空文字への復元でも保存ボタンが活性化する
    // よう、明示的に change を発火させる(未変更なら disabled のままのため)。
    await announcement.fill(originalAnnouncement);
    await saveButton.dispatchEvent("change");
    await expect(saveButton).toBeEnabled();
    await saveAndSettle(page, saveButton);

    // 元のアナウンス文に戻っていること
    await gotoSettings(page, portalParams);
    announcement = await announcementInput(page);
    expect((await announcement.inputValue()).trim()).toBe(originalAnnouncement);
  });
});
