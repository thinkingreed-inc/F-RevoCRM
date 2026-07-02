import { expect, Page } from "@playwright/test";
import { url } from "./util";

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
