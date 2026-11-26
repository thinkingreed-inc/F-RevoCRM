/**
 * 更新履歴（ModTracker）の型定義
 *
 * API: GET ?module=ModTracker&api=GetHistory&parent_module=<Module>&parent_id=<id>
 * 実装: modules/ModTracker/apis/GetHistory.php
 */

/** 履歴イベントの種別（vtiger_modtracker_basic.status に対応） */
export type HistoryEventType =
  "create" | "update" | "delete" | "restore" | "link" | "unlink";

/**
 * 項目のデータ型（Vtiger_Field_Model::getFieldDataType の戻り値）
 * 表示の出し分けに使う値のみ列挙し、それ以外は string として扱う。
 */
export type HistoryFieldType =
  | "string"
  | "text"
  | "picklist"
  | "multipicklist"
  | "owner"
  | "reference"
  | "currency"
  | "integer"
  | "double"
  | "percentage"
  | "phone"
  | "email"
  | "url"
  | "date"
  | "datetime"
  | "time"
  | "boolean"
  | (string & {});

/** 項目値（空値は null で表す） */
export interface HistoryValue {
  /** DB 上の値 */
  raw: string;
  /** 表示用に整形済みのプレーンテキスト（HTML を含まない） */
  display: string;
}

/** 数値項目の増減（ユーザー設定の書式で整形済み） */
export interface HistoryDelta {
  sign: "+" | "-";
  /** 差の絶対値（例: "220,000,000.00"、"220.000.000 ¥"） */
  display: string;
  /** 変更前に対する増減率（%）。変更前が 0 のときは null */
  percent: number | null;
}

/** 1 項目の変更 */
export interface HistoryChange {
  /** fieldname */
  field: string;
  before: HistoryValue | null;
  after: HistoryValue | null;
  /** 数値・金額項目で、変更前後とも値がある場合のみ */
  delta?: HistoryDelta;
}

/** 関連付け／関連解除の相手レコード */
export interface HistoryRelated {
  module: string;
  moduleLabel: string;
  recordId: number;
  label: string;
  /** 詳細画面の URL（遷移先が無い場合は null） */
  url: string | null;
}

/** 履歴イベント */
export interface HistoryEvent {
  id: number;
  type: HistoryEventType;
  userId: number;
  /** ユーザーのタイムゾーンでの日時（YYYY-MM-DDTHH:mm:ss） */
  changedOn: string;
  /** create / update のみ値を持つ */
  changes: HistoryChange[];
  /** link / unlink のみ */
  related?: HistoryRelated;
}

/** 履歴に登場する項目のメタ情報 */
export interface HistoryField {
  name: string;
  /** 翻訳済みラベル */
  label: string;
  type: HistoryFieldType;
  /** 最終更新者・作成日時などシステムが自動で更新する項目 */
  system: boolean;
  /** 詳細画面での並び順（ブロック順 → ブロック内の項目順） */
  order: number;
}

/** ユーザー設定の日付・時刻形式 */
export interface HistoryDateFormat {
  /** yyyy-mm-dd / dd-mm-yyyy / mm-dd-yyyy / dd.mm.yyyy / dd/mm/yyyy */
  dateFormat: string;
  hourFormat: "12" | "24";
}

/** 履歴に登場するユーザー */
export interface HistoryUser {
  id: number;
  name: string;
  imageUrl: string | null;
}

/** GetHistory API のレスポンス */
export interface GetHistoryResponse {
  success: boolean;
  /** ユーザーのタイムゾーンでの現在時刻（相対時間の基準） */
  now: string;
  /** 変更日時の表示に使うユーザー設定の書式 */
  format: HistoryDateFormat;
  /** 新しい順 */
  events: HistoryEvent[];
  users: Record<string, HistoryUser>;
  fields: Record<string, HistoryField>;
}

/** クライアント側の絞り込み条件 */
export interface HistoryFilterCriteria {
  /** 項目名・値の部分一致（大文字小文字は区別しない） */
  keyword: string;
  /** 空なら全項目 */
  fields: string[];
  /** 空なら全ユーザー */
  userIds: number[];
  /** 空なら全種別 */
  types: HistoryEventType[];
  /** システム項目を表示するか */
  showSystemFields: boolean;
}

/** 絞り込み後のイベント（表示対象の変更だけを持つ） */
export interface FilteredHistoryEvent extends HistoryEvent {
  visibleChanges: HistoryChange[];
}

/** 項目ごとの値の変遷の 1 世代 */
export interface FieldTrackEntry {
  eventId: number;
  userId: number;
  changedOn: string;
  value: HistoryValue | null;
  /** この値が設定されたのがレコード作成時か */
  isInitial: boolean;
  /** 次の値に変わった日時（現在の値なら null） */
  until: string | null;
}

/** record-history の表示形式 */
export type RecordHistoryVariant = "full" | "compact";

/** record-history Web Component の props */
export interface RecordHistoryProps {
  /** 親モジュール名 */
  module: string;
  /** 親レコード ID */
  recordId: string | number;
  /** full: 更新履歴タブ / compact: 概要タブのウィジェット */
  variant?: RecordHistoryVariant;
  /** 1 回に表示するイベント数（full: さらに表示の単位 / compact: 表示件数） */
  pageSize?: number;
  /** compact の「すべて表示」リンク先 */
  fullHistoryUrl?: string;
  /** 値が変わると再取得する */
  refreshKey?: string | number;
}
