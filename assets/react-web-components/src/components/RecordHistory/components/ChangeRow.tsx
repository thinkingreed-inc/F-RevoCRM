import React from "react";
import { cn } from "@/lib/utils";
import type { HistoryChange, HistoryField, HistoryUser } from "@/types/history";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";
import { ChangeValue } from "./ChangeValue";
import { TextDiff } from "./TextDiff";
import { formatDelta } from "../utils/formatChange";

export interface ChangeRowProps {
  change: HistoryChange;
  field: HistoryField;
  users: Record<string, HistoryUser>;
  /** 作成時の値として表示する（変更前を出さない） */
  initial?: boolean;
  onSelectField?: (field: string) => void;
}

/** 「項目｜変更前 → 変更後」の 1 行 */
export const ChangeRow: React.FC<ChangeRowProps> = ({
  change,
  field,
  users,
  initial = false,
  onSelectField,
}) => {
  const t = useHistoryTranslation();
  const { before, after } = change;

  let body: React.ReactNode;
  if (initial) {
    body = <ChangeValue type={field.type} value={after} users={users} />;
  } else if (field.type === "text" && before && after) {
    body = <TextDiff before={before.display} after={after.display} />;
  } else if (!before) {
    body = (
      <>
        <span className="rounded-[3px] bg-emerald-50 px-1.5 text-xs font-bold leading-[16px] text-emerald-700">
          {t("LBL_HISTORY_ADDED")}
        </span>
        <ChangeValue
          type={field.type}
          value={after}
          role="after"
          users={users}
        />
      </>
    );
  } else if (!after) {
    body = (
      <>
        <span className="rounded-[3px] bg-red-50 px-1.5 text-xs font-bold leading-[16px] text-red-700">
          {t("LBL_HISTORY_REMOVED")}
        </span>
        <ChangeValue
          type={field.type}
          value={before}
          role="before"
          users={users}
        />
      </>
    );
  } else {
    const { delta } = change;
    body = (
      <>
        <ChangeValue
          type={field.type}
          value={before}
          role="before"
          users={users}
        />
        <span className="text-sm text-slate-400" aria-hidden="true">
          →
        </span>
        <ChangeValue
          type={field.type}
          value={after}
          role="after"
          users={users}
        />
        {delta && (
          <span
            className={cn(
              "font-mono text-xs tabular-nums",
              delta.sign === "+" ? "text-emerald-700" : "text-red-700",
            )}
          >
            {formatDelta(delta)}
          </span>
        )}
      </>
    );
  }

  return (
    <div className="grid grid-cols-[10em_minmax(0,1fr)] gap-x-6 gap-y-1 px-5 py-3">
      {onSelectField ? (
        <button
          type="button"
          className="cursor-pointer self-start border-0 bg-transparent p-0 pt-0.5 text-left text-sm text-slate-500 hover:text-blue-700 hover:underline"
          title={t("LBL_HISTORY_VIEW_FIELD_TRACK", field.label)}
          onClick={() => onSelectField(field.name)}
        >
          {field.label}
        </button>
      ) : (
        <span className="pt-0.5 text-sm text-slate-500">{field.label}</span>
      )}
      <div className="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1 text-base">
        {field.type === "text" && before && after && !initial ? (
          <div className="w-full">{body}</div>
        ) : (
          body
        )}
      </div>
    </div>
  );
};
