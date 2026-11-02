import {
  differenceInCalendarDays,
  differenceInMinutes,
  format,
  parseISO,
} from "date-fns";
import type { HistoryDateFormat } from "@/types/history";

type Translate = (key: string, ...args: (string | number)[]) => string;

export const DEFAULT_DATE_FORMAT: HistoryDateFormat = {
  dateFormat: "yyyy-mm-dd",
  hourFormat: "24",
};

/** vtiger の日付形式（yyyy-mm-dd / dd-mm-yyyy / mm-dd-yyyy / dd.mm.yyyy / dd/mm/yyyy）を date-fns の書式に変換する */
function toDateFnsPattern(vtigerFormat: string): string {
  return vtigerFormat.replace(/mm/g, "MM");
}

/**
 * 年を除いた月日の書式。月・日の順番はユーザー設定に従い、区切りは「/」に統一する
 * （例: yyyy-mm-dd → MM/dd、dd-mm-yyyy → dd/MM）。
 */
export function monthDayPattern(fmt: HistoryDateFormat): string {
  const base = fmt.dateFormat || DEFAULT_DATE_FORMAT.dateFormat;
  return base.indexOf("dd") < base.indexOf("mm") ? "dd/MM" : "MM/dd";
}

export function datePattern(fmt: HistoryDateFormat): string {
  return toDateFnsPattern(fmt.dateFormat || DEFAULT_DATE_FORMAT.dateFormat);
}

/** 12 時間制は vtiger の表示（3:41 PM）に合わせる */
export function timePattern(fmt: HistoryDateFormat): string {
  return fmt.hourFormat === "12" ? "h:mm a" : "HH:mm";
}

/** API の日時（YYYY-MM-DDTHH:mm:ss、ユーザーのタイムゾーン）をローカル Date として解釈する */
export function parseHistoryDate(value: string): Date {
  return parseISO(value);
}

export function weekdayLabel(date: Date, t: Translate): string {
  return t(`LBL_HISTORY_WEEKDAY_${date.getDay()}`);
}

export function formatMonthDay(value: string, fmt: HistoryDateFormat) {
  return format(parseHistoryDate(value), monthDayPattern(fmt));
}

export function formatTime(value: string, fmt: HistoryDateFormat) {
  return format(parseHistoryDate(value), timePattern(fmt));
}

/** ユーザー設定の書式での日時（例: 01-11-2026（日）9:30 AM） */
export function formatFullDateTime(
  value: string,
  t: Translate,
  fmt: HistoryDateFormat,
): string {
  const date = parseHistoryDate(value);
  return `${format(date, datePattern(fmt))}（${weekdayLabel(date, t)}）${format(date, timePattern(fmt))}`;
}

/** 「たった今」「5分前」「3時間前」「昨日」「18日前」「2か月前」「1年前」 */
export function formatRelative(value: string, now: string, t: Translate) {
  const date = parseHistoryDate(value);
  const base = parseHistoryDate(now);
  const minutes = differenceInMinutes(base, date);
  const days = differenceInCalendarDays(base, date);

  if (days <= 0) {
    if (minutes < 1) return t("LBL_HISTORY_JUST_NOW");
    if (minutes < 60) return t("LBL_HISTORY_MINUTES_AGO", minutes);
    return t("LBL_HISTORY_HOURS_AGO", Math.floor(minutes / 60));
  }
  if (days === 1) return t("LBL_HISTORY_YESTERDAY");
  if (days < 30) return t("LBL_HISTORY_DAYS_AGO", days);
  if (days < 365) return t("LBL_HISTORY_MONTHS_AGO", Math.floor(days / 30));
  return t("LBL_HISTORY_YEARS_AGO", Math.floor(days / 365));
}

/** 値が保持されていた日数（until が null なら now まで） */
export function heldDays(from: string, until: string | null, now: string) {
  return Math.max(
    0,
    differenceInCalendarDays(
      parseHistoryDate(until ?? now),
      parseHistoryDate(from),
    ),
  );
}

export interface MonthGroup<T> {
  /** YYYY-MM */
  key: string;
  year: number;
  month: number;
  items: T[];
}

/** 新しい順に並んだ要素を、並びを保ったまま年月でまとめる */
export function groupByMonth<T extends { changedOn: string }>(
  items: T[],
): MonthGroup<T>[] {
  const groups: MonthGroup<T>[] = [];
  for (const item of items) {
    const key = item.changedOn.slice(0, 7);
    let group = groups[groups.length - 1];
    if (!group || group.key !== key) {
      group = {
        key,
        year: Number(key.slice(0, 4)),
        month: Number(key.slice(5, 7)),
        items: [],
      };
      groups.push(group);
    }
    group.items.push(item);
  }
  return groups;
}

/** 詳細画面の並び順で項目を並べる */
export function sortByLayoutOrder<T extends { order: number }>(items: T[]) {
  return [...items].sort((a, b) => a.order - b.order);
}
