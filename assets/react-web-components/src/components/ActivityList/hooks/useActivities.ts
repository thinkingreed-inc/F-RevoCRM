import { useState, useEffect, useCallback } from 'react';
import { Activity, GetActivitiesResponse } from '@/types/activity';

/**
 * useActivities フックの戻り値
 */
export interface UseActivitiesResult {
  /** アクティビティ一覧 */
  activities: Activity[];
  /** ローディング状態 */
  loading: boolean;
  /** 初回ロード中フラグ（スケルトン表示用） */
  initialLoading: boolean;
  /** エラーメッセージ */
  error: string | null;
  /** 次のページがあるか */
  hasMore: boolean;
  /** 現在のページ番号 */
  page: number;
  /** 追加ロード関数 */
  loadMore: () => Promise<void>;
  /** リフレッシュ関数（最初から再取得） */
  refresh: () => Promise<void>;
}

/**
 * アクティビティ一覧を取得するHook
 *
 * @param module 親モジュール名（Accounts, Contacts, Potentials）
 * @param recordId 親レコードID
 * @param mode フィルタモード（upcoming, overdue, all）
 * @param limit 1ページあたりの件数
 *
 * @example
 * const { activities, loading, hasMore, loadMore } = useActivities('Accounts', '123', 'all', 10);
 */
export function useActivities(
  module: string,
  recordId: string,
  mode: 'upcoming' | 'overdue' | 'all' = 'all',
  limit: number = 10
): UseActivitiesResult {
  const [activities, setActivities] = useState<Activity[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [initialLoading, setInitialLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState<boolean>(false);
  const [page, setPage] = useState<number>(1);

  /**
   * APIからアクティビティを取得
   */
  const fetchActivities = useCallback(async (pageNum: number, append: boolean = false): Promise<void> => {
    if (!module || !recordId) {
      setError('モジュール名またはレコードIDが指定されていません');
      setInitialLoading(false);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      // API URL構築
      const params = new URLSearchParams({
        module: 'Calendar',
        api: 'GetActivities',
        parent_module: module,
        parent_id: recordId,
        page: String(pageNum),
        limit: String(limit),
      });

      // モードが指定されている場合のみ追加（空文字は除外）
      if (mode && mode !== 'all') {
        params.set('mode', mode);
      }

      const response = await fetch(`?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      // HTTP エラーチェック
      if (!response.ok) {
        if (response.status === 401) {
          throw new Error('セッションがタイムアウトしました。ページを再読み込みしてください。');
        }
        if (response.status === 403) {
          throw new Error('このレコードにアクセスする権限がありません。');
        }
        throw new Error(`APIエラー: ${response.status}`);
      }

      const data: GetActivitiesResponse = await response.json();

      if (!data.success) {
        throw new Error('アクティビティの取得に失敗しました');
      }

      // アクティビティ一覧を更新（追加時はIDで重複を除去）
      if (append) {
        setActivities(prev => {
          const existingIds = new Set(prev.map(a => a.id));
          const newItems = data.activities.filter(a => !existingIds.has(a.id));
          return [...prev, ...newItems];
        });
      } else {
        setActivities(data.activities);
      }

      setHasMore(data.hasMore);
      setPage(pageNum);

    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'アクティビティの取得中にエラーが発生しました';
      setError(errorMessage);
      console.error('useActivities error:', err);
    } finally {
      setLoading(false);
      setInitialLoading(false);
    }
  }, [module, recordId, mode, limit]);

  /**
   * 追加ロード（次のページを取得）
   */
  const loadMore = useCallback(async (): Promise<void> => {
    if (loading || !hasMore) return;
    await fetchActivities(page + 1, true);
  }, [loading, hasMore, page, fetchActivities]);

  /**
   * リフレッシュ（最初から再取得）
   */
  const refresh = useCallback(async (): Promise<void> => {
    setActivities([]);
    setPage(1);
    setInitialLoading(true);
    await fetchActivities(1, false);
  }, [fetchActivities]);

  // 初回ロード
  useEffect(() => {
    fetchActivities(1, false);
  }, [fetchActivities]);

  return {
    activities,
    loading,
    initialLoading,
    error,
    hasMore,
    page,
    loadMore,
    refresh
  };
}

export default useActivities;
