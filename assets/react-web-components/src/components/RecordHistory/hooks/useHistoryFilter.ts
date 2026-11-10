import { useCallback, useDeferredValue, useMemo, useState } from "react";
import type {
  FilteredHistoryEvent,
  GetHistoryResponse,
  HistoryEventType,
  HistoryFilterCriteria,
} from "@/types/history";
import { EMPTY_CRITERIA, filterHistory } from "../utils/filterHistory";

const toggle = <T>(list: T[], value: T): T[] =>
  list.includes(value) ? list.filter((v) => v !== value) : [...list, value];

export interface UseHistoryFilterResult {
  criteria: HistoryFilterCriteria;
  filtered: FilteredHistoryEvent[];
  setKeyword: (keyword: string) => void;
  toggleField: (field: string) => void;
  toggleUser: (userId: number) => void;
  setTypes: (types: HistoryEventType[]) => void;
  setShowSystemFields: (show: boolean) => void;
  clear: () => void;
}

/** 絞り込み条件の状態と、条件を適用したイベント一覧 */
export function useHistoryFilter(
  data: GetHistoryResponse | null,
): UseHistoryFilterResult {
  const [criteria, setCriteria] =
    useState<HistoryFilterCriteria>(EMPTY_CRITERIA);
  // キーワード入力中の再計算で入力が引っかからないようにする
  const deferredCriteria = useDeferredValue(criteria);

  const filtered = useMemo(
    () =>
      data
        ? filterHistory(data.events, data.fields, data.users, deferredCriteria)
        : [],
    [data, deferredCriteria],
  );

  const setKeyword = useCallback(
    (keyword: string) => setCriteria((c) => ({ ...c, keyword })),
    [],
  );
  const toggleField = useCallback(
    (field: string) =>
      setCriteria((c) => ({ ...c, fields: toggle(c.fields, field) })),
    [],
  );
  const toggleUser = useCallback(
    (userId: number) =>
      setCriteria((c) => ({ ...c, userIds: toggle(c.userIds, userId) })),
    [],
  );
  const setTypes = useCallback(
    (types: HistoryEventType[]) => setCriteria((c) => ({ ...c, types })),
    [],
  );
  const setShowSystemFields = useCallback(
    (showSystemFields: boolean) =>
      setCriteria((c) => ({ ...c, showSystemFields })),
    [],
  );
  const clear = useCallback(
    () =>
      setCriteria((c) => ({
        ...EMPTY_CRITERIA,
        showSystemFields: c.showSystemFields,
      })),
    [],
  );

  return {
    criteria,
    filtered,
    setKeyword,
    toggleField,
    toggleUser,
    setTypes,
    setShowSystemFields,
    clear,
  };
}
