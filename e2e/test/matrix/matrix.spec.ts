import { test } from "../../fixtures/isolated";
import { readFileSync } from "fs";
import { sessionNameFile } from "../../utils/util";
import { MatrixTest } from "../../model/matrix/MatrixTest";
import {
  MATRIX,
  ALL_CASES,
  CASE_LABELS,
  capabilityOf,
  reason,
} from "../../model/matrix/capabilities";

for (const m of MATRIX) {
  // FrTest.testRecordDelete は「対象モジュールの最終更新レコード」を API で選び削除する
  // (getOneRecordFromModuleName: ORDER BY modifiedtime desc LIMIT 1)。並行実行すると、
  // 他ケース(list.search/detail.comment.post)が作った使い捨てレコードを先取りして
  // 削除してしまう(既存の fr.common.spec.ts と同じ理由で .serial が必須)。
  test.describe.serial(`マトリクス: ${m.module}`, () => {
    if (!m.enabled) {
      test.skip(true, "未有効化(展開ゲート)");
    }

    let driver: MatrixTest;

    test.beforeAll(async () => {
      const sessionName = readFileSync(sessionNameFile, "utf-8");
      driver = await MatrixTest.init(m.module, m.app ?? "MARKETING", sessionName);
    });

    for (const caseId of ALL_CASES) {
      test(CASE_LABELS[caseId], async ({ page, browser }) => {
        test.setTimeout(120000);
        const cap = capabilityOf(m, caseId);
        test.skip(cap !== "run", reason(cap));
        await driver.run(page, browser, caseId);
      });
    }
  });
}
