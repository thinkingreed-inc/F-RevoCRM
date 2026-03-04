import { useMemo, useCallback } from 'react';
import { FieldInfo } from '../../../types/field';
import { PicklistDependency, PicklistDependencyCondition } from '../../../types/quickcreate';

/**
 * ピックリスト連動設定を適用するHookの戻り値
 */
export interface UsePicklistDependencyResult {
  /**
   * フィールドに連動設定を適用して、フィルタされた選択肢を持つフィールドを返す
   */
  getFilteredFields: (
    fields: FieldInfo[],
    formData: Record<string, any>
  ) => FieldInfo[];

  /**
   * 指定したソースフィールドの変更によって影響を受けるターゲットフィールド名を取得
   */
  getAffectedTargetFields: (sourceFieldName: string) => string[];

  /**
   * 指定した値が連動設定で許可されているかをチェック
   * @param targetFieldName - チェック対象のフィールド名
   * @param value - チェックする値
   * @param formData - 現在のフォームデータ
   * @returns 許可されている場合はtrue、連動設定がない場合もtrue
   */
  isValueAllowed: (
    targetFieldName: string,
    value: any,
    formData: Record<string, any>
  ) => boolean;

  /**
   * ソースフィールド変更時にクリアが必要なターゲットフィールドを取得
   * @param sourceFieldName - 変更されたソースフィールド名
   * @param newSourceValue - 新しいソースフィールドの値
   * @param formData - 現在のフォームデータ
   * @returns クリアが必要なフィールド名の配列
   */
  getFieldsToClear: (
    sourceFieldName: string,
    newSourceValue: any,
    formData: Record<string, any>
  ) => string[];

  /**
   * 連動設定が存在するかどうか
   */
  hasDependency: boolean;
}

/**
 * ピックリスト連動設定を適用するHook
 *
 * @param picklistDependency - GetFields APIから取得した連動設定データ
 * @returns 連動設定を適用するためのユーティリティ
 */
export function usePicklistDependency(
  picklistDependency: PicklistDependency | undefined
): UsePicklistDependencyResult {

  /**
   * 連動設定が存在するかどうか
   */
  const hasDependency = useMemo(() => {
    return picklistDependency !== undefined && Object.keys(picklistDependency).length > 0;
  }, [picklistDependency]);

  /**
   * 指定したソースフィールドの変更によって影響を受けるターゲットフィールド名を取得
   */
  const getAffectedTargetFields = useCallback((sourceFieldName: string): string[] => {
    if (!picklistDependency || !picklistDependency[sourceFieldName]) {
      return [];
    }

    const targetFields = new Set<string>();
    const sourceConfig = picklistDependency[sourceFieldName];

    // 全てのソース値に対するターゲットフィールドを収集
    Object.values(sourceConfig).forEach(targetConfig => {
      Object.keys(targetConfig).forEach(targetField => {
        targetFields.add(targetField);
      });
    });

    return Array.from(targetFields);
  }, [picklistDependency]);

  /**
   * 条件付き連動設定（PicklistDependencyCondition[]）から
   * formDataに一致する値を取得
   */
  const getValuesFromConditions = useCallback((
    conditions: PicklistDependencyCondition[],
    formData: Record<string, any>
  ): string[] | null => {
    for (const cond of conditions) {
      // 全ての条件が一致するかチェック
      const allMatch = Object.entries(cond.condition).every(([fieldName, allowedValues]) => {
        const currentValue = formData[fieldName];
        return allowedValues.includes(currentValue);
      });

      if (allMatch) {
        return cond.values;
      }
    }
    return null;
  }, []);

  /**
   * 指定したターゲットフィールドに対して許可される値を取得
   */
  const getAllowedValues = useCallback((
    targetFieldName: string,
    formData: Record<string, any>
  ): string[] | null => {
    if (!picklistDependency) {
      return null;
    }

    // 各ソースフィールドをチェック
    for (const [sourceField, sourceConfig] of Object.entries(picklistDependency)) {
      const sourceValue = formData[sourceField];

      // ソースフィールドの値に対応する設定を取得
      const targetConfig = sourceConfig[sourceValue] || sourceConfig['__DEFAULT__'];

      if (!targetConfig || !(targetFieldName in targetConfig)) {
        continue;
      }

      const targetValues = targetConfig[targetFieldName];

      // 条件付きの場合
      if (Array.isArray(targetValues) && targetValues.length > 0) {
        const firstItem = targetValues[0];

        // 条件付き設定（オブジェクトの配列）かどうかを判定
        if (typeof firstItem === 'object' && firstItem !== null && 'condition' in firstItem) {
          const conditionValues = getValuesFromConditions(
            targetValues as PicklistDependencyCondition[],
            formData
          );
          if (conditionValues !== null) {
            return conditionValues;
          }
          // 条件に一致しない場合はDEFAULTを使用
          const defaultConfig = sourceConfig['__DEFAULT__'];
          if (defaultConfig && targetFieldName in defaultConfig) {
            const defaultValues = defaultConfig[targetFieldName];
            if (Array.isArray(defaultValues) && typeof defaultValues[0] === 'string') {
              return defaultValues as string[];
            }
          }
        } else {
          // 単純な文字列配列
          return targetValues as string[];
        }
      }
    }

    return null;
  }, [picklistDependency, getValuesFromConditions]);

  /**
   * フィールドに連動設定を適用して、フィルタされた選択肢を持つフィールドを返す
   */
  const getFilteredFields = useCallback((
    fields: FieldInfo[],
    formData: Record<string, any>
  ): FieldInfo[] => {
    if (!hasDependency) {
      return fields;
    }

    return fields.map(field => {
      // ピックリストフィールドでない場合はそのまま返す
      if (!field.picklistValues || field.picklistValues.length === 0) {
        return field;
      }

      // このフィールドに対する許可値を取得
      const allowedValues = getAllowedValues(field.name, formData);

      // 許可値がない（連動設定がない）場合はそのまま返す
      if (allowedValues === null) {
        return field;
      }

      // 許可された値のみにフィルタリング
      const filteredPicklistValues = field.picklistValues.filter(option =>
        allowedValues.includes(option.value) || option.value === ''
      );

      // フィルタ結果を適用した新しいフィールドオブジェクトを返す
      return {
        ...field,
        picklistValues: filteredPicklistValues
      };
    });
  }, [hasDependency, getAllowedValues]);

  /**
   * 指定した値が連動設定で許可されているかをチェック
   */
  const isValueAllowed = useCallback((
    targetFieldName: string,
    value: any,
    formData: Record<string, any>
  ): boolean => {
    // 空値は常に許可
    if (value === undefined || value === null || value === '') {
      return true;
    }

    const allowedValues = getAllowedValues(targetFieldName, formData);

    // 許可値がない（連動設定がない）場合は許可
    if (allowedValues === null) {
      return true;
    }

    return allowedValues.includes(value);
  }, [getAllowedValues]);

  /**
   * ソースフィールド変更時にクリアが必要なターゲットフィールドを取得
   */
  const getFieldsToClear = useCallback((
    sourceFieldName: string,
    newSourceValue: any,
    formData: Record<string, any>
  ): string[] => {
    if (!hasDependency) {
      return [];
    }

    const affectedFields = getAffectedTargetFields(sourceFieldName);
    const fieldsToClear: string[] = [];

    // 新しいソース値を反映したformDataを作成
    const updatedFormData = {
      ...formData,
      [sourceFieldName]: newSourceValue
    };

    for (const targetFieldName of affectedFields) {
      const currentValue = formData[targetFieldName];

      // 現在値が空の場合はクリア不要
      if (currentValue === undefined || currentValue === null || currentValue === '') {
        continue;
      }

      // 新しいソース値で、現在の値が許可されるかチェック
      const allowedValues = getAllowedValues(targetFieldName, updatedFormData);

      // 許可値がない（連動設定がない）場合はクリア不要
      if (allowedValues === null) {
        continue;
      }

      // 現在値が許可リストに含まれない場合はクリア対象
      if (!allowedValues.includes(currentValue)) {
        fieldsToClear.push(targetFieldName);
      }
    }

    return fieldsToClear;
  }, [hasDependency, getAffectedTargetFields, getAllowedValues]);

  return {
    getFilteredFields,
    getAffectedTargetFields,
    isValueAllowed,
    getFieldsToClear,
    hasDependency
  };
}

export default usePicklistDependency;
