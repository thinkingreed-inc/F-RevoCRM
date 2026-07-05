import { test, expect } from "@playwright/test";
import { loginInIsolatedContext } from "../../utils/settings";
import { gotoList, expectSearchCount, clearListSearch } from "../../utils/listview";
import { seedSpec, passwordFor } from "../../fixtures/seedSpec";

/**
 * 共通機能: 権限 / 可視範囲(誰のデータが見えるか) — TEST_COVERAGE 最重要ギャップ
 *
 * 拡充ベースライン(seed-spec.json)が用意する
 *   - ロール階層(H2 管理者 ▸ E2E営業部長 ▸ 1課長/2課長 ▸ 1課員/2課員)
 *   - Leads を org 共有 Private(3) 化
 *   - 各ロールユーザー所有の [E2E-PERM] Leads、グループ所有の [E2E-GRP] Leads
 * を利用し、ユーザーごとに Leads 一覧で見える件数が期待どおりであることを検証する。
 *
 * 期待可視数は seed-spec.json の expectedVisible が唯一の出所。アプリの共有エンジン
 * (QueryGenerator)で dump ビルド時に一致確認済みの値と同じ。
 *
 * 並列安全: 各ユーザーで独立 context にログインし、READ 専用でプレフィックス絞り込み
 * するだけ(データを変更しない)。他テストが Leads を増減させても [E2E-PERM]/[E2E-GRP]
 * の部分集合は不変なので件数がぶれない。期待値は最大 20(=既定ページサイズ)で 1 ページに収まる。
 */
test.describe("共通: 権限/可視範囲 (Private + ロール階層/グループ共有)", () => {
  const perm = seedSpec.leadPerm;
  const grp = seedSpec.leadGroup;
  // expectedVisible のキー(admin, e2e_director, ...)を対象ユーザーとする
  const users = Object.keys(perm.expectedVisible);

  for (const userName of users) {
    test(`${userName}: Leads の可視件数が期待どおり`, async ({ browser }) => {
      test.setTimeout(60000);
      const { context, page } = await loginInIsolatedContext(
        browser,
        userName,
        passwordFor(userName)
      );
      try {
        // [E2E-PERM]: ロール階層による可視範囲
        await gotoList(page, "Leads");
        await expectSearchCount(
          page,
          "company",
          perm.prefix,
          perm.expectedVisible[userName]
        );
        await clearListSearch(page);

        // [E2E-GRP]: グループ所有レコードの可視範囲(メンバーのみ)
        await gotoList(page, "Leads");
        await expectSearchCount(
          page,
          "company",
          grp.prefix,
          grp.expectedVisible[userName]
        );
      } finally {
        await context.close();
      }
    });
  }
});
