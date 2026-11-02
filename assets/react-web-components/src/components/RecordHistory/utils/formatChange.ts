import type {
  FilteredHistoryEvent,
  HistoryDelta,
  HistoryField,
} from "@/types/history";
import type { HistoryTranslate } from "../hooks/useHistoryTranslation";

/** 増減の表示（例: +220,000,000.00（+44%））。マイナスは見やすい全角幅の − を使う */
export function formatDelta(delta: HistoryDelta): string {
  const sign = delta.sign === "+" ? "+" : "−";
  const percent =
    delta.percent === null ? "" : `（${sign}${Math.abs(delta.percent)}%）`;
  return `${sign}${delta.display}${percent}`;
}

/** 1 イベントを 1 行の文章に要約する */
export function summarizeEvent(
  event: FilteredHistoryEvent,
  fields: Record<string, HistoryField>,
  t: HistoryTranslate,
): string {
  if (event.type === "update" && event.visibleChanges.length > 0) {
    const [first, ...rest] = event.visibleChanges;
    const empty = t("LBL_HISTORY_EMPTY_VALUE");
    const label = fields[first.field]?.label ?? first.field;
    const text = `${label}：${first.before?.display ?? empty} → ${first.after?.display ?? empty}`;
    return rest.length > 0
      ? `${text}\u3000${t("LBL_HISTORY_AND_N_MORE", rest.length)}`
      : text;
  }
  if (event.related) {
    return `${t(`LBL_HISTORY_TYPE_${event.type}`)}：${event.related.moduleLabel}「${event.related.label}」`;
  }
  return t(`LBL_HISTORY_TYPE_${event.type}`);
}
