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
import { postComment } from "../../utils/comment";
import { duplicateViaDetail } from "../../utils/duplicate";
import {
  uploadDocumentToRecord,
  downloadDocumentFromRecord,
} from "../../utils/documentsFile";
import {
  createPersonalFilter,
  deletePersonalFilter,
  duplicatePersonalFilter,
  editPersonalFilter,
  expectFilterInSidebar,
} from "../../utils/customview";
import type { CaseId } from "./capabilities";

export class MatrixTest {
  private fr: FrTest;

  constructor(
    public moduleName: string,
    public app: string,
    sessionName: string
  ) {
    this.fr = new FrTest(moduleName, sessionName);
  }

  static async init(
    moduleName: string,
    app: string,
    sessionName: string
  ): Promise<MatrixTest> {
    const m = new MatrixTest(moduleName, app, sessionName);
    // FrTest は describe(API) を遅延取得するため、ここでは疎通のみ
    await m.fr.getDescribe();
    return m;
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
    await page.goto(this.fr.getCreateUrl());
    await page.waitForLoadState("domcontentloaded");
    const hash = generateRandomString(8);
    await this.fr.fillAllFieldsPublic(page, hash);

    const name = `E2Emx${generateRandomString(8)}`;
    const nameField = this.searchField();
    if (nameField) {
      await page.fill(`input[name="${nameField}"]`, name);
    }

    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
    const id = page.url().match(/record=(\d+)/)?.[1];
    if (!id) throw new Error(`${this.moduleName}: 使い捨てレコード作成に失敗`);
    return { id, name };
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
      default:
        throw new Error(`未実装ケース: ${caseId}`);
    }
  }

  /** モジュールの名前列(列検索対象)。既定は accountname 相当。 */
  private searchField(): string {
    const map: Record<string, string> = {
      Accounts: "accountname",
    };
    return map[this.moduleName] ?? "";
  }
}
