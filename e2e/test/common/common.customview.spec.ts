import { test, expect } from "../../fixtures/isolated";
import { gotoList } from "../../utils/listview";
import { generateRandomString } from "../../utils/util";
import {
  itemsBy,
  revealHiddenFilters,
  createPersonalFilter,
  deletePersonalFilter,
  duplicatePersonalFilter,
} from "../../utils/customview";

/**
 * 共通機能: リスト機能(CustomView) — 機能一覧 2-2
 *
 * 個人リストの 作成 / 切替 / 複製 / 削除 を検証する。
 *
 * 主要セレクタ・知見は e2e/utils/customview.ts のヘルパ実装を参照(このファイルは
 * Accounts を固定モジュールとしてヘルパを呼び出すだけ)。
 */

const MODULE = "Accounts";

test.describe("共通: リスト(CustomView)", () => {
  test("個人リストを作成して削除できる", async ({ page }) => {
    test.setTimeout(90000);
    await gotoList(page, MODULE);
    const name = `E2Ecv${generateRandomString(6)}`;
    await createPersonalFilter(page, MODULE, name);
    await deletePersonalFilter(page, MODULE, name);
  });

  test("リストを切り替えられる", async ({ page }) => {
    test.setTimeout(90000);
    await gotoList(page, MODULE);
    const name = `E2Ecvsw${generateRandomString(6)}`;
    await createPersonalFilter(page, MODULE, name);

    // 標準リスト「すべて」へ切替 → 現在のリスト名が変わる
    await page
      .locator("#module-filters a.filterName")
      .filter({ hasText: "すべて" })
      .first()
      .click();
    await page.waitForLoadState("networkidle");
    await expect(page.locator(".current-filter-name")).toContainText("すべて");

    // 作成したリストへ戻す(10 件超で隠れる場合があるので展開してから)
    await revealHiddenFilters(page);
    await itemsBy(page, name).first().locator("a.filterName").click();
    await page.waitForLoadState("networkidle");
    await expect(page.locator(".current-filter-name")).toContainText(name);

    await deletePersonalFilter(page, MODULE, name);
  });

  test("リストを複製できる", async ({ page }) => {
    test.setTimeout(120000);
    await gotoList(page, MODULE);
    const src = `E2Ecvsrc${generateRandomString(6)}`;
    const dup = `E2Ecvdup${generateRandomString(6)}`;
    await createPersonalFilter(page, MODULE, src);
    await duplicatePersonalFilter(page, MODULE, src, dup);

    // 後始末: 複製元・複製先の両方を削除
    await deletePersonalFilter(page, MODULE, dup);
    await deletePersonalFilter(page, MODULE, src);
  });
});
