import React from "react";
import type {
  FilteredHistoryEvent,
  HistoryField,
  HistoryUser,
} from "@/types/history";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";
import { summarizeEvent } from "../utils/formatChange";
import { useHistoryFormat } from "../hooks/useHistoryFormat";
import { UserAvatar } from "./UserAvatar";

export interface CompactHistoryProps {
  events: FilteredHistoryEvent[];
  fields: Record<string, HistoryField>;
  users: Record<string, HistoryUser>;
  limit: number;
  fullHistoryUrl?: string;
}

/** 概要タブのウィジェット用。最新 limit 件を 1 行ずつ表示する */
export const CompactHistory: React.FC<CompactHistoryProps> = ({
  events,
  fields,
  users,
  limit,
  fullHistoryUrl,
}) => {
  const t = useHistoryTranslation();
  const fmt = useHistoryFormat();
  if (events.length === 0) {
    return (
      <p className="m-0 py-4 text-center text-md text-slate-500">
        {t("LBL_HISTORY_NO_UPDATES")}
      </p>
    );
  }

  return (
    <div className="grid gap-4">
      <ul className="m-0 grid list-none gap-4 p-0">
        {events.slice(0, limit).map((event) => {
          const user = users[String(event.userId)];
          const summary = summarizeEvent(event, fields, t);
          return (
            <li
              key={event.id}
              className="grid grid-cols-[24px_minmax(0,1fr)] gap-3 text-md"
              data-testid="compact-history-row"
            >
              <UserAvatar user={user} />
              <div className="min-w-0">
                <div className="flex justify-between gap-3">
                  <b className="truncate text-slate-800">
                    {user?.name || t("LBL_HISTORY_UNKNOWN_USER")}
                  </b>
                  <time
                    className="whitespace-nowrap text-xs text-slate-400"
                    dateTime={event.changedOn}
                    title={fmt.fullDateTime(event.changedOn)}
                  >
                    {fmt.relative(event.changedOn)}
                  </time>
                </div>
                <div className="truncate text-slate-500" title={summary}>
                  {summary}
                </div>
              </div>
            </li>
          );
        })}
      </ul>
      {fullHistoryUrl && events.length > 0 && (
        <a
          href={fullHistoryUrl}
          className="justify-self-end text-sm text-blue-700"
        >
          {t("LBL_HISTORY_VIEW_ALL")}
        </a>
      )}
    </div>
  );
};
