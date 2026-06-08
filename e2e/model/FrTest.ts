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
   * 戻り値は「保存後の詳細画面で表示されているはずの値」の配列。
   * create と edit で日付項目の扱いだけ異なるため mode で分岐する。
   */
  private async fillFieldsAndCollect(
    page: Page,
    hash: string,
    mode: "create" | "edit"
  ): Promise<string[]> {
    const valuesArray: string[] = [];
    const moduleInfo = await this.getDescribe();
    if (!moduleInfo) {
      return valuesArray;
    }

    const fields: FRDescribeFieldsTypeWithModuleName[] = moduleInfo.fields.map(
      (info) => ({ moduleName: this.moduleName, ...info })
    );

    for (const fieldObj of fields) {
      if (dontTestFieldsName(fieldObj)) {
        continue;
      }

      const normalValue = (await getFieldValue(fieldObj, hash)) || "";
      await fillField(page, fieldObj, normalValue);

      // 入力した値のうち、詳細画面で検証可能なものだけ保持しておく
      switch (fieldObj.type.name) {
        case "picklist":
          if (fieldObj.type.picklistValues?.[0]?.label) {
            valuesArray.push(fieldObj.type.picklistValues[0].label);
          }
          break;
        case "date":
          if (mode === "edit") {
            // 編集時はカレンダーが開いたままになることがあるため閉じる
            await page.keyboard.press("Escape");
          } else {
            valuesArray.push(normalValue);
          }
          break;
        case "boolean":
        case "reference":
          // 詳細画面での値検証対象にしない
          break;
        case "currency":
          // 表示はカンマ区切りになるため変換して保持
          valuesArray.push(parseInt(normalValue, 10).toLocaleString());
          break;
        default:
          valuesArray.push(normalValue);
          break;
      }
    }

    return valuesArray;
  }

  /**
   * 保存ボタンを押し、保存後に表示された詳細画面で hash と各値が見えることを検証する。
   * 保存直後の page.url() に依存した文字列結合は壊れやすいため、
   * URL から record ID を抽出し、正規の詳細URL(getDetailUrl)へ明示的に遷移する。
   */
  private async saveAndVerify(page: Page, hash: string, valuesArray: string[]) {
    await page.click("text=保存");
    await page.waitForLoadState("networkidle");

    const recordId = this.extractRecordIdFromUrl(page);
    await page.goto(this.getDetailUrl(recordId));
    await page.waitForLoadState("networkidle");

    // hash(=必ずいずれかの文字列項目に含まれる)が表示されていること
    await expect(page.locator(`text=${hash}`).first()).toBeVisible();

    // 入力した各値が詳細画面に表示されていること
    for (const value of valuesArray) {
      await expect(
        page.locator(`#detailView >> text=${value}`).first()
      ).toBeVisible();
    }
  }

  /**
   * 保存後のURLから record ID(数値)を取り出す
   */
  private extractRecordIdFromUrl(page: Page): string {
    const match = page.url().match(/[?&]record=(\d+)/);
    if (!match) {
      throw new Error(
        `保存後のURLから record ID を取得できませんでした: ${page.url()}`
      );
    }
    return match[1];
  }

  /**
   * レコードが正常に作成されたことを確認するテスト
   */
  async testRecordCreate(page: Page) {
    await page.goto(this.getCreateUrl());
    await page.waitForLoadState("domcontentloaded");

    const hash = generateRandomString(8);
    const valuesArray = await this.fillFieldsAndCollect(page, hash, "create");
    await this.saveAndVerify(page, hash, valuesArray);
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
    const valuesArray = await this.fillFieldsAndCollect(page, hash, "edit");
    await this.saveAndVerify(page, hash, valuesArray);
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
