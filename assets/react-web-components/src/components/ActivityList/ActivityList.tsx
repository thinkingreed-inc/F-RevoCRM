import React, { useCallback } from 'react';
import { Loader2, RefreshCw, AlertCircle, CalendarX } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ActivityListProps } from '@/types/activity';
import { useActivities } from './hooks/useActivities';
import { useActivityStatusUpdate } from './hooks/useActivityStatusUpdate';
import { ActivityListItem } from './ActivityListItem';
import { Button } from '../ui/button';

/**
 * ActivityList - Display a list of activities for a record
 *
 * Features:
 * - Fetches activities from GetActivities API
 * - Load more button for pagination
 * - Loading states (initial skeleton, load more spinner)
 * - Empty state display
 * - Error handling with retry
 *
 * @example
 * <ActivityList module="Accounts" recordId="123" mode="all" limit={10} />
 */
export const ActivityList: React.FC<ActivityListProps> = ({
  module,
  recordId,
  mode = 'all',
  limit = 5
}) => {
  const {
    activities,
    loading,
    initialLoading,
    error,
    hasMore,
    loadMore,
    refresh
  } = useActivities(module, recordId, mode, limit);

  const { updateStatus } = useActivityStatusUpdate();

  /**
   * ステータス変更ハンドラー
   * 更新後にリストをリフレッシュして最新状態を取得
   */
  const handleStatusChange = useCallback(async (activityId: string, newStatus: string): Promise<void> => {
    // 対象のアクティビティを見つける
    const activity = activities.find(a => a.id === activityId);
    if (!activity) {
      throw new Error('アクティビティが見つかりません');
    }

    // ステータスフィールド名を取得
    const fieldName = activity.statusField || 'eventstatus';

    // APIで更新（activityTypeを渡してcalendarModuleを決定）
    await updateStatus(activityId, fieldName, newStatus, activity.activityType);

    // リストをリフレッシュして最新のデータを取得
    await refresh();
  }, [activities, updateStatus, refresh]);

  // 初回ローディング表示
  if (initialLoading) {
    return (
      <div className="w-full">
        <ActivityListSkeleton count={3} />
      </div>
    );
  }

  // エラー表示
  if (error) {
    return (
      <div className="w-full p-4 rounded-lg border border-red-200 bg-red-50">
        <div className="flex items-center gap-3">
          <AlertCircle className="h-5 w-5 text-red-500 flex-shrink-0" aria-hidden="true" />
          <div className="flex-1 min-w-0">
            <p className="text-sm text-red-800">{error}</p>
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={refresh}
            className="flex-shrink-0"
          >
            <RefreshCw className="h-4 w-4 mr-1" aria-hidden="true" />
            再試行
          </Button>
        </div>
      </div>
    );
  }

  // 空状態表示
  if (activities.length === 0) {
    return (
      <div className="w-full p-8 rounded-lg border border-gray-200 bg-gray-50">
        <div className="flex flex-col items-center justify-center text-center">
          <CalendarX className="h-12 w-12 text-gray-400 mb-3" aria-hidden="true" />
          <p className="text-md text-gray-600">
            {mode === 'upcoming' && '今後の予定はありません'}
            {mode === 'overdue' && '期限切れの予定はありません'}
            {mode === 'all' && '関連する活動はありません'}
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full space-y-2">
      {/* アクティビティリスト */}
      <div className="space-y-1" role="list" aria-label="活動一覧">
        {activities.map((activity) => (
          <div key={activity.id} role="listitem">
            <ActivityListItem
              activity={activity}
              onStatusChange={handleStatusChange}
            />
          </div>
        ))}
      </div>

      {/* Load More ボタン */}
      {hasMore && (
        <div className="pt-2">
          <Button
            variant="outline"
            size="lg"
            onClick={loadMore}
            disabled={loading}
            className="w-full h-8 text-md"
          >
            {loading ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" aria-hidden="true" />
                読み込み中...
              </>
            ) : (
              'LBL_SHOW_MORE'
            )}
          </Button>
        </div>
      )}
    </div>
  );
};

/**
 * スケルトンローダー
 */
const ActivityListSkeleton: React.FC<{ count?: number }> = ({ count = 3 }) => {
  return (
    <div className="space-y-2" aria-busy="true" aria-label="読み込み中">
      {Array.from({ length: count }).map((_, index) => (
        <div
          key={index}
          className={cn(
            'flex items-start gap-3 p-3 rounded-lg border border-gray-200',
            'animate-pulse'
          )}
        >
          {/* Icon skeleton */}
          <div className="flex-shrink-0 mt-0.5">
            <div className="h-5 w-5 bg-gray-200 rounded" />
          </div>

          {/* Content skeleton */}
          <div className="flex-1 min-w-0 space-y-2">
            {/* Subject */}
            <div className="h-4 bg-gray-200 rounded w-3/4" />
            {/* Date and user */}
            <div className="flex gap-4">
              <div className="h-3 bg-gray-200 rounded w-24" />
              <div className="h-3 bg-gray-200 rounded w-16" />
            </div>
          </div>

          {/* Badge skeleton */}
          <div className="flex-shrink-0">
            <div className="h-5 w-16 bg-gray-200 rounded-full" />
          </div>
        </div>
      ))}
    </div>
  );
};

export default ActivityList;
