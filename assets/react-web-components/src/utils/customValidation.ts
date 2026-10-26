/**
 * カスタムバリデーション（GetFields API の customValidations）を評価する
 *
 * customValidations は Settings_CustomValidation_Module_Model がある環境（OSS 版には無い）でだけ返る。
 * body は関数本体の JavaScript で、引数 value（項目の値）・formData（フォーム全体）を受け取り、
 * 正しければ true を返す。評価中の例外は「判定できない」として通す（入力を止めない）。
 */
import type { FieldInfo, FieldValue } from "../types/field";

type CustomValidator = (
  value: FieldValue,
  formData: Record<string, FieldValue>,
) => unknown;

const compiled = new Map<string, CustomValidator | null>();

function compile(body: string): CustomValidator | null {
  if (!compiled.has(body)) {
    try {
      // 管理者が設定した検証式をそのまま評価する（GetFields が返す設定値）
      compiled.set(
        body,
        new Function("value", "formData", body) as CustomValidator,
      );
    } catch {
      compiled.set(body, null);
    }
  }
  return compiled.get(body) ?? null;
}

/**
 * @returns エラーメッセージ（問題なければ null）
 */
export function runCustomValidations(
  field: FieldInfo,
  value: FieldValue,
  formData: Record<string, FieldValue>,
): string | null {
  for (const validation of field.customValidations ?? []) {
    const validator = compile(validation.body);
    if (!validator) {
      continue;
    }
    try {
      if (validator(value, formData) === false) {
        return validation.message;
      }
    } catch {
      // 判定できない式は通す
    }
  }
  return null;
}
