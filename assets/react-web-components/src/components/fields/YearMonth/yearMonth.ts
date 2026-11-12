/**
 * 年月（YYYY-MM-01）の変換
 */

export interface YearMonth {
  year: number;
  /** 1〜12 */
  month: number;
}

/** 月初日（YYYY-MM-DD / YYYY-MM）から年月を取り出す。解釈できなければ null */
export function parseYearMonth(
  value: string | null | undefined,
): YearMonth | null {
  if (!value) return null;
  const match = /^(\d{4})-(\d{1,2})(?:-\d{1,2})?$/.exec(value.trim());
  if (!match) return null;
  const year = Number(match[1]);
  const month = Number(match[2]);
  if (year < 1000 || month < 1 || month > 12) return null;
  return { year, month };
}

/** 年月を月初日（YYYY-MM-01）にする */
export function toDbValue({ year, month }: YearMonth): string {
  return `${String(year).padStart(4, "0")}-${String(month).padStart(2, "0")}-01`;
}

/** 書式（{Y} {M} {MM}）で表示用の文字にする */
export function formatYearMonth(
  value: YearMonth | null,
  format: string,
): string {
  if (!value) return "";
  return format
    .replace("{Y}", String(value.year))
    .replace("{MM}", String(value.month).padStart(2, "0"))
    .replace("{M}", String(value.month));
}

/** 今月 */
export function currentYearMonth(now: Date = new Date()): YearMonth {
  return { year: now.getFullYear(), month: now.getMonth() + 1 };
}
