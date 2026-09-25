/**
 * カレンダーの日時文字列を Date に変換する。
 * `YYYY-MM-DD` のみの場合は ISO 仕様で UTC 解釈されローカル時刻とズレるため、
 * ローカル時刻として再構築する。
 * `YYYY-MM-DDTHH:MM` 形式（タイムゾーン指定なし）はネイティブのローカル時刻解釈に委ねる。
 *
 * @param endOfDay true の場合、日付のみの値をその日の終わり（23:59:59.999）として扱う
 */
function parseDateTimeLocal(value: string, endOfDay = false): Date {
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    const [y, m, d] = value.split("-").map(Number);
    return endOfDay
      ? new Date(y, m - 1, d, 23, 59, 59, 999)
      : new Date(y, m - 1, d);
  }
  return new Date(value);
}

/**
 * 未来日付の活動はステータス「完了」(Held) で登録できないかを判定する
 * @returns true: 未来×Held で NG（invalid）/ false: それ以外（OK）
 */
export function isFutureEventHeldInvalid(
  eventstatus: unknown,
  dateStart: unknown,
  now: Date = new Date(),
): boolean {
  if (eventstatus !== "Held") return false;
  if (typeof dateStart !== "string" || !dateStart) return false;
  const start = parseDateTimeLocal(dateStart);
  if (isNaN(start.getTime())) return false;
  return start.getTime() > now.getTime();
}

/**
 * 終了日時（due_date）が開始日時（date_start）より前かを判定する
 *
 * ToDo の完了日と終日活動の終了日は日付のみ（`YYYY-MM-DD`）で保持されるため、
 * その日の終わり（23:59:59.999）まで有効とみなして比較する。
 * 旧 Edit 画面のバリデータ（Calendar_greaterThanDependentField）と同じ扱い。
 *
 * @returns true: 終了日時が開始日時より前で NG（invalid）/ false: それ以外（OK）
 */
export function isDateRangeInvalid(
  dateStart: unknown,
  dueDate: unknown,
): boolean {
  if (typeof dateStart !== "string" || !dateStart) return false;
  if (typeof dueDate !== "string" || !dueDate) return false;

  const start = parseDateTimeLocal(dateStart);
  const end = parseDateTimeLocal(dueDate, true);
  if (isNaN(start.getTime()) || isNaN(end.getTime())) return false;

  return start.getTime() > end.getTime();
}
