import { test, expect } from "../../fixtures/isolated";
import { gotoList } from "../../utils/listview";
import { generateRandomString } from "../../utils/util";
import {
  itemsBy,
  revealHiddenFilters,
  createPersonalFilter,
  deletePersonalFilter,
  duplicatePersonalFilter,
  editPersonalFilter,
  expectFilterInSidebar,
} from "../../utils/customview";
import {
  createSharedFilter,
  expectSharedVisibleAs,
  expectFilterHiddenAs,
} from "../../utils/sharedList";

/**
 * 共通機能: リスト機能(CustomView) — 機能一覧 2-2
 *
 * 個人リストの 作成 / 切替 / 複製 / 編集 / 削除 と、共有リスト(全員公開)の
 * 可視範囲(自分 / 別ユーザー)、マイリスト(非公開)が別ユーザーに出ないことを検証する。
 *
 * **CustomView の実装はモジュール非依存**(`Vtiger_CustomView_Model` を
 * オーバーライドしているモジュールは無く、`views/ListView.php` のモジュール別実装も無い)。
 * そのため CI では マトリクス(2-1)の CV 系ケースをモジュール横断で反復せず、
 * この spec が Accounts 1 モジュールで代表検証する
 * (マトリクス側は `CI_SKIP_CASES`。実測でマトリクス CPU 時間の 68% を占めていた)。
 * ローカルのフル実行では従来どおりマトリクスが全モジュールで CV を回す。
 *
 * 共有リストの可視範囲は過去に本体不具合があった箇所
 * (`vtiger_cv2role`/`vtiger_cv2rs` 欠落で非 admin の CustomView が 0 件になる →
 *  migration `20260709161603_add_missing_cv2role_cv2rs_tables.php` で解消)。
 * 回帰検知のため CI に残す必要がある。
 *
 * 主要セレクタ・知見は e2e/utils/customview.ts / e2e/utils/sharedList.ts の
 * ヘルパ実装を参照(このファイルは Accounts を固定モジュールとして呼び出すだけ)。
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

  test("個人リストを編集(名称変更)できる", async ({ page }) => {
    test.setTimeout(90000);
    const name = `E2Ecvedit${generateRandomString(6)}`;
    const renamed = `${name}R`;
    await createPersonalFilter(page, MODULE, name);
    await editPersonalFilter(page, MODULE, name, renamed);
    await deletePersonalFilter(page, MODULE, renamed);
  });

  test("共有リストは作成者自身のサイドバーに出る", async ({ page }) => {
    test.setTimeout(90000);
    const name = `E2Ecvshr${generateRandomString(6)}`;
    await createSharedFilter(page, MODULE, name);
    try {
      await expectFilterInSidebar(page, MODULE, name, true);
    } finally {
      await deletePersonalFilter(page, MODULE, name);
    }
  });

  test("共有リストは別ユーザーにも出る", async ({ page, browser }) => {
    test.setTimeout(120000);
    const name = `E2Ecvshro${generateRandomString(6)}`;
    await createSharedFilter(page, MODULE, name);
    try {
      // 非 admin ユーザーで見えることを確認する(cv2role/cv2rs の回帰検知)
      await expectSharedVisibleAs(browser, MODULE, name, "e2e_director");
    } finally {
      await deletePersonalFilter(page, MODULE, name);
    }
  });

  test("マイリスト(非公開)は別ユーザーには出ない", async ({ page, browser }) => {
    test.setTimeout(120000);
    const name = `E2Ecvmineo${generateRandomString(6)}`;
    await createPersonalFilter(page, MODULE, name);
    try {
      await expectFilterHiddenAs(browser, MODULE, name, "e2e_director");
    } finally {
      await deletePersonalFilter(page, MODULE, name);
    }
  });
});
