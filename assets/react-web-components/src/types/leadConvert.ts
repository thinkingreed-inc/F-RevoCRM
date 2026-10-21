/**
 * リード昇格画面（<lead-convert>）の型定義
 *
 * サーバー側: modules/Leads/apis/ConvertLeadInfo.php / ConvertLeadSave.php
 * 値はすべて DB 形式（日付は YYYY-MM-DD、数値は区切り無し、参照は crmid）。
 */
import type { FieldValue } from "./field";

/** 昇格先モジュール */
export type ConvertModule = "Accounts" | "Contacts" | "Potentials";

export const CONVERT_MODULES: readonly ConvertModule[] = [
  "Accounts",
  "Contacts",
  "Potentials",
];

/** 既存レコードに紐づけられるモジュール */
export type AttachableModule = "Accounts" | "Contacts";

/** 各セクションの扱い */
export type ConvertMode = "create" | "existing" | "none";

/** 昇格済み判定で当たったレコード */
export interface ConvertMatchRecord {
  id: number;
  label: string;
  /** 担当者の所属企業（企業の場合は null） */
  accountId: number | null;
  accountLabel: string | null;
}

/** 昇格済み判定の結果（最初に当たった条件） */
export interface ConvertMatch {
  /** 条件の項目名 */
  rule: string[];
  /** 条件の表示名（例: 姓 + 名） */
  ruleLabel: string;
  /** 当たった件数（閲覧権限のあるもの） */
  total: number;
  records: ConvertMatchRecord[];
}

export interface ConvertModuleInfo {
  /** モジュールが有効で作成権限があるか */
  available: boolean;
  label: string;
  /** 昇格画面に出す項目（並び順どおり） */
  fields: string[];
  /** 初期値（マッピング・昇格時デフォルト・項目のデフォルトを反映済み） */
  values: Record<string, string>;
  /** 参照項目の初期値の表示名 */
  referenceLabels: Record<string, string>;
  match: ConvertMatch | null;
}

export interface ConvertConstraints {
  contactAccountMandatory: boolean;
  potentialAccountMandatory: boolean;
  potentialContactMandatory: boolean;
}

export interface ConvertTransferOption {
  module: AttachableModule;
  label: string;
}

/** ConvertLeadInfo の応答 */
export interface ConvertLeadInfo {
  record: { id: number; label: string };
  assignedUserId: string;
  transferOptions: ConvertTransferOption[];
  constraints: ConvertConstraints;
  modules: Record<ConvertModule, ConvertModuleInfo>;
}

/** 既存レコードの概要（ConvertLeadInfo mode=summary） */
export interface ConvertRecordSummary {
  id: number;
  module: AttachableModule;
  label: string;
  accountId: number | null;
  accountLabel: string | null;
  fields: Array<{ label: string; value: string }>;
}

/** サーバー側の検証エラー */
export interface ConvertError {
  module: ConvertModule | "";
  /** 空なら項目に紐づかないエラー */
  field: string;
  label: string;
  message: string;
  /** 昇格画面に出していない項目のエラー */
  hidden: boolean;
  /** 編集権限があるか（false なら画面では直せない） */
  editable: boolean;
  /** 保存しようとした値（追加表示した項目の初期値に使う） */
  value?: string;
}

/** ConvertLeadSave の応答 */
export interface ConvertSaveResult {
  converted: boolean;
  errors: ConvertError[];
  redirectUrl?: string;
  entityIds?: Partial<Record<ConvertModule, number>>;
}

/** 画面上の 1 セクションの状態 */
export interface ConvertSectionState {
  mode: ConvertMode;
  /** 既存レコードの crmid（未選択は空文字） */
  existingId: string;
  existingLabel: string;
  values: Record<string, FieldValue>;
  /** エラーのため追加表示した項目 */
  extraFields: string[];
}

export type ConvertSections = Record<ConvertModule, ConvertSectionState>;

/** ConvertLeadSave に送る data */
export interface ConvertRequestPayload {
  modules: Record<
    ConvertModule,
    {
      mode: ConvertMode;
      existingId?: number;
      values: Record<string, FieldValue>;
    }
  >;
  assignedUserId: string;
  transferTo: AttachableModule;
}

/** API の共通エラー応答 */
export interface ApiErrorResponse {
  success?: boolean;
  error?: { message?: string; code?: string };
}
