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

/** ダッシュボードのウィジェット（リンクのまとまり） */
export interface SharedLinkSet {
  id: number;
  /** 見出し（「ウィジェットの追加」メニューと各ユーザーのウィジェットに出る） */
  title: string;
  /** vtiger_links.linkid（ダッシュボードのウィジェットの登録） */
  linkid: number;
  sequence: number;
}

/** 一覧で受け取るリンク */
export interface SharedLink extends Omit<
  SharedLinkInput,
  "params" | "targets"
> {
  id: number;
  /** 出すウィジェット */
  set_id: number;
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
  widget_title_max: number;
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
  sets: SharedLinkSet[];
  links: SharedLink[];
}

/** ウィジェットの作成・見出しの変更の結果（mode=save_set） */
export type SharedLinkSaveSetResult =
  | { saved: true; id: number; title: string }
  | { saved: false; errors: Record<string, string> };

/** 保存の結果（mode=save） */
export type SharedLinkSaveResult =
  | { saved: true; id: number }
  | { saved: false; errors: Record<string, string> };

/** ダッシュボードのウィジェットに渡すリンク（サーバーで公開範囲の判定と値の解決を済ませたもの） */
export interface SharedLinkWidgetItem {
  id: number;
  title: string;
  description: string;
  method: SharedLinkMethod;
  /** GET はパラメータを付けた最終的な URL。POST は送り先 */
  url: string;
  /** POST で送る値（GET は空） */
  params: { name: string; value: string }[];
  openMode: SharedLinkOpenMode;
  width: number | null;
  height: number | null;
  noreferrer: boolean;
  /** POST の送り先が CSP（form-action）で許可されていない */
  blocked: boolean;
  /** 左に出す目印の文字（タイトルの頭文字） */
  initial: string;
  /** 目印の色（0〜7） */
  color: number;
}

/** ウィジェットの文言 */
export interface SharedLinkWidgetLabels {
  empty: string;
  settings: string;
  openInNewTab: string;
  openInNewWindow: string;
  popupBlocked: string;
  postBlocked: string;
}
