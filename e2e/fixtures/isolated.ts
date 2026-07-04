import { test as base, expect } from "@playwright/test";
import { BASE_URL } from "../utils/util";
import * as path from "path";

/**
 * ワーカー単位で独立したログインセッションを与えるフィクスチャ。
 *
 * 既定の共有 storageState(.auth/user.json)を全ワーカーで使い回すと、サーバ側の
 * PHP セッションが 1 つになり、一覧の検索条件など「セッションに保存される状態」が
 * 並行テスト間で衝突する(あるテストの絞り込みが別テストの一覧を 0 件にする等)。
 *
 * そこでワーカーごとに admin で個別ログインし、専用の storageState を持たせて
 * セッションを分離する。これにより workers=1 に頼らず並行実行できる。
 * (vtiger は同一ユーザーの複数セッションを許容する)
 *
 * 共通機能テスト(状態を伴う横断操作)はこの test を import して使う。
 */
export const test = base.extend<{}, { workerStorageState: string }>({
  storageState: ({ workerStorageState }, use) => use(workerStorageState),

  workerStorageState: [
    async ({ browser }, use, workerInfo) => {
      // ワーカーごとに毎回ログインし直す。
      // 保存ファイルを再利用すると、実行をまたいでサーバ側セッションが失効した
      // 際に古い Cookie でログイン画面へ飛ばされるため、常に取得し直す。
      const fileName = path.resolve(
        `.auth/worker-${workerInfo.workerIndex}.json`
      );

      const page = await browser.newPage({ storageState: undefined });
      // ログイン画面は外部 CDN 画像を参照するため 'load' 待ちだとオフライン環境で
      // タイムアウトする。DOM 構築完了で十分(フォームはサーバ描画済み)。
      await page.goto(BASE_URL, { waitUntil: "domcontentloaded" });
      await page.fill("id=username", process.env.E2E_USER_NAME || "admin");
      await page.fill(
        "id=password",
        process.env.E2E_USER_PASSWORD || "Admin1234/"
      );
      await page.getByRole("button", { name: "ログイン" }).click();
      await page.waitForURL(`${BASE_URL}index.php**`);
      await page.context().storageState({ path: fileName });
      await page.close();

      await use(fileName);
    },
    { scope: "worker" },
  ],
});

export { expect };
