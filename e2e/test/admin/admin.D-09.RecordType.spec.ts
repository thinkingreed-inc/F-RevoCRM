import { test, expect, type Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * D-09 レコードタイプ (モジュール管理 > レコードタイプ)
 *
 * 現行の一覧/編集 UI は React 製 Web コンポーネント `<record-type-manager>`。
 * 旧 Smarty テーブル版 (temp のもの) は廃止され、次の構造に置き換わっている。
 *   - モジュール選択:   Radix Select (data-slot="select-trigger")、選択肢は portal 内 [role="option"]
 *   - 判定フィールド:   Radix Select
 *   - 判定値 (複数選択): MultiSelectChips (button[aria-haspopup="listbox"] + li[role="option"])
 *   - 一覧の行操作:     button[aria-label="編集"/"複製"/"削除"]
 *   - 保存/削除:        fetch POST (?parent=Settings&module=RecordType&api=Save|Delete)
 *   - 削除確認:         ネイティブ window.confirm (アプリの confirm-box ではない)
 *
 * 追加→編集→削除を直列で検証し、作成したルールは最後に必ず削除して後始末する。
 * 対象モジュールは既定の Accounts。判定フィールド/判定値は環境のメタから
 * 「先頭の候補」を選ぶことで、特定のピックリスト値に依存しない。
 */
test.describe.skip("管理: レコードタイプ (RecordType)", () => {
  // 本環境には RecordType モジュール(WC版は別ブランチ)が未導入のためスキップ
  const params = { module: "RecordType", view: "List" };
  const token = generateRandomString(8);
  const nameAdd = `e2ert${token}`;
  const nameEdit = `e2ertedit${token}`;

  // 一覧テーブルの行(操作ボタンを持つ tr)を名前で特定
  const row = (page: Page, text: string) =>
    page
      .locator("record-type-manager table tbody tr")
      .filter({ hasText: text });

  // Radix Select の開いているリスト(portal 内)
  const openSelectList = (page: Page) =>
    page.locator('[data-slot="select-content"]');

  // 対象モジュールの一覧が描画され、操作可能になるまで待つ
  const waitPanelReady = async (page: Page) => {
    await expect(
      page.locator("record-type-manager .record-type-manager")
    ).toBeVisible();
    // 「+ 新規ルール」ボタン、またはルール一覧のいずれかが出るまで待つ
    await expect(
      page.getByRole("button", { name: "+ 新規ルール" })
    ).toBeVisible({ timeout: 15000 });
  };

  // 判定フィールド Select で先頭の候補を選ぶ
  const selectFirstField = async (page: Page) => {
    // レコードタイプ名入力の直後にある判定フィールド Select トリガ。
    // フォーム内の Select トリガは 1 つ(新規時は複製 Select が出ないため)。
    const trigger = page
      .locator('[data-slot="select-trigger"]')
      .filter({ hasText: "フィールド選択" });
    await trigger.click();
    const firstOption = openSelectList(page).locator('[role="option"]').first();
    await firstOption.waitFor({ state: "visible" });
    await firstOption.click();
  };

  // 判定値 (MultiSelectChips) で先頭の候補を選ぶ
  const selectFirstPickValue = async (page: Page) => {
    const trigger = page.locator('button[aria-haspopup="listbox"]');
    await trigger.click();
    const option = page.locator('ul[role="listbox"] li[role="option"]').first();
    await option.waitFor({ state: "visible" });
    await option.click();
    // Popover を閉じてフォーカスを外す
    await page.keyboard.press("Escape");
  };

  test("レコードタイプの追加", async ({ page }) => {
    await gotoSettings(page, params);
    await waitPanelReady(page);

    await page.getByRole("button", { name: "+ 新規ルール" }).click();

    // レコードタイプ名
    await page.getByPlaceholder("例: 大企業").fill(nameAdd);
    // 判定フィールド → 判定値(依存関係があるためこの順)
    await selectFirstField(page);
    await selectFirstPickValue(page);

    // 保存 (fetch POST を待つ)
    await saveAndSettle(
      page,
      page.getByRole("button", { name: "保存", exact: true })
    );

    // 再読込して一覧に追加行が現れること
    await gotoSettings(page, params);
    await waitPanelReady(page);
    await expect(row(page, nameAdd)).toBeVisible();
  });

  test("レコードタイプの編集", async ({ page }) => {
    await gotoSettings(page, params);
    await waitPanelReady(page);

    await row(page, nameAdd).getByRole("button", { name: "編集" }).click();

    // 編集フォームに現在名が反映されていること
    const nameInput = page.getByPlaceholder("例: 大企業");
    await expect(nameInput).toHaveValue(nameAdd);

    await nameInput.fill(nameEdit);
    await saveAndSettle(
      page,
      page.getByRole("button", { name: "保存", exact: true })
    );

    // 再読込して新名が現れ、旧名が消えていること
    await gotoSettings(page, params);
    await waitPanelReady(page);
    await expect(row(page, nameEdit)).toBeVisible();
    await expect(row(page, nameAdd)).toHaveCount(0);
  });

  test("レコードタイプの削除", async ({ page }) => {
    // 削除確認はネイティブ window.confirm。承認する。
    page.on("dialog", (d) => d.accept().catch(() => {}));

    await gotoSettings(page, params);
    await waitPanelReady(page);

    await saveAndSettle(
      page,
      row(page, nameEdit).getByRole("button", { name: "削除" })
    );

    // 再読込して一覧から消えていること
    await gotoSettings(page, params);
    await waitPanelReady(page);
    await expect(row(page, nameEdit)).toHaveCount(0);
  });
});
