import React, { useState, useRef, useEffect } from "react";
import { Calendar, CheckSquare, Phone, User } from "lucide-react";
import { Badge } from "../ui/badge";
import { ActivityStatusEditor } from "./ActivityStatusEditor";
import { cn } from "@/lib/utils";
import { Activity } from "@/types/activity";

/**
 * Props for ActivityListItem component
 */
export interface ActivityListItemProps {
  /** Activity data */
  activity: Activity;
  /** Callback when status is changed */
  onStatusChange?: (activityId: string, newStatus: string) => Promise<void>;
}

/**
 * Get icon component based on activity type
 */
const getActivityIcon = (activityType: string) => {
  switch (activityType) {
    case "Meeting":
      return Calendar;
    case "Task":
      return CheckSquare;
    case "Call":
      return Phone;
    default:
      return Calendar;
  }
};

/**
 * Get icon color based on activity type
 */
const getIconColor = (activityType: string): string => {
  switch (activityType) {
    case "Meeting":
      return "text-blue-600";
    case "Task":
      return "text-green-600";
    case "Call":
      return "text-purple-600";
    default:
      return "text-gray-600";
  }
};

/**
 * Get badge variant based on status
 */
const getStatusVariant = (
  status: string | null | undefined,
): "default" | "success" | "warning" | "destructive" | "secondary" => {
  const lowerStatus = (status ?? "").toLowerCase();

  if (
    lowerStatus.includes("completed") ||
    lowerStatus.includes("held") ||
    lowerStatus.includes("完了")
  ) {
    return "success";
  }
  if (lowerStatus.includes("progress") || lowerStatus.includes("進行中")) {
    return "warning";
  }
  if (lowerStatus.includes("cancel") || lowerStatus.includes("キャンセル")) {
    return "destructive";
  }
  if (lowerStatus.includes("planned") || lowerStatus.includes("計画")) {
    return "secondary";
  }

  return "default";
};

/**
 * Format date for display (YYYY/MM/DD HH:MM)
 */
const formatDateTime = (dateStr: string, timeStr: string): string => {
  if (!dateStr) return "";

  try {
    const [year, month, day] = dateStr.split("-");
    const datePart = `${year}/${month}/${day}`;

    if (timeStr) {
      const timeParts = timeStr.split(":");
      if (timeParts.length >= 2) {
        return `${datePart} ${timeParts[0]}:${timeParts[1]}`;
      }
    }

    return datePart;
  } catch {
    return dateStr;
  }
};

/** テキストが省略表示すべき長さかを判定する高さの閾値(px) */
const COLLAPSE_HEIGHT = 60;

/**
 * 長文を開閉できるテキストブロック
 */
const CollapsibleText: React.FC<{ text: string; label?: string }> = ({
  text,
  label,
}) => {
  const contentRef = useRef<HTMLDivElement>(null);
  const [needsCollapse, setNeedsCollapse] = useState(false);
  const [expanded, setExpanded] = useState(false);

  useEffect(() => {
    if (contentRef.current) {
      setNeedsCollapse(contentRef.current.scrollHeight > COLLAPSE_HEIGHT);
    }
  }, [text]);

  return (
    <div>
      {label && (
        <span className="text-gray-400 text-xs font-medium">{label}</span>
      )}
      <div
        ref={contentRef}
        className={cn(
          "whitespace-pre-wrap text-gray-600 text-md",
          !expanded && needsCollapse && "line-clamp-2",
        )}
      >
        {text}
      </div>
      {needsCollapse && (
        <button
          type="button"
          className="text-blue-500 hover:text-blue-700 text-xs mt-0.5 cursor-pointer"
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
            setExpanded((prev) => !prev);
          }}
        >
          {expanded ? "閉じる" : "もっと見る"}
        </button>
      )}
    </div>
  );
};

/**
 * ActivityListItem - カード型の活動表示
 */
export const ActivityListItem: React.FC<ActivityListItemProps> = ({
  activity,
  onStatusChange,
}) => {
  const Icon = getActivityIcon(activity.activityType);
  const iconColor = getIconColor(activity.activityType);
  const statusVariant = getStatusVariant(activity.status);
  const formattedDateTime = formatDateTime(
    activity.dateStart,
    activity.timeStart,
  );
  const hasDescription =
    activity.description && activity.description.trim().length > 0;
  const hasCommonMemo =
    activity.commonMemo && activity.commonMemo.trim().length > 0;
  const hasDetails = hasDescription || hasCommonMemo;
  const statusLabel =
    activity.statusOptions?.find((opt) => opt.value === activity.status)
      ?.label || activity.status;

  const handleStatusAreaClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
  };

  return (
    <div
      className={cn(
        "rounded-lg border border-gray-200 bg-white",
        "hover:border-gray-300 transition-colors",
      )}
    >
      <a href={activity.detailViewUrl} className="block p-3 no-underline">
        {/* Row 1: アイコン + 日付 + ステータス */}
        <div className="flex items-center gap-2 mb-1.5">
          <div
            className={cn("flex-shrink-0", iconColor)}
            title={activity.activityType}
          >
            <Icon className="h-4 w-4" aria-hidden="true" />
          </div>
          {formattedDateTime && (
            <time
              dateTime={`${activity.dateStart}T${activity.timeStart}`}
              className="text-md text-gray-500"
            >
              {formattedDateTime}
            </time>
          )}
          {/* ステータス - 右寄せ */}
          <div
            className="ml-auto flex-shrink-0"
            onClick={handleStatusAreaClick}
          >
            {activity.canEdit &&
            activity.statusOptions &&
            activity.statusOptions.length > 0 ? (
              <ActivityStatusEditor
                value={activity.status}
                fieldName={activity.statusField || "status"}
                options={activity.statusOptions}
                canEdit={activity.canEdit}
                onSave={async (newValue) => {
                  if (onStatusChange) {
                    await onStatusChange(activity.id, newValue);
                  }
                }}
              />
            ) : (
              <Badge variant={statusVariant}>{statusLabel}</Badge>
            )}
          </div>
        </div>

        {/* Row 2: 件名 */}
        <div
          className="font-bold text-gray-900 text-md truncate mb-1"
          title={activity.subject}
        >
          {activity.subject}
        </div>

        {/* Row 3: 詳細（説明・共有メモ） */}
        {hasDetails && (
          <div className="space-y-1 mb-1" onClick={(e) => e.stopPropagation()}>
            {hasDescription && <CollapsibleText text={activity.description!} />}
            {hasCommonMemo && (
              <CollapsibleText text={activity.commonMemo!} label="共有メモ" />
            )}
          </div>
        )}

        {/* Row 4: 担当者 */}
        {activity.assignedTo && (
          <div className="flex items-center gap-1 text-xs text-gray-400">
            <User className="h-3 w-3 flex-shrink-0" aria-hidden="true" />
            <span>{activity.assignedTo.name}</span>
          </div>
        )}
      </a>
    </div>
  );
};

export default ActivityListItem;
