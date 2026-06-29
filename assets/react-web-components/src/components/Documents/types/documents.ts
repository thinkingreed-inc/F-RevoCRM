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
}

/** 電帳法コンプライアンス情報（一覧用） */
export interface ComplianceListData {
  document_category: DocumentCategory;
  preservation_type: PreservationType;
  compliance_status: ComplianceStatus | null;
  compliance_notes: string | null;
  input_deadline: string | null;
  input_deadline_status: DeadlineStatus;
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

/** 監査ログエントリ */
export interface AuditLogEntry {
  audit_id: number;
  action_type: "create" | "update" | "delete" | "restore" | "download" | "verify";
  action_detail: any;
  file_hash_before: string | null;
  file_hash_after: string | null;
  performed_by: number;
  performer_name: string;
  performed_at: string;
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
}

/** フォルダ権限エントリ */
export interface FolderPermission {
  permission_id?: number;
  permission_type: "view" | "edit";
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
