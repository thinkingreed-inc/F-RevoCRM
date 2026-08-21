import React from "react";
import { useOptionalTranslation } from "../../hooks/useTranslation";
import type { DeadlineStatus } from "./types/documents";

/**
 * 入力期限状態（期限内／期限間近／期限超過）のバッジ
 *
 * 色だけで状態を伝えると色覚特性や白黒印刷で区別できなくなるため、
 * 必ずラベル文字を添えて表示する。色は詳細画面のピル表示と揃えている。
 */

interface DeadlineStatusBadgeProps {
  status: DeadlineStatus | null | undefined;
  /**
   * 期限日。状態は入力期限から導かれる値なので、期限日が無ければ表示しない。
   * （input_deadline_status 列の既定値が 'within' のため、期限を持たない
   *   ドキュメントにも状態だけが入っていることがある）
   */
  deadline?: string | null;
  /** 一覧の狭い列で使う小さめの表示 */
  compact?: boolean;
}

interface BadgeStyle {
  labelKey: string;
  bg: string;
  color: string;
}

/** 状態ごとの表示（詳細画面 DocumentDetailModal のピルと同じ配色） */
const BADGE_STYLES: Record<DeadlineStatus, BadgeStyle> = {
  within: { labelKey: "LBL_DEADLINE_WITHIN", bg: "#e7f4e8", color: "#2e8b46" },
  warning: {
    labelKey: "LBL_DEADLINE_WARNING",
    bg: "#fdf1e0",
    color: "#a86a12",
  },
  overdue: {
    labelKey: "LBL_DEADLINE_OVERDUE",
    bg: "#FED7D7",
    color: "#822727",
  },
};

/** 入力期限状態として扱える値かどうか（サーバーが null や空文字を返す場合がある） */
function isDeadlineStatus(
  value: DeadlineStatus | null | undefined,
): value is DeadlineStatus {
  return value === "within" || value === "warning" || value === "overdue";
}

export const DeadlineStatusBadge: React.FC<DeadlineStatusBadgeProps> = ({
  status,
  deadline,
  compact,
}) => {
  const { t } = useOptionalTranslation();

  // スキャナ保存以外は入力期限を持たないため、何も表示しない
  if (!isDeadlineStatus(status) || !deadline) return null;

  const style = BADGE_STYLES[status];
  const label = t(style.labelKey);
  const tooltip = `${label}（${t("LBL_INPUT_DEADLINE")}: ${deadline.substring(0, 10)}）`;

  return (
    <span
      title={tooltip}
      style={{
        display: "inline-block",
        padding: compact ? "1px 4px" : "2px 8px",
        borderRadius: 10,
        backgroundColor: style.bg,
        color: style.color,
        fontSize: compact ? 10 : 11,
        fontWeight: 600,
        lineHeight: 1.5,
        whiteSpace: "nowrap",
      }}
    >
      {label}
    </span>
  );
};
