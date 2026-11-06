/**
 * 共有リンク（設定 > 共有リンク）の型定義
 *
 * サーバー側は Settings:SharedLinks の SharedLinkAPI（JSON）。
 * 仕様: docs/specs/shared_links_widget.md
 */

export type SharedLinkMethod = "GET" | "POST";
export type SharedLinkOpenMode = "tab" | "window";
export type SharedLinkVisibility = "all" | "targets";
export type SharedLinkValueType = "fixed" | "user";
export type SharedLinkTargetType =
  "Users" | "Groups" | "Roles" | "RoleAndSubordinates";

/** リクエストパラメータ */
export interface SharedLinkParam {
  name: string;
  value_type: SharedLinkValueType;
  /** fixed のときは固定値、user のときはユーザーの項目名 */
  value: string;
}

/** 一覧で受け取るパラメータ（表示用の情報つき） */
export interface SharedLinkParamView extends SharedLinkParam {
  /** user のときの項目の表示ラベル。項目が削除されていれば null */
  label: string | null;
  /** user で、項目が削除された・差し込めなくなったとき true */
  missing: boolean;
}

/** 公開範囲の対象 */
export interface SharedLinkTarget {
  type: SharedLinkTargetType;
  id: string;
}

/** 一覧で受け取る対象（表示用の情報つき） */
export interface SharedLinkTargetView extends SharedLinkTarget {
  /** 表示名。削除されていれば null */
  name: string | null;
  deleted: boolean;
}

/** 保存するリンクの内容 */
export interface SharedLinkInput {
  title: string;
  url: string;
  description: string;
  method: SharedLinkMethod;
  open_mode: SharedLinkOpenMode;
  window_width: number | null;
  window_height: number | null;
  noreferrer: boolean;
  active: boolean;
  visibility: SharedLinkVisibility;
  params: SharedLinkParam[];
  targets: SharedLinkTarget[];
}

/** 一覧で受け取るリンク */
export interface SharedLink extends Omit<
  SharedLinkInput,
  "params" | "targets"
> {
  id: number;
  sequence: number;
  params: SharedLinkParamView[];
  targets: SharedLinkTargetView[];
  /** 削除された項目・対象を含む */
  has_warning: boolean;
  /** POST の送り先が CSP（form-action）で許可されておらず、ウィジェットから開けない */
  post_blocked: boolean;
}

/** 差し込める項目 */
export interface SharedLinkUserField {
  name: string;
  label: string;
}

/** 公開範囲の選択肢 */
export interface SharedLinkOption {
  id: string;
  name: string;
}

/** 役割の選択肢（階層の深さつき） */
export interface SharedLinkRoleOption extends SharedLinkOption {
  depth: number;
}

/** 入力の上限 */
export interface SharedLinkLimits {
  title_max: number;
  url_max: number;
  description_max: number;
  params_max: number;
  param_name_max: number;
  param_value_max: number;
  window_min: number;
  window_max: number;
  default_window_width: number;
  default_window_height: number;
}

/** 画面の初期情報（mode=info） */
export interface SharedLinkInfo {
  user_fields: SharedLinkUserField[];
  roles: SharedLinkRoleOption[];
  groups: SharedLinkOption[];
  users: SharedLinkOption[];
  limits: SharedLinkLimits;
  labels: Record<string, string>;
}

/** 一覧（mode=list） */
export interface SharedLinkListResult {
  links: SharedLink[];
}

/** 保存の結果（mode=save） */
export type SharedLinkSaveResult =
  | { saved: true; id: number }
  | { saved: false; errors: Record<string, string> };
