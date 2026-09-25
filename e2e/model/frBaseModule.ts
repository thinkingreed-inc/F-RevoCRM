import {
  frgetDescribe,
  frgetOneRecord,
  frRetrieve,
  login,
} from "./fetcher";
import type { FRDescribeType } from "./types/frBase";
import { BASE_URL } from "../utils/util";

export class FrBaseModule {
  moduleName: string;
  private sessionName: string;
  private moduleInfo: FRDescribeType;
  baseUrl: string = BASE_URL;

  constructor(moduleName: string, sessionName: string) {
    this.moduleName = moduleName;
    this.sessionName = sessionName;
  }

  /**
   * 初期化処理
   * 継承先から使われることを想定して、ジェネリクス型で継承先のクラスを返す用に設定
   */
  public static async init<T extends FrBaseModule>(
    this: new (moduleName: string, sessionName: string) => T,
    moduleName: string,
    sessionName?: string
  ): Promise<T | null> {
    if (!sessionName) {
      const response = await login(
        process.env.E2E_USER_NAME || "",
        process.env.E2E_USER_ACCESSKEY || ""
      );
      if (!response) {
        return null;
      }
      sessionName = response.sessionName;
    }
    return new this(moduleName, sessionName);
  }

  /**********************************
   * TEST用
   *********************************/
  getDetailUrl(recordId: string) {
    return `${this.baseUrl}index.php?module=${this.moduleName}&view=Detail&app=MARKETING&record=${recordId}&mode=showDetailViewByMode&requestMode=full`;
  }
  getCreateUrl() {
    return `${this.baseUrl}index.php?module=${this.moduleName}&view=Edit&app=MARKETING`;
  }
  getEditUrl(recordId: string) {
    return `${this.baseUrl}index.php?module=${this.moduleName}&view=Edit&app=MARKETING&record=${recordId}`;
  }

  /**********************************
   * API
   *********************************/

  /**
   * モジュール詳細を取得する
   */
  async getDescribe() {
    if (this.moduleInfo) {
      return this.moduleInfo;
    }

    const response = await frgetDescribe(this.sessionName, this.moduleName);
    if (!response) {
      return false;
    }
    this.moduleInfo = response;

    return this.moduleInfo;
  }

  /**
   * 特定のモジュールのレコードを1権取得する
   */
  async getOneRecordFromModuleName(moduleName: string) {
    const response = await frgetOneRecord(this.sessionName, moduleName);
    if (!response) {
      return false;
    }
    // 1件のみ返却する
    return response[0];
  }

  /**
   * record ID(数値)を指定して、このモジュールのレコードの実保存値を取得する。
   * Webservice IDは describe の idPrefix を用いて `<idPrefix>x<recordId>` を組み立てる。
   */
  async retrieveRecord(recordId: string): Promise<Record<string, string> | false> {
    const moduleInfo = await this.getDescribe();
    if (!moduleInfo) {
      return false;
    }
    const id = `${moduleInfo.idPrefix}x${recordId}`;
    return frRetrieve(this.sessionName, id);
  }
}
