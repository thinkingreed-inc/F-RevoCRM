import React, { useEffect, useMemo, useState } from "react";
import type {
  FilteredHistoryEvent,
  HistoryField,
  HistoryUser,
} from "@/types/history";
import { Button } from "@/components/ui/button";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";
import { groupByMonth } from "../utils/historyDate";
import { HistoryEventItem } from "./HistoryEventItem";

export interface HistoryTimelineProps {
  events: FilteredHistoryEvent[];
  fields: Record<string, HistoryField>;
  users: Record<string, HistoryUser>;
  pageSize: number;
  filtering: boolean;
  onSelectField: (field: string) => void;
  onClearFilters: () => void;
}

/** 月ごとの見出し付きタイムライン。表示件数はクライアント側で段階的に増やす */
export const HistoryTimeline: React.FC<HistoryTimelineProps> = ({
  events,
  fields,
  users,
  pageSize,
  filtering,
  onSelectField,
  onClearFilters,
}) => {
  const t = useHistoryTranslation();
  const [visibleCount, setVisibleCount] = useState(pageSize);

  // 条件が変わったら表示件数を戻す
  useEffect(() => setVisibleCount(pageSize), [events, pageSize]);

  const visible = useMemo(
    () => events.slice(0, visibleCount),
    [events, visibleCount],
  );
  const groups = useMemo(() => groupByMonth(visible), [visible]);
  // 見出しの件数は、表示中の分ではなく条件に合う全件で数える
  const monthTotals = useMemo(() => {
    const totals: Record<string, number> = {};
    for (const e of events) {
      const key = e.changedOn.slice(0, 7);
      totals[key] = (totals[key] ?? 0) + 1;
    }
    return totals;
  }, [events]);

  if (events.length === 0) {
    return (
      <div className="grid justify-items-center gap-2 px-5 py-16 text-center text-slate-500">
        <b className="text-base text-slate-700">
          {filtering ? t("LBL_HISTORY_NO_MATCH") : t("LBL_HISTORY_NO_UPDATES")}
        </b>
        {filtering && (
          <>
            <span className="text-sm">{t("LBL_HISTORY_NO_MATCH_HINT")}</span>
            <Button variant="link" size="sm" onClick={onClearFilters}>
              {t("LBL_HISTORY_CLEAR_FILTERS")}
            </Button>
          </>
        )}
      </div>
    );
  }

  const remaining = events.length - visible.length;

  return (
    <div className="px-5 pb-8 pt-2">
      {groups.map((group) => (
        <section
          key={group.key}
          aria-label={t("LBL_HISTORY_MONTH_HEADING", group.year, group.month)}
        >
          <h4 className="m-0 mb-1 flex justify-between border-0 border-b border-solid border-slate-200 pb-2 pt-5 text-sm font-bold tracking-wider text-slate-500">
            <span>
              {t("LBL_HISTORY_MONTH_HEADING", group.year, group.month)}
            </span>
            <span className="font-normal tracking-normal">
              {t("LBL_HISTORY_COUNT", monthTotals[group.key])}
            </span>
          </h4>
          <div className="divide-y divide-dashed divide-slate-200">
            {group.items.map((event) => (
              <HistoryEventItem
                key={event.id}
                event={event}
                fields={fields}
                users={users}
                filtering={filtering}
                onSelectField={onSelectField}
              />
            ))}
          </div>
        </section>
      ))}
      {remaining > 0 && (
        <div className="flex justify-center pt-5">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setVisibleCount((n) => n + pageSize)}
          >
            {t("LBL_HISTORY_SHOW_MORE", remaining)}
          </Button>
        </div>
      )}
    </div>
  );
};
