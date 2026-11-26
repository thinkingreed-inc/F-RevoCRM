import React, { useCallback, useMemo, useState } from "react";
import { AlertCircle } from "lucide-react";
import type {
  GetHistoryResponse,
  HistoryField,
  RecordHistoryProps,
} from "@/types/history";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { TranslationProvider } from "@/contexts/TranslationContext";
import { useHistoryFilter } from "./hooks/useHistoryFilter";
import { HistoryFormatContext } from "./hooks/useHistoryFormat";
import { useHistoryTranslation } from "./hooks/useHistoryTranslation";
import { useRecordHistory } from "./hooks/useRecordHistory";
import {
  countFieldUpdates,
  EMPTY_CRITERIA,
  filterHistory,
  hasActiveFilter,
} from "./utils/filterHistory";
import { DEFAULT_DATE_FORMAT, sortByLayoutOrder } from "./utils/historyDate";
import { ActiveFilterBar } from "./components/ActiveFilterBar";
import { CompactHistory } from "./components/CompactHistory";
import { FieldTrackView } from "./components/FieldTrackView";
import { HistoryTimeline } from "./components/HistoryTimeline";
import {
  HistoryToolbar,
  type HistoryViewMode,
} from "./components/HistoryToolbar";

const DEFAULT_PAGE_SIZE = { full: 20, compact: 5 } as const;

/**
 * record-history Web Component の本体。
 *
 * @example
 * <record-history module="Accounts" record-id="3"></record-history>
 * <record-history module="Accounts" record-id="3" variant="compact" page-size="5" full-history-url="..."></record-history>
 */
export const RecordHistory: React.FC<RecordHistoryProps> = (props) => (
  <TranslationProvider module={props.module}>
    <RecordHistoryInner {...props} />
  </TranslationProvider>
);

const RecordHistoryInner: React.FC<RecordHistoryProps> = ({
  module,
  recordId,
  variant = "full",
  pageSize,
  fullHistoryUrl,
  refreshKey,
}) => {
  const t = useHistoryTranslation();
  const { data, loading, error, reload } = useRecordHistory(
    module,
    String(recordId),
    refreshKey,
  );
  const size =
    Number(pageSize) > 0 ? Number(pageSize) : DEFAULT_PAGE_SIZE[variant];

  if (loading && !data) {
    return (
      <div
        className="grid gap-4 p-5"
        aria-busy="true"
        aria-label={t("LBL_LOADING")}
      >
        {Array.from({ length: variant === "compact" ? 3 : 4 }, (_, i) => (
          <Skeleton key={i} className="h-[48px] w-full" />
        ))}
      </div>
    );
  }

  if (error || !data) {
    return (
      <div
        className="grid justify-items-center gap-3 px-5 py-10 text-center text-slate-600"
        role="alert"
      >
        <AlertCircle className="size-[20px] text-red-600" aria-hidden="true" />
        <span>{t("LBL_HISTORY_LOAD_ERROR")}</span>
        <Button variant="outline" size="sm" onClick={reload}>
          {t("LBL_HISTORY_RETRY")}
        </Button>
      </div>
    );
  }

  return (
    <HistoryFormatContext.Provider
      value={{ now: data.now, format: data.format ?? DEFAULT_DATE_FORMAT }}
    >
      {variant === "compact" ? (
        <CompactBody data={data} limit={size} fullHistoryUrl={fullHistoryUrl} />
      ) : (
        <FullBody data={data} pageSize={size} />
      )}
    </HistoryFormatContext.Provider>
  );
};

const CompactBody: React.FC<{
  data: GetHistoryResponse;
  limit: number;
  fullHistoryUrl?: string;
}> = ({ data, limit, fullHistoryUrl }) => {
  // ウィジェットでは絞り込みを出さず、システム項目だけ除いた最新分を表示する
  const events = useMemo(
    () => filterHistory(data.events, data.fields, data.users, EMPTY_CRITERIA),
    [data],
  );
  return (
    <CompactHistory
      events={events}
      fields={data.fields}
      users={data.users}
      limit={limit}
      fullHistoryUrl={fullHistoryUrl}
    />
  );
};

const FullBody: React.FC<{ data: GetHistoryResponse; pageSize: number }> = ({
  data,
  pageSize,
}) => {
  const filter = useHistoryFilter(data);
  const { criteria } = filter;
  const [view, setView] = useState<HistoryViewMode>("timeline");
  const [trackField, setTrackField] = useState<string | null>(null);

  const fieldCounts = useMemo(
    () => countFieldUpdates(data.events),
    [data.events],
  );
  const users = useMemo(() => Object.values(data.users), [data.users]);
  // システム項目はトグルが ON のときだけ選択肢に出す
  const selectableFields = useMemo(
    () =>
      Object.values(data.fields).filter(
        (f: HistoryField) =>
          !f.system ||
          criteria.showSystemFields ||
          criteria.fields.includes(f.name),
      ),
    [data.fields, criteria.showSystemFields, criteria.fields],
  );
  const trackableFields = useMemo(
    () =>
      sortByLayoutOrder(
        selectableFields.filter((f) => (fieldCounts[f.name] ?? 0) > 0),
      ),
    [selectableFields, fieldCounts],
  );

  const openFieldTrack = useCallback((field: string) => {
    setTrackField(field);
    setView("field");
  }, []);

  const filtering = hasActiveFilter(criteria);

  return (
    <div className="bg-white text-slate-800" data-testid="record-history">
      <HistoryToolbar
        criteria={criteria}
        fields={selectableFields}
        fieldCounts={fieldCounts}
        users={users}
        view={view}
        onKeywordChange={filter.setKeyword}
        onToggleField={(field) => {
          filter.toggleField(field);
          setView("timeline");
        }}
        onToggleUser={filter.toggleUser}
        onTypesChange={(types) => {
          filter.setTypes(types);
          setView("timeline");
        }}
        onShowSystemFieldsChange={filter.setShowSystemFields}
        onViewChange={setView}
      />
      <ActiveFilterBar
        criteria={criteria}
        fields={data.fields}
        users={data.users}
        shownCount={filter.filtered.length}
        totalCount={data.events.length}
        onKeywordChange={filter.setKeyword}
        onToggleField={filter.toggleField}
        onToggleUser={filter.toggleUser}
        onClearTypes={() => filter.setTypes([])}
        onClear={filter.clear}
      />
      {view === "timeline" ? (
        <HistoryTimeline
          events={filter.filtered}
          fields={data.fields}
          users={data.users}
          pageSize={pageSize}
          filtering={filtering}
          onSelectField={openFieldTrack}
          onClearFilters={filter.clear}
        />
      ) : (
        <FieldTrackView
          events={data.events}
          fields={trackableFields}
          counts={fieldCounts}
          users={data.users}
          userIds={criteria.userIds}
          selectedField={trackField}
          onSelectField={setTrackField}
        />
      )}
    </div>
  );
};
