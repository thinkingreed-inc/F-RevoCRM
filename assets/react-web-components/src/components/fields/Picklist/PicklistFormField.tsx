import React from "react";
import { cn } from "@/lib/utils";
import type {
  PicklistEffectiveStyle,
  PicklistWebComponentOption,
} from "@/types/picklistField";
import { PicklistFieldView } from "./PicklistFieldView";
import { parseSelected, serializeSelected } from "./picklistValue";

/**
 * Props（Reactフォーム経路 = FieldRenderer 用）
 *
 * FieldRenderer から渡される値は文字列（複数は ' |##| ' 連結）。
 * 内部で parseSelected/serializeSelected を介して PicklistFieldView と橋渡しする。
 */
export interface PicklistFormFieldProps {
  /** フィールド名 */
  name: string;
  /** ラベル */
  label: string;
  /** 現在値（複数は ' |##| ' 連結文字列） */
  value?: string;
  /** 値変更（FieldRenderer と同じ (name, value:string) シグネチャ） */
  onChange: (name: string, value: string) => void;
  /** 選択肢（色付き） */
  options: PicklistWebComponentOption[];
  /** 実効スタイル（checkbox/radio） */
  effectiveStyle: PicklistEffectiveStyle;
  /** 列数（1 or 2） */
  columns: number;
  /** 複数選択か（uitype 33） */
  multiple: boolean;
  /** 必須か */
  mandatory?: boolean;
  /** 無効化 */
  disabled?: boolean;
  /** エラーメッセージ */
  error?: string;
  /** カスタムクラス */
  className?: string;
  /** ラベルのカスタムクラス名（縦並び時の左寄せ等） */
  labelClassName?: string;
}

/**
 * PicklistFormField - Reactフォーム（QuickCreate 等）で #1677 の
 * チェックボックス/ラジオ表示・色・列数を描画するフィールド。
 *
 * 描画本体は経路A（Smarty編集画面）と同じ PicklistFieldView を再利用し、
 * ラベル/エラーの枠は既存の PicklistField と同じ見た目に合わせる。
 */
export const PicklistFormField: React.FC<PicklistFormFieldProps> = ({
  name,
  label,
  value,
  onChange,
  options,
  effectiveStyle,
  columns,
  multiple,
  mandatory = false,
  disabled = false,
  error,
  className,
  labelClassName,
}) => {
  const selected = parseSelected(value, multiple);

  const handleChange = (next: string[]) => {
    onChange(name, serializeSelected(next, multiple));
  };

  return (
    <div className={cn("flex items-start gap-2", className)}>
      {labelClassName ? (
        <div className="flex items-baseline md:contents">
          <span
            className={cn(
              "text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]",
              disabled && "text-gray-400",
              labelClassName,
            )}
          >
            {label}
            {mandatory && (
              <span className="text-red-500" aria-hidden="true">
                *
              </span>
            )}
            {mandatory && <span className="sr-only"> (必須)</span>}
          </span>
          <span
            className="w-3 flex-shrink-0 hidden md:block"
            aria-hidden="true"
          />
        </div>
      ) : (
        <>
          <span
            className={cn(
              "text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]",
              disabled && "text-gray-400",
            )}
          >
            {label}
            {mandatory && <span className="sr-only"> (必須)</span>}
          </span>
          <span
            className="w-3 leading-[30px] text-red-500 text-center flex-shrink-0"
            aria-hidden="true"
          >
            {mandatory ? "*" : ""}
          </span>
        </>
      )}

      {/* 入力エリア */}
      <div className="flex-1 min-w-0">
        <PicklistFieldView
          fieldName={name}
          options={options}
          selected={selected}
          multiple={multiple}
          effectiveStyle={effectiveStyle}
          columns={columns}
          mandatory={mandatory}
          disabled={disabled}
          onChange={handleChange}
        />

        {/* 送信用 hidden（標準フォームと同様、値を name で送る） */}
        <input type="hidden" name={name} value={value || ""} />

        {/* エラーメッセージ */}
        {error && (
          <div className="mt-1 text-sm text-red-600 flex items-center">
            <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path
                fillRule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
            {error}
          </div>
        )}
      </div>
    </div>
  );
};

export default PicklistFormField;
