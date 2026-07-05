import { test, expect } from "@playwright/test";
import { loginInIsolatedContext } from "../../utils/settings";
import {
  gotoList,
  listRows,
  listSearch,
  clearListSearch,
} from "../../utils/listview";
import { seedSpec, passwordFor } from "../../fixtures/seedSpec";

/**
 * 共通機能: カスタム共有ルール（datashare ROLE→ROLE） — 共有ルールによる可視範囲
 *
 * 拡充ベースラインは、何も所有しない観測者ロール `e2e_observer` に対し、
 * Leads の「1課長(MGRA)ロール所有レコード」を **read-only 共有** するカスタム共有ルール
 * （vtiger_datashare_role2role, `E2E営業1課長 → E2E共有_観測者`）を持つ。
 *
 * 観測者は階層上は Leads を 1 件も見られない（何も所有せず部下も無い）が、共有ルールにより
 * 「共有元ロール(MGRA)が所有するレコードだけ」見えるようになることを検証する:
 *   - `[E2E-PERM] MGRA` … 共有されるので 4 件見える
 *   - `[E2E-PERM] MGRB` … 別ロール所有なので 0 件（ルールは共有元ロール限定）
 *   - `[E2E-PERM] REPA` … MGRA の配下ロールだが、ROLE→ROLE ルールは配下を含まない → 0 件
 *
 * これにより「共有ルールが共有元ロールの所有レコードだけを対象にする」ことを示す。
 * 既存の階層テスト（common.permission）の各件数は本ルールでは変わらない（共有方向は MGRA→観測者）。
 * READ 専用・プレフィックス絞り込みで並列安全。
 */

const sr = seedSpec.sharingRule;

test.describe("共通: カスタム共有ルール (datashare ROLE→ROLE)", () => {
  test(`${sr.observerUserName}: 共有元ロール(${sr.sharedOwnerCode})所有のレコードだけ見える`, async ({
    browser,
  }) => {
    test.setTimeout(60000);
    const { context, page } = await loginInIsolatedContext(
      browser,
      sr.observerUserName,
      passwordFor(sr.observerUserName)
    );
    try {
      // 共有される MGRA 所有 → 見える
      await gotoList(page, sr.module);
      await listSearch(page, "company", `${sr.leadPrefix} ${sr.sharedOwnerCode}`);
      await expect(listRows(page)).toHaveCount(sr.expectedSharedCount);
      await clearListSearch(page);

      // 共有されない他ロール/配下ロール所有 → 見えない
      for (const code of sr.notSharedOwnerCodes) {
        await gotoList(page, sr.module);
        await listSearch(page, "company", `${sr.leadPrefix} ${code}`);
        await expect(listRows(page)).toHaveCount(sr.expectedNotSharedCount);
        await clearListSearch(page);
      }
    } finally {
      await context.close();
    }
  });
});
