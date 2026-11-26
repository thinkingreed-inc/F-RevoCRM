import type { FieldTrackEntry, HistoryEvent } from "@/types/history";

/**
 * 指定項目の値の変遷を新しい順で返す。
 * 各世代の until には「次の値に変わった日時」を入れる（現在の値は null）。
 *
 * @param events 新しい順のイベント（API の並び順）
 * @param fieldName 対象の fieldname
 * @param userIds 空でなければ、そのユーザーによる変更だけを返す（until は全体の変遷から求める）
 */
export function buildFieldTrack(
  events: HistoryEvent[],
  fieldName: string,
  userIds: number[] = [],
): FieldTrackEntry[] {
  const entries: FieldTrackEntry[] = [];
  let nextChangedOn: string | null = null;

  for (const event of events) {
    if (event.type !== "create" && event.type !== "update") continue;
    const change = event.changes.find((c) => c.field === fieldName);
    if (!change) continue;

    const entry: FieldTrackEntry = {
      eventId: event.id,
      userId: event.userId,
      changedOn: event.changedOn,
      value: change.after,
      isInitial: event.type === "create",
      until: nextChangedOn,
    };
    nextChangedOn = event.changedOn;

    if (userIds.length > 0 && !userIds.includes(event.userId)) continue;
    entries.push(entry);
  }

  return entries;
}
