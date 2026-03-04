/**
 * QuickCreate関連の型定義
 */

import { FieldInfo } from './field';

/**
 * QuickCreateコンポーネントのProps
 */
export interface QuickCreateProps {
  /** 対象モジュール名 */
  module: string;
  /** モーダル表示状態（外部制御用） */
  isOpen?: boolean;
  /** 初期値（関連レコードからの値等） */
  initialData?: Record<string, any>;
  /** 保存成功時コールバック */
  onSave?: (result: QuickCreateSaveResult) => void;
  /** キャンセル時コールバック */
  onCancel?: () => void;
  /** 完全フォーム遷移時コールバック */
  onGoToFullForm?: (detail: { editUrl: string; formData: Record<string, any> }) => void;
  /** モーダル表示状態変更時コールバック */
  onOpenChange?: (isOpen: boolean) => void;
}

/**
 * QuickCreateModalのProps
 */
export interface QuickCreateModalProps extends QuickCreateProps {
  /** トリガー要素（ボタン等） */
  trigger?: React.ReactNode;
}

/**
 * QuickCreateFormのProps
 */
export interface QuickCreateFormProps {
  /** 対象モジュール名 */
  module: string;
  /** フィールド一覧 */
  fields: FieldInfo[];
  /** フォームデータ */
  formData: Record<string, any>;
  /** フィールド値変更ハンドラ */
  onFieldChange: (fieldName: string, value: any) => void;
  /** RecordType変更ハンドラ（RecordTypeフィールドの場合のみ呼ばれる） */
  onRecordTypeChange?: (fieldName: string, value: string) => void;
  /** 保存中フラグ */
  isSaving?: boolean;
  /** 無効化フラグ */
  disabled?: boolean;
  /** バリデーションエラー */
  errors?: Record<string, string>;
}

/**
 * QuickCreateFooterのProps
 */
export interface QuickCreateFooterProps {
  /** モジュール名 */
  module: string;
  /** 保存ハンドラ */
  onSave: () => void;
  /** キャンセルハンドラ */
  onCancel: () => void;
  /** 完全フォーム遷移ハンドラ */
  onGoToFullForm: () => void;
  /** 保存中フラグ */
  isSaving?: boolean;
  /** 保存ボタン無効化フラグ */
  saveDisabled?: boolean;
  /** 編集モードフラグ */
  isEditMode?: boolean;
}

/**
 * QuickCreate保存結果
 */
export interface QuickCreateSaveResult {
  /** 成功フラグ */
  success: boolean;
  /** 作成されたレコードID */
  recordId?: string;
  /** レコードラベル（名前等） */
  recordLabel?: string;
  /** モジュール名 */
  module: string;
  /** 詳細画面URL */
  detailViewUrl?: string;
  /** エラーメッセージ（失敗時） */
  error?: string;
}

/**
 * useQuickCreateFieldsの戻り値
 */
export interface UseQuickCreateFieldsResult {
  /** フィールド一覧 */
  fields: FieldInfo[];
  /** 読み込み中フラグ */
  loading: boolean;
  /** エラーメッセージ */
  error: string | null;
  /** フィールド再取得 */
  refetch: () => Promise<void>;
  /** 編集画面URL */
  editViewUrl: string | null;
  /** 翻訳されたモジュール名（例: "案件", "顧客企業"） */
  moduleLabel: string | null;
}

/**
 * useQuickCreateSaveの戻り値
 */
export interface UseQuickCreateSaveResult {
  /** 保存実行 */
  save: (formData: Record<string, any>) => Promise<QuickCreateSaveResult>;
  /** 保存中フラグ */
  isSaving: boolean;
  /** エラーメッセージ */
  error: string | null;
  /** エラークリア */
  clearError: () => void;
}

/**
 * useQuickCreateの戻り値（統合Hook）
 */
export interface UseQuickCreateResult {
  /** フィールド一覧 */
  fields: FieldInfo[];
  /** フォームデータ */
  formData: Record<string, any>;
  /** フォームデータ設定 */
  setFormData: React.Dispatch<React.SetStateAction<Record<string, any>>>;
  /** フィールド値変更ハンドラ */
  handleFieldChange: (fieldName: string, value: any) => void;
  /** 保存実行 */
  handleSave: () => Promise<QuickCreateSaveResult | null>;
  /** フィールド読み込み中 */
  isLoading: boolean;
  /** 保存中 */
  isSaving: boolean;
  /** エラー */
  error: string | null;
  /** バリデーションエラー */
  validationErrors: Record<string, string>;
  /** 編集画面URL */
  editViewUrl: string | null;
}

/**
 * GetFields APIレスポンス（QuickCreate用）
 */
export interface QuickCreateFieldsResponse {
  module: string;
  /** 翻訳されたモジュール名（例: "案件", "顧客企業"） */
  moduleLabel?: string;
  totalFields: number;
  fields: QuickCreateFieldData[];
  timestamp: string;
  recordTypeInfo?: {
    available: Array<{
      fieldName: string;
      values: Array<{
        value: string;
        recordFieldId: number;
      }>;
    }>;
    applied: Record<string, string>;
    recordFieldIdList: number[];
  };
  /** ピックリスト連動設定データ */
  picklistDependency?: PicklistDependency;
}

/**
 * ピックリスト連動設定の型定義
 *
 * 構造:
 * {
 *   "sourceFieldName": {
 *     "sourceValue1": {
 *       "targetFieldName": ["allowedValue1", "allowedValue2"]
 *     },
 *     "__DEFAULT__": {
 *       "targetFieldName": ["allValues..."]
 *     }
 *   }
 * }
 */
export interface PicklistDependency {
  [sourceField: string]: {
    [sourceValue: string]: {
      [targetField: string]: string[] | PicklistDependencyCondition[];
    };
  };
}

/**
 * 条件付きピックリスト連動設定
 * 複数のソースフィールドに依存する場合に使用
 */
export interface PicklistDependencyCondition {
  condition: Record<string, string[]>;
  values: string[];
}

/**
 * GetFields APIのフィールドデータ
 */
export interface QuickCreateFieldData {
  name: string;
  label: string;
  uitype: string;
  type: string;
  mandatory: boolean;
  readonly: boolean;
  editable: boolean;
  displaytype: string;
  block: string;
  maxlength?: number;
  defaultValue?: any;
  fieldinfo?: Record<string, any>;
  picklistValues?: Array<{
    value: string;
    label: string;
  }>;
  referenceModules?: string[];
  /** 参照先モジュールの翻訳ラベル（モジュール名 -> 翻訳ラベルのマップ） */
  referenceModuleLabels?: Record<string, string>;
  quickcreate: boolean;
  quickcreatesequence: number | null;
  /** カスタムバリデーション設定 */
  customValidations?: Array<{
    name: string;
    body: string;
    message: string;
  }>;
  /** フィールドデータタイプ（'multireference' など） */
  datatype?: string;
  /** 複数選択可能かどうか（multireference型フィールドなど） */
  isMultiple?: boolean;
}

/**
 * Save APIリクエスト
 */
export interface QuickCreateSaveRequest {
  module: string;
  record?: string;
  [fieldName: string]: any;
}

/**
 * Save APIレスポンス
 */
export interface QuickCreateSaveResponse {
  _recordId?: string;
  _recordLabel?: string;
  _detailViewUrl?: string;
  [key: string]: any;
}

/**
 * バリデーション関数の型
 */
export type ValidateFieldFn = (
  field: FieldInfo,
  value: any,
  formData: Record<string, any>
) => string | null;

/**
 * バリデーション設定
 */
export interface ValidationConfig {
  /** カスタムバリデーション関数 */
  customValidators?: Record<string, ValidateFieldFn>;
  /** 必須チェックをスキップするフィールド */
  skipMandatoryFields?: string[];
}
