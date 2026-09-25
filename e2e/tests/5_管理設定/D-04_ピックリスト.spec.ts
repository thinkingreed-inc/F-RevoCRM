import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings } from "../../utils/settings";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { apiSession } from "../../utils/api";
import { frQuery } from "../../model/fetcher";

/**
 * D-04 選択肢エディタ (モジュール管理 > 選択肢エディタ)
 *
 * 一覧CRUD型の代表。既定で選択される選択肢フィールド(案件/タイプ)に対し、
 * 追加→編集→削除を直列で行い、各操作が一覧に反映されることを検証する。
 * 行は data-key(=選択肢の値そのもの)で一意に特定するため prefix 検索の曖昧さがない。
 *
 * さらに「選択肢を使っているレコードがある状態で選択肢を削除すると、削除モーダルで
 * 指定した置き換え先の値にレコードが付け替わる」ことも検証する
 * (`vtiger_picklist` の値を消すだけでなく、既存レコードの値を UPDATE する処理が走る)。
 *
 * 注意: モーダルの土台(.modal-content)は非表示の雛形と表示中のものが2つ存在するため、
 * モーダル操作は必ず表示中(:visible)へスコープする。
 */
test.describe.serial("管理: 選択肢エディタ (Picklist)", () => {
  const listParams = { module: "Picklist", view: "Index" };
  const token = generateRandomString(8);
  const value = `e2e${token}`;
  const editedValue = `e2eedit${token}`;

  // 選択肢の値を表す行(data-key に値そのものが入る)
  const row = (page: Page, key: string) =>
    page.locator(`tr.pickListValue[data-key="${key}"]`);

  // 表示中のモーダル
  const modal = (page: Page) => page.locator(".modal-content:visible");

  /**
   * 選択肢を 1 件追加する。
   * モーダル先頭の select2(「選択肢名」。2つ目は「役割」)にキー入力し、Enter でタグ化する。
   * select2 のタグ生成はキーストロークで発火するため pressSequentially を使う。
   */
  async function addPicklistValue(
    page: Page,
    newValue: string,
    params: Record<string, string> = listParams
  ): Promise<void> {
    await gotoSettings(page, params);
    await page.getByRole("button", { name: "選択肢の追加" }).first().click();

    const valueWidget = modal(page).locator(".select2-container").first();
    const tagInput = valueWidget.locator("input.select2-input");
    await tagInput.click();
    await tagInput.pressSequentially(newValue);
    await tagInput.press("Enter");
    await expect(
      valueWidget.locator("li.select2-search-choice")
    ).toContainText(newValue);

    await modal(page).locator('button.btn-success[name="saveButton"]').click();

    // 一覧に追加した値が現れること(AJAX保存の反映ラグに強くするため開き直してリトライ)
    await expect(async () => {
      await gotoSettings(page, params);
      await expect(row(page, newValue)).toBeVisible({ timeout: 3000 });
    }).toPass({ timeout: 25000 });
  }

  test("選択肢の追加", async ({ page }) => {
    await addPicklistValue(page, value);
  });

  test("選択肢の編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = row(page, value);
    await target.hover();
    await target.locator("a.renameItem").click();

    await modal(page).locator('input[name="renamedValue"]').fill(editedValue);
    await modal(page).locator('button.btn-success[name="saveButton"]').click();

    // 新しい値が現れ、元の値は消えていること(反映ラグに強く)
    await expect(async () => {
      await gotoSettings(page, listParams);
      await expect(row(page, editedValue)).toBeVisible({ timeout: 3000 });
      await expect(row(page, value)).toHaveCount(0, { timeout: 3000 });
    }).toPass({ timeout: 25000 });
  });

  test("選択肢の削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = row(page, editedValue);
    await target.hover();
    await target.locator("a.deleteItem").click();

    // 削除確認モーダルの削除ボタン
    await modal(page).locator('button.btn-danger[name="saveButton"]').click();

    // 一覧から削除した値が消えていること(反映ラグに強く)
    await expect(async () => {
      await gotoSettings(page, listParams);
      await expect(row(page, editedValue)).toHaveCount(0, { timeout: 3000 });
    }).toPass({ timeout: 25000 });
  });

  /**
   * 選択肢を削除する。削除モーダルには「削除する選択肢(#deleteValue)」と
   * 「置き換え先(#replaceValue)」があり、置き換え先は必須。
   * `replaceLabel` を渡すとその表示名の候補を選ぶ(未指定なら先頭の候補)。
   *
   * 注意: #replaceValue の option の value は選択肢そのものではなく内部 ID で、
   * 表示テキストは vtranslate される(例: "Existing Business" → 「既存ビジネス」)。
   * そのため DB 値を厳密に検証したい場合は、置き換え先も**テスト内で作った値**
   * (翻訳辞書に無い＝表示 = DB 値になる)にする。
   */
  async function deletePicklistValue(
    page: Page,
    targetValue: string,
    params: Record<string, string>,
    replaceLabel?: string
  ): Promise<void> {
    await gotoSettings(page, params);
    const targetRow = row(page, targetValue);
    await targetRow.hover();
    await targetRow.locator("a.deleteItem").click();

    const replace = modal(page).locator("#replaceValue");
    await expect(replace).toBeAttached({ timeout: 20000 });
    if (replaceLabel) {
      await replace.selectOption({ label: replaceLabel });
    } else {
      const first = await replace
        .locator("option")
        .first()
        .getAttribute("value");
      expect(first, "置き換え先の候補があること").toBeTruthy();
      await replace.selectOption(first!);
    }
    await modal(page).locator('button.btn-danger[name="saveButton"]').click();

    await expect(async () => {
      await gotoSettings(page, params);
      await expect(row(page, targetValue)).toHaveCount(0, { timeout: 3000 });
    }).toPass({ timeout: 25000 });
  }

  test("選択肢を使ったレコードがある状態で削除すると、指定した値へ付け替わる", async ({
    page,
  }) => {
    test.setTimeout(180000);
    // 対象を明示する(既定は「選択肢が使えるモジュールの先頭 + 先頭の選択肢項目」で
    // 環境によって変わりうるため、案件のタイプを URL で固定する)。
    const target = {
      module: "Picklist",
      view: "Index",
      source_module: "Potentials",
      fieldname: "opportunity_type",
    };
    const suffix = generateRandomString(8);
    const useValue = `e2euse${suffix}`;
    const replaceTo = `e2erepl${suffix}`;
    let record: Awaited<ReturnType<typeof createRecordViaApi>> | null = null;
    let replaceLeft = false;

    try {
      // === 1. 選択肢を 2 件登録する(削除対象と置き換え先) ===
      await addPicklistValue(page, useValue, target);
      await addPicklistValue(page, replaceTo, target);
      replaceLeft = true;

      // === 2. 削除対象の選択肢を使う案件を作成する ===
      record = await createRecordViaApi("Potentials", {
        opportunity_type: useValue,
      });
      const session = await apiSession();
      const before = await frQuery(
        session,
        `SELECT opportunity_type FROM Potentials WHERE id = '${record.wsId}';`
      );
      expect(
        before?.[0]?.opportunity_type,
        "作成した案件に追加した選択肢が入っていること"
      ).toBe(useValue);

      // === 3. 選択肢を削除し、置き換え先を明示する ===
      await deletePicklistValue(page, useValue, target, replaceTo);

      // === 4. レコードの値が置き換え先へ付け替わっていること ===
      await expect
        .poll(
          async () => {
            const rows = await frQuery(
              session,
              `SELECT opportunity_type FROM Potentials WHERE id = '${record!.wsId}';`
            );
            return rows?.[0]?.opportunity_type ?? "";
          },
          { timeout: 30000, intervals: [500, 1000, 2000] }
        )
        .toBe(replaceTo);
    } finally {
      if (record) await deleteRecordViaApi(record.session, record.wsId);
      // 置き換え先として作った選択肢も片付ける(既定の候補へ寄せる)
      if (replaceLeft) {
        await deletePicklistValue(page, replaceTo, target).catch(() => {});
      }
    }
  });
});
