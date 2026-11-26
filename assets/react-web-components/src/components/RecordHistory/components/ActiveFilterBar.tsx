import React from "react";
import { X } from "lucide-react";
import type {
  HistoryField,
  HistoryFilterCriteria,
  HistoryUser,
} from "@/types/history";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";

export interface ActiveFilterBarProps {
  criteria: HistoryFilterCriteria;
  fields: Record<string, HistoryField>;
  users: Record<string, HistoryUser>;
  shownCount: number;
  totalCount: number;
  onKeywordChange: (keyword: string) => void;
  onToggleField: (field: string) => void;
  onToggleUser: (userId: number) => void;
  onClearTypes: () => void;
  onClear: () => void;
}

const Chip: React.FC<{
  label: string;
  removeLabel: string;
  onRemove: () => void;
}> = ({ label, removeLabel, onRemove }) => (
  <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 py-px pl-3 pr-1 font-medium text-blue-700">
    {label}
    <button
      type="button"
      aria-label={removeLabel}
      onClick={onRemove}
      className="inline-flex size-[18px] cursor-pointer items-center justify-center rounded-full border-0 bg-transparent p-0 text-current hover:bg-white"
    >
      <X className="size-[12px]" aria-hidden="true" />
    </button>
  </span>
);

/** 件数と、適用中の絞り込み条件（個別に外せる） */
export const ActiveFilterBar: React.FC<ActiveFilterBarProps> = ({
  criteria,
  fields,
  users,
  shownCount,
  totalCount,
  onKeywordChange,
  onToggleField,
  onToggleUser,
  onClearTypes,
  onClear,
}) => {
  const t = useHistoryTranslation();
  const chips: React.ReactNode[] = [];

  criteria.fields.forEach((name) => {
    const label = fields[name]?.label ?? name;
    chips.push(
      <Chip
        key={`f-${name}`}
        label={label}
        removeLabel={t("LBL_HISTORY_REMOVE_FILTER", label)}
        onRemove={() => onToggleField(name)}
      />,
    );
  });
  criteria.userIds.forEach((id) => {
    const label = users[String(id)]?.name || t("LBL_HISTORY_UNKNOWN_USER");
    chips.push(
      <Chip
        key={`u-${id}`}
        label={label}
        removeLabel={t("LBL_HISTORY_REMOVE_FILTER", label)}
        onRemove={() => onToggleUser(id)}
      />,
    );
  });
  if (criteria.types.length > 0) {
    const label = criteria.types.includes("link")
      ? t("LBL_HISTORY_TYPE_RELATION")
      : t(`LBL_HISTORY_TYPE_${criteria.types[0]}`);
    chips.push(
      <Chip
        key="types"
        label={label}
        removeLabel={t("LBL_HISTORY_REMOVE_FILTER", label)}
        onRemove={onClearTypes}
      />,
    );
  }
  if (criteria.keyword.trim()) {
    const label = t("LBL_HISTORY_KEYWORD_CHIP", criteria.keyword.trim());
    chips.push(
      <Chip
        key="q"
        label={label}
        removeLabel={t("LBL_HISTORY_REMOVE_FILTER", label)}
        onRemove={() => onKeywordChange("")}
      />,
    );
  }

  return (
    <div
      className="flex flex-wrap items-center gap-2 border-0 border-b border-solid border-slate-200 px-5 py-3 text-sm text-slate-500"
      aria-live="polite"
    >
      <span>{t("LBL_HISTORY_RESULT_COUNT", shownCount, totalCount)}</span>
      {chips.length > 0 && (
        <>
          <span aria-hidden="true">·</span>
          {chips}
          <button
            type="button"
            onClick={onClear}
            className="cursor-pointer border-0 bg-transparent p-0 text-sm text-blue-700 underline underline-offset-2"
          >
            {t("LBL_HISTORY_CLEAR_FILTERS")}
          </button>
        </>
      )}
    </div>
  );
};
