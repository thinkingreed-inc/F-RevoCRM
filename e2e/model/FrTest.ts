import { expect, type Page } from "@playwright/test";
import { FrBaseModule } from "./frBaseModule";
import { generateRandomString } from "../utils/util";
import { dontTestFieldsName, fillField, getFieldValue } from "../utils/field";
import type { FRDescribeFieldsTypeWithModuleName } from "./types/frTest";

export class FrTest extends FrBaseModule {
  constructor(public moduleName: string, sessionName: string) {
    super(moduleName, sessionName);
  }

  /**
   * 編集画面の全項目に対し、項目型に応じた値を入力する。
   * 戻り値は実際に入力した項目の定義一覧(後で詳細画面の検証に使う)。
   */
  private async fillAllFields(
    page: Page,
    hash: string
  ): Promise<FRDescribeFieldsTypeWithModuleName[]> {
    const filled: FRDescribeFieldsTypeWithModuleName[] = [];
    const moduleInfo = await this.getDescribe();
    if (!moduleInfo) {
      return filled;
    }

    const fields: FRDescribeFieldsTypeWithModuleName[] = moduleInfo.fields.map(
      (info) => ({ moduleName: this.moduleName, ...info })
    );

    for (const fieldObj of fields) {
      if (dontTestFieldsName(fieldObj)) {
        continue;
      }
      if (await this.isNonEditableControl(page, fieldObj)) {
        continue;
      }
      const normalValue = (await getFieldValue(fieldObj, hash)) || "";
      await fillField(page, fieldObj, normalValue);
      filled.push(fieldObj);
    }

    return filled;
  }

  /**
   * 編集できる入力UIを持たない項目かどうか。
   * describe上はeditableでも、フォームでは type="hidden" や readonly のinputしか
   * 持たない計算/換算用の項目(conversion_rate, balance, 各種合計等)が存在するため、
   * それらをスキップする。
   * (関連項目は hidden + 専用UI のため対象外。チェックボックスは type="checkbox"
   *  かつreadonlyでないため、この判定には掛からない)
   */
  private async isNonEditableControl(
    page: Page,
    fieldObj: FRDescribeFieldsTypeWithModuleName
  ): Promise<boolean> {
    if (fieldObj.type.name === "reference") {
      // 関連項目は hidden値 + 専用UI(_select/_display)で扱うため対象外
      return false;
    }
    const name = fieldObj.name;
    // 編集可能なコントロール: 非hidden/非readonlyのinput、非readonlyのtextarea
    // (display:noneのJodit用textareaも含む)、select。
    const editable = await page
      .locator(
        `input[name="${name}"]:not([type="hidden"]):not([readonly]), ` +
          `textarea[name="${name}"]:not([readonly]), ` +
          `select[name="${name}"]`
      )
      .count();
    return editable === 0;
  }

  /**
   * 保存し、保存後の詳細画面で hash と各項目の値が表示されていることを検証する。
   *
   * 検証は「生成した値」ではなく Webservice API で取得した実保存値を基準にする。
   * こうすることでDBの桁数による切り詰めや、項目型ごとの表示差(リッチテキストの
   * HTMLタグ、選択肢のラベル化、通貨のカンマ区切り)を環境依存のハードコード無しに
   * 吸収できる。
   */
  private async saveAndVerify(
    page: Page,
    hash: string,
    filledFields: FRDescribeFieldsTypeWithModuleName[]
  ) {
    // メインの保存ボタン(EditView.tplの btn-success saveButton)。
    // インベントリ系は割引ポップアップ等にも "保存" があり text=保存 では
    // 複数マッチするため、クラスで一意に指定する。
    await page.locator("button.saveButton").first().click();
    // 保存に成功すると record=付きの詳細画面へ遷移する。
    // networkidle直後にURLを読むとリダイレクト完了前で誤判定する(レース)ため、
    // 遷移そのものを明示的に待つ。失敗時はタイムアウトし、extractRecordIdFromUrl
    // がバリデーションエラーを添えて投げる。
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 }).catch(() => {});

    const recordId = await this.extractRecordIdFromUrl(page);

    // 保存リダイレクトが進行中だと明示gotoが net::ERR_ABORTED で中断される
    // ことがあるため、進行中の遷移を落ち着かせてからリトライ付きで遷移する。
    await page.waitForLoadState("domcontentloaded").catch(() => {});
    for (let attempt = 0; attempt < 3; attempt++) {
      try {
        await page.goto(this.getDetailUrl(recordId));
        break;
      } catch (e) {
        if (attempt === 2) throw e;
        await page.waitForTimeout(500);
      }
    }
    await page.waitForLoadState("networkidle");

    // hash(=必ずいずれかの文字列項目に含まれる)が表示されていること
    await expect(page.locator(`text=${hash}`).first()).toBeVisible();

    // 実保存値を取得し、各項目の表示値が詳細画面に表示されていることを検証する
    const stored = await this.retrieveRecord(recordId);
    if (!stored) {
      return;
    }
    for (const field of filledFields) {
      const expected = this.expectedDisplayValue(field, stored[field.name]);
      if (expected === null) {
        continue;
      }
      await expect(
        page.locator(`#detailView >> text=${expected}`).first()
      ).toBeVisible();
    }
  }

  /**
   * 実保存値(API)から、詳細画面に表示されるはずの文字列を求める。
   * 表示形式が環境/設定依存で一意に定まらない型(日付/真偽/関連項目/数値)は
   * 検証対象外(null)とする。
   */
  private expectedDisplayValue(
    field: FRDescribeFieldsTypeWithModuleName,
    rawValue: string | undefined
  ): string | null {
    if (rawValue === undefined || rawValue === null || rawValue === "") {
      return null;
    }

    switch (field.type.name) {
      case "picklist": {
        const option = field.type.picklistValues?.find(
          (v) => String(v.value) === rawValue
        );
        return option?.label ?? rawValue;
      }
      case "currency": {
        const num = Number(rawValue);
        return Number.isNaN(num) ? rawValue : Math.trunc(num).toLocaleString();
      }
      case "string":
      case "text":
      case "url":
      case "email":
      case "phone":
        // リッチテキスト等はHTMLで保存されるためタグを除去して比較する
        return this.stripHtml(rawValue);
      default:
        // integer/double/date/datetime/boolean/reference/owner等は
        // 表示形式が一意でないため検証しない
        return null;
    }
  }

  private stripHtml(value: string): string {
    return value.replace(/<[^>]*>/g, "").trim();
  }

  /**
   * 保存後のURLから record ID(数値)を取り出す。
   * 取得できない(=保存に失敗し編集画面に留まっている)場合は、
   * 原因究明のため可視のバリデーションエラー文言を収集して例外に含める。
   */
  private async extractRecordIdFromUrl(page: Page): Promise<string> {
    const match = page.url().match(/[?&]record=(\d+)/);
    if (match) {
      return match[1];
    }

    const validationErrors = await page
      .locator("label.error:visible, span.error:visible")
      .allTextContents()
      .catch(() => [] as string[]);
    const detail =
      validationErrors.length > 0
        ? ` バリデーションエラー: ${validationErrors
            .map((t) => t.trim())
            .filter(Boolean)
            .join(" / ")}`
        : "";
    throw new Error(
      `保存後のURLから record ID を取得できませんでした: ${page.url()}${detail}`
    );
  }

  /**
   * レコードが正常に作成されたことを確認するテスト
   */
  async testRecordCreate(page: Page) {
    await page.goto(this.getCreateUrl());
    await page.waitForLoadState("domcontentloaded");

    const hash = generateRandomString(8);
    const filledFields = await this.fillAllFields(page, hash);
    await this.saveAndVerify(page, hash, filledFields);
  }

  /**
   * レコードが正常に編集されたことを確認するテスト
   */
  async testRecordEdit(page: Page) {
    const recordWsId = await this.getOneRecordFromModuleName(this.moduleName);
    if (!recordWsId) {
      return false;
    }
    // recordWsId.id は 22x1 のような形式なため、xで分割した後ろの数字だけを取得する
    const recordId = recordWsId.id.split("x")[1];
    await page.goto(this.getEditUrl(recordId));
    await page.waitForLoadState("domcontentloaded");

    const hash = generateRandomString(8);
    const filledFields = await this.fillAllFields(page, hash);
    await this.saveAndVerify(page, hash, filledFields);
  }

  /**
   * レコードが正常に削除されたことを確認するテスト
   */
  async testRecordDelete(page: Page) {
    const recordWsId = await this.getOneRecordFromModuleName(this.moduleName);
    if (!recordWsId) {
      return false;
    }
    // recordWsId.id は 22x1 のような形式なため、xで分割した後ろの数字だけを取得する
    const recordId = recordWsId.id.split("x")[1];
    await page.goto(this.getDetailUrl(recordId));
    await page.waitForLoadState("domcontentloaded");

    // その他ボタンをクリック
    await page.click("text=その他");
    await page.click("text=削除");
    await page.waitForLoadState("networkidle");
    // .modal-contentの Yes ボタンをクリック
    await page.click(".modal-content >> text=Yes");
    await page.waitForTimeout(1000);
    await page.waitForLoadState("domcontentloaded");

    await page.goto(this.getDetailUrl(recordId));
    await page.waitForLoadState("domcontentloaded");

    // 削除済みレコードを開いたときのメッセージ(LBL_RECORD_DELETE)。
    // 日本語ロケール環境のため日本語の文言で検証する。
    await expect(
      page.locator(`text=指定したレコードは削除されています`).first()
    ).toBeVisible();
  }
}
