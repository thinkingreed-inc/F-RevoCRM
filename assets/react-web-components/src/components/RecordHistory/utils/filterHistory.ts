import type {
  FilteredHistoryEvent,
  HistoryChange,
  HistoryEvent,
  HistoryField,
  HistoryFilterCriteria,
  HistoryUser,
} from "@/types/history";

export const EMPTY_CRITERIA: HistoryFilterCriteria = {
  keyword: "",
  fields: [],
  userIds: [],
  types: [],
  showSystemFields: false,
};

/** 絞り込み条件が既定値から変わっているか（システム項目トグルは除く） */
export function hasActiveFilter(criteria: HistoryFilterCriteria): boolean {
  return (
    criteria.keyword.trim() !== "" ||
    criteria.fields.length > 0 ||
    criteria.userIds.length > 0 ||
    criteria.types.length > 0
  );
}

function isChangeVisible(
  change: HistoryChange,
  fields: Record<string, HistoryField>,
  criteria: HistoryFilterCriteria,
  keyword: string,
): boolean {
  const field = fields[change.field];
  if (!field) return false;
  // 明示的に項目を選んだ場合は、システム項目でも表示する
  if (criteria.fields.length > 0) {
    if (!criteria.fields.includes(change.field)) return false;
  } else if (field.system && !criteria.showSystemFields) {
    return false;
  }
  if (keyword === "") return true;
  const haystack = [
    field.label,
    change.before?.display ?? "",
    change.after?.display ?? "",
  ]
    .join("\n")
    .toLowerCase();
  return haystack.includes(keyword);
}

/**
 * 履歴イベントをクライアント側で絞り込む。
 * 変更を持つイベント（create / update）は、条件に合う変更が 1 件も無ければ除外する。
 */
export function filterHistory(
  events: HistoryEvent[],
  fields: Record<string, HistoryField>,
  users: Record<string, HistoryUser>,
  criteria: HistoryFilterCriteria,
): FilteredHistoryEvent[] {
  const keyword = criteria.keyword.trim().toLowerCase();
  const result: FilteredHistoryEvent[] = [];

  for (const event of events) {
    if (criteria.userIds.length > 0 && !criteria.userIds.includes(event.userId))
      continue;
    if (criteria.types.length > 0 && !criteria.types.includes(event.type))
      continue;

    if (event.type === "create" || event.type === "update") {
      const visibleChanges = event.changes.filter((change) =>
        isChangeVisible(change, fields, criteria, keyword),
      );
      if (visibleChanges.length === 0) continue;
      result.push({ ...event, visibleChanges });
      continue;
    }

    // 項目を持たないイベントは、項目で絞り込んでいる間は出さない
    if (criteria.fields.length > 0) continue;
    if (keyword !== "") {
      const userName = users[String(event.userId)]?.name ?? "";
      const haystack = [
        event.related?.label ?? "",
        event.related?.moduleLabel ?? "",
        userName,
      ]
        .join("\n")
        .toLowerCase();
      if (!haystack.includes(keyword)) continue;
    }
    result.push({ ...event, visibleChanges: [] });
  }

  return result;
}

/** 項目ごとの「更新」回数（作成時の値は数えない） */
export function countFieldUpdates(
  events: HistoryEvent[],
): Record<string, number> {
  const counts: Record<string, number> = {};
  for (const event of events) {
    if (event.type !== "update") continue;
    for (const change of event.changes) {
      counts[change.field] = (counts[change.field] ?? 0) + 1;
    }
  }
  return counts;
}
