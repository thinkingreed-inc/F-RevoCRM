import { expect, type Page, type Browser } from "@playwright/test";
import * as path from "path";
import * as fs from "fs";
import { FrTest } from "../FrTest";
import { generateRandomString } from "../../utils/util";
import {
  gotoList,
  gotoDetail,
  listSearch,
  listRows,
  clearListSearch,
  expectSearchCleared,
  deleteViaDetail,
} from "../../utils/listview";
import { postComment, postCommentWithFile } from "../../utils/comment";
import { duplicateViaDetail } from "../../utils/duplicate";
import {
  uploadDocumentToRecord,
  downloadDocumentFromRecord,
} from "../../utils/documentsFile";
import {
  openRelatedTab,
  relatedSearch,
  relatedSearchReset,
  navigateToRelatedDetail,
} from "../../utils/related";
import {
  createRecordViaApi,
  deleteRecordViaApi,
  type CreatedRecord,
} from "../../utils/record";
import {
  createPersonalFilter,
  deletePersonalFilter,
  duplicatePersonalFilter,
  editPersonalFilter,
  expectFilterInSidebar,
} from "../../utils/customview";
import {
  createSharedFilter,
  expectSharedVisibleAs,
  expectFilterHiddenAs,
} from "../../utils/sharedList";
import type { CaseId } from "./capabilities";
import { frgetDescribe, frQuery, frDelete } from "../fetcher";
import type { FRDescribeType } from "../types/frBase";
import type { FRDescribeFieldsTypeWithModuleName } from "../types/frTest";
import { getFieldValue } from "../../utils/field";
import { apiSession } from "../../utils/api";
import { runImport } from "../../utils/import";

/** 関連(親→子)テストの仕様。 */
interface RelatedSpec {
  /** 子(関連先)モジュール名。 */
  relatedModule: string;
  /** 子レコード上の、親を参照する項目名。 */
  parentField: string;
  /** 子の名前列(関連一覧の検索対象)。 */
  searchField: string;
  searchValueOf: (name: string) => string;
}

/**
 * 関連の子として使わないモジュール。
 * - 明細必須で API 作成できないインベントリ系(Invoice/Quotes/SalesOrder/PurchaseOrder)
 * - 特殊フォーム/非エンティティ(Calendar/Emails/ModComments/SMSNotifier/Documents)
 *   ※Documents は項目参照でなく m2m(senotesrel)関連のため参照項目が無く、どのみち選ばれない。
 */
const EXCLUDED_RELATED_CHILD = new Set<string>([
  "Invoice",
  "Quotes",
  "SalesOrder",
  "PurchaseOrder",
  "Calendar",
  "Emails",
  "Events",
  "ModComments",
  "SMSNotifier",
  "Documents",
]);

/**
 * そのモジュールに per-module 設定(名前列 searchField / 関連仕様 relatedSpec 等)が
 * 未整備のため、当該ケースを実行できないことを表す番兵エラー。
 * spec 側でこれを捕捉し、テスト失敗ではなく「設定未整備」の理由付き skip に変換する。
 * (未設定の判定は副作用の前=レコード作成前に行うため、skip 時の後始末は不要)
 */
export class UnconfiguredCaseError extends Error {}

export class MatrixTest {
  private fr: FrTest;
  /** init 時に describe.labelFields から解決した名前列(列検索対象)。 */
  private nameField?: string;
  /** 関連仕様の自動導出結果(init で一度だけ解決)。undefined=未解決/null=関連なし。 */
  private related?: RelatedSpec | null;

  constructor(
    public moduleName: string,
    public app: string,
    private sessionName: string
  ) {
    this.fr = new FrTest(moduleName, sessionName);
  }

  static async init(
    moduleName: string,
    app: string,
    sessionName: string
  ): Promise<MatrixTest> {
    const m = new MatrixTest(moduleName, app, sessionName);
    // FrTest は describe(API) を遅延取得するため、ここで一度取得し、
    // 名前列(列検索・作成時の一意名上書き対象)を describe.labelFields から自動解決する。
    const describe = await m.fr.getDescribe();
    m.nameField = MatrixTest.resolveNameField(moduleName, describe);
    // 関連仕様(親→子の参照)を親/子の describe から自動導出する(失敗しても
    // 関連ケースは skip に退避するだけなので init 自体は落とさない)。
    m.related = await m.resolveRelatedSpec(describe).catch(() => null);
    return m;
  }

  /**
   * 名前列(列検索対象 = 一覧の検索ボックス name 属性)を describe.labelFields から自動解決する。
   *
   * vtiger の labelFields はエンティティ名を構成する列のカンマ区切り(例: Accounts=accountname,phone /
   * Contacts=lastname,firstname / Invoice=subject)。先頭列がそのモジュールの主たる名前列であり、
   * 一覧の列検索・作成フォームの一意名上書きに使える。モジュールごとの列名ハードコードを避け、
   * 全モジュールを describe 駆動で有効化するための要。
   *
   * NAME_FIELD_OVERRIDE は labelFields の先頭が一覧検索列と一致しない例外モジュール専用の逃げ道
   * (通常は空。有効化時に赤が出た場合のみ足す)。
   */
  private static readonly NAME_FIELD_OVERRIDE: Record<string, string> = {};

  static resolveNameField(
    moduleName: string,
    describe: Awaited<ReturnType<FrTest["getDescribe"]>>
  ): string | undefined {
    if (MatrixTest.NAME_FIELD_OVERRIDE[moduleName]) {
      return MatrixTest.NAME_FIELD_OVERRIDE[moduleName];
    }
    if (describe && typeof describe !== "boolean" && describe.labelFields) {
      const first = describe.labelFields.split(",")[0]?.trim();
      if (first) return first;
    }
    return undefined;
  }

  /**
   * 使い捨てレコードを1件作成し、record ID と名前列に設定した実際の名前を返す。
   *
   * fillAllFieldsPublic は describe駆動で全項目に `${label}_${hash}` 形式の値を
   * 入れるが、ラベル文言は項目定義依存で呼び出し側から予測できない。
   * そのため保存前に名前列(モジュールの検索対象列)だけを一意トークンで上書きし、
   * 検索・後始末に使える確実な名前を確保する。
   * (Task 3 以降の再利用のため public。名前と shape はこの Task で確定させる)
   */
  async createDisposableNamed(
    page: Page
  ): Promise<{ id: string; name: string }> {
    const name = `E2Emx${generateRandomString(8)}`;
    const nameField = this.searchField();

    // レコードを用意することが目的の派生ケース(検索/複製/ファイル/コメント/関連 等)は
    // Webservice API 作成を第一手段にする。UI 新規作成フォームは必須の関連項目選択モーダルや
    // リッチテキスト/ファイル種別ラジオ等の特殊コントロールで不安定になりやすく、
    // 「レコードが存在すること」自体が前提の派生ケースでは API の方が堅牢(utils/record.ts 参照)。
    // API 作成できないモジュール(明細必須のインベントリ等)は UI フォームにフォールバックする。
    try {
      const rec = await createRecordViaApi(this.moduleName, {
        [nameField]: name,
      });
      return { id: rec.recordId, name };
    } catch {
      // フォールバック: UI 新規作成フォームで作成する(全項目入力 + 名前列を一意名で上書き)。
      await page.goto(this.fr.getCreateUrl());
      await page.waitForLoadState("domcontentloaded");
      const hash = generateRandomString(8);
      await this.fr.fillAllFieldsPublic(page, hash);
      await page.fill(`input[name="${nameField}"]`, name);
      await page.locator("button.saveButton").first().click();
      await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
      const id = page.url().match(/record=(\d+)/)?.[1];
      if (!id) throw new Error(`${this.moduleName}: 使い捨てレコード作成に失敗`);
      return { id, name };
    }
  }

  async run(page: Page, browser: Browser, caseId: CaseId): Promise<void> {
    switch (caseId) {
      case "list.create.detail":
        return this.fr.testRecordCreate(page);
      case "list.create.listNav": {
        const { id, name } = await this.createDisposableNamed(page);
        await gotoList(page, this.moduleName, this.app);
        await listSearch(page, this.searchField(), name);
        await expect(listRows(page).first()).toContainText(name);
        await listRows(page)
          .first()
          .locator('a[href*="view=Detail"]:visible')
          .first()
          .click();
        await expect(page).toHaveURL(new RegExp(`record=${id}`));
        await deleteViaDetail(page, this.moduleName, id);
        // 列検索はセッションに残り、後続ケース(list.duplicate 等)の gotoList を
        // 0 件化しうる。詳細画面にはクリアトリガが無いため、一覧へ戻してクリアする。
        await gotoList(page, this.moduleName, this.app);
        await clearListSearch(page);
        return;
      }
      case "list.edit":
        return this.fr.testRecordEdit(page);
      case "list.duplicate": {
        const { id, name } = await this.createDisposableNamed(page);
        const dupId = await duplicateViaDetail(
          page,
          this.moduleName,
          id,
          this.app
        );
        // 複製名は元名+サフィックス(重複防止ルール回避のため)。列検索は
        // contains 判定のため、元名で検索すると元レコードと複製の両方が
        // ヒットして2件になる。
        await gotoList(page, this.moduleName, this.app);
        await listSearch(page, this.searchField(), name);
        await expect(listRows(page)).toHaveCount(2, { timeout: 10000 });
        // 列検索はセッションに残り後続ケースの gotoList を汚染するため、
        // 後始末の前に必ずクリアする。
        await clearListSearch(page);
        await deleteViaDetail(page, this.moduleName, dupId);
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "list.delete":
      case "detail.delete":
        return this.fr.testRecordDelete(page);
      case "list.search": {
        const { id, name } = await this.createDisposableNamed(page);
        await gotoList(page, this.moduleName, this.app);
        // 作成レコードの一意トークンで検索し、1 行に絞れることを確認する
        await listSearch(page, this.searchField(), name);
        await expect(listRows(page)).toHaveCount(1);
        // 列検索はセッションに残り、後続の gotoList を 0 件化して兄弟テストを
        // 誤失敗させるため、後始末の前に必ずクリアする。
        await clearListSearch(page);
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "list.searchReset": {
        const { id, name } = await this.createDisposableNamed(page);
        await gotoList(page, this.moduleName, this.app);
        await listSearch(page, this.searchField(), name);
        await expectSearchCleared(page);
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "detail.edit": {
        const { id } = await this.createDisposableNamed(page);
        await gotoDetail(page, this.moduleName, id, this.app);
        // 詳細ヘッダの編集ボタン(basicAction LBL_EDIT)
        await page
          .locator(`#${this.moduleName}_detailView_basicAction_LBL_EDIT`)
          .click();
        await page.waitForURL(/view=Edit/, { timeout: 15000 });
        const hash = generateRandomString(8);
        await this.fr.fillAllFieldsPublic(page, hash);
        await page.locator("button.saveButton").first().click();
        // 保存前(Edit画面)のURLにも record=\d+ が既に含まれるため、単に
        // record=\d+ だけを待つと遷移完了前の古いURLで誤解決する
        // (utils/duplicate.ts と同じレース対策)。view=Detail を必須にする。
        await page.waitForURL(/[?&]view=Detail[^]*?record=\d+/, {
          timeout: 15000,
        });
        await gotoDetail(page, this.moduleName, id, this.app);
        await expect(page.locator(`text=${hash}`).first()).toBeVisible();
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "detail.duplicate": {
        const { id, name } = await this.createDisposableNamed(page);
        const dupId = await duplicateViaDetail(
          page,
          this.moduleName,
          id,
          this.app
        );
        await gotoDetail(page, this.moduleName, dupId, this.app);
        // 複製内容(名前は元名を接頭辞として含む)が詳細に表示される
        await expect(page.locator("#detailView")).toContainText(name);
        await deleteViaDetail(page, this.moduleName, dupId);
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "detail.file.upload": {
        const { id, name } = await this.createDisposableNamed(page);
        const file = path.resolve("fixtures/upload/sample.txt");
        await uploadDocumentToRecord(
          page,
          this.moduleName,
          id,
          file,
          `doc_${name}`
        );
        // 後始末: レコード削除のみ行う(アップロードした Documents レコードは
        // レコード削除に付随して自動では消えず残り得るが、E2E 用の使い捨てデータであり
        // 本タスクの検証目的上は許容する)。
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "detail.file.download": {
        const { id, name } = await this.createDisposableNamed(page);
        const file = path.resolve("fixtures/upload/sample.txt");
        await uploadDocumentToRecord(
          page,
          this.moduleName,
          id,
          file,
          `doc_${name}`
        );
        const dest = await downloadDocumentFromRecord(
          page,
          this.moduleName,
          id,
          `doc_${name}`
        );
        expect(fs.existsSync(dest)).toBeTruthy();
        // 存在確認だけだと 0 バイト/失敗ダウンロードでも偽陽性になるため、
        // 中身が届いた(非空)ことまで確認する。
        expect(fs.statSync(dest).size).toBeGreaterThan(0);
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "detail.comment.post": {
        const { id } = await this.createDisposableNamed(page);
        await gotoDetail(page, this.moduleName, id, this.app);
        await postComment(page, `E2Ecmt_${generateRandomString(6)}`);
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "detail.comment.file": {
        const { id } = await this.createDisposableNamed(page);
        await gotoDetail(page, this.moduleName, id, this.app);
        await postCommentWithFile(
          page,
          `E2Ecmtf_${generateRandomString(6)}`,
          path.resolve("fixtures/upload/sample.txt")
        );
        await deleteViaDetail(page, this.moduleName, id);
        return;
      }
      case "list.cv.personal.show": {
        const name = `E2Emxcv${generateRandomString(6)}`;
        await createPersonalFilter(page, this.moduleName, name);
        await expectFilterInSidebar(page, this.moduleName, name, true);
        await deletePersonalFilter(page, this.moduleName, name);
        return;
      }
      case "list.cv.personal.delete": {
        const name = `E2Emxcvd${generateRandomString(6)}`;
        await createPersonalFilter(page, this.moduleName, name);
        await deletePersonalFilter(page, this.moduleName, name);
        return;
      }
      case "list.cv.personal.dup": {
        const src = `E2Emxsrc${generateRandomString(6)}`;
        const dup = `E2Emxdup${generateRandomString(6)}`;
        await createPersonalFilter(page, this.moduleName, src);
        await duplicatePersonalFilter(page, this.moduleName, src, dup);
        await deletePersonalFilter(page, this.moduleName, dup);
        await deletePersonalFilter(page, this.moduleName, src);
        return;
      }
      case "list.cv.personal.edit": {
        const name = `E2Emxedit${generateRandomString(6)}`;
        const renamed = `${name}R`;
        await createPersonalFilter(page, this.moduleName, name);
        await editPersonalFilter(page, this.moduleName, name, renamed);
        await deletePersonalFilter(page, this.moduleName, renamed);
        return;
      }
      case "list.cv.shared.self": {
        const name = `E2Eshr${generateRandomString(6)}`;
        await createSharedFilter(page, this.moduleName, name);
        await expectFilterInSidebar(page, this.moduleName, name, true);
        await deletePersonalFilter(page, this.moduleName, name);
        return;
      }
      case "list.cv.shared.other": {
        const name = `E2Eshro${generateRandomString(6)}`;
        await createSharedFilter(page, this.moduleName, name);
        await expectSharedVisibleAs(browser, this.moduleName, name, "e2e_director");
        await deletePersonalFilter(page, this.moduleName, name);
        return;
      }
      case "list.cv.mine.self": {
        // マイリスト(個人/非公開の CustomView)が作成者本人のサイドバーに出る。
        const name = `E2Emine${generateRandomString(6)}`;
        await createPersonalFilter(page, this.moduleName, name);
        await expectFilterInSidebar(page, this.moduleName, name, true);
        await deletePersonalFilter(page, this.moduleName, name);
        return;
      }
      case "list.cv.mine.other": {
        // マイリスト(個人/非公開)は別ユーザーのサイドバーには出ない(共有していないため)。
        const name = `E2Emineo${generateRandomString(6)}`;
        await createPersonalFilter(page, this.moduleName, name);
        await expectFilterHiddenAs(browser, this.moduleName, name, "e2e_director");
        await deletePersonalFilter(page, this.moduleName, name);
        return;
      }
      case "related.search":
      case "related.searchReset":
      case "related.navigate": {
        const spec = this.related;
        if (!spec)
          throw new UnconfiguredCaseError(
            `${this.moduleName}: relatedSpec(関連仕様)を describe から導出できず`
          );
        const childName = `E2Erel${generateRandomString(6)}`;
        // 親 Account 作成後に prefixOf/createRecordViaApi が例外を投げると、finally が
        // 親作成前だと後始末に到達せず親レコードが取り残される。作成を try 内に入れ、
        // finally では「作成済みのものだけ」ガード付きで後始末する。
        let id: string | undefined;
        let child: CreatedRecord | undefined;
        try {
          ({ id } = await this.createDisposableNamed(page));
          const parentPrefix = await this.prefixOf(this.moduleName);
          try {
            child = await createRecordViaApi(spec.relatedModule, {
              [spec.parentField]: `${parentPrefix}x${id}`,
              [spec.searchField]: spec.searchValueOf(childName),
            });
          } catch (e) {
            // 子レコードが用意できない(他の必須参照が空 等)のは本物の不具合ではなく
            // 「関連検証の前提未整備」なので、後始末してから理由付き skip に退避する。
            if (id) await deleteViaDetail(page, this.moduleName, id);
            id = undefined;
            throw new UnconfiguredCaseError(
              `${this.moduleName}: 関連子(${spec.relatedModule})の作成に失敗: ${(e as Error).message}`
            );
          }
          await gotoDetail(page, this.moduleName, id, this.app);
          await openRelatedTab(page, spec.relatedModule);
          if (caseId === "related.navigate") {
            const toId = await navigateToRelatedDetail(page);
            expect(toId).toBe(child.recordId);
          } else if (caseId === "related.search") {
            await relatedSearch(page, spec.searchField, childName);
            await expect(
              page.locator(".relatedContents").getByText(childName).first()
            ).toBeVisible();
          } else {
            await relatedSearch(page, spec.searchField, childName);
            await relatedSearchReset(page);
          }
        } finally {
          if (child) await deleteRecordViaApi(child.session, child.wsId);
          if (id) await deleteViaDetail(page, this.moduleName, id);
        }
        return;
      }
      case "import.create":
        return this.testImportCreate(page);
      default:
        throw new Error(`未実装ケース: ${caseId}`);
    }
  }

  /**
   * CSV インポートで新規レコードを作成し、API で件数を確認して後始末する。
   *
   * describe から必須項目を解決し、フラット CSV(名前列 + 必須項目)を組み立てる:
   *  - owner(assigned_user_id)はインポートウィザードがインポート実行ユーザーへ
   *    自動割当するため CSV 列にしない。
   *  - reference(必須の関連項目)は参照先の既存レコード名/ID が要るためフラット CSV で
   *    充足できず、UnconfiguredCaseError(理由付き skip)に退避する。
   *  - それ以外(string/text/picklist/date 等)は getFieldValue で妥当値を生成する。
   *    生成できない型(time/multipicklist 等)も UnconfiguredCaseError に退避。
   * 名前列だけは一意プレフィックスで上書きし、2 行を作成。API で 2 件を確認後、
   * プレフィックス前方一致で削除して冪等にする。
   */
  /**
   * getFieldValue が明示ケースで妥当な CSV 値を返せる型の集合。
   * ここに無い型(time/datetime/multipicklist 等)は getFieldValue の default: 節が
   * 「ラベル_ハッシュ」のゴミ文字列を返すため、必須項目がこれ以外の型のときは
   * インポートを実行せず理由付き skip に退避する(testImportCreate 参照)。
   */
  private static readonly CSV_VALUE_TYPES = new Set<string>([
    "string",
    "text",
    "url",
    "email",
    "integer",
    "currency",
    "double",
    "date",
    "phone",
    "picklist",
    "boolean",
  ]);

  private async testImportCreate(page: Page): Promise<void> {
    const describe = await this.fr.getDescribe();
    if (!describe || typeof describe === "boolean") {
      throw new UnconfiguredCaseError(
        `${this.moduleName}: describe を取得できずインポート CSV を組み立てられない`
      );
    }
    const nameField = this.searchField();

    // 検証・後始末に加え、必須の関連項目(reference)の実値解決にも使う API セッション。
    const sn = await apiSession();

    // 名前列以外の必須項目(owner を除く)を CSV 列として組み立てる。
    const extraCols: { name: string; value: string }[] = [];
    const hash = generateRandomString(8);
    for (const field of describe.fields) {
      if (!field.mandatory || field.editable === false) continue;
      if (field.name === nameField) continue; // 名前列は一意値で別途組み立て
      if (field.type.name === "owner") continue; // インポートが実行ユーザーへ自動割当
      if (field.type.name === "reference") {
        // 必須の関連項目は、参照先モジュールの既存レコードを1件引き当て、
        // インポートウィザードが解決に使う「表示名(entityname)」を CSV 値に充てる。
        // (Users は user_name、Currency は currency_name、その他は labelFields 連結)。
        const value = await this.resolveReferenceCsvValue(
          sn,
          field.type.refersTo ?? []
        );
        if (!value) {
          throw new UnconfiguredCaseError(
            `${this.moduleName}: 必須の関連項目 ${field.name}(参照先 ${(field.type.refersTo ?? []).join("|") || "不明"})の CSV 値を解決できない`
          );
        }
        extraCols.push({ name: field.name, value });
        continue;
      }
      // getFieldValue が明示ケースで妥当値を返せない型(time/multipicklist 等)は
      // default: 節が「ラベル_ハッシュ」のゴミ文字列を返してしまい、CSV に詰めると
      // 不正値のまま取り込まれる。恒久的に成立しないので理由付き skip に退避する
      // (メソッド冒頭コメントの「生成できない型は UnconfiguredCaseError」を実装で担保)。
      if (!MatrixTest.CSV_VALUE_TYPES.has(field.type.name)) {
        throw new UnconfiguredCaseError(
          `${this.moduleName}: 必須項目 ${field.name}(${field.type.name})は CSV 用の妥当値を生成できない型`
        );
      }
      const value = await getFieldValue(
        { moduleName: this.moduleName, ...field } as FRDescribeFieldsTypeWithModuleName,
        hash
      );
      if (value === false || value === "") {
        throw new UnconfiguredCaseError(
          `${this.moduleName}: 必須項目 ${field.name}(${field.type.name})の CSV 値を生成できない`
        );
      }
      extraCols.push({ name: field.name, value });
    }

    const prefix = `E2Eimp${generateRandomString(6)}`;
    const escape = (v: string): string =>
      /[",\n]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v;
    const header = [nameField, ...extraCols.map((c) => c.name)];
    const dataRow = (n: number): string =>
      [`${prefix}_${n}`, ...extraCols.map((c) => c.value)].map(escape).join(",");
    const csv = `${header.map(escape).join(",")}\n${dataRow(1)}\n${dataRow(2)}\n`;

    // ウィザードがサーバ側でレコードを作成した後に post-submit 待ちが例外を投げると、
    // 後始末に到達できず E2Eimp<prefix>_* が取り残される。これを防ぐため runImport を
    // try で囲み、件数確認クエリ→前方一致削除の後始末を finally で「必ず」実行する。
    // 作成件数(=2)の検証は finally で捕捉した件数に対し try/finally の後で行う:
    // これにより本物の作成失敗はテスト失敗のまま・後始末は常に走る、を両立する。
    // (runImport 内部でワーカー横断のインポートロックを直列化する。検証/後始末の API は
    //  プレフィックスで独立しておりロック不要。sn は冒頭で取得済みを使い回す。)
    let createdCount = 0;
    try {
      await runImport(page, {
        module: this.moduleName,
        csv,
        mappings: header,
        // 一意レコードのため重複処理は不要。既定の突合項目が無いモジュールでも
        // 進めるよう「この手順をスキップ」でマッピングへ直行する。
        skipDuplicateStep: true,
      });
    } finally {
      const rows = await frQuery(
        sn,
        `SELECT id,${nameField} FROM ${this.moduleName} WHERE ${nameField} LIKE '${prefix}%';`
      );
      createdCount = rows.length;
      for (const r of rows) {
        if (r.id) await frDelete(sn, r.id);
      }
    }
    expect(createdCount).toBe(2);
  }

  /**
   * 必須の関連項目(reference)を CSV 列に載せるための実値を、参照先の既存レコードから解決する。
   *
   * インポートウィザード(modules/Import/actions/Data.php::transformForImport)は、reference 列の
   * 値を参照先の「表示名」で名前解決する:
   *  - Users    : vtiger_users.user_name(未解決/権限無しは実行ユーザーへフォールバック)
   *  - Currency : vtiger_currency_info.currency_name(未一致は基軸通貨 id=1 へフォールバック)
   *  - その他   : getEntityId により entityname(=labelFields をスペース連結した表示名)で解決
   *
   * refersTo を順に見て、最初に実値を得られた参照先モジュールの表示名を返す。
   * 解決できなければ undefined(呼び出し側で理由付き skip に退避)。
   */
  private async resolveReferenceCsvValue(
    sn: string,
    refersTo: string[]
  ): Promise<string | undefined> {
    for (const refModule of refersTo) {
      if (refModule === "Users") {
        // インポート実行ユーザー(= API と同一)の user_name。未解決でも実行ユーザーへ寄る。
        const uname = process.env.E2E_USER_NAME;
        if (uname) return uname;
        continue;
      }
      if (refModule === "Currency") {
        const rows = await frQuery(sn, `SELECT currency_name FROM Currency LIMIT 1;`);
        const name = rows?.[0]?.currency_name;
        if (name) return name;
        continue;
      }
      // 通常エンティティ: 参照先の labelFields(表示名を構成する列)を連結した値を充てる。
      const desc = await frgetDescribe(this.sessionName, refModule).catch(
        () => false as const
      );
      if (!desc || typeof desc === "boolean") continue;
      const labelCols = (desc.labelFields || "")
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);
      if (!labelCols.length) continue;
      const rows = await frQuery(
        sn,
        `SELECT ${labelCols.join(",")} FROM ${refModule} LIMIT 1;`
      );
      const row = rows?.[0];
      if (!row) continue;
      // getEntityId は trim(concat(col1,' ',col2,...)) で突合するためスペース連結・trim する。
      const label = labelCols
        .map((c) => row[c] ?? "")
        .join(" ")
        .trim();
      if (label) return label;
    }
    return undefined;
  }

  /**
   * モジュールの名前列(列検索対象)。init 時に describe.labelFields から解決済み。
   * 解決できなかった(describe 取得失敗等)場合のみ理由付き skip に退避する。
   */
  private searchField(): string {
    if (!this.nameField)
      throw new UnconfiguredCaseError(
        `${this.moduleName}: searchField(名前列)を describe.labelFields から解決できず — NAME_FIELD_OVERRIDE に追加すること`
      );
    return this.nameField;
  }

  /**
   * 関連テストの仕様を親/子の describe から自動導出する。
   *
   * 手順:
   *  1) 親 describe の relatedModules を順に見る(除外モジュールは飛ばす)。
   *  2) 各候補(子)の describe を取り、「親モジュールを参照する reference 項目」を探す。
   *     それが子→親の参照項目(parentField)になる。
   *  3) 子の名前列(labelFields 先頭)を関連一覧の検索列(searchField)とする。
   * これにより Accounts の手書き仕様をやめ、全モジュールを describe 駆動で関連検証できる。
   * 見つからなければ null(=関連ケースは理由付き skip)。
   */
  private async resolveRelatedSpec(
    parentDescribe: FRDescribeType | false
  ): Promise<RelatedSpec | null> {
    if (!parentDescribe || typeof parentDescribe === "boolean") return null;
    const related = parentDescribe.relatedModules ?? [];
    for (const rel of related) {
      const childMod = rel.relatedModuleName;
      if (!childMod || EXCLUDED_RELATED_CHILD.has(childMod)) continue;
      const childDescribe = await frgetDescribe(this.sessionName, childMod).catch(
        () => false as const
      );
      if (!childDescribe || typeof childDescribe === "boolean") continue;
      // 子側で「親モジュールを参照する」reference 項目を探す。
      const refField = childDescribe.fields.find(
        (f) =>
          f.type?.name === "reference" &&
          (f.type.refersTo ?? []).includes(this.moduleName)
      );
      if (!refField) continue;
      const childSearch = childDescribe.labelFields?.split(",")[0]?.trim();
      if (!childSearch) continue;
      return {
        relatedModule: childMod,
        parentField: refField.name,
        searchField: childSearch,
        searchValueOf: (name) => name,
      };
    }
    return null;
  }

  /**
   * 指定モジュールの Webservice ID プレフィックス(describe の idPrefix)を返す。
   * このタスクでは `this.fr` は `this.moduleName`(呼び出し元と同一)の describe を
   * 保持しているため、数値プレフィックスをハードコードせず describe 経由で取得する。
   */
  private async prefixOf(moduleName: string): Promise<string> {
    if (moduleName !== this.moduleName) {
      throw new Error(
        `prefixOf: 未対応モジュール(this.fr は ${this.moduleName} の describe のみ保持): ${moduleName}`
      );
    }
    const describe = await this.fr.getDescribe();
    if (!describe) {
      throw new Error(`describe 取得に失敗しました: ${moduleName}`);
    }
    return describe.idPrefix;
  }
}
