import { test } from "../../fixtures/isolated";
import { readFileSync } from "fs";
import { sessionNameFile } from "../../utils/util";
import { MatrixTest, UnconfiguredCaseError } from "../../model/matrix/MatrixTest";
import type { CaseId } from "../../model/matrix/capabilities";
import {
  MATRIX,
  ALL_CASES,
  CASE_LABELS,
  capabilityOf,
  reason,
} from "../../model/matrix/capabilities";

/**
 * モジュール×機能の能力表マトリクス
 *
 * 全 29 モジュールに対し共通機能ケース(一覧 CRUD / 複製 / CustomView / 詳細編集 /
 * ファイル / コメント / 関連 / インポート)を `model/matrix/capabilities.ts` の
 * `ModuleMatrix` 宣言から生成して実行する。セルごとに run / skip / na を宣言し、
 * 汎用ドライバで扱えないモジュール(インベントリ・Calendar・テンプレート等)は
 * 理由付き skip に退避する(実機能は各専用 spec が担保)。
 *
 * モジュールごとに `describe.serial`。同一モジュール内のケースを並列にすると、
 * 削除系が「対象モジュールの最終更新レコード」を先取りして他ケースの使い捨て
 * レコードを消してしまうため。
 */

// CI(E2E_SCOPE=ci)ではフルの 29 モジュール実行は時間が掛かりすぎるため、
// 挙動の代表(標準/関連/CV・インベントリskip・特殊フォームskip・ファイル/na 混在)を
// カバーする少数モジュールに絞る。ローカル(E2E_SCOPE 未設定)は全モジュール実行。
const CI_SAMPLE_MODULES = [
  "Accounts", // 標準フル(作成/編集/複製/ファイル/コメント)。actions/Save は Vtiger 共通
  "Contacts", // 標準 + 複合名。actions/Save 個別実装あり
  "Leads", // actions/Save 個別実装あり(昇格前提の項目処理)
  "Potentials", // actions/Save 個別実装あり
  "Documents", // UIフォーム skip + ファイル系 na の代表。API 作成できるケースは run で残る
  "HelpDesk", // サポート系
  //
  // 【Invoice / Calendar を CI 代表から外している理由】(2026-08-26 実測)
  // capabilities.ts の applySkip(..., RECORD_DEPENDENT_CASES) により、
  // この 2 モジュールは「レコードを 1 件作る必要があるケース」がすべて skip される:
  //   - Invoice(インベントリ系): 明細(productid)が無いと API/UI どちらでも保存できない
  //   - Calendar: 日時ウィジェット等の特殊コントロールで UI/API とも作成できない
  // 残っていた run は CustomView 系だけで、それを CI から外した(CI_SKIP_CASES)ため
  // CI では 15 件 collect / 15 件すべて実行時 skip = 実行 0 件になっていた。
  // collect だけ払って何も検証せず、カタログ上の「PRで実行」も過大に見えるので代表から外す。
  //
  // 機能面の穴ではない: Invoice は tests/4_モジュール/4-9_在庫/(CRUD・明細・PDF・相互生成)、
  // Calendar は tests/4_モジュール/4-8_カレンダー/(基本・モーダル・繰り返し・招待・共有・
  // 活動ToDo・追加機能)が CI に入っており、専用ドライバで厚く担保している。
  // ローカルのフル実行(E2E_SCOPE 未設定)では全 29 モジュールが対象なので、
  // capability が run に変わった際の検知はそちらで行う。
];
const MATRIX_SCOPE =
  process.env.E2E_SCOPE === "ci"
    ? MATRIX.filter((m) => CI_SAMPLE_MODULES.includes(m.module))
    : MATRIX;

/**
 * CI では CustomView(リスト)系のケースをマトリクスから外す。
 *
 * 実測(2026-07-22 の CI run)で、この 8 ケース × 5 モジュール = 40 件が
 * マトリクスの CPU 時間の **68.6%**(1066 秒 / 1554 秒)を占めていた。
 * 「個人リストが複製できる」単体で 43 秒 × モジュール数、別ユーザー可視性の
 * 2 ケースは毎回別コンテキストでログインするため 26 秒級。
 *
 * CustomView はモジュール非依存の共通機能(`Vtiger_CustomView_Model` を
 * オーバーライドしているモジュールは無く、`views/ListView.php` のモジュール別実装も無い)
 * なので、Accounts 1 モジュールで代表検証すれば足りる。
 * `tests/3_共通機能/3-04_リスト.spec.ts` が この 8 ケース相当をすべてカバーする:
 *   作成/削除・切替(=personal.show)・複製・編集・共有リスト(自分/別ユーザー)・
 *   マイリスト(非公開)が別ユーザーに出ないこと。
 * モジュール横断で反復する価値より CI 時間の方が高くつくため、CI ではこの 8 ケースを
 * 生成しない(ローカルのフル実行 = E2E_SCOPE 未設定 では従来どおり全ケース実行する)。
 */
const CI_SKIP_CASES: CaseId[] = [
  "list.cv.personal.show",
  "list.cv.personal.delete",
  "list.cv.personal.dup",
  "list.cv.personal.edit",
  "list.cv.shared.self",
  "list.cv.shared.other",
  "list.cv.mine.self",
  "list.cv.mine.other",
];
/**
 * **現状のサーバ実装では成立しないため、CI/ローカルとも生成しないケース。**
 *
 * 関連(related.*)は `MatrixTest.resolveRelatedSpec` が describe の `relatedModules` から
 * 親→子の関連仕様を自動導出する前提だが、F-RevoCRM の Webservice describe は
 * そのキーを返していない(`include/Webservices/VtigerModuleOperation::describe()` の返却は
 * label / name / createable / updateable / deleteable / retrieveable / fields / idPrefix /
 * isEntity / allowDuplicates / labelFields のみ)。
 * そのため全モジュールで「relatedSpec を describe から導出できず」の理由付き skip になり、
 * collect のコストだけ払って一度も実行されていなかった(2026-08-25 の実測で判明)。
 *
 * 関連一覧の機能自体は `tests/3_共通機能/3-12_関連一覧.spec.ts`(表示) と
 * `3-13_関連追加.spec.ts`(関連からの追加) が担保する。
 * describe API が relatedModules を返すようになったら、この除外を外して自動導出を生かす。
 */
const UNSUPPORTED_CASES: CaseId[] = [
  "related.search",
  "related.searchReset",
  "related.navigate",
];

const SCOPED_CASES = ALL_CASES.filter(
  (c) =>
    !UNSUPPORTED_CASES.includes(c) &&
    !(process.env.E2E_SCOPE === "ci" && CI_SKIP_CASES.includes(c))
);

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

    for (const caseId of SCOPED_CASES) {
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
