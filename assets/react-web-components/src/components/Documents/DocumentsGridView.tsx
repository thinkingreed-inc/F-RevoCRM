import React from "react";
import { useOptionalTranslation } from "../../hooks/useTranslation";
import type { DocumentRecord, Folder } from "./types/documents";
import { FileIcon } from "./FileIcon";
import { StarButton } from "./StarButton";

interface DocumentsGridViewProps {
  records: DocumentRecord[];
  total: number;
  page: number;
  pageLimit: number;
  isLoading: boolean;
  folders: Folder[];
  selectedFolderId: number | "all";
  onPageChange: (page: number) => void;
  onRecordClick: (record: DocumentRecord) => void;
  onFolderClick: (folderId: number | "all") => void;
}

function formatFileSize(bytes: number): string {
  if (!bytes || bytes === 0) return "—";
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

function formatShortDate(dateStr: string): string {
  if (!dateStr) return "";
  // MM-DD
  return dateStr.substring(5, 10);
}

function getFileTypeLabel(record: DocumentRecord): string {
  if (record.filelocationtype === "E") return "URL";
  if (!record.filetype) return "";
  const ext = record.filename?.split(".").pop()?.toUpperCase();
  return ext || record.filetype.split("/").pop() || "";
}

export const DocumentsGridView: React.FC<DocumentsGridViewProps> = ({
  records,
  total,
  page,
  pageLimit,
  isLoading,
  folders,
  selectedFolderId,
  onPageChange,
  onRecordClick,
  onFolderClick,
}) => {
  const { t } = useOptionalTranslation();
  const totalPages = Math.ceil(total / pageLimit);

  // サブフォルダを取得（選択中のフォルダの直下のみ）
  const subFolders =
    selectedFolderId !== "all"
      ? folders.filter((f) => f.parent_id === selectedFolderId)
      : [];

  // 親フォルダの特定
  const currentFolder = selectedFolderId !== "all" ? folders.find((f) => f.id === selectedFolderId) : undefined;
  const parentTarget: number | "all" | null =
    currentFolder
      ? currentFolder.parent_id > 0
        ? currentFolder.parent_id
        : "all"
      : null;

  // フォルダセクションを表示するか
  const showFolderSection = subFolders.length > 0 || parentTarget !== null;

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden" }}>
      {/* ヘッダー情報 */}
      <div
        style={{
          display: "flex",
          justifyContent: "flex-end",
          alignItems: "center",
          gap: 12,
          padding: "8px 16px",
          fontSize: 13,
          color: "#718096",
          borderBottom: "1px solid #EDF2F7",
        }}
      >
        {subFolders.length > 0 && (
          <span>{t('LBL_SUBFOLDERS', subFolders.length)}</span>
        )}
        <span>{t('LBL_FILES_COUNT', total)}</span>
        <span style={{ marginLeft: "auto" }}>
          {(page - 1) * pageLimit + 1} - {Math.min(page * pageLimit, total)} / {total}
        </span>
        <button
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
          style={{ padding: "2px 8px", border: "1px solid #E2E8F0", borderRadius: 3, background: "#fff", cursor: page <= 1 ? "not-allowed" : "pointer", opacity: page <= 1 ? 0.4 : 1 }}
        >
          &lt;
        </button>
        <button
          disabled={page >= totalPages}
          onClick={() => onPageChange(page + 1)}
          style={{ padding: "2px 8px", border: "1px solid #E2E8F0", borderRadius: 3, background: "#fff", cursor: page >= totalPages ? "not-allowed" : "pointer", opacity: page >= totalPages ? 0.4 : 1 }}
        >
          &gt;
        </button>
      </div>

      {/* コンテンツエリア */}
      <div style={{ flex: 1, overflowY: "auto", padding: 16 }}>
        {/* サブフォルダ + 上の階層へ */}
        {showFolderSection && (
          <div style={{ marginBottom: 20 }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(160px, 1fr))", gap: 12 }}>
              {/* 上の階層へ戻るカード */}
              {parentTarget !== null && (
                <div
                  onClick={() => onFolderClick(parentTarget)}
                  style={{
                    padding: "12px 16px",
                    border: "1px dashed #CBD5E0",
                    borderRadius: 8,
                    cursor: "pointer",
                    backgroundColor: "#fff",
                    transition: "box-shadow 0.15s, background-color 0.15s",
                    display: "flex",
                    alignItems: "center",
                    gap: 8,
                  }}
                  onMouseEnter={(e) => { e.currentTarget.style.boxShadow = "0 2px 8px rgba(0,0,0,0.08)"; e.currentTarget.style.backgroundColor = "#F7FAFC"; }}
                  onMouseLeave={(e) => { e.currentTarget.style.boxShadow = ""; e.currentTarget.style.backgroundColor = "#fff"; }}
                >
                  <span style={{ fontSize: 18, color: "#718096", lineHeight: 1 }}>←</span>
                  <div>
                    <div style={{ fontWeight: 500, fontSize: 13, color: "#4A5568" }}>
                      {parentTarget === "all" ? t('LBL_ALL_DOCUMENTS') : folders.find((f) => f.id === parentTarget)?.name || ""}
                    </div>
                  </div>
                </div>
              )}
              {subFolders.map((folder) => (
                <div
                  key={folder.id}
                  onClick={() => onFolderClick(folder.id)}
                  style={{
                    padding: "12px 16px",
                    border: "1px solid #E2E8F0",
                    borderRadius: 8,
                    cursor: "pointer",
                    backgroundColor: "#F7FAFC",
                    transition: "box-shadow 0.15s",
                  }}
                  onMouseEnter={(e) => (e.currentTarget.style.boxShadow = "0 2px 8px rgba(0,0,0,0.08)")}
                  onMouseLeave={(e) => (e.currentTarget.style.boxShadow = "")}
                >
                  <div style={{ fontWeight: 500, fontSize: 13, color: "#2D3748", marginBottom: 4 }}>
                    {folder.name}
                  </div>
                  <div style={{ fontSize: 12, color: "#A0AEC0" }}>{t('LBL_FOLDER_COUNT', folder.count)}</div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* ドキュメントカード */}
        {isLoading && records.length === 0 ? (
          <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>{t('LBL_LOADING')}</div>
        ) : records.length === 0 ? (
          <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>{t('LBL_NO_DOCUMENTS')}</div>
        ) : (
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(200px, 1fr))", gap: 12 }}>
            {records.map((rec) => (
              <div
                key={rec.id}
                onClick={() => onRecordClick(rec)}
                style={{
                  border: "1px solid #E2E8F0",
                  borderRadius: 8,
                  padding: 14,
                  cursor: "pointer",
                  display: "flex",
                  flexDirection: "column",
                  gap: 8,
                  transition: "box-shadow 0.15s",
                  backgroundColor: "#fff",
                }}
                onMouseEnter={(e) => (e.currentTarget.style.boxShadow = "0 2px 8px rgba(0,0,0,0.1)")}
                onMouseLeave={(e) => (e.currentTarget.style.boxShadow = "")}
              >
                {/* 上部: アイコン + スター */}
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start" }}>
                  <FileIcon filetype={rec.filetype} filelocationtype={rec.filelocationtype} filename={rec.filename} size="lg" />
                  <StarButton recordId={rec.id} starred={rec.starred} />
                </div>

                {/* タイトル */}
                <div
                  style={{
                    fontWeight: 500,
                    fontSize: 13,
                    color: "#2D3748",
                    lineHeight: 1.4,
                    display: "-webkit-box",
                    WebkitLineClamp: 2,
                    WebkitBoxOrient: "vertical",
                    overflow: "hidden",
                    minHeight: 36,
                  }}
                >
                  {rec.title}
                </div>

                {/* ファイル情報 */}
                <div style={{ fontSize: 11, color: "#A0AEC0", marginTop: "auto" }}>
                  {getFileTypeLabel(rec)} ・ {formatFileSize(rec.filesize)} ・ {formatShortDate(rec.modifiedtime)}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};
