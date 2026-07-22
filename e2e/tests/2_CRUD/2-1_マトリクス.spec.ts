import { test } from "../../fixtures/isolated";
import { readFileSync } from "fs";
import { sessionNameFile } from "../../utils/util";
import { MatrixTest, UnconfiguredCaseError } from "../../model/matrix/MatrixTest";
import {
  MATRIX,
  ALL_CASES,
  CASE_LABELS,
  capabilityOf,
  reason,
} from "../../model/matrix/capabilities";

// CI(E2E_SCOPE=ci)ではフルの 29 モジュール実行は時間が掛かりすぎるため、
// 挙動の代表(標準/関連/CV・インベントリskip・特殊フォームskip・ファイル/na 混在)を
// カバーする少数モジュールに絞る。ローカル(E2E_SCOPE 未設定)は全モジュール実行。
const CI_SAMPLE_MODULES = [
  "Accounts", // 標準フル(作成/編集/複製/CV/共有/マイ/関連/ファイル/コメント)
  "Contacts", // 標準 + 複合名 + 関連
  "Invoice", // インベントリ(明細必須 → 作成系 skip の代表)
  "Calendar", // 特殊フォーム(CV/作成系 skip の代表)
  "Documents", // ファイル系 na + UIフォーム skip の代表
  "HelpDesk", // サポート系・関連あり
];
const MATRIX_SCOPE =
  process.env.E2E_SCOPE === "ci"
    ? MATRIX.filter((m) => CI_SAMPLE_MODULES.includes(m.module))
    : MATRIX;

for (const m of MATRIX_SCOPE) {
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
        // インポートはワーカー横断のロックで直列化するため、順番待ちを見込んで
        // 他ケース(120s)より長い上限を与える。
        test.setTimeout(caseId === "import.create" ? 300000 : 120000);
        const cap = capabilityOf(m, caseId);
        test.skip(cap !== "run", reason(cap));
        try {
          await driver.run(page, browser, caseId);
        } catch (e) {
          // per-module 設定未整備のケースは失敗ではなく理由付き skip に退避する
          // (全モジュール一括有効化時、名前列/関連仕様が未設定の非Accountsを、
          //  本物の不具合ではなく「未整備」として区別するため)
          if (e instanceof UnconfiguredCaseError) {
            test.skip(true, e.message);
          }
          throw e;
        }
      });
    }
  });
}
