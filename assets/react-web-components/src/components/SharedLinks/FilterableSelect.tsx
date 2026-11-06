import React, { useId, useMemo, useState } from "react";

export interface FilterableOption {
  value: string;
  label: string;
  /** 字下げの段数（役割の階層） */
  depth?: number;
}

interface FilterableSelectProps {
  options: FilterableOption[];
  value: string;
  onChange: (value: string) => void;
  /** 未選択のときに出す文言 */
  placeholder: string;
  /** 絞り込み欄の文言 */
  filterPlaceholder: string;
  ariaLabel: string;
  /** 選択肢に無い値（削除された項目など）を選んでいるときの表示 */
  missingLabel?: string;
  invalid?: boolean;
  style?: React.CSSProperties;
}

const baseStyle: React.CSSProperties = {
  width: "100%",
  padding: "6px 8px",
  fontSize: 13,
  border: "1px solid #CBD5E0",
  borderRadius: 4,
  boxSizing: "border-box",
};

/**
 * 名前で絞り込める選択欄
 *
 * 絞り込み欄に入力すると、選択肢を表示名の部分一致で絞る。
 * 選んでいる値は、絞り込みで外れても選択肢に残す。
 */
export const FilterableSelect: React.FC<FilterableSelectProps> = ({
  options,
  value,
  onChange,
  placeholder,
  filterPlaceholder,
  ariaLabel,
  missingLabel,
  invalid = false,
  style,
}) => {
  const [filter, setFilter] = useState("");
  const filterId = useId();

  const visibleOptions = useMemo(() => {
    const keyword = filter.trim().toLowerCase();
    if (keyword === "") return options;
    return options.filter(
      (option) =>
        option.value === value || option.label.toLowerCase().includes(keyword),
    );
  }, [filter, options, value]);

  const isMissing = value !== "" && !options.some((o) => o.value === value);
  const borderColor = invalid ? "#E53E3E" : "#CBD5E0";

  return (
    <div style={{ display: "flex", gap: 4, ...style }}>
      <input
        id={filterId}
        type="search"
        value={filter}
        placeholder={filterPlaceholder}
        aria-label={`${ariaLabel} ${filterPlaceholder}`}
        onChange={(e) => setFilter(e.target.value)}
        style={{ ...baseStyle, width: "40%", borderColor: "#CBD5E0" }}
      />
      <select
        aria-label={ariaLabel}
        aria-invalid={invalid}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        style={{ ...baseStyle, width: "60%", borderColor }}
      >
        <option value="">{placeholder}</option>
        {isMissing && <option value={value}>{missingLabel ?? value}</option>}
        {visibleOptions.map((option) => (
          <option key={option.value} value={option.value}>
            {"　".repeat(Math.max(0, (option.depth ?? 0) - 1))}
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
};
