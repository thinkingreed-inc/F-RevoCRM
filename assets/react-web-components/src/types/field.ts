/**
 * フィールド値の型（any型を置換）
 * HTMLフォーム要素に渡す際は string | number | string[] | undefined に変換が必要
 */
export type FieldValue = string | number | boolean | string[] | null | undefined;

/**
 * HTMLフォーム要素のvalue属性に渡せる型
 */
export type HTMLInputValue = string | number | readonly string[] | undefined;

/**
 * バリデータ情報の型定義
 */
export interface FieldValidator {
  /** バリデータ名 */
  name?: string;
  /** バリデータパラメータ */
  params?: Record<string, unknown>;
}

/**
 * フィールド情報の型定義
 * GetFieldsAPIから取得される情報と対応
 */
export interface FieldInfo {
  /** フィールド名（データベース列名） */
  name: string;
  /** ラベル（表示名） */
  label: string;
  /** UIType番号（VtigerCRM内部識別子） */
  uitype: string;
  /** 必須フィールドかどうか */
  mandatory: boolean;
  /** 読み取り専用かどうか */
  readonly: boolean;
  /** 最大文字数（テキスト系フィールド） */
  maxlength?: number;
  /** 所属ブロック名（レイアウトエディタで設定されたブロック） */
  block?: string;
  /** フィールド詳細情報 */
  fieldinfo: {
    /** データ型 */
    type: string;
    /** バリデーション情報 */
    validator?: FieldValidator | FieldValidator[];
    /** デフォルト値 */
    defaultvalue?: FieldValue;
    /** その他のフィールド属性（unknown型でより安全に） */
    [key: string]: unknown;
  };
  /** ピックリスト値（SelectやMultiSelect系フィールド） */
  picklistValues?: Array<{
    label: string;
    value: string;
  }>;
  /** RecordTypeフィールドかどうか */
  isRecordTypeField?: boolean;
  /** RecordFieldID（RecordTypeの場合） */
  recordTypeFieldId?: number;
  /** QuickCreateで表示するかどうか */
  quickcreate?: boolean;
  /** QuickCreate時の表示順序 */
  quickcreatesequence?: number | null;
  /** 参照先モジュール名（Reference型フィールド用） */
  referenceModules?: string[];
  /** 参照先モジュールの翻訳ラベル（モジュール名 -> 翻訳ラベルのマップ） */
  referenceModuleLabels?: Record<string, string>;
  /** 複数選択可能かどうか（multireference型フィールドなど） */
  isMultiple?: boolean;
  /** フィールドデータタイプ（'multireference' など） */
  datatype?: string;
  /** カスタムバリデーション設定 */
  customValidations?: CustomValidation[];
}

/**
 * カスタムバリデーション情報
 */
export interface CustomValidation {
  /** バリデーション関数名 */
  name: string;
  /** バリデーション関数本体（JavaScriptコード） */
  body: string;
  /** エラーメッセージ */
  message: string;
}

/**
 * フィールドコンポーネントの共通Props
 * 注: valueはコンポーネント内で適切な型に変換して使用
 */
export interface FieldComponentProps {
  /** フィールド情報 */
  field: FieldInfo;
  /** 現在の値（フィールドタイプにより異なる） */
  value: FieldValue;
  /** 値変更時のコールバック */
  onChange: (name: string, value: FieldValue) => void;
  /** 無効化状態 */
  disabled?: boolean;
  /** エラーメッセージ */
  error?: string;
  /** カスタムクラス名 */
  className?: string;
}

/**
 * FieldRendererコンポーネントのProps
 * 注: valueはコンポーネント内で適切な型に変換して使用
 */
export interface FieldRendererProps {
  /** フィールド情報 */
  field: FieldInfo;
  /** 現在の値（フィールドタイプにより異なる） */
  value: FieldValue;
  /** 値変更時のコールバック */
  onChange: (name: string, value: FieldValue) => void;
  /** 無効化状態 */
  disabled?: boolean;
  /** エラーメッセージ */
  error?: string;
  /** カスタムクラス名 */
  className?: string;
  /** RecordType変更時のコールバック（RecordTypeフィールドの場合のみ） */
  onRecordTypeChange?: (fieldName: string, value: string) => void;
  /** フォームデータ全体（参照フィールドの表示値取得に使用） */
  formData?: Record<string, FieldValue>;
}

/**
 * UIType定数定義
 * VtigerCRMの主要UITypeを定義
 */
export const UI_TYPES = {
  /** 文字列（短い） */
  STRING: '1',
  /** 文字列（長い） */
  STRING_LONG: '2',
  /** 数値 */
  NUMBER: '7',
  /** 小数 */
  DECIMAL: '9',
  /** 電話番号 */
  PHONE: '11',
  /** Email */
  EMAIL: '13',
  /** ピックリスト */
  PICKLIST: '15',
  /** ピックリスト（空白なし） */
  PICKLIST_NO_BLANK: '16',
  /** マルチピックリスト */
  MULTIPICKLIST: '33',
  /** テキストエリア */
  TEXTAREA: '19',
  /** テキストエリア（長い） */
  TEXTAREA_LONG: '21',
  /** 日付 */
  DATE: '5',
  /** 日時（Calendar開始日時用） */
  DATETIME_CALENDAR: '6',
  /** 日付（closingdate等で使用） */
  DATE_23: '23',
  /** 時刻 */
  TIME: '14',
  /** 真偽値 */
  BOOLEAN: '56',
  /** URL */
  URL: '17',
  /** 通貨 */
  CURRENCY: '71',
  /** 参照 */
  REFERENCE: '10',
  /** 所有者 */
  OWNER: '53',
  /** ファイル */
  FILE: '28',
  /** 画像 */
  IMAGE: '69',
  /** パーセンテージ */
  PERCENTAGE: '72',
  /** 敬称 */
  SALUTATION: '55',
  /** パスワード */
  PASSWORD: '99',

  // 追加の参照系UIType（mobile_frontendに準拠）
  /** Account参照 */
  REFERENCE_ACCOUNT: '51',
  /** ユーザー参照 */
  REFERENCE_USER: '52',
  /** Contact参照 */
  REFERENCE_CONTACT: '57',
  /** 複数参照（multireference型、Contact等を複数選択可能） */
  MULTIREFERENCE: '57',
  /** Campaign参照 */
  REFERENCE_CAMPAIGN: '58',
  /** Product/Service参照 */
  REFERENCE_PRODUCT: '59',
  /** 関連先（ポリモーフィック参照） */
  REFERENCE_RELATED: '66',
  /** Account参照（別タイプ） */
  REFERENCE_ACCOUNT2: '73',
  /** Vendor参照 */
  REFERENCE_VENDOR: '75',
  /** Potential参照 */
  REFERENCE_POTENTIAL: '76',
  /** 参照77 */
  REFERENCE_77: '77',
  /** Quote参照 */
  REFERENCE_QUOTE: '78',
  /** SalesOrder参照 */
  REFERENCE_SALESORDER: '80',
  /** PurchaseOrder参照 */
  REFERENCE_PURCHASEORDER: '81',
  /** ユーザー参照（別タイプ） */
  REFERENCE_USER2: '101',

  // 追加のテキスト系UIType
  /** テキストエリア20 */
  TEXTAREA_20: '20',
  /** テキスト106 */
  STRING_106: '106',
} as const;

/**
 * 参照系UITypeのリスト
 */
export const REFERENCE_UI_TYPES = [
  '10', '51', '52', '57', '58', '59', '66', '73', '75', '76', '77', '78', '80', '81', '101'
] as const;

/**
 * UIType値の型
 */
export type UITypeValue = typeof UI_TYPES[keyof typeof UI_TYPES];

/**
 * フォームデータの型
 */
export interface FormData {
  [fieldName: string]: FieldValue;
}

/**
 * バリデーションエラーの型
 */
export interface ValidationError {
  fieldName: string;
  message: string;
}

/**
 * フィールド表示設定の型
 */
export interface FieldDisplayConfig {
  /** 列数（グリッドレイアウト用） */
  columns?: number;
  /** フィールドグループ化 */
  groups?: Array<{
    title: string;
    fields: string[];
  }>;
  /** 非表示フィールド */
  hiddenFields?: string[];
}

/**
 * RecordType情報の型定義
 */
export interface RecordTypeInfo {
  /** 利用可能なRecordTypeフィールドと値 */
  available: RecordTypeField[];
  /** 現在適用されているRecordType値 */
  applied: Record<string, string>;
  /** 計算されたRecordFieldIDリスト */
  recordFieldIdList: number[];
}

/**
 * RecordTypeフィールドの型定義
 */
export interface RecordTypeField {
  /** フィールド名 */
  fieldName: string;
  /** 選択可能な値のリスト */
  values: RecordTypeValue[];
}

/**
 * RecordType値の型定義
 */
export interface RecordTypeValue {
  /** 値 */
  value: string;
  /** 対応するRecordFieldID */
  recordFieldId: number;
}

/**
 * GetFields APIレスポンス
 */
export interface GetFieldsResponse {
  success: boolean;
  result?: {
    module: string;
    totalFields: number;
    fields: FieldInfo[];
    timestamp: string;
    recordTypeInfo?: RecordTypeInfo;
  };
  error?: string;
  message?: string;
}

/**
 * GetFields APIリクエストパラメータ
 */
export interface GetFieldsRequest {
  module: string;
  recordtype_fields?: Record<string, string>;
  recordfieldidlist?: number[];
  include_recordtype_info?: boolean;
  /** 表示モード: edit（通常編集）またはquickcreate（クイック作成） */
  view?: 'edit' | 'quickcreate';
}