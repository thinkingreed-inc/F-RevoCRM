import { expect, type Page } from "@playwright/test";
import { FrBaseModule } from "./frBaseModule";
import { generateRandomString, url } from "../utils/util";
import { dontTestFieldsName, fillField, getFieldValue } from "../utils/field";

export class FrTest extends FrBaseModule {
  constructor(public moduleName: string, sessionName: string) {
    super(moduleName, sessionName);
  }

  /**
   * レコードが正常に作成されたことを確認するテスト
   */
  async testRecordCreate(page: Page) {
    // ページ遷移
    const createUrl = this.getCreateUrl();
    await page.goto(createUrl);
    await page.waitForLoadState("domcontentloaded");

    // 値の登録
    const valuesArray: string[] = [];
    const hash = generateRandomString(8);
    const moduleInfo = await this.getDescribe();
    if (moduleInfo) {
      const fieldsWithModuleName = moduleInfo.fields.map((info) => {
        return {
          moduleName: this.moduleName,
          ...info,
        }
      });
      for (const [_key, fieldObj] of Object.entries(fieldsWithModuleName)) {
        if (dontTestFieldsName(fieldObj)) {
          continue;
        }

        const normalValue = (await getFieldValue(fieldObj, hash)) || "";

        await fillField(page, fieldObj, normalValue);

        // console.log("fieldObj", fieldObj.name, fieldObj.type.name, normalValue);

        // 値を保持しておく
        if (fieldObj.type.name === "picklist") {
          if (fieldObj.type.picklistValues?.[0]?.label) {
            valuesArray.push(fieldObj.type.picklistValues?.[0]?.label);
          }
        } else if (fieldObj.type.name === "boolean") {
          // 何もしない
        } else if (fieldObj.type.name === "reference") {
          // 何もしない
        } else if (fieldObj.type.name === "currency") {
          // intに変換して、カンマを付ける
          const intValue = parseInt(normalValue, 10);
          valuesArray.push(intValue.toLocaleString());
        } else {
          valuesArray.push(normalValue);
        }
      }
    }

    // 保存ボタンをクリックして保存
    await page.click("text=保存");
    await page.waitForLoadState("networkidle");
    await page.waitForTimeout(1000);

    // 現在のURLを取得
    const currentUrl = page.url();
    await page.goto(`${currentUrl}&mode=showDetailViewByMode&requestMode=full`);
    await page.waitForLoadState("networkidle");
    // hashがあるかどうかチェックする
    expect(page.locator(`text=${hash}`).first()).toBeVisible();

    valuesArray.forEach(async (value) => {
      expect(page.locator(`#detailView >> text=${value}`).first()).toBeVisible();
    });
  }

  /**
   * レコードが正常に編集されたことを確認するテスト
   */
  async testRecordEdit(page: Page) {
    // ページ遷移
    const recordWsId = await this.getOneRecordFromModuleName(this.moduleName);
    if (!recordWsId) {
      return false;
    }
    // recordWsIdは22x1のような形式なため、xで分割した後ろの数字だけを取得する
    const recordId = recordWsId.id.split("x")[1];
    await page.goto(this.getEditUrl(recordId));
    await page.waitForLoadState("domcontentloaded");

    // 値の登録
    const valuesArray: string[] = [];
    const hash = generateRandomString(8);
    const moduleInfo = await this.getDescribe();
    if (moduleInfo) {
      const fieldsWithModuleName = moduleInfo.fields.map((info) => {
        return {
          moduleName: this.moduleName,
          ...info,
        }
      });
      for (const [_key, fieldObj] of Object.entries(fieldsWithModuleName)) {
        if (dontTestFieldsName(fieldObj)) {
          continue;
        }

        const normalValue = (await getFieldValue(fieldObj, hash)) || "";
        await fillField(page, fieldObj, normalValue);

        // 値を保持しておく
        if (fieldObj.type.name === "picklist") {
          if (fieldObj.type.picklistValues?.[0]?.label) {
            valuesArray.push(fieldObj.type.picklistValues?.[0]?.label);
          }
        } else if (fieldObj.type.name === "date") {
          await page.keyboard.press("Escape");
        } else if (fieldObj.type.name === "boolean") {
          // 何もしない
        } else if (fieldObj.type.name === "reference") {
          // 何もしない
        } else if (fieldObj.type.name === "currency") {
          // intに変換して、カンマを付ける
          const intValue = parseInt(normalValue, 10);
          console.log("intValue", intValue);
          valuesArray.push(intValue.toLocaleString());
        } else {
          valuesArray.push(normalValue);
        }
      }
    }

    // 保存ボタンをクリックして保存
    await page.click("text=保存");
    await page.waitForLoadState("networkidle");
    await page.waitForTimeout(1000);
    
    // 現在のURLを取得
    const currentUrl = page.url();
    await page.goto(`${currentUrl}&mode=showDetailViewByMode&requestMode=full`);
    await page.waitForLoadState("networkidle");
    // hashがあるかどうかチェックする
    expect(page.locator(`text=${hash}`).first()).toBeVisible();

    valuesArray.forEach(async (value) => {
      expect(page.locator(`#detailView >> text=${value}`).first()).toBeVisible();
    });
  }

  /**
   * レコードが正常に削除されたことを確認するテスト
   */
  async testRecordDelete(page: Page) {
    // ページ遷移
    const recordWsId = await this.getOneRecordFromModuleName(this.moduleName);
    if (!recordWsId) {
      return false;
    }
    // recordWsIdは22x1のような形式なため、xで分割した後ろの数字だけを取得する
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

    expect(
      page
        .locator(`text=The record you are trying to view has been deleted.`)
        .first()
    ).toBeVisible();
  }
}
