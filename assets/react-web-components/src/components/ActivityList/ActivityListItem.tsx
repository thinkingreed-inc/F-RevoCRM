import React from 'react';
import { Calendar, CheckSquare, Phone, User, Clock } from 'lucide-react';
import { Badge } from '../ui/badge';
import { ActivityStatusEditor } from './ActivityStatusEditor';
import { cn } from '@/lib/utils';
import { Activity } from '@/types/activity';

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
    case 'Meeting':
      return Calendar;
    case 'Task':
      return CheckSquare;
    case 'Call':
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
    case 'Meeting':
      return 'text-blue-600';
    case 'Task':
      return 'text-green-600';
    case 'Call':
      return 'text-purple-600';
    default:
      return 'text-gray-600';
  }
};

/**
 * Get badge variant based on status
 */
const getStatusVariant = (status: string): 'default' | 'success' | 'warning' | 'destructive' | 'secondary' => {
  const lowerStatus = status.toLowerCase();

  if (lowerStatus.includes('completed') || lowerStatus.includes('完了')) {
    return 'success';
  }
  if (lowerStatus.includes('progress') || lowerStatus.includes('進行中')) {
    return 'warning';
  }
  if (lowerStatus.includes('cancel') || lowerStatus.includes('キャンセル')) {
    return 'destructive';
  }
  if (lowerStatus.includes('planned') || lowerStatus.includes('計画')) {
    return 'secondary';
  }

  return 'default';
};

/**
 * Format date and time for display
 */
const formatDateTime = (dateStr: string, timeStr: string): string => {
  if (!dateStr) return '';

  try {
    // Parse date (YYYY-MM-DD)
    const date = new Date(dateStr);

    // Format date in user-friendly format (e.g., "2024年1月15日")
    const dateFormatted = new Intl.DateTimeFormat('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(date);

    // If time is available, add it
    if (timeStr) {
      // timeStr format: HH:MM:SS
      const timeParts = timeStr.split(':');
      if (timeParts.length >= 2) {
        const hours = timeParts[0];
        const minutes = timeParts[1];
        return `${dateFormatted} ${hours}:${minutes}`;
      }
    }

    return dateFormatted;
  } catch (error) {
    console.error('Error formatting date:', error);
    return dateStr;
  }
};

/**
 * ActivityListItem - Display a single activity item in table-style layout
 *
 * Features:
 * - Table-style layout with activity info organized in columns
 * - Activity type icon with color coding
 * - Subject as clickable link to detail view
 * - Start date/time formatted in user-friendly format
 * - Assigned user name
 * - Status badge with edit icon (when canEdit is true)
 * - Collapsible description section
 * - Hover effect for better UX
 *
 * @example
 * <ActivityListItem
 *   activity={activity}
 *   onStatusChange={(id, status) => console.log('Status changed', id, status)}
 * />
 */
export const ActivityListItem: React.FC<ActivityListItemProps> = ({
  activity,
  onStatusChange
}) => {
  const Icon = getActivityIcon(activity.activityType);
  const iconColor = getIconColor(activity.activityType);
  const statusVariant = getStatusVariant(activity.status);
  const formattedDateTime = formatDateTime(activity.dateStart, activity.timeStart);
  const hasDescription = activity.description && activity.description.trim().length > 0;

  /**
   * ステータス部分のクリックイベントが親リンクに伝播しないようにする
   */
  const handleStatusAreaClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
  };

  return (
    <div
      className={cn(
        'rounded-lg border border-gray-200',
        'hover:bg-gray-50 hover:border-gray-300 transition-colors',
      )}
    >
      {/* Table-style layout for main activity info - 全体がクリッカブル */}
      <a
        href={activity.detailViewUrl}
        className="grid grid-cols-[auto_1fr_auto_auto_auto] gap-3 items-center p-3 text-md font-medium no-underline"
      >
        {/* Column 1: Activity type icon */}
        <div className={cn('flex-shrink-0', iconColor)} title={activity.activityType}>
          <Icon className="h-5 w-5" aria-hidden="true" />
        </div>

        {/* Column 2: Subject */}
        <div className="min-w-0">
          <span
            className={cn(
              'text-gray-900',
              'hover:text-blue-600 hover:underline',
              'line-clamp-2'
            )}
            title={activity.subject}
          >
            {activity.subject}
          </span>
        </div>

        {/* Column 3: Start date/time */}
        <div className="flex-shrink-0 min-w-[120px]">
          {formattedDateTime && (
            <div className="flex items-center gap-1 text-md text-gray-600">
              <Clock className="h-3 w-3 flex-shrink-0" aria-hidden="true" />
              <time
                dateTime={`${activity.dateStart}T${activity.timeStart}`}
                className="truncate"
              >
                {formattedDateTime}
              </time>
            </div>
          )}
        </div>

        {/* Column 4: Assigned user */}
        <div className="flex-shrink-0 min-w-[100px]">
          {activity.assignedTo && (
            <div className="flex items-center gap-1 text-md text-gray-600">
              <User className="h-3 w-3 flex-shrink-0" aria-hidden="true" />
              <span className="truncate" title={activity.assignedTo.name}>
                {activity.assignedTo.name}
              </span>
            </div>
          )}
        </div>

        {/* Column 5: Status with inline editor - クリックイベントを止める */}
        <div className="flex-shrink-0" onClick={handleStatusAreaClick}>
          {activity.canEdit && activity.statusOptions && activity.statusOptions.length > 0 ? (
            <ActivityStatusEditor
              value={activity.status}
              fieldName={activity.statusField || 'status'}
              options={activity.statusOptions}
              canEdit={activity.canEdit}
              onSave={async (newValue) => {
                if (onStatusChange) {
                  await onStatusChange(activity.id, newValue);
                }
              }}
            />
          ) : (
            <Badge variant={statusVariant}>
              {activity.status}
            </Badge>
          )}
        </div>
      </a>

      {/* Collapsible description section */}
      {hasDescription && (
        <div className="border-t border-gray-200 p-4 text-md text-gray-700 whitespace-pre-wrap">
          {activity.description}
        </div>
      )}
    </div>
  );
};

export default ActivityListItem;
