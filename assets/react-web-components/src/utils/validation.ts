/**
 * フィールドバリデーションユーティリティ
 *
 * 従来のjQuery.validatorと同等のバリデーションロジックをReact版で実装
 * 参照: public/layouts/v7/modules/Vtiger/resources/validation.js
 */

import { FieldInfo, UI_TYPES } from '../types/field';

/**
 * バリデーション結果
 */
export interface ValidationResult {
  valid: boolean;
  message?: string;
}

/**
 * URLバリデーション
 * 従来のvalidation.js 488-504行目と同等
 *
 * @param value 入力値
 * @returns バリデーション結果
 */
export function validateUrl(value: string): ValidationResult {
  if (!value || !value.trim()) {
    return { valid: true };
  }

  const trimmedValue = value.trim();
  // 従来と同じ正規表現パターン
  // ドメイン形式（例: example.com, sub.example.co.jp）を必須とする
  const regexp = /(^|\s)((https?:\/\/)?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/gi;
  const result = regexp.test(trimmedValue);

  if (!result) {
    return {
      valid: false,
      message: 'JS_INVALID_URL' // 翻訳キー
    };
  }

  return { valid: true };
}

/**
 * Emailバリデーション
 * 従来のvalidation.js 381-392行目と同等
 *
 * @param value 入力値
 * @returns バリデーション結果
 */
export function validateEmail(value: string): ValidationResult {
  if (!value || !value.trim()) {
    return { valid: true };
  }

  const trimmedValue = value.trim();
  // 従来と同じ正規表現パターン
  const emailFilter = /^[_/a-zA-Z0-9*]+([!"#$%&'()*+,./:;<=>?\^_`'{|}~-]?[a-zA-Z0-9/_/-])*@[a-zA-Z0-9]+([\_\.]?[a-zA-Z0-9\-]+)*\.([\-\_]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)?$/;

  if (!emailFilter.test(trimmedValue)) {
    return {
      valid: false,
      message: 'JS_PLEASE_ENTER_VALID_EMAIL_ADDRESS'
    };
  }

  return { valid: true };
}

/**
 * 整数バリデーション
 * 従来のvalidation.js 314-327行目と同等
 *
 * @param value 入力値
 * @returns バリデーション結果
 */
export function validateInteger(value: string): ValidationResult {
  if (!value || !value.trim()) {
    return { valid: true };
  }

  const trimmedValue = value.trim();
  // 整数パターン（正負の整数）
  const integerRegex = /(^[-+]?\d+)$/;
  // 小数も整数として許容（小数点以下を無視）
  const decimalIntegerRegex = /(^[-+]?\d*).\d+$/;

  if (!trimmedValue.match(integerRegex)) {
    if (!trimmedValue.match(decimalIntegerRegex)) {
      return {
        valid: false,
        message: 'JS_PLEASE_ENTER_INTEGER_VALUE'
      };
    }
  }

  return { valid: true };
}

/**
 * 小数バリデーション
 * 従来のvalidation.js 37-60行目（double）と同等
 *
 * @param value 入力値
 * @param groupSeparator 桁区切り文字（デフォルト: ,）
 * @param decimalSeparator 小数点文字（デフォルト: .）
 * @returns バリデーション結果
 */
export function validateDouble(
  value: string,
  groupSeparator: string = ',',
  decimalSeparator: string = '.'
): ValidationResult {
  if (!value || !value.trim()) {
    return { valid: true };
  }

  let strippedValue = value.replace(decimalSeparator, '');

  // スペースが区切り文字の場合、スペースを除去
  const spacePattern = /\s/;
  if (spacePattern.test(decimalSeparator) || spacePattern.test(groupSeparator)) {
    strippedValue = strippedValue.replace(/ /g, '');
  }

  // 桁区切り文字を除去（特殊文字エスケープ）
  let escapedGroupSeparator = groupSeparator;
  if (groupSeparator === '$') {
    escapedGroupSeparator = '\\$';
  }
  const regex = new RegExp(escapedGroupSeparator, 'g');
  strippedValue = strippedValue.replace(regex, '');

  if (isNaN(Number(strippedValue))) {
    return {
      valid: false,
      message: 'JS_PLEASE_ENTER_VALID_VALUE'
    };
  }

  return { valid: true };
}

/**
 * 正の数バリデーション
 * 従来のvalidation.js 707-713行目と同等
 *
 * @param value 入力値
 * @returns バリデーション結果
 */
export function validatePositive(value: string): ValidationResult {
  if (!value || !value.trim()) {
    return { valid: true };
  }

  const numValue = Number(value);
  const negativeRegex = /(^[-]+\d+)$/;

  if (isNaN(numValue) || numValue < 0 || value.match(negativeRegex)) {
    return {
      valid: false,
      message: 'JS_ACCEPT_POSITIVE_NUMBER'
    };
  }

  return { valid: true };
}

/**
 * パーセンテージバリデーション
 * 従来のvalidation.js 727-738行目と同等
 *
 * @param value 入力値
 * @param decimalSeparator 小数点文字（デフォルト: .）
 * @returns バリデーション結果
 */
export function validatePercentage(
  value: string,
  decimalSeparator: string = '.'
): ValidationResult {
  if (!value || !value.trim()) {
    return { valid: true };
  }

  let strippedValue = value.replace(decimalSeparator, '');
  const spacePattern = /\s/;
  if (spacePattern.test(decimalSeparator)) {
    strippedValue = strippedValue.replace(/ /g, '');
  }

  if (isNaN(Number(strippedValue))) {
    return {
      valid: false,
      message: 'JS_PLEASE_ENTER_VALID_VALUE'
    };
  }

  return { valid: true };
}

/**
 * UITypeに基づいてフィールド値をバリデーション
 *
 * @param field フィールド情報
 * @param value フィールド値
 * @param t 翻訳関数
 * @returns エラーメッセージ（nullの場合はエラーなし）
 */
export function validateFieldByUIType(
  field: FieldInfo,
  value: unknown,
  t: (key: string, ...args: (string | number)[]) => string
): string | null {
  // 値が空の場合はUITypeバリデーションをスキップ（必須チェックは別途行う）
  if (value === undefined || value === null || value === '' || value === false) {
    return null;
  }

  const stringValue = String(value);
  let result: ValidationResult;

  switch (field.uitype) {
    case UI_TYPES.URL:
      result = validateUrl(stringValue);
      if (!result.valid && result.message) {
        return t(result.message);
      }
      break;

    case UI_TYPES.EMAIL:
      result = validateEmail(stringValue);
      if (!result.valid && result.message) {
        return t(result.message);
      }
      break;

    case UI_TYPES.NUMBER:
      result = validateInteger(stringValue);
      if (!result.valid && result.message) {
        return t(result.message);
      }
      break;

    case UI_TYPES.DECIMAL:
    case UI_TYPES.CURRENCY:
      result = validateDouble(stringValue);
      if (!result.valid && result.message) {
        return t(result.message);
      }
      break;

    case UI_TYPES.PERCENTAGE:
      result = validatePercentage(stringValue);
      if (!result.valid && result.message) {
        return t(result.message);
      }
      break;

    default:
      // その他のUITypeは特別なバリデーションなし
      break;
  }

  return null;
}

/**
 * フォーム全体のフィールドタイプバリデーション
 *
 * @param fields フィールドリスト
 * @param formData フォームデータ
 * @param t 翻訳関数
 * @returns フィールド名をキーとしたエラーメッセージのマップ
 */
export function validateFormFields(
  fields: FieldInfo[],
  formData: Record<string, unknown>,
  t: (key: string, ...args: (string | number)[]) => string
): Record<string, string> {
  const errors: Record<string, string> = {};

  for (const field of fields) {
    const value = formData[field.name];
    const error = validateFieldByUIType(field, value, t);
    if (error) {
      errors[field.name] = error;
    }
  }

  return errors;
}
