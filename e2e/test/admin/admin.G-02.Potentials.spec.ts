import { test, expect, type Page } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * G-02 案件とプロジェクトのマッピング (マーケティングと営業 > 案件とプロジェクトのマッピング)
 *
 * Leads マッピング(G-01)と同型の MappingDetail 設定画面。編集フォームを開いて
 * 変更なし保存し、フォームが閉じて一覧に戻り、マッピングが保持されることを検証する。
 * 非破壊(値は変更しない)のため原状復帰は不要。
 */
test.describe("管理: 案件とプロジェクトのマッピング (Potentials Mapping)", () => {
  const detailParams = { module: "Potentials", view: "MappingDetail" };

  const mappingRows = (page: Page) =>
    page.locator("#listview-table tbody tr.listViewEntries");

  const editButton = (page: Page) =>
    page
      .locator(".potentialsFieldMappingListPageDiv .settingsHeader")
      .getByRole("button", { name: "編集", exact: true });

  test("編集フォームを開いて保存でき、マッピングが保持される", async ({
    page,
  }) => {
    await gotoSettings(page, detailParams);
    await expect(page.locator("#listview-table")).toBeVisible();
    await expect(editButton(page)).toBeVisible();

    const rows = mappingRows(page);
    await expect(rows.first()).toBeVisible();
    const originalCount = await rows.count();
    expect(originalCount).toBeGreaterThan(0);

    // 「編集」で編集フォームが AJAX 読み込みされ、一覧は DOM ごと差し替わる。
    await editButton(page).click();

    const form = page.locator("form#potentialsMapping");
    await expect(form).toBeVisible();
    await expect(page.locator("#convertPotentialMapping")).toBeVisible();
    await expect(page.locator("#listview-table")).toHaveCount(0);

    const saveButton = form.locator("button.saveButton");
    await expect(saveButton).toBeVisible();

    // 変更なし保存(AJAX)。
    await saveAndSettle(page, saveButton);

    // 保存後: 編集フォームが消え、一覧が再表示されマッピングが残っていること。
    await expect(page.locator("form#potentialsMapping")).toHaveCount(0);
    await expect(page.locator("#listview-table")).toBeVisible();
    expect(await mappingRows(page).count()).toBeGreaterThan(0);

    // リロードしても保持されていること(永続化)。
    await gotoSettings(page, detailParams);
    await expect(page.locator("#listview-table")).toBeVisible();
    expect(await mappingRows(page).count()).toBeGreaterThan(0);
  });
});
