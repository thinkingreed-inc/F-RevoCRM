import { useState, useEffect, useCallback } from 'react';
import { FieldInfo } from '../../../types/field';
import {
  UseQuickCreateFieldsResult,
  QuickCreateFieldsResponse,
  QuickCreateFieldData,
  PicklistDependency
} from '../../../types/quickcreate';

/**
 * useQuickCreateFieldsの拡張戻り値（picklistDependency含む）
 */
export interface UseQuickCreateFieldsResultExtended extends UseQuickCreateFieldsResult {
  /** ピックリスト連動設定データ */
  picklistDependency: PicklistDependency | undefined;
  /** 翻訳されたモジュール名（例: "案件", "顧客企業"） */
  moduleLabel: string | null;
}

/**
 * QuickCreate用フィールド情報を取得するHook
 */
export function useQuickCreateFields(
  module: string,
  recordTypeFields?: Record<string, string>
): UseQuickCreateFieldsResultExtended {
  const [fields, setFields] = useState<FieldInfo[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [editViewUrl, setEditViewUrl] = useState<string | null>(null);
  const [picklistDependency, setPicklistDependency] = useState<PicklistDependency | undefined>(undefined);
  const [moduleLabel, setModuleLabel] = useState<string | null>(null);

  /**
   * APIフィールドデータをFieldInfo型に変換
   * @param apiField APIから取得したフィールドデータ
   * @param recordTypeFieldNames RecordTypeとして設定されているフィールド名の配列
   */
  const convertToFieldInfo = useCallback((apiField: QuickCreateFieldData, recordTypeFieldNames: string[]): FieldInfo => {
    return {
      name: apiField.name,
      label: apiField.label,
      uitype: apiField.uitype,
      mandatory: apiField.mandatory || false,
      readonly: apiField.readonly || false,
      maxlength: apiField.maxlength,
      // blockはトップレベルに設定（CalendarFormなどでブロック単位グループ化に使用）
      block: apiField.block,
      fieldinfo: {
        ...apiField.fieldinfo,
        type: apiField.type || 'string',
        defaultvalue: apiField.defaultValue,  // APIのdefaultValueを優先
        editable: apiField.editable,
        displaytype: apiField.displaytype,
        block: apiField.block,
        referenceModules: apiField.referenceModules
      },
      picklistValues: apiField.picklistValues?.map(item => ({
        label: item.label,
        value: item.value
      })),
      quickcreate: apiField.quickcreate,
      quickcreatesequence: apiField.quickcreatesequence,
      referenceModules: apiField.referenceModules,
      referenceModuleLabels: apiField.referenceModuleLabels,
      // CustomValidation情報を引き継ぐ
      customValidations: apiField.customValidations,
      // RecordTypeフィールドかどうか
      isRecordTypeField: recordTypeFieldNames.includes(apiField.name),
      // multireference型フィールドの識別情報
      datatype: apiField.datatype,
      isMultiple: apiField.isMultiple
    };
  }, []);

  /**
   * フィールド情報を取得
   */
  const fetchFields = useCallback(async () => {
    if (!module || !module.trim()) {
      setError('モジュール名が指定されていません');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      // APIパラメータ構築
      const params = new URLSearchParams({
        module: module,
        api: 'GetFields',
        view: 'quickcreate',
        include_recordtype_info: '1'  // 常にRecordType情報を取得
      });

      // RecordType選択値がある場合は追加
      if (recordTypeFields && Object.keys(recordTypeFields).length > 0) {
        params.append('recordtype_fields', JSON.stringify(recordTypeFields));
      }

      const response = await fetch(`?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data: QuickCreateFieldsResponse = await response.json();

      // エラーレスポンスのチェック
      if ((data as any).error || (data as any).message) {
        throw new Error((data as any).error || (data as any).message);
      }

      // フィールド情報の変換と設定
      if (data && data.fields && Array.isArray(data.fields)) {
        // RecordTypeフィールド名を抽出
        const recordTypeFieldNames: string[] = data.recordTypeInfo?.available?.map(
          (item: { fieldName: string }) => item.fieldName
        ) || [];

        const convertedFields = data.fields
          .filter(field => field.editable && field.displaytype !== '2' && !field.readonly)
          .map(field => convertToFieldInfo(field, recordTypeFieldNames));

        setFields(convertedFields);

        // 編集画面URLを構築
        setEditViewUrl(`index.php?module=${module}&view=Edit`);

        // 翻訳されたモジュール名を設定
        if (data.moduleLabel) {
          setModuleLabel(data.moduleLabel);
        } else {
          setModuleLabel(null);
        }

        // PicklistDependency情報を設定
        if (data.picklistDependency) {
          setPicklistDependency(data.picklistDependency);
        } else {
          setPicklistDependency(undefined);
        }
      } else {
        setFields([]);
        setModuleLabel(null);
        setPicklistDependency(undefined);
      }

    } catch (err) {
      console.error('QuickCreate fields fetch error:', err);
      setError(err instanceof Error ? err.message : 'フィールド情報の取得に失敗しました');
      setFields([]);
    } finally {
      setLoading(false);
    }
  }, [module, recordTypeFields, convertToFieldInfo]);

  // recordTypeFieldsを文字列化して依存配列で比較（オブジェクト参照の問題を回避）
  const recordTypeFieldsKey = JSON.stringify(recordTypeFields || {});

  // モジュール変更時またはRecordType変更時にフィールド再取得
  useEffect(() => {
    fetchFields();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [module, recordTypeFieldsKey]);

  return {
    fields,
    loading,
    error,
    refetch: fetchFields,
    editViewUrl,
    moduleLabel,
    picklistDependency
  };
}

export default useQuickCreateFields;
