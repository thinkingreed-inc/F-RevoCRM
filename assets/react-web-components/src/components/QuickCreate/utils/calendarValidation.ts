/**
 * date_start 文字列を Date に変換する。
 * `YYYY-MM-DD` のみの場合は ISO 仕様で UTC 解釈されローカル時刻とズレるため、
 * ローカル時刻 0:00 として再構築する。
 * `YYYY-MM-DDTHH:MM` 形式（タイムゾーン指定なし）はネイティブのローカル時刻解釈に委ねる。
 */
function parseDateStartLocal(dateStart: string): Date {
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateStart)) {
    const [y, m, d] = dateStart.split("-").map(Number);
    return new Date(y, m - 1, d);
  }
  return new Date(dateStart);
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
  const start = parseDateStartLocal(dateStart);
  if (isNaN(start.getTime())) return false;
  return start.getTime() > now.getTime();
}
