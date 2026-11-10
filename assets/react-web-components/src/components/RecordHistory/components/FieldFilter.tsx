import React, { useMemo, useState } from "react";
import { ChevronDown } from "lucide-react";
import type { HistoryField } from "@/types/history";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";

export interface FieldFilterProps {
  fields: HistoryField[];
  counts: Record<string, number>;
  selected: string[];
  onToggle: (field: string) => void;
}

/** 項目の複数選択。詳細画面の並び順に並べ、項目名で検索できる */
export const FieldFilter: React.FC<FieldFilterProps> = ({
  fields,
  counts,
  selected,
  onToggle,
}) => {
  const t = useHistoryTranslation();
  const [query, setQuery] = useState("");

  const options = useMemo(() => {
    const q = query.trim().toLowerCase();
    return fields
      .filter((f) => q === "" || f.label.toLowerCase().includes(q))
      .sort((a, b) => a.order - b.order);
  }, [fields, query]);

  return (
    <Popover onOpenChange={(open) => !open && setQuery("")}>
      <PopoverTrigger asChild>
        <button
          type="button"
          className="inline-flex h-[32px] cursor-pointer items-center gap-2 whitespace-nowrap rounded-md border border-solid border-slate-300 bg-white px-4 text-md hover:border-blue-600"
        >
          {t("LBL_HISTORY_FIELDS")}
          {selected.length > 0 && (
            <span className="rounded-full bg-blue-700 px-2 font-mono text-xs leading-[17px] text-white">
              {selected.length}
            </span>
          )}
          <ChevronDown
            className="size-[14px] text-slate-500"
            aria-hidden="true"
          />
        </button>
      </PopoverTrigger>
      <PopoverContent align="start" className="w-[260px] bg-white p-3">
        <input
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={t("LBL_HISTORY_FIELDS_SEARCH")}
          aria-label={t("LBL_HISTORY_FIELDS_SEARCH")}
          className="mb-2 h-[30px] w-full rounded border border-solid border-slate-300 px-3 text-md"
        />
        <div
          className="grid max-h-[300px] gap-0.5 overflow-y-auto"
          role="group"
          aria-label={t("LBL_HISTORY_FIELDS")}
        >
          {options.map((field) => {
            const id = `record-history-field-${field.name}`;
            return (
              <label
                key={field.name}
                htmlFor={id}
                className="m-0 grid cursor-pointer grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded px-3 py-2 text-md font-normal hover:bg-slate-50"
              >
                <Checkbox
                  id={id}
                  checked={selected.includes(field.name)}
                  onCheckedChange={() => onToggle(field.name)}
                />
                <span className="truncate">{field.label}</span>
                <span className="font-mono text-xs text-slate-400">
                  {counts[field.name] ?? 0}
                </span>
              </label>
            );
          })}
        </div>
      </PopoverContent>
    </Popover>
  );
};
