import { test, expect } from "../../fixtures/isolated";
import type { Page, Locator } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * D-07 文言変更 (モジュール管理 > 文言変更 / LanguageConverter)
 *
 * 文言変換ルールの一覧 CRUD。追加/編集/削除はいずれもモーダル(EditAjax)+ AJAX 保存で、
 * 保存後は #listViewContent が差し替えられる(画面遷移はしない)。
 *
 * ルールは全体の翻訳へ影響する設定のため、テストで作成したルールのみを対象に
 * 追加 → 編集 → 削除 を直列で検証し、最後に必ず削除して原状復帰する
 * (共有環境に副作用を残さない)。
 *
 * 【行の特定は必ず after_string(変換後)で行う理由 — 重要】
 * この一覧の各セルは ListViewContents.tpl の {vtranslate(...)} を通して描画される。
 * vtranslate() は LanguageHandler 経由で Settings_LanguageConverter_Module_Model::convertTranslate()
 * を呼び、登録済みの全ルールを preg_replace で適用する。つまり「変換前(before_string)」の
 * 文言はそのルール自身によって「変換後(after_string)」へ置換されてしまい、一覧の
 * before カラムには before の文字列は決して現れず after の文字列が表示される
 * (DB 上は before_string に before が正しく保存されている。表示だけが変換される)。
 *
 * したがって:
 *   - 行の特定・存在確認は after_string の文言だけで行う(before は表示されない)。
 *   - 「同じ行に before も表示される」ことは原理上あり得ないので確認しない。
 *   - 削除対象の特定も、その時点で有効な after_string(追加後は afterAdd、編集後は afterEdit)で行う。
 *
 * さらにフォーム入力後は toHaveValue で値の定着を確認してから保存する
 * (モーダル描画直後の select2 初期化・再レンダリングで入力が失われる事象を早期に検出するため)。
 */
test.describe.serial("管理: 文言変更 (LanguageConverter)", () => {
  const params = { module: "LanguageConverter", view: "List" };
  const token = generateRandomString(8);
  const beforeAdd = `e2ebefore${token}`;
  const afterAdd = `e2eafter${token}`;
  const afterEdit = `e2eafteredit${token}`;

  // データ行(アクションセルを持つ tr)を after_string の文言で特定する。
  // (before の文言はルール自身の変換で表示されないため使えない)
  const rowByAfter = (page: Page, afterText: string): Locator =>
    page
      .locator("tr.listViewEntries")
      .filter({ has: page.locator(".table-actions") })
      .filter({ hasText: afterText });

  // 表示中のモーダル(EditAjax)内のフォームにスコープしたロケータ。
  const form = (page: Page): Locator =>
    page.locator(".modal-content:visible #editCurrency");

  // フォーム項目に値を入れ、定着を確認してから返す。exact な name で個別に
  // 特定し(before/after は同じ class="inputElement" のため name で厳密に分ける)、
  // fill 直後に toHaveValue で確認する(select2 等の再描画で入力が失われるケースを検出)。
  const fillAndConfirm = async (page: Page, name: string, value: string) => {
    const input = form(page).locator(`input[name="${name}"]`);
    await expect(input).toHaveCount(1);
    await input.fill(value);
    await expect(input).toHaveValue(value);
  };

  test("文言変更ルールの追加", async ({ page }) => {
    await gotoSettings(page, params);

    // 「追加」ボタン(triggerAdd → showEditView)でモーダルを開く。
    await page.locator("button.addButton").first().click();
    await expect(form(page)).toBeVisible();

    // 対象モジュール/言語は既定(全モジュール共通 / 全ての言語)のまま、
    // 変更前・変更後の文言を入力する。input[name] を厳密に分けて入れ、定着を確認する。
    await fillAndConfirm(page, "before_string", beforeAdd);
    await fillAndConfirm(page, "after_string", afterAdd);

    // 保存直前にも before/after が各入力に正しく入っていることを再確認する
    // (select2 初期化やモーダル再レンダリングで一方が消える事象への保険)。
    await expect(form(page).locator('input[name="before_string"]')).toHaveValue(beforeAdd);
    await expect(form(page).locator('input[name="after_string"]')).toHaveValue(afterAdd);

    // 保存(SaveAjax の POST を待ってから一覧差し替えの完了を待つ)。
    await saveAndSettle(page, form(page).locator('button[name="saveButton"]'));

    // リロードしても追加したルールが一覧に残っていること。
    // after_string で一意特定する(before は変換されて表示されないため使わない)。
    await gotoSettings(page, params);
    await expect(rowByAfter(page, afterAdd)).toHaveCount(1);
  });

  test("文言変更ルールの編集", async ({ page }) => {
    await gotoSettings(page, params);

    // 追加した行(after で特定)をクリックすると編集モーダルが開く(showEditView)。
    await rowByAfter(page, afterAdd).click();
    await expect(form(page)).toBeVisible();

    // 既存の before/after が読み込まれていること(編集対象が正しいことの確認)。
    // フォームの value は DB の生値なので before/after そのものが入っている。
    await expect(form(page).locator('input[name="before_string"]')).toHaveValue(beforeAdd);
    await expect(form(page).locator('input[name="after_string"]')).toHaveValue(afterAdd);

    // 変更後の文言を書き換えて保存する(before_string は不変)。
    await fillAndConfirm(page, "after_string", afterEdit);
    await expect(form(page).locator('input[name="before_string"]')).toHaveValue(beforeAdd);
    await saveAndSettle(page, form(page).locator('button[name="saveButton"]'));

    // リロードして編集内容が反映されていること。
    // before は不変だが、一覧では新しい after(afterEdit)へ変換表示されるため、
    // 行の特定・確認はいずれも afterEdit で行う。旧 after(afterAdd)は表示されない。
    // 注: afterAdd は afterEdit の部分文字列ではない(afterEdit は before/after に "edit" を挟む)
    //     ため、afterEdit で絞った行が afterAdd 由来と混同されることはない。
    await gotoSettings(page, params);
    await expect(rowByAfter(page, afterEdit)).toHaveCount(1);
    await expect(rowByAfter(page, afterAdd)).toHaveCount(0);
  });

  test("文言変更ルールの削除", async ({ page }) => {
    await gotoSettings(page, params);

    // 対象行(編集後の有効な after = afterEdit で特定)の削除アイコンを押す。
    // 削除リンクは <a title="削除"> 内に .fa-trash を持つ(ListViewRecordActions.tpl)。
    // triggerDelete は確認ダイアログ → AJAX 削除 → loadListViewContents で再描画する。
    const target = rowByAfter(page, afterEdit);
    await expect(target).toHaveCount(1);
    await target.locator('a[title="削除"]').click();
    await confirmYes(page);
    await page.waitForLoadState("networkidle").catch(() => {});

    // AJAX 削除は networkidle が早く解決して反映前に確認してしまうことがあるため、
    // 一覧を開き直して行が消えるまでリトライする(反映ラグに強い確認)。
    await expect(async () => {
      await gotoSettings(page, params);
      await expect(rowByAfter(page, afterEdit)).toHaveCount(0, { timeout: 3000 });
    }).toPass({ timeout: 30000 });
  });
});
