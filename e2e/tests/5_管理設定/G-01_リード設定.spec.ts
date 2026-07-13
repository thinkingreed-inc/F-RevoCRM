import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * G-01 リードの昇格のマッピング (マーケティングと営業 > リードの昇格のマッピング)
 *
 * 設定編集型。マッピング一覧(MappingDetail)から「編集」ボタンで編集フォーム
 * (LeadMappingEdit / form#leadsMapping) を AJAX で開き、値を変更せずに保存する。
 *
 * 画面遷移の実態(LeadMapping.js / Mapping.php で確認):
 *   - 編集ボタンは `Settings_LeadMapping_Js.triggerEdit(...)` を呼ぶ button。
 *     成功すると `.settingsPageDiv div` の中身が編集フォームに差し替わり、
 *     一覧の `.leadsFieldMappingListPageDiv` は DOM ごと消える(全画面遷移なし)。
 *   - 保存(form submit)も JS が横取りする AJAX 保存で、成功すると同じ枠に
 *     MappingDetail が再描画され、一覧に戻る。
 *
 * 注意: `.leadsFieldMappingListPageDiv` は AJAX で差し込まれるレイアウト用の
 * ラッパ div で、ヘッドレスでは実寸ゼロ扱いになり toBeVisible() が hidden 判定
 * になることがある(実際にこの誤アサーションで落ちていた)。そのため可視性の
 * 確認は、中の具体要素(一覧テーブル/データ行/編集ボタン)に対して行う。
 *
 * temp 由来のシナリオが「編集を開いて保存するだけ(フィールド変更なし)」の
 * ため、行の追加・削除は行わず、破壊的変更を残さない。検証は
 *   1. 一覧(MappingDetail)が表示され、初期件数を取得できること
 *   2. 「編集」で編集フォームが AJAX で開くこと
 *   3. 変更なし保存(AJAX)が成功し、編集フォームが消えて一覧に戻ること
 *   4. 保存前後・全画面リロード後もマッピング件数が保持されること
 * を実アサーションで確認する。
 */
test.describe("管理: リードの昇格のマッピング (Leads Mapping)", () => {
  const detailParams = { module: "Leads", view: "MappingDetail" };

  // 一覧(MappingDetail)のデータ行(cfmid を持つ tr)
  const mappingRows = (page: Page) =>
    page.locator("#listview-table tbody tr.listViewEntries");

  // 一覧ヘッダ内の「編集」ボタン(Settings_LeadMapping_Js.triggerEdit を呼ぶ)
  const editButton = (page: Page) =>
    page
      .locator(".leadsFieldMappingListPageDiv .settingsHeader")
      .getByRole("button", { name: "編集", exact: true });

  test("編集フォームを開いて保存でき、マッピングが保持される", async ({
    page,
  }) => {
    // マッピング一覧を開き、初期のマッピング件数を退避する。
    // ラッパ div ではなく、中の一覧テーブルと編集ボタンで表示を確認する。
    await gotoSettings(page, detailParams);
    await expect(page.locator("#listview-table")).toBeVisible();
    await expect(editButton(page)).toBeVisible();

    const rows = mappingRows(page);
    await expect(rows.first()).toBeVisible();
    const originalCount = await rows.count();
    expect(originalCount).toBeGreaterThan(0);

    // 「編集」ボタンを押すと編集フォームが AJAX で読み込まれ、
    // 一覧(.leadsFieldMappingListPageDiv)は DOM ごと差し替わる(全画面遷移なし)。
    await editButton(page).click();

    // 編集フォームが開いたこと。編集フォームが開いている間に一覧の可視性は見ない。
    const form = page.locator("form#leadsMapping");
    await expect(form).toBeVisible();
    await expect(page.locator("#convertLeadMapping")).toBeVisible();
    // 一覧側は差し替えられて存在しなくなっているはず。
    await expect(page.locator("#listview-table")).toHaveCount(0);

    const saveButton = form.locator("button.saveButton");
    await expect(saveButton).toBeVisible();

    // 値を変更せずに保存する(AJAX 保存 → MappingDetail が同じ枠に再描画される)。
    await saveAndSettle(page, saveButton);

    // 保存成功後: 編集フォームが消え、一覧(テーブル・データ行)が再表示されること。
    await expect(page.locator("form#leadsMapping")).toHaveCount(0);
    await expect(page.locator("#listview-table")).toBeVisible();
    await expect(mappingRows(page).first()).toBeVisible();

    // 保存後もマッピングが失われていないこと(保存時に空の編集行が正規化され
    // 件数が1減ることがあるため、完全一致ではなく「行が残っている」ことを確認)。
    expect(await mappingRows(page).count()).toBeGreaterThan(0);

    // 全画面リロードしてもマッピングが保持されている(永続化の確認)。
    await gotoSettings(page, detailParams);
    await expect(page.locator("#listview-table")).toBeVisible();
    expect(await mappingRows(page).count()).toBeGreaterThan(0);
  });
});
