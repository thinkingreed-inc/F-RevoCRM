/**
 * ファイルアップロード項目（uitype 998 / UI_TYPES.FILE_UPLOAD）の型定義
 *
 * 項目の値はアップロードしたファイルを登録したドキュメントのID。
 * 画面でファイルを選んだ（手書きした）直後は一時ファイルを指す値（draft:...）を入れ、
 * レコードの保存時にサーバーがドキュメントにして ID に置き換える。
 * API は module=<項目を持つモジュール>&api=FileField（modules/Vtiger/apis/FileField.php）。
 */

import type { WebComponentFieldProps } from "./webComponentField";

/** 画面に渡すファイル情報（Documents_FieldFile::toClientInfo） */
export interface FieldFileInfo {
  /** ドキュメントID（項目の値）。保存前の一時ファイルなら 0 */
  id: number;
  filename: string;
  filetype: string;
  filesize: number;
  /** サムネイルを出せる画像か */
  is_image: boolean;
  /** ドキュメントのダウンロード URL（相対パス）。保存前の一時ファイルなら空 */
  download_url: string;
  /** 保存前の一時ファイルを指す値（draft:...）。あればこれを項目の値にする */
  draft?: string;
}

/** 縮小画像（Documents_FieldFile::buildThumbnail） */
export interface FieldThumbnail {
  data_uri: string;
  width: number;
  height: number;
  mime: string;
}

/** mode=upload の応答（一時ファイルに置いた結果） */
export interface FileFieldUploadResponse {
  /** 項目に入れる値（draft:...） */
  draft: string;
  filename: string;
  filetype: string;
  filesize: number;
  is_image: boolean;
  thumbnail: FieldThumbnail | null;
}

/** mode=thumbnail の応答（record だけ指定して項目が空なら file は null） */
export interface FileFieldThumbnailResponse {
  file: FieldFileInfo | null;
  thumbnail: FieldThumbnail | null;
}

/** mode=config の応答 */
export interface FileFieldConfigResponse {
  field_name: string;
  handwriting: boolean;
  max_upload_size: number;
  max_upload_size_label: string;
}

/** API がエラーのときの応答（Vtiger_Response のエラー形式） */
export interface FileFieldErrorResponse {
  success: false;
  error: {
    code?: string | number;
    message: string;
  };
}

/** <fr-field-fileupload> の props（Vtiger_Fileupload_UIType::getWebComponentProps） */
export interface FileUploadWebComponentProps extends WebComponentFieldProps {
  /** 現在のファイル（無ければ null） */
  file: FieldFileInfo | null;
  /**
   * 現在の値（ドキュメントID）。file を渡せない場面（詳細画面のインライン編集など）で使い、
   * Web コンポーネントがファイル情報を取得する
   */
  value?: string;
  /** 手書き入力の項目か（未指定なら API から取得する） */
  handwriting?: boolean;
  /** アップロードできる大きさ（バイト。未指定なら API から取得する） */
  maxUploadSize?: number;
}
