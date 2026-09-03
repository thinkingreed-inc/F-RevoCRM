/** ドキュメントレコード */
export interface DocumentRecord {
  id: number;
  title: string;
  filename: string;
  filetype: string | null;
  filesize: number;
  filelocationtype: "I" | "E";
  folderid: number;
  foldername: string;
  assigned_user_id: string;
  assigned_user_name: string;
  modifiedtime: string;
  createdtime: string;
  filedownloadcount: number;
  filestatus: number;
  fileversion: string | null;
  starred: boolean;
  notecontent: string | null;
  note_no: string;
  download_url: string;
  compliance: ComplianceListData | null;
  /**
   * 変更できるか（フォルダの権限が「参照」だけなら false）
   *
   * false のドキュメントは編集・削除・移動ができないため、
   * 画面側でも操作を出さない。
   */
  can_edit?: boolean;
}

/** 電帳法コンプライアンス情報（一覧用） */
export interface ComplianceListData {
  document_category: DocumentCategory;
  preservation_type: PreservationType;
  compliance_status: ComplianceStatus | null;
  compliance_notes: string | null;
  input_deadline: string | null;
  /** スキャナ保存以外は入力期限を持たないため null になる */
  input_deadline_status: DeadlineStatus | null;
}

/** 電帳法コンプライアンス情報（詳細用） */
export interface ComplianceDetailData extends ComplianceListData {
  file_hash_algorithm: string;
  file_hash: string | null;
  scan_resolution_dpi: number | null;
  scan_color_type: "color" | "grayscale" | null;
  original_paper_size: string | null;
  scanned_by: number | null;
  scanned_at: string | null;
  receipt_date: string | null;
  compliance_checked_at: string | null;
  compliance_notes: string | null;
}

/** 監査ログの項目値変更 */
export interface AuditLogChange {
  /** フィールド名 */
  field: string;
  /** 翻訳済みフィールドラベル（サーバー側で付与） */
  label?: string;
  old_value: string | null;
  new_value: string | null;
  /** 表示用に整形された値（ピックリスト・参照等を解決したもの） */
  old_display?: string | null;
  new_display?: string | null;
}

/** 監査ログの操作詳細 */
export interface AuditLogDetail {
  /** 項目値の変更内容 */
  changes?: AuditLogChange[];
  /** 変更理由 */
  reason?: string;
  /** ファイル差し替えの場合true */
  file_replaced?: boolean;
  /** 新規登録時のタイトル */
  title?: string;
  /** 新規登録時のファイル名 */
  filename?: string;
  /** ハッシュ検証結果 */
  result?: "success" | "failure";
  /** ハッシュ検証メッセージ */
  message?: string;
}

/** 変更履歴の記録元（vtiger 標準の更新履歴 / 電帳法の監査ログ） */
export type HistorySource = "modtracker" | "audit";

/** 変更履歴エントリ（ModTracker と監査ログを統合したもの） */
export interface AuditLogEntry {
  /** 記録元ごとのID。記録元をまたぐと重複するため、単独ではキーにしない */
  entry_id: number;
  source: HistorySource;
  action_type:
    "create" | "update" | "delete" | "restore" | "download" | "verify";
  action_detail: AuditLogDetail | string | null;
  file_hash_before: string | null;
  file_hash_after: string | null;
  /** ModTracker は操作者が入らない場合がある */
  performed_by: number | null;
  performer_name: string;
  performed_at: string;
  /** ModTracker は IP アドレスを持たない */
  ip_address: string | null;
}

/** ファイルバージョンエントリ */
export interface FileVersionEntry {
  version_number: number;
  file_hash: string;
  file_size: number;
  change_reason: string | null;
  created_by: number;
  creator_name: string;
  created_at: string;
  is_current: boolean;
  download_url: string;
}

/** 書類区分 */
export type DocumentCategory =
  | "invoice"
  | "receipt"
  | "contract"
  | "estimate"
  | "order"
  | "delivery"
  | "other";

/** 保存区分 */
export type PreservationType = "electronic_transaction" | "scanner";

/** 適合状態 */
export type ComplianceStatus = "compliant" | "non_compliant";

/** 入力期限状態 */
export type DeadlineStatus = "within" | "warning" | "overdue";

/** ドキュメント詳細（DetailAPI用。関連レコード等を含む） */
export interface DocumentDetail extends DocumentRecord {
  folder_path: FolderPathItem[];
  modified_by_name: string;
  preview_url: string;
  /** サイズ上限内でプレビュー可能か（サーバー判定） */
  previewable?: boolean;
  /** プレビュー可能な上限（バイト） */
  preview_max_size?: number;
  /** プレビュー可能な上限の表示用文字列（例: 20 MB） */
  preview_max_size_label?: string;
  related_records: RelatedRecord[];
  compliance: ComplianceDetailData | null;
  audit_log: AuditLogEntry[];
  file_versions: FileVersionEntry[];
  dynamic_fields?: Record<string, any>;
}

/** フォルダパスの1要素 */
export interface FolderPathItem {
  id: number;
  name: string;
}

/** 関連レコード */
export interface RelatedRecord {
  id: number;
  module: string;
  module_label: string;
  label: string;
  summary?: {
    date?: string;
    amount?: string;
    currency_symbol?: string;
  } | null;
}

/** フォルダ */
export interface Folder {
  id: number;
  name: string;
  description: string;
  parent_id: number;
  sequence: number;
  count: number;
  can_edit?: boolean;
  /** 権限設定を変更できるか（管理者またはオーナー。サーバー判定） */
  can_manage_permissions?: boolean;
}

/** フォルダ権限の種類（強い順に owner > edit > view。強い権限は弱い権限を兼ねる） */
export type FolderPermissionType = "view" | "edit" | "owner";

/** フォルダ権限エントリ */
export interface FolderPermission {
  permission_id?: number;
  permission_type: FolderPermissionType;
  target_type: "everyone" | "user" | "role" | "group";
  target_id: string | number | null;
  target_name?: string | null;
}

/** 権限付与先候補 */
export interface PermissionTargets {
  users: Array<{ id: number; name: string }>;
  roles: Array<{ id: string; name: string }>;
  groups: Array<{ id: number; name: string }>;
}

/** フォルダツリーAPIレスポンス */
export interface FolderTreeResponse {
  success: boolean;
  result?: {
    folders: Folder[];
    totalCount: number;
    starredCount: number;
  };
  error?: { message: string };
}

/** ドキュメント一覧APIレスポンス */
export interface DocumentListResponse {
  success: boolean;
  result?: {
    records: DocumentRecord[];
    total: number;
    page: number;
    pageLimit: number;
  };
  error?: { message: string };
}

/** ドキュメント詳細APIレスポンス */
export interface DocumentDetailResponse {
  success: boolean;
  result?: DocumentDetail;
  error?: { message: string };
}

/** 表示モード */
export type ViewMode = "list" | "grid" | "preview";

/** ソート設定 */
export interface SortConfig {
  field: string;
  order: "ASC" | "DESC";
}

/** インラインフィルタ */
export interface ColumnFilters {
  title?: string;
  filename?: string;
  filetype?: string;
  foldername?: string;
  assigned_user?: string;
  notecontent?: string;
}

/** 特殊フィルタタイプ */
export type FilterType = "all" | "starred" | "recent";

/** ファイル種別カテゴリ */
export type FileCategory =
  | "pdf"
  | "word"
  | "excel"
  | "powerpoint"
  | "image"
  | "text"
  | "video"
  | "audio"
  | "archive"
  | "url"
  | "other";
