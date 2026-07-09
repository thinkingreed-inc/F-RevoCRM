import { expect, type Browser, type Page } from "@playwright/test";
import { gotoList } from "./listview";
import { loginInIsolatedContext } from "./settings";
import { passwordFor } from "../fixtures/seedSpec";
import { revealHiddenFilters, itemsBy, saveModalAs } from "./customview";

/**
 * 共有リスト(CustomView の全員公開)操作の共通ヘルパ。
 *
 * 【実画面で確定した共有設定 UI】(brief の想定 `input[name="status"][value="2"]` ラジオではなかった)
 *  - 「このリストを共有する」チェックボックス: `input[data-toogle-members="true"]`
 *    (name="sharelist", value="1")。チェックすると `#memberList` の select2 が有効化される。
 *  - 共有先メンバー: `select#memberList`(select2 multi、name="members[]")。
 *    `optgroup[label=全て]` 配下の `option[value="All::Users"]`(表示名「全てのユーザー」)を選ぶと
 *    全員に公開できる。select2 で見た目上は隠れているが、native な `<select>` は
 *    `display:block`(opacity 0 のみ)で残っているため Playwright の `selectOption` が
 *    そのまま効き、change イベント経由で select2 側の状態も追従する。
 *  - 実際に POST される `status` の値は、チェックボックス ON かつ All::Users 選択時に
 *    JS(CustomView.js の submitHandler)が `#allUsersStatusValue`(data-public 属性)から
 *    動的に補完する。実測では data-public="3" / data-private="1"
 *    (`CV_STATUS_PUBLIC=3`, `CV_STATUS_PRIVATE=1`。include/utils/utils.php 参照)。
 *    brief にあった status=2(`CV_STATUS_PENDING`)は「承認待ち」であり、全員公開ではない。
 *  - 承認フロー: status=3 は保存直後から即時に公開扱いになり、承認待ち一覧
 *    (isPending/status=2)を経由しない。admin(作成者)が承認する手順は不要だった。
 *  - サイドバーの共有リスト節: `#module-filters .list-group#sharedList` 配下の
 *    `h6.lists-header`(見出し文言「共有リスト」)。個人リストは `.list-group#myList`。
 *    どちらも `li.listViewFilter` の構造は同一のため、既存の `itemsBy`/`expectFilterInSidebar`
 *    (グループを問わず名前で検索)がそのまま使える。
 */

/** 全員に共有(公開)する CustomView を作成する。 */
export async function createSharedFilter(
  page: Page,
  module: string,
  name: string
): Promise<void> {
  await gotoList(page, module);
  await page.locator("#createFilter").click();
  const modal = page.locator(".modal-content:visible").first();
  await expect(modal).toBeVisible({ timeout: 15000 });
  // 「このリストを共有する」を ON にしてメンバー選択を有効化し、全ユーザーを選ぶ。
  // (saveModalAs が viewname 入力→保存を担うので、共有トグルは保存前に済ませておく)
  await modal.locator('input[data-toogle-members="true"]').check();
  await modal.locator("#memberList").selectOption("All::Users");
  // viewname 入力 + AJAX 保存ハンドラ登録待ち + 保存確定待ちは customview.ts と共通化する
  // (作成/複製/編集モーダルと同じ AJAX タイミング知見を一箇所に集約し drift を防ぐ)
  await saveModalAs(page, modal, name);
}

/**
 * 別ユーザーでログインし、指定名のリストが「表示されない」ことを確認する。
 * マイリスト(個人/非公開の CustomView)は作成者本人にしか見えず、別ユーザーには
 * 出ないことの検証に使う(共有リストの expectSharedVisibleAs の否定版)。
 */
export async function expectFilterHiddenAs(
  browser: Browser,
  module: string,
  name: string,
  username: string
): Promise<void> {
  const { context, page } = await loginInIsolatedContext(
    browser,
    username,
    passwordFor(username)
  );
  try {
    await gotoList(page, module);
    await revealHiddenFilters(page);
    // 非公開リストは別ユーザーのサイドバーに現れない。念のため「もっと」展開後も
    // 0 件であることを確認する。
    await expect(itemsBy(page, name)).toHaveCount(0, { timeout: 15000 });
  } finally {
    await context.close();
  }
}

/** 別ユーザーでログインし、共有リストに name が出ることを確認する。 */
export async function expectSharedVisibleAs(
  browser: Browser,
  module: string,
  name: string,
  username: string
): Promise<void> {
  const { context, page } = await loginInIsolatedContext(
    browser,
    username,
    passwordFor(username)
  );
  try {
    await gotoList(page, module);
    await revealHiddenFilters(page);
    await expect(itemsBy(page, name).first()).toBeVisible({ timeout: 15000 });
  } finally {
    await context.close();
  }
}
