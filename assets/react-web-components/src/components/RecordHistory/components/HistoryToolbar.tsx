import React from "react";
import { Search } from "lucide-react";
import { cn } from "@/lib/utils";
import type {
  HistoryEventType,
  HistoryField,
  HistoryFilterCriteria,
  HistoryUser,
} from "@/types/history";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";
import { FieldFilter } from "./FieldFilter";
import { UserAvatar } from "./UserAvatar";

export type HistoryViewMode = "timeline" | "field";

/** 種別の切り替え。関連付けと関連解除は「関連」にまとめる */
const TYPE_OPTIONS: {
  key: string;
  labelKey: string;
  types: HistoryEventType[];
}[] = [
  { key: "all", labelKey: "LBL_HISTORY_TYPE_ALL", types: [] },
  { key: "update", labelKey: "LBL_HISTORY_TYPE_update", types: ["update"] },
  { key: "create", labelKey: "LBL_HISTORY_TYPE_create", types: ["create"] },
  {
    key: "relation",
    labelKey: "LBL_HISTORY_TYPE_RELATION",
    types: ["link", "unlink"],
  },
];

const sameTypes = (a: HistoryEventType[], b: HistoryEventType[]) =>
  a.length === b.length && a.every((v) => b.includes(v));

const segmentButton = (pressed: boolean) =>
  cn(
    "h-[30px] cursor-pointer border-0 px-4 text-md [&+&]:border-l [&+&]:border-solid [&+&]:border-slate-300",
    pressed
      ? "bg-blue-50 font-bold text-blue-700"
      : "bg-white text-slate-700 hover:bg-slate-50",
  );

export interface HistoryToolbarProps {
  criteria: HistoryFilterCriteria;
  fields: HistoryField[];
  fieldCounts: Record<string, number>;
  users: HistoryUser[];
  view: HistoryViewMode;
  onKeywordChange: (keyword: string) => void;
  onToggleField: (field: string) => void;
  onToggleUser: (userId: number) => void;
  onTypesChange: (types: HistoryEventType[]) => void;
  onShowSystemFieldsChange: (show: boolean) => void;
  onViewChange: (view: HistoryViewMode) => void;
}

export const HistoryToolbar: React.FC<HistoryToolbarProps> = ({
  criteria,
  fields,
  fieldCounts,
  users,
  view,
  onKeywordChange,
  onToggleField,
  onToggleUser,
  onTypesChange,
  onShowSystemFieldsChange,
  onViewChange,
}) => {
  const t = useHistoryTranslation();

  return (
    <div className="flex flex-wrap items-center gap-x-6 gap-y-3 border-0 border-b border-solid border-slate-200 bg-slate-50 px-5 py-4">
      <label className="relative m-0 min-w-0 max-w-[280px] flex-[1_1_200px]">
        <Search
          className="pointer-events-none absolute left-[9px] top-[9px] size-[14px] text-slate-400"
          aria-hidden="true"
        />
        <input
          type="search"
          value={criteria.keyword}
          onChange={(e) => onKeywordChange(e.target.value)}
          placeholder={t("LBL_HISTORY_SEARCH")}
          aria-label={t("LBL_HISTORY_SEARCH")}
          className="h-[32px] w-full rounded-md border border-solid border-slate-300 bg-white pl-[30px] pr-3 text-md"
        />
      </label>

      <FieldFilter
        fields={fields}
        counts={fieldCounts}
        selected={criteria.fields}
        onToggle={onToggleField}
      />

      {users.length > 1 && (
        <div
          className="flex flex-wrap items-center gap-2"
          role="group"
          aria-label={t("LBL_HISTORY_USERS")}
        >
          <span className="text-sm text-slate-500">
            {t("LBL_HISTORY_USERS")}
          </span>
          {users.map((user) => {
            const pressed = criteria.userIds.includes(user.id);
            return (
              <button
                key={user.id}
                type="button"
                aria-pressed={pressed}
                onClick={() => onToggleUser(user.id)}
                className={cn(
                  "inline-flex h-[30px] cursor-pointer items-center gap-1.5 rounded-full border border-solid py-0 pl-[3px] pr-3 text-sm",
                  pressed
                    ? "border-blue-600 bg-blue-50 font-bold text-blue-700"
                    : "border-slate-300 bg-white text-slate-700 hover:border-blue-600",
                )}
              >
                <UserAvatar user={user} size="md" />
                {user.name || t("LBL_HISTORY_UNKNOWN_USER")}
              </button>
            );
          })}
        </div>
      )}

      <div
        className="inline-flex overflow-hidden rounded-md border border-solid border-slate-300"
        role="group"
      >
        {TYPE_OPTIONS.map((option) => (
          <button
            key={option.key}
            type="button"
            aria-pressed={sameTypes(criteria.types, option.types)}
            className={segmentButton(sameTypes(criteria.types, option.types))}
            onClick={() => onTypesChange(option.types)}
          >
            {t(option.labelKey)}
          </button>
        ))}
      </div>

      <label
        className="m-0 inline-flex cursor-pointer items-center gap-2 text-sm font-normal text-slate-600"
        title={t("LBL_HISTORY_SYSTEM_FIELDS_HINT")}
      >
        <input
          type="checkbox"
          checked={criteria.showSystemFields}
          onChange={(e) => onShowSystemFieldsChange(e.target.checked)}
          className="m-0 accent-blue-700"
        />
        {t("LBL_HISTORY_SHOW_SYSTEM_FIELDS")}
      </label>

      <div
        className="ml-auto inline-flex overflow-hidden rounded-md border border-solid border-slate-300"
        role="group"
      >
        {(["timeline", "field"] as const).map((mode) => (
          <button
            key={mode}
            type="button"
            aria-pressed={view === mode}
            className={segmentButton(view === mode)}
            onClick={() => onViewChange(mode)}
          >
            {t(
              mode === "timeline"
                ? "LBL_HISTORY_VIEW_TIMELINE"
                : "LBL_HISTORY_VIEW_FIELD",
            )}
          </button>
        ))}
      </div>
    </div>
  );
};
