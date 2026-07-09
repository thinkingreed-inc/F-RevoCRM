import { expect, type Page, type Browser } from "@playwright/test";
import { FrTest } from "../FrTest";
import { generateRandomString } from "../../utils/util";
import {
  gotoList,
  gotoDetail,
  listSearch,
  listRows,
  deleteViaDetail,
} from "../../utils/listview";
import { postComment } from "../../utils/comment";
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
      case "list.edit":
        return this.fr.testRecordEdit(page);
      case "list.delete":
      case "detail.delete":
        return this.fr.testRecordDelete(page);
      case "list.search": {
        const { id, name } = await this.createDisposableNamed(page);
        await gotoList(page, this.moduleName, this.app);
        // 作成レコードの一意トークンで検索し、1 行に絞れることを確認する
        await listSearch(page, this.searchField(), name);
        await expect(listRows(page)).toHaveCount(1);
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
