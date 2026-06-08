import {
  frgetDescribe,
  frgetListTypes,
  frgetOneRecord,
  login,
} from "./fetcher";
import type { FRDescribeType } from "./types/frBase";

export class FrBaseModule {
  moduleName: string;
  private sessionName: string;
  private listTypes: {
    isEntity: boolean;
    label: string;
    singular: string;
  }[];
  private moduleInfo: FRDescribeType;
  baseUrl: string = "http://localhost/";

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
   * Module一覧を取得する
   */
  async fetchAllListTypes() {
    if (this.listTypes?.[this.moduleName]) {
      return this.listTypes;
    }

    const response = await frgetListTypes(this.sessionName);
    if (!response) {
      return false;
    }

    Object.keys(this.listTypes).forEach((key) => {
      this.listTypes[key] = this.listTypes[key];
    });

    return this.listTypes;
  }

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
}
