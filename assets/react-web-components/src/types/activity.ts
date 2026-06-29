/**
 * アクティビティ（活動）の型定義
 * Task, Meeting, Call などの活動情報
 */

/**
 * ステータス選択肢の型
 */
export interface StatusOption {
  /** 値（DB保存用） */
  value: string;
  /** 表示ラベル */
  label: string;
}

/**
 * アクティビティ情報の型定義
 */
export interface Activity {
  /** アクティビティID */
  id: string;
  /** 件名 */
  subject: string;
  /** アクティビティタイプ（Task, Meeting, Call など） */
  activityType: 'Task' | 'Meeting' | 'Call' | string;
  /** ステータス */
  status: string;
  /** 開始日（YYYY-MM-DD形式） */
  dateStart: string;
  /** 開始時刻（HH:MM:SS形式） */
  timeStart: string;
  /** 終了日（YYYY-MM-DD形式） */
  dueDate: string;
  /** 終了時刻（HH:MM:SS形式） */
  timeEnd: string;
  /** 担当者情報 */
  assignedTo: {
    /** 担当者ID */
    id: string;
    /** 担当者名 */
    name: string;
  };
  /** 説明（オプショナル） */
  description?: string;
  /** 共有メモ（オプショナル） */
  commonMemo?: string;
  /** 詳細表示URL */
  detailViewUrl: string;
  /** ステータスフィールド名（taskstatus または eventstatus） */
  statusField?: string;
  /** ステータスの選択肢一覧 */
  statusOptions?: StatusOption[];
  /** 編集権限の有無 */
  canEdit?: boolean;
}

/**
 * GetActivities APIレスポンスの型定義
 */
export interface GetActivitiesResponse {
  /** APIの成功/失敗 */
  success: boolean;
  /** アクティビティリスト */
  activities: Activity[];
  /** 次のページがあるかどうか */
  hasMore: boolean;
  /** 現在のページ番号 */
  page: number;
  /** 1ページあたりの件数 */
  limit: number;
  /** 総件数（オプショナル） */
  totalCount?: number;
}

/**
 * ActivityListコンポーネントのProps
 */
export interface ActivityListProps {
  /** モジュール名 */
  module: string;
  /** レコードID */
  recordId: string;
  /** 表示モード（upcoming: 今後の予定, overdue: 期限切れ, all: すべて） */
  mode?: 'upcoming' | 'overdue' | 'all';
  /** 1ページあたりの表示件数 */
  limit?: number;
  /** リフレッシュ用キー（値が変わるとデータを再取得） */
  refreshKey?: string | number;
}
