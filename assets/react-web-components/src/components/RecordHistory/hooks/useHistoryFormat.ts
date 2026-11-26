import { createContext, useContext, useMemo } from "react";
import type { HistoryDateFormat } from "@/types/history";
import {
  DEFAULT_DATE_FORMAT,
  formatFullDateTime,
  formatMonthDay,
  formatRelative,
  formatTime,
  heldDays,
  parseHistoryDate,
  weekdayLabel,
} from "../utils/historyDate";
import { useHistoryTranslation } from "./useHistoryTranslation";

export interface HistoryFormatContextValue {
  /** ユーザーのタイムゾーンでの現在時刻（API の now） */
  now: string;
  /** ユーザー設定の日付形式・時刻形式（API の format） */
  format: HistoryDateFormat;
}

export const HistoryFormatContext = createContext<HistoryFormatContextValue>({
  now: new Date().toISOString().slice(0, 19),
  format: DEFAULT_DATE_FORMAT,
});

/** 変更日時をユーザー設定の書式で整形する関数群 */
export function useHistoryFormat() {
  const { now, format } = useContext(HistoryFormatContext);
  const t = useHistoryTranslation();
  return useMemo(
    () => ({
      fullDateTime: (value: string) => formatFullDateTime(value, t, format),
      monthDay: (value: string) => formatMonthDay(value, format),
      time: (value: string) => formatTime(value, format),
      weekday: (value: string) => weekdayLabel(parseHistoryDate(value), t),
      relative: (value: string) => formatRelative(value, now, t),
      heldDays: (from: string, until: string | null) =>
        heldDays(from, until, now),
    }),
    [now, format, t],
  );
}
