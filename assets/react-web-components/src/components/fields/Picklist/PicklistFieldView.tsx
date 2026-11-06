import React from "react";
import { cn } from "@/lib/utils";
import type {
  PicklistEffectiveStyle,
  PicklistWebComponentOption,
} from "@/types/picklistField";
import { useOptionalTranslation } from "@/hooks/useTranslation";
import { normalizeHexColor, readableTextColor } from "./picklistValue";

export interface PicklistFieldViewProps {
  /** 項目名（ラジオグループ name 等に使う） */
  fieldName: string;
  /** 選択肢 */
  options: PicklistWebComponentOption[];
  /** 選択中の値 */
  selected: string[];
  /** 複数選択か */
  multiple: boolean;
  /** 実効スタイル（checkbox/radio/dropdown） */
  effectiveStyle: PicklistEffectiveStyle;
  /** 列数（1 or 2） */
  columns: number;
  /** 必須か（単一ラジオのクリア可否に使う） */
  mandatory: boolean;
  /** 読み取り専用 */
  disabled: boolean;
  /** 選択変更（次の選択値配列を渡す） */
  onChange: (next: string[]) => void;
}

/** 色付き選択肢ラベル。色があれば背景チップ、無ければ素のテキスト */
const OptionLabel: React.FC<{ option: PicklistWebComponentOption }> = ({
  option,
}) => {
  const hex = normalizeHexColor(option.color);
  if (!hex) {
    return <span className="text-md text-gray-800">{option.label}</span>;
  }
  return (
    <span
      className="inline-block rounded px-2 py-0.5 text-md"
      style={{ backgroundColor: hex, color: readableTextColor(hex) }}
    >
      {option.label}
    </span>
  );
};

/**
 * ピックリスト／複数選択ピックリストの表示（#1677）
 *
 * - checkbox: 複数選択。選択の追加/削除をトグル。
 * - radio   : 単一選択（排他）。必須でなければクリア可。
 * - dropdown: 保険（サーバ側で通常テンプレートに戻すため通常到達しない）。
 *             name なし select で hidden へ書き戻す。
 */
export const PicklistFieldView: React.FC<PicklistFieldViewProps> = ({
  fieldName,
  options,
  selected,
  multiple,
  effectiveStyle,
  columns,
  mandatory,
  disabled,
  onChange,
}) => {
  const { t } = useOptionalTranslation();
  const gridClass =
    columns === 2
      ? "grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5"
      : "grid grid-cols-1 gap-y-1.5";

  // 保険: 実効スタイルが dropdown（通常到達しない）
  if (effectiveStyle === "dropdown") {
    return (
      <select
        className="fr-wc-select border border-gray-300 rounded px-2 py-1 text-md"
        multiple={multiple}
        disabled={disabled}
        value={multiple ? selected : (selected[0] ?? "")}
        onChange={(e) => {
          if (multiple) {
            const next = Array.from(e.target.selectedOptions).map(
              (o) => o.value,
            );
            onChange(next);
          } else {
            onChange(e.target.value === "" ? [] : [e.target.value]);
          }
        }}
      >
        {!multiple && <option value="" />}
        {options.map((opt) => (
          <option key={opt.value} value={opt.value}>
            {opt.label}
          </option>
        ))}
      </select>
    );
  }

  const toggleCheckbox = (value: string) => {
    if (selected.includes(value)) {
      onChange(selected.filter((v) => v !== value));
    } else {
      // 選択肢の並び順を保って追加する
      const next = options
        .map((o) => o.value)
        .filter((v) => v === value || selected.includes(v));
      onChange(next);
    }
  };

  const selectRadio = (value: string) => {
    onChange([value]);
  };

  const isRadio = effectiveStyle === "radio";

  return (
    <div className="fr-wc-picklist" data-effective-style={effectiveStyle}>
      <div className={gridClass}>
        {options.map((opt) => {
          const checked = selected.includes(opt.value);
          return (
            <label
              key={opt.value}
              className={cn(
                "flex items-center gap-2 cursor-pointer select-none py-0.5",
                disabled && "cursor-not-allowed opacity-70",
              )}
            >
              <input
                type={isRadio ? "radio" : "checkbox"}
                name={isRadio ? `fr_wc_${fieldName}` : undefined}
                className="h-4 w-4 flex-shrink-0 accent-blue-600"
                checked={checked}
                disabled={disabled}
                onChange={() =>
                  isRadio ? selectRadio(opt.value) : toggleCheckbox(opt.value)
                }
              />
              <OptionLabel option={opt} />
            </label>
          );
        })}
      </div>

      {/* 単一ラジオで必須でなければ、選択をクリアできるようにする */}
      {isRadio && !mandatory && !disabled && selected.length > 0 && (
        <button
          type="button"
          className="mt-1 text-sm text-blue-600 hover:underline"
          onClick={() => onChange([])}
        >
          {t("LBL_PICKLIST_CLEAR_SELECTION")}
        </button>
      )}
    </div>
  );
};

export default PicklistFieldView;
