/**
 * リードマッピング設定画面（<lead-mapping-settings>）の型定義
 *
 * サーバー側: modules/Settings/Leads/apis/ConvertSettings.php
 */
import type { ConvertModule } from "./leadConvert";

/** マッピングで選べる項目（型が一致する項目どうしだけ対応づけられる） */
export interface MappingFieldOption {
  id: number;
  name: string;
  label: string;
  /** 旧設定画面と同じ型名（string, email, currency, picklist など） */
  type: string;
}

/** 昇格画面に出せる項目・判定条件に使える項目 */
export interface InputFieldOption {
  name: string;
  label: string;
  mandatory: boolean;
}

/** サーバーから受け取るマッピング 1 行 */
export interface MappingRowData {
  id: number;
  /** false の行（ロック行）は変更・削除できない */
  editable: boolean;
  lead: number | null;
  Accounts: number | null;
  Contacts: number | null;
  Potentials: number | null;
}

/** 画面上のマッピング 1 行 */
export interface MappingRow extends Omit<MappingRowData, "id"> {
  /** 新しく追加した行は null */
  id: number | null;
  /** React の key（新しい行にも一意な値を振る） */
  key: string;
  deleted: boolean;
}

export interface LeadMappingModuleSettings {
  label: string;
  mappableFields: MappingFieldOption[];
  inputFields: InputFieldOption[];
  matchableFields: InputFieldOption[];
  layout: string[];
  defaults: Record<string, string>;
  matchRules: string[][];
}

/** ConvertSettings mode=info の応答 */
export interface LeadMappingInfo {
  mapping: MappingRowData[];
  leadFields: MappingFieldOption[];
  /** ロック行が使っている昇格先の項目（新しい行では選べない） */
  restrictedFieldIds: number[];
  modules: Record<ConvertModule, LeadMappingModuleSettings>;
}

/** 保存系 API の応答 */
export interface SettingsSaveResult {
  saved: boolean;
  errors: Array<{ field: string; message: string }>;
}
