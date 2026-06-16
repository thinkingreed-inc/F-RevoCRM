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
}

/** ドキュメント詳細（DetailAPI用。関連レコード等を含む） */
export interface DocumentDetail extends DocumentRecord {
  folder_path: FolderPathItem[];
  modified_by_name: string;
  preview_url: string;
  related_records: RelatedRecord[];
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
  label: string;
}

/** フォルダ */
export interface Folder {
  id: number;
  name: string;
  description: string;
  parent_id: number;
  sequence: number;
  count: number;
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
