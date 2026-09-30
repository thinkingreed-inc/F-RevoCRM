import { test, expect } from "../../fixtures/isolated";
import type { Locator, Page } from "@playwright/test";
import { gotoSettings } from "../../utils/settings";

/**
 * F-07 システム変数 (System > システム構成 > システム変数 / Settings.Parameters)
 *
 * #1469 で編集 UI が React WebComponent(<parameter-edit>)のダイアログに置き換わり、
 * 追加・削除の導線が一覧から外れた。そのため旧 UI(EditAjax のモーダル form#editCurrency)
 * 前提の「追加 → 編集 → 削除」シナリオは成立しない。
 *
 * システム変数はグローバル設定のため新規追加できない前提に合わせ、既存の変数を
 * 一時的に編集して検証し、各テストの最後に必ず元の値へ戻す(後続テストへ痕跡を残さない)。
 *
 * 型ごとの入力 UI と、シークレット(値のマスク)の挙動を通しで確認するため serial で実行する。
 */
test.describe.serial("管理: システム変数 (Parameters)", () => {
  // settingsUrl が parent=Settings を付与するため、module/view のみ指定する
  const listParams = { module: "Parameters", view: "List" };

  // 検証に使う既存のシステム変数(初期データとして必ず存在する)
  const INTEGER_KEY = "USER_LOCK_TIME";
  const BOOLEAN_KEY = "SHOW_SCHEDULE_CONFIRM_FLAG";

  /**
   * 一覧の指定キーの行。
   * 備考に別のキー名が登場する変数があるため(USER_LOCK_COUNT の備考に USER_LOCK_TIME 等)、
   * 部分一致ではなくセルの完全一致で絞る。
   */
  const rowOf = (page: Page, key: string) =>
    page
      .locator("#listview-table tr.listViewEntries")
      .filter({ has: page.getByText(key, { exact: true }) });

  /** 一覧の「値」セル(列は キー / 値 / 備考 の順。ID 列は非表示) */
  const valueCellOf = (page: Page, key: string) =>
    rowOf(page, key).locator("td.listViewEntryValue").nth(1);

  /** 編集ダイアログ。Radix は閉じた後も非表示要素を残すため :visible で絞る */
  const dialog = (page: Page) => page.locator('[role="dialog"]:visible');

  /** 指定キーの編集ダイアログを開く */
  const openEditDialog = async (page: Page, key: string): Promise<Locator> => {
    await rowOf(page, key).locator("a.parameter-edit-btn").click();
    const opened = dialog(page);
    await expect(opened.getByRole("heading", { name: key })).toBeVisible();
    return opened;
  };

  /**
   * ダイアログの保存。保存に成功すると Parameters.js が一覧をリロードするため、
   * ダイアログが閉じ切るまで待ってから次の操作へ進む。
   */
  const saveDialog = async (page: Page, opened: Locator): Promise<void> => {
    await opened.getByRole("button", { name: "保存" }).click();
    await expect(opened).toBeHidden({ timeout: 15000 });
    await page.waitForLoadState("networkidle").catch(() => {});
  };

  /** ダイアログを保存せずに閉じる */
  const cancelDialog = async (opened: Locator): Promise<void> => {
    await opened.getByRole("button", { name: "キャンセル" }).click();
    await expect(opened).toBeHidden();
  };

  test("追加・削除の導線が一覧に出ない", async ({ page }) => {
    await gotoSettings(page, listParams);

    await expect(page.locator("#listview-table")).toBeVisible();
    // 追加ボタン(triggerAdd)は hasCreatePermissions() で非表示にしている
    await expect(page.locator("button.addButton")).toHaveCount(0);
    // 削除アイコンは getRecordLinks() から外している
    await expect(page.locator("#listview-table i.fa-trash")).toHaveCount(0);
    // 編集アイコンは残っている
    await expect(page.locator("#listview-table i.fa-pencil").first()).toBeVisible();
  });

  test("型に応じた入力 UI で編集ダイアログが開く", async ({ page }) => {
    await gotoSettings(page, listParams);

    // integer 型は数値入力。トグルはシークレットの 1 つだけ
    const integerDialog = await openEditDialog(page, INTEGER_KEY);
    await expect(integerDialog.getByRole("spinbutton")).toBeVisible();
    await expect(integerDialog.getByRole("switch")).toHaveCount(1);
    await cancelDialog(integerDialog);

    // boolean 型は値もトグル。値とシークレットで 2 つになる
    const booleanDialog = await openEditDialog(page, BOOLEAN_KEY);
    await expect(booleanDialog.getByRole("spinbutton")).toHaveCount(0);
    await expect(booleanDialog.getByRole("switch")).toHaveCount(2);
    await cancelDialog(booleanDialog);
  });

  test("値セルのクリックでも編集ダイアログが開く", async ({ page }) => {
    await gotoSettings(page, listParams);

    await valueCellOf(page, INTEGER_KEY).click();

    const opened = dialog(page);
    await expect(opened.getByRole("heading", { name: INTEGER_KEY })).toBeVisible();
    await cancelDialog(opened);
  });

  test("値を変更すると一覧に反映される", async ({ page }) => {
    await gotoSettings(page, listParams);

    const originalValue = (await valueCellOf(page, INTEGER_KEY).innerText()).trim();
    // 元の値と必ず異なる値にする
    const editedValue = String(Number(originalValue) + 5);

    try {
      const opened = await openEditDialog(page, INTEGER_KEY);
      await opened.getByRole("spinbutton").fill(editedValue);
      await saveDialog(page, opened);

      await gotoSettings(page, listParams);
      await expect(valueCellOf(page, INTEGER_KEY)).toHaveText(editedValue);
    } finally {
      // 原状復帰
      await gotoSettings(page, listParams);
      const opened = await openEditDialog(page, INTEGER_KEY);
      await opened.getByRole("spinbutton").fill(originalValue);
      await saveDialog(page, opened);
    }
  });

  test("シークレット ON で値がマスクされ、解除には値の再入力が必要", async ({
    page,
  }) => {
    await gotoSettings(page, listParams);

    const originalValue = (await valueCellOf(page, INTEGER_KEY).innerText()).trim();
    const editedDescription = `E2E シークレット検証 ${Date.now()}`;
    let originalDescription = "";

    try {
      // 1. シークレットを ON にして保存する
      let opened = await openEditDialog(page, INTEGER_KEY);
      originalDescription = await opened.getByRole("textbox").inputValue();
      await opened.getByRole("switch", { name: "値を表示する" }).click();
      await saveDialog(page, opened);

      // 2. 一覧ではマスク表示になり、実際の値が見えないこと
      await gotoSettings(page, listParams);
      await expect(valueCellOf(page, INTEGER_KEY)).toHaveText("*******");

      // 3. 編集ダイアログでも現在の値は表示されず、その旨の注記が出ること
      opened = await openEditDialog(page, INTEGER_KEY);
      await expect(opened.getByRole("spinbutton")).toHaveValue("");
      await expect(
        opened.getByText(/現在の値は表示されません/)
      ).toBeVisible();

      // 4. 値欄に触れず備考だけ変更しても保存できること
      //    (このとき既存値が壊れないことは Vitest / PHPUnit 側で検証している)
      await opened.getByRole("textbox").fill(editedDescription);
      await saveDialog(page, opened);
      await gotoSettings(page, listParams);
      await expect(valueCellOf(page, INTEGER_KEY)).toHaveText("*******");

      // 5. 値を入力せずに解除しようとすると弾かれること。
      //    解除するだけで秘匿していた値を一覧で覗けてしまうのを防ぐ。
      opened = await openEditDialog(page, INTEGER_KEY);
      await opened.getByRole("switch", { name: "値を隠す" }).click();
      await expect(
        opened.getByText(/シークレットを解除する場合は/)
      ).toBeVisible();
      await opened.getByRole("button", { name: "保存" }).click();
      // 案内文にも同じ言い回しが含まれるため、エラー表示だけを完全一致で拾う
      await expect(
        opened.getByText("値を入力してください", { exact: true })
      ).toBeVisible();
      // 保存されず、ダイアログは開いたままになる
      await expect(opened).toBeVisible();

      // 6. 新しい値を入れれば解除でき、その値が一覧に出ること
      await opened.getByRole("spinbutton").fill(originalValue);
      await saveDialog(page, opened);
      await gotoSettings(page, listParams);
      await expect(valueCellOf(page, INTEGER_KEY)).toHaveText(originalValue);
    } finally {
      // 原状復帰(シークレット OFF・元の値・元の備考)
      await gotoSettings(page, listParams);
      const opened = await openEditDialog(page, INTEGER_KEY);
      const secretOn = opened.getByRole("switch", { name: "値を隠す" });
      if (await secretOn.count()) {
        await secretOn.click();
      }
      await opened.getByRole("spinbutton").fill(originalValue);
      if (originalDescription) {
        await opened.getByRole("textbox").fill(originalDescription);
      }
      await saveDialog(page, opened);
    }
  });
});
