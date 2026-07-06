import { test, expect } from "../../fixtures/isolated";
import { gotoList, listRows } from "../../utils/listview";
import { seedSpec } from "../../fixtures/seedSpec";
import { generateRandomString } from "../../utils/util";

/**
 * 共通機能: CustomView の絞り込み条件ビルダ — TEST_COVERAGE の保留ギャップ
 *
 * 個人リストを **条件付き** で作成し、絞り込み結果が既知件数になることを検証する。
 * 拡充ベースラインの `[E2E-SRCH]` Accounts（industry 6 種 × 10 件）を使い、
 * 条件「accountname に "[E2E-SRCH] &lt;industry&gt;" を含む」で industry 1 種ぶん = 10 件に絞る。
 * （industry は picklist で値 UI が select2 に差し替わり不安定なため、テキスト項目の
 *   accountname 条件で決定論的に検証する。）
 *
 * セレクタ（実 DOM 確認済み）:
 *  - 作成: `#createFilter` → モーダル。名前 `input[name="viewname"]`
 *  - 条件(AND)行: `.allConditionContainer .conditionRow`（初期から 1 行ある）
 *    項目 `select[name="columnname"]` / 演算子 `select[name="comparator"]` /
 *    値 `.fieldUiHolder input.inputElement`
 *  - accountname の項目 value = `vtiger_account:accountname:accountname:Accounts_Account_Name:V`
 *
 * 【知見】保存 AJAX ハンドラはモーダル読込後に非同期登録されるため、登録前クリックは
 * ネイティブ GET になり保存されない。待機してから保存し、`view=List&viewname=NN` を待つ。
 */

const sr = seedSpec.accountSearch;
const industry = sr.industries[1]; // "Chemicals"（一意トークンで名前置換されない industry）
const ACCOUNTNAME_COL =
  "vtiger_account:accountname:accountname:Accounts_Account_Name:V";

test.describe("共通: CustomView の絞り込み条件", () => {
  test(`条件 accountname に "${sr.prefix} ${industry}" を含む → ${sr.perIndustry} 件`, async ({
    page,
  }) => {
    test.setTimeout(90000);
    const viewName = `E2Ecvcond${generateRandomString(6)}`;

    await gotoList(page, "Accounts");
    await page.locator("#createFilter").click();
    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 15000 });
    await modal.locator('input[name="viewname"]').fill(viewName);

    // 「条件を追加」で AND 条件の実行行を 1 本出す(初期の .conditionRow は隠しテンプレート)。
    // 保存ボタン同様、追加ハンドラはモーダルの AJAX 読込後に非同期登録される。高並列で
    // 登録が遅れるとクリックが空振りし行が出ない(retries でしか回復しない)ため、行が
    // 現れるまでクリックを再試行する。既に 1 行あれば再クリックせず二重生成を防ぐ。
    const rows = page.locator(
      ".allConditionContainer .conditionList .conditionRow"
    );
    await expect(async () => {
      if ((await rows.count()) === 0) {
        await page
          .locator(".allConditionContainer .addCondition button")
          .first()
          .click();
      }
      await expect(rows.first()).toBeVisible({ timeout: 2000 });
    }).toPass({ timeout: 20000 });
    const row = rows.first();

    // 条件行(AND): accountname / contains(c) / "[E2E-SRCH] Chemicals"
    // 項目・演算子の select は select2 で隠されるため、value を直接設定し change を発火して
    // アプリの再描画ハンドラ(演算子・値 UI 生成)を起動する。
    const setSelect = async (name: string, value: string) => {
      await row.locator(`select[name="${name}"]`).evaluate((el, v) => {
        const sel = el as HTMLSelectElement;
        sel.value = v as string;
        sel.dispatchEvent(new Event("change", { bubbles: true }));
      }, value);
    };
    // columnname 変更で値 UI が非同期に作り直されるため、固定待ちだと高並列で
    // 値の入力が再描画に流されて消える(→ 空条件で 0 件になる)。値が確定するまで
    // 「項目→値UI待ち→演算子→値入力→値の確定確認」を検証つきで再試行する。
    const wanted = `${sr.prefix} ${industry}`;
    const valueInput = row.locator(".fieldUiHolder input.inputElement").first();
    await expect(async () => {
      await setSelect("columnname", ACCOUNTNAME_COL);
      await expect(valueInput).toBeVisible({ timeout: 3000 });
      await setSelect("comparator", "c"); // contains
      await valueInput.fill(wanted);
      await expect(valueInput).toHaveValue(wanted, { timeout: 2000 });
    }).toPass({ timeout: 20000 });

    // 保存 AJAX ハンドラ登録待ち → 保存 → 絞り込み済みリストへ遷移
    await page.waitForTimeout(2500);
    await Promise.all([
      page.waitForURL(/view=List&viewname=\d+/, { timeout: 20000 }).catch(() => {}),
      modal.locator("button.saveButton").first().click(),
    ]);
    await page.waitForLoadState("networkidle").catch(() => {});

    // 条件どおり industry 1 種ぶん = 10 件(保存後の一覧反映ラグを許容)
    await expect(listRows(page)).toHaveCount(sr.perIndustry, { timeout: 10000 });
  });
});
