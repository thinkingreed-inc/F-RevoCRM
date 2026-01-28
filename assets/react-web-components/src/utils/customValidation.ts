/**
 * CustomValidation ユーティリティ
 *
 * GetFields APIから取得したCustomValidation情報を使用して、
 * フロントエンドでバリデーションを実行する
 */

import { FieldInfo, CustomValidation, FieldValue } from '../types/field';

/**
 * バリデーション関数のキャッシュ
 * キー: validation.body（関数本体のコード）
 * 値: コンパイル済みの関数
 */
const validationFnCache = new Map<string, (value: FieldValue, formData: Record<string, FieldValue>) => boolean>();

/**
 * キャッシュから関数を取得、または新規生成してキャッシュに追加
 * @param body 関数本体のコード
 * @returns コンパイル済みの関数
 */
function getOrCreateValidationFn(body: string): (value: FieldValue, formData: Record<string, FieldValue>) => boolean {
  let fn = validationFnCache.get(body);
  if (!fn) {
    // 関数を動的に生成
    // 引数: value（入力値）, formData（フォーム全体のデータ）
    // 戻り値: true（検証OK）/ false（検証NG）
    fn = new Function('value', 'formData', body) as (value: FieldValue, formData: Record<string, FieldValue>) => boolean;
    validationFnCache.set(body, fn);
  }
  return fn;
}

/**
 * バリデーション関数キャッシュをクリア
 * （テストやモジュール再読み込み時に使用）
 */
export function clearValidationFnCache(): void {
  validationFnCache.clear();
}

/**
 * 単一のCustomValidationを実行
 * @param validation バリデーション情報
 * @param value 入力値
 * @param formData フォーム全体のデータ
 * @returns エラーメッセージ（エラーがない場合はnull）
 */
export function executeCustomValidation(
  validation: CustomValidation,
  value: FieldValue,
  formData: Record<string, FieldValue>
): string | null {
  if (!validation.body) {
    return null;
  }

  try {
    // キャッシュから関数を取得または生成
    const fn = getOrCreateValidationFn(validation.body);
    const result = fn(value, formData);

    if (result === false) {
      return validation.message || `${validation.name} バリデーションに失敗しました`;
    }

    return null;
  } catch (error) {
    console.error(`CustomValidation実行エラー (${validation.name}):`, error);
    // 実行エラーの場合はエラーを返す（安全側に倒す）
    return `バリデーション実行エラー: ${validation.name}`;
  }
}

/**
 * フィールドに設定された全CustomValidationを実行
 * @param field フィールド情報
 * @param value 入力値
 * @param formData フォーム全体のデータ
 * @returns 最初に見つかったエラーメッセージ（エラーがない場合はnull）
 */
export function applyCustomValidations(
  field: FieldInfo,
  value: FieldValue,
  formData: Record<string, FieldValue>
): string | null {
  // CustomValidationが設定されていない場合はスキップ
  if (!field.customValidations || field.customValidations.length === 0) {
    return null;
  }

  // 空値の場合はCustomValidationをスキップ（必須チェックは別途行う）
  if (value === undefined || value === null || value === '') {
    return null;
  }

  // 全てのCustomValidationを順番に実行
  for (const validation of field.customValidations) {
    const error = executeCustomValidation(validation, value, formData);
    if (error) {
      // 最初のエラーを返す
      return error;
    }
  }

  return null;
}

/**
 * フォーム全体のCustomValidationを実行
 * @param fields フィールド情報の配列
 * @param formData フォームデータ
 * @returns フィールド名をキー、エラーメッセージを値とするオブジェクト
 */
export function validateFormWithCustomValidations(
  fields: FieldInfo[],
  formData: Record<string, FieldValue>
): Record<string, string> {
  const errors: Record<string, string> = {};

  for (const field of fields) {
    const value = formData[field.name];
    const error = applyCustomValidations(field, value, formData);

    if (error) {
      errors[field.name] = error;
    }
  }

  return errors;
}

/**
 * CustomValidationが設定されているフィールドを抽出
 * @param fields フィールド情報の配列
 * @returns CustomValidationが設定されているフィールドの配列
 */
export function getFieldsWithCustomValidation(fields: FieldInfo[]): FieldInfo[] {
  return fields.filter(
    field => field.customValidations && field.customValidations.length > 0
  );
}

/**
 * デバッグ用: フィールドのCustomValidation情報を表示
 * @param field フィールド情報
 */
export function debugCustomValidation(field: FieldInfo): void {
  if (!field.customValidations || field.customValidations.length === 0) {
    console.log(`[CustomValidation] ${field.name}: なし`);
    return;
  }

  console.group(`[CustomValidation] ${field.name}`);
  field.customValidations.forEach((v, i) => {
    console.log(`  [${i}] ${v.name}: ${v.message}`);
    console.log(`      body: ${v.body.substring(0, 50)}...`);
  });
  console.groupEnd();
}
