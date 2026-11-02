import React, { useState } from "react";
import { cn } from "@/lib/utils";
import type {
  FilteredHistoryEvent,
  HistoryField,
  HistoryUser,
} from "@/types/history";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";
import { useHistoryFormat } from "../hooks/useHistoryFormat";
import { ChangeRow } from "./ChangeRow";
import { UserAvatar } from "./UserAvatar";

export interface HistoryEventItemProps {
  event: FilteredHistoryEvent;
  fields: Record<string, HistoryField>;
  users: Record<string, HistoryUser>;
  /** 絞り込み中か（作成時の値を最初から開く） */
  filtering: boolean;
  onSelectField: (field: string) => void;
}

/** タイムラインの 1 イベント（左: 日時 / 中: アイコン / 右: 内容） */
export const HistoryEventItem: React.FC<HistoryEventItemProps> = ({
  event,
  fields,
  users,
  filtering,
  onSelectField,
}) => {
  const t = useHistoryTranslation();
  const [initialOpen, setInitialOpen] = useState(false);
  const user = users[String(event.userId)];
  const fmt = useHistoryFormat();
  const userName = user?.name || t("LBL_HISTORY_UNKNOWN_USER");

  const renderChanges = (initial: boolean) => (
    <div className="divide-y divide-slate-200 overflow-hidden rounded-md border border-solid border-slate-200">
      {event.visibleChanges.map((change) => (
        <ChangeRow
          key={change.field}
          change={change}
          field={fields[change.field]}
          users={users}
          initial={initial}
          onSelectField={onSelectField}
        />
      ))}
    </div>
  );

  let body: React.ReactNode = null;
  if (event.type === "update") {
    body = renderChanges(false);
  } else if (event.type === "create") {
    const open = initialOpen || filtering;
    body = (
      <>
        {open && renderChanges(true)}
        {!filtering && (
          <button
            type="button"
            className="cursor-pointer justify-self-start border-0 bg-transparent p-0 text-sm text-blue-700 underline underline-offset-2"
            aria-expanded={open}
            onClick={() => setInitialOpen((v) => !v)}
          >
            {open
              ? t("LBL_HISTORY_HIDE_INITIAL")
              : t("LBL_HISTORY_SHOW_INITIAL", event.visibleChanges.length)}
          </button>
        )}
      </>
    );
  } else if (event.related) {
    body = (
      <div className="flex flex-wrap items-center gap-3 rounded-md border border-solid border-slate-200 px-5 py-3 text-base">
        <span className="rounded-full bg-slate-100 px-3 text-sm leading-[20px]">
          {event.related.moduleLabel}
        </span>
        {event.related.url ? (
          <a href={event.related.url} className="font-medium text-blue-700">
            {event.related.label}
          </a>
        ) : (
          <span className="font-medium">{event.related.label}</span>
        )}
      </div>
    );
  }

  return (
    <article
      className="grid grid-cols-[64px_32px_minmax(0,1fr)] gap-x-5 py-5"
      data-testid="history-event"
      data-event-id={event.id}
    >
      {/* vtiger 本体の .block（詳細ブロックの枠線）と衝突するため block ユーティリティは使わない */}
      <div className="grid content-start justify-items-end pt-0.5 font-mono leading-tight tabular-nums">
        <span className="text-[15px] font-medium text-slate-800">
          {fmt.monthDay(event.changedOn)}
        </span>
        <span className="font-sans text-xs text-slate-500">
          （{fmt.weekday(event.changedOn)}）
        </span>
        <span className="mt-0.5 text-xs text-slate-500">
          {fmt.time(event.changedOn)}
        </span>
      </div>
      <UserAvatar user={user} size="lg" />
      <div className="grid min-w-0 gap-3">
        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
          <span className="text-base font-bold text-slate-800">{userName}</span>
          <span className="text-base text-slate-500">
            {t(`LBL_HISTORY_VERB_${event.type}`)}
          </span>
          <span
            className={cn(
              "rounded-[3px] border border-solid px-1.5 text-xs font-bold leading-[18px]",
              event.type === "update" && "border-slate-300 text-slate-500",
              event.type === "create" && "border-emerald-600 text-emerald-700",
              (event.type === "link" || event.type === "restore") &&
                "border-blue-600 text-blue-700",
              (event.type === "unlink" || event.type === "delete") &&
                "border-red-600 text-red-700",
            )}
          >
            {event.type === "update"
              ? t("LBL_HISTORY_N_FIELDS", event.visibleChanges.length)
              : t(`LBL_HISTORY_TYPE_${event.type}`)}
          </span>
          <time
            className="ml-auto text-sm text-slate-400"
            dateTime={event.changedOn}
            title={fmt.fullDateTime(event.changedOn)}
          >
            {fmt.relative(event.changedOn)}
          </time>
        </div>
        {body}
      </div>
    </article>
  );
};
