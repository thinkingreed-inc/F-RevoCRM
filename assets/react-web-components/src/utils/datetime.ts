import {
  format,
  parse,
  differenceInDays,
  differenceInHours,
  differenceInMinutes,
  isToday,
  isBefore,
  addDays,
} from "date-fns";

/**
 * 文字列のYYYY-MM-DD HH:mm:ss形式を、YYYY/MM/DD HH:mm形式に変換する
 * @param dateStr - YYYY-MM-DD HH:mm:ss形式
 * @returns YYYY/MM/DD HH:mm形式
 */
export function convertDateFormat(dateStr: string): string {
  // Parse the input date string to a Date object
  const date = parse(dateStr, "yyyy-MM-dd HH:mm:ss", new Date());

  // Format the Date object to the desired format
  const formattedDate = format(date, "yyyy/MM/dd HH:mm");

  return formattedDate;
}

/**
 * 文字列のYYYY-MM-DD HH:mm:ss形式を、相対的な日付表記に変換する
 * 現在時刻からみて、
 * - 1時間以内の場合は、「n分前」
 * - 当日の日付の場合は、「n時間前」
 * - 2週間以内の場合は、「n日前」
 * と文字を返す
 * @param dateStr - YYYY-MM-DD HH:mm:ss形式の文字列
 * @returns 相対的な日付表記の文字列
 */
export function formatRelativeDate(dateStr: string): string {
  // 現在時刻を取得
  const now = new Date();

  // 入力された日付文字列をDateオブジェクトに変換する
  let date: Date;
  try {
    date = parse(dateStr, "yyyy-MM-dd HH:mm:ss", new Date());
  } catch {
    return "";
  }

  // 未来の日付の場合は元のフォーマットで返す
  if (isBefore(now, date)) {
    return format(date, "yyyy/MM/dd HH:mm");
  }

  // 現在時刻と入力された日付の差を計算する
  const daysDifference = differenceInDays(now, date);
  const hoursDifference = differenceInHours(now, date);
  const minutesDifference = differenceInMinutes(now, date);

  if (daysDifference < 14) {
    if (isToday(date)) {
      if (hoursDifference < 1) {
        return `${minutesDifference}分前`;
      } else {
        return `${hoursDifference}時間前`;
      }
    } else if (hoursDifference < 24) {
      return `${hoursDifference}時間前`;
    } else {
      return `${daysDifference}日前`;
    }
  } else {
    // 2週間以上前の場合は元のフォーマットで返す
    return format(date, "yyyy/MM/dd HH:mm");
  }
}

/**
 * Date型を引数にとり、YYYY-MM-DDに変換して返す関数
 */
export function formatDateYYYYMMDD(date: Date): string {
  return format(date, "yyyy-MM-dd");
}

// 日付を加算して取得
export function offsetDate(offset: number, date: string | Date = new Date()): string {
  const baseDate = typeof date === "string" ? parse(date, "yyyy-MM-dd HH:mm", new Date()) : date;
  return format(addDays(baseDate, offset), "yyyy-MM-dd");
}

export function formatTime(time: string, formatStr: string): string {
  const date = parse(time, "HH:mm:ss", new Date());
  return format(date, formatStr);
}

export function dayOfWeekToNumber(day: string | undefined): 0 | 1 | 2 | 3 | 4 | 5 | 6 {
  const days: { [key: string]: 0 | 1 | 2 | 3 | 4 | 5 | 6 } = {
    Sunday: 0,
    Monday: 1,
    Tuesday: 2,
    Wednesday: 3,
    Thursday: 4,
    Friday: 5,
    Saturday: 6,
  };
  return days[day ?? ""] ?? 0;
}

/**
 * 指定した日付の分を丸めて返す関数
 */
export function calcRoundMinDate(date: Date, roundNum: number): Date {
  const newMin = Math.round(date.getMinutes() / roundNum) * roundNum;
  const newDate = new Date(date);
  newDate.setMinutes(newMin !== 60 ? newMin : 0);
  newDate.setHours(newMin !== 60 ? newDate.getHours() : newDate.getHours() + 1);

  return newDate;
}
