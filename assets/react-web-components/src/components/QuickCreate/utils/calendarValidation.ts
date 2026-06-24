/**
 * 未来日付の活動はステータス「完了」(Held) で登録できないかを判定する
 * @returns true: バリデーションOK / false: 未来×Held で NG
 */
export function isFutureEventHeldInvalid(
  eventstatus: unknown,
  dateStart: unknown,
  now: Date = new Date()
): boolean {
  if (eventstatus !== 'Held') return false;
  if (typeof dateStart !== 'string' || !dateStart) return false;
  const start = new Date(dateStart);
  if (isNaN(start.getTime())) return false;
  return start.getTime() > now.getTime();
}
