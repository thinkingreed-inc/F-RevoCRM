import { expect, Page, Browser, BrowserContext, Locator } from "@playwright/test";
import { url, BASE_URL } from "./util";

/**
 * 管理画面(Settings)系テストの共通ヘルパ。
 *
 * temp 由来のテストに散らばっていた定型(URL生成・遷移・保存成功の確認)を集約する。
 * 必要になった分だけ足す方針(YAGNI)。
 */

/**
 * Settings 画面の URL を生成する。
 *
 * temp では `block=`/`fieldid=` を URL にハードコードしていたが、これらは
 * 左メニューのハイライト用でビュー表示自体には不要なため既定では付けない
 * (環境ごとに変わる値のため脆さの原因になる)。必要な画面だけ extra で渡す。
 *
 * @param params module/view など index.php のクエリに載せるパラメータ
 */
export function settingsUrl(params: Record<string, string>): string {
  const query = new URLSearchParams({ parent: "Settings", ...params });
  return url(`index.php?${query.toString()}`);
}

/**
 * Settings 画面へ遷移する。temp の「メニューをクリックしてから同じ画面へ直 goto」
 * という無駄ナビは廃止し、直接遷移に一本化する。
 *
 * 保存直後などリダイレクト進行中に goto すると net::ERR_ABORTED で中断される
 * ことがあるため、進行中の遷移を落ち着かせてからリトライする
 * (FrTest.saveAndVerify と同じ考え方)。
 */
export async function gotoSettings(
  page: Page,
  params: Record<string, string>
): Promise<void> {
  const target = settingsUrl(params);
  for (let attempt = 0; attempt < 3; attempt++) {
    try {
      await page.goto(target);
      return;
    } catch (e) {
      if (attempt === 2) throw e;
      await page.waitForLoadState("domcontentloaded").catch(() => {});
      await page.waitForTimeout(500);
    }
  }
}

/**
 * 確認ダイアログ(app.helper.showConfirmationBox)の「はい」を押す。
 * ボタンは .confirm-box-ok(既定ラベル Yes)。
 */
export async function confirmYes(page: Page): Promise<void> {
  // ネイティブ dialog を使う画面では確認ボックスが出ないため、出ない場合は素通りする
  await page
    .locator(".confirm-box-ok")
    .first()
    .click({ timeout: 6000 })
    .catch(() => {});
}

/**
 * 別ユーザーのログインを、共有 storageState を汚さない独立 context で検証する。
 * 呼び出し側は finally で context.close() すること。
 */
export async function loginInIsolatedContext(
  browser: Browser,
  username: string,
  password: string
): Promise<{ context: BrowserContext; page: Page }> {
  // Cookie を明示的に空にして確実にログアウト状態の context を作る
  // (プロジェクト既定の storageState を引き継がないようにする)
  const context = await browser.newContext({
    storageState: { cookies: [], origins: [] },
  });
  const page = await context.newPage();
  // ログイン画面は外部 CDN 画像を参照するため 'load' 待ちだとオフライン環境で
  // タイムアウトする。DOM 構築完了で十分(フォームはサーバ描画済み)。
  await page.goto(BASE_URL, { waitUntil: "domcontentloaded" });
  await page.fill("id=username", username);
  await page.fill("id=password", password);
  await page.getByRole("button", { name: "ログイン" }).click();
  await page.waitForLoadState("domcontentloaded").catch(() => {});
  return { context, page };
}

/**
 * 保存ボタンを押し、AJAX 保存(画面遷移しないことがある)の完了を待つ。
 * 遷移せずに裏で保存する画面では、押した直後に別画面へ遷移すると保存 POST が
 * 中断されるため、POST 応答と networkidle を待ってから次に進む。
 */
export async function saveAndSettle(
  page: Page,
  button: Locator,
  opts: { force?: boolean } = {}
): Promise<void> {
  await Promise.all([
    page
      .waitForResponse((r) => r.request().method() === "POST", {
        timeout: 15000,
      })
      .catch(() => {}),
    button.click({ force: opts.force ?? false }),
  ]);
  await page.waitForLoadState("networkidle").catch(() => {});
}
