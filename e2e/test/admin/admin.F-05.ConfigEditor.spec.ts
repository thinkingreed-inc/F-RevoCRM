import { test, expect, type Page, type Locator } from "@playwright/test";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";

/**
 * F-05 構成エディタ (System > 構成エディタ / ConfigEditor)
 *
 * 設定編集型。構成エディタはアプリ全体に効くグローバル設定を編集するため、
 * ログイン・権限・セッションに影響しない低リスクな項目のみを対象にする。
 *
 * 対象項目: `list_max_entries_per_page`(一覧の 1 ページあたり表示件数)。
 * 表示件数は各モジュールの一覧表示に効くだけで、後続テストの動作や認証には
 * 影響しないため安全。値域は 1〜100(テンプレートの data-rule-range 参照)。
 *
 * 手順: 元の値を退避 → 安全な値へ変更 → 編集フォームを開き直して反映を検証 →
 * 元の値へ厳密に復帰する。
 *
 * === CI(遅い docker 環境)で落ちていた原因と対策 ===
 * 編集フォームは pjax(loadContents)の POST 完了後に JS 側で
 * registerEditViewEvents() が走り、はじめて vtValidate / submitHandler が
 * バインドされる。ここが走る前に fill+click してしまうと、
 *  - submitHandler(=AJAX 保存)が未バインドのままネイティブ submit になり、
 *    保存 POST(ConfigEditorSaveAjax)が飛ばない/中断される、
 *  - jQuery Validation の内部状態に値が反映されず保存されない、
 * といった形で「保存されず旧値(20)のまま」になる。ローカルは速いので JS
 * バインドが間に合い成功するが、CI では間に合わず失敗していた。
 *
 * 対策:
 *  1) fill 後に input/change/keyup を明示 dispatch し、値と検証状態を確実に確定。
 *  2) 保存ボタンが enabled になっていることを確認してからクリック。
 *  3) 保存は ConfigEditorSaveAjax の POST 完了を待つ(pjax の POST と取り違えない)。
 *  4) 反映確認は #ConfigEditorDetails 全体の toContainText(数値が他項目と衝突し
 *     得るため racy)ではなく、編集フォームを開き直して当該 input の toHaveValue
 *     を長めのタイムアウトでポーリングする。
 */
test.describe("管理: 構成エディタ (ConfigEditor)", () => {
  const detailParams = { module: "Vtiger", view: "ConfigEditorDetail" };
  const FIELD = "list_max_entries_per_page";
  const FORM = "#ConfigEditorForm";
  const inputSel = `${FORM} input[name="${FIELD}"]`;
  const saveSel = `${FORM} button.saveButton`;

  /**
   * 詳細画面で編集ボタンを押し、インラインの編集フォームを開く。
   * pjax + JS のイベント登録(vtValidate/submitHandler)が終わって操作可能に
   * なるまで待つ。保存ボタンが表示・有効になっていることをもって「準備完了」とする。
   */
  async function openEditForm(page: Page): Promise<void> {
    await gotoSettings(page, detailParams);
    await page.locator("#ConfigEditorDetails .editButton").click();
    // 編集フォームは pjax で #ConfigEditorDetails 内に差し替わる
    await expect(page.locator(inputSel)).toBeVisible({ timeout: 15000 });
    await expect(page.locator(inputSel)).toBeEditable({ timeout: 15000 });
    // 保存ボタンが出て有効になる = JS のイベント登録まで到達している目安
    await expect(page.locator(saveSel)).toBeVisible({ timeout: 15000 });
    await expect(page.locator(saveSel)).toBeEnabled({ timeout: 15000 });
  }

  /**
   * 入力欄へ値を確実にセットする。
   * fill だけでは JS(jQuery Validation / change 依存の処理)が反応しない
   * ことがあるため、input/change/keyup を明示的に bubbling で dispatch する。
   */
  async function setFieldValue(input: Locator, value: string): Promise<void> {
    await input.fill(value);
    // JS が待ち受けるイベントを明示発火(遅い CI での取りこぼし対策)
    await input.dispatchEvent("input");
    await input.dispatchEvent("keyup");
    await input.dispatchEvent("change");
    await input.blur().catch(() => {});
    await expect(input).toHaveValue(value);
  }

  /**
   * 開いている編集フォームに value をセットして保存し、詳細へ戻ったことを確認する。
   * 保存は ConfigEditorSaveAjax の POST 完了を待つ(saveAndSettle は
   * 「最初の POST」を待つが、pjax の POST と取り違えないよう明示的に待機する)。
   */
  async function editAndSave(page: Page, value: string): Promise<void> {
    const input = page.locator(inputSel);
    const saveButton = page.locator(saveSel);

    await setFieldValue(input, value);

    // クリック直前に保存ボタンが有効であることを確認(no-op クリック防止)
    await expect(saveButton).toBeEnabled({ timeout: 15000 });

    // ConfigEditorSaveAjax の保存 POST 完了を待ちつつクリックする
    const savePost = page
      .waitForResponse(
        (r) =>
          r.request().method() === "POST" &&
          /action=ConfigEditorSaveAjax/i.test(r.url() + r.request().postData()),
        { timeout: 15000 }
      )
      .catch(() => {});
    await Promise.all([savePost, saveAndSettle(page, saveButton)]);
  }

  /**
   * 編集フォームを開き直し、当該 input が期待値になるまで長めにポーリングする。
   * 数値が他項目(history_max_viewed など)と衝突し得るため、詳細ビュー全体の
   * テキスト一致ではなく当該 input の値で厳密に検証する。
   */
  async function expectPersistedValue(page: Page, value: string): Promise<void> {
    await openEditForm(page);
    await expect(page.locator(inputSel)).toHaveValue(value, { timeout: 15000 });
  }

  test("一覧の表示件数を編集して反映され、元に戻せる", async ({ page }) => {
    // 元の値を退避する
    await openEditForm(page);
    const originalValue = (await page.locator(inputSel).inputValue()).trim();

    // 元の値と衝突しない安全な値(値域 1〜100 の範囲内)を作る。
    // generateRandomString の英数字から数値を導き、20〜79 に収める。
    // (先頭ゼロ除去や 1〜100 範囲チェックに掛からない安全域)
    const seed = generateRandomString(8)
      .split("")
      .reduce((acc, c) => acc + c.charCodeAt(0), 0);
    let candidate = 20 + (seed % 60); // 20〜79
    if (String(candidate) === originalValue) {
      candidate = candidate === 79 ? 20 : candidate + 1;
    }
    const testValue = String(candidate);

    try {
      // テスト値へ変更して AJAX 保存する(保存後は詳細へ pjax で戻る)
      await editAndSave(page, testValue);

      // 編集フォームを開き直して永続化されていることを厳密に検証する
      await expectPersistedValue(page, testValue);
    } finally {
      // 原状復帰: テスト値で開いている編集フォームから元の値へ厳密に戻す。
      // 途中で失敗しても必ず復帰させるため finally 内で実行する。
      // フォームが開いていない可能性に備えて開き直してから戻す。
      await openEditForm(page);
      await editAndSave(page, originalValue);
    }

    // 元の値へ確実に戻っていること
    await expectPersistedValue(page, originalValue);
  });
});
