import { test, expect } from "../../fixtures/isolated";
import { url } from "../../utils/util";

/**
 * 追加機能(UI/UX): 編集ページに編集不可(読み取り専用)項目を表示する機能
 * (元スプレッドシート `2_〇×_OSS版_基本機能.xlsx` の `_UI UX` シート No.2)
 *
 * 項目設定(LayoutEditor)の「編集画面に読み取り専用項目の表示」トグル
 * (input[name="editReadonlyDisplay"] / action=updateEditReadonlyDisplay)を
 * FAQ モジュールで ON/OFF し、FAQ のレコード編集ページに読み取り専用項目
 * (登録日時=createdtime 等)が表示される/されないことを検証する。
 *
 * トグルはモジュール共通設定のため、テスト終了時に元の状態へ必ず戻す。
 */
test.describe.serial("追加(UI/UX): 編集画面の読み取り専用項目表示", () => {
  const layoutUrl = url(
    "index.php?module=LayoutEditor&parent=Settings&view=Index&sourceModule=Faq"
  );
  const faqEditUrl = url("index.php?module=Faq&view=Edit&app=SUPPORT");
  const toggle = 'input[name="editReadonlyDisplay"]';
  // 読み取り専用項目(displaytype=2)。ON のときだけ編集フォームに現れる。
  const readonlyFieldInput = 'input[name="createdtime"], [name="createdtime"]';

  async function setToggle(page, on: boolean): Promise<void> {
    await page.goto(layoutUrl);
    await page.waitForLoadState("networkidle");
    const box = page.locator(toggle);
    await box.waitFor({ state: "attached", timeout: 15000 });
    if ((await box.isChecked()) === on) return;
    // bootstrap-switch は実 input が opacity:0。ウィジェット本体(.bootstrap-switch)を
    // クリックしてトグルすると updateEditReadonlyDisplay の AJAX が走る。
    const widget = box
      .locator('xpath=ancestor::div[contains(@class,"bootstrap-switch")][1]')
      .first();
    for (let attempt = 0; attempt < 3; attempt++) {
      await widget.click();
      await page.waitForLoadState("networkidle");
      // トグルが反映されると input の checked 状態が target に変わる。
      if ((await box.isChecked()) === on) return;
      await page.waitForTimeout(500);
    }
    // 反映確認: 再読込しても target 状態であること。
    await page.goto(layoutUrl);
    await page.waitForLoadState("networkidle");
    expect(await page.locator(toggle).isChecked()).toBe(on);
  }

  test("FAQ編集画面で読み取り専用項目の表示ON/OFFが切り替わる", async ({
    page,
  }) => {
    test.setTimeout(90000);
    // 元の状態を控えておく。
    await page.goto(layoutUrl);
    await page.waitForLoadState("networkidle");
    const original = await page.locator(toggle).isChecked();

    try {
      // --- ON: 読み取り専用項目が編集フォームに出る ---
      await setToggle(page, true);
      await page.goto(faqEditUrl);
      await page.waitForLoadState("domcontentloaded");
      await expect(page.locator(readonlyFieldInput).first()).toBeVisible({
        timeout: 15000,
      });

      // --- OFF: 読み取り専用項目が編集フォームに出ない ---
      await setToggle(page, false);
      await page.goto(faqEditUrl);
      await page.waitForLoadState("domcontentloaded");
      await expect(page.locator(readonlyFieldInput)).toHaveCount(0);
    } finally {
      await setToggle(page, original).catch(() => {});
    }
  });
});
