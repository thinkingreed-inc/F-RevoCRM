import React, { useState } from "react";
import type { DocumentRecord, DocumentDetail, Folder } from "./types/documents";
import { FileIcon } from "./FileIcon";
import { StarButton } from "./StarButton";
import { DocumentsPreviewPanel } from "./DocumentsPreviewPanel";
import { useDocumentDetail } from "./hooks/useDocumentDetail";
import { useOptionalTranslation } from "../../hooks/useTranslation";

interface DocumentsPreviewViewProps {
  records: DocumentRecord[];
  total: number;
  isLoading: boolean;
  folderName: string;
  folders: Folder[];
  selectedFolderId: number | "all";
  onFolderClick: (folderId: number | "all") => void;
  onEdit?: (doc: DocumentDetail) => void;
}

function formatFileSize(bytes: number): string {
  if (!bytes || bytes === 0) return "—";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

function formatShortDate(dateStr: string): string {
  if (!dateStr) return "";
  return dateStr.substring(5, 10);
}

function getFileTypeLabel(rec: DocumentRecord): string {
  if (rec.filelocationtype === "E") return "URL";
  const ext = rec.filename?.split(".").pop()?.toUpperCase();
  return ext || "";
}

export const DocumentsPreviewView: React.FC<DocumentsPreviewViewProps> = ({
  records,
  total,
  isLoading,
  folderName,
  folders,
  selectedFolderId,
  onFolderClick,
  onEdit,
}) => {
  const { t } = useOptionalTranslation();
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const { document: selectedDoc, isLoading: detailLoading } = useDocumentDetail(selectedId);

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
    <div style={{ flex: 1, display: "flex", overflow: "hidden" }}>
      {/* 中央パネル: ドキュメントリスト */}
      <div
        style={{
          width: 320,
          borderRight: "1px solid #E2E8F0",
          display: "flex",
          flexDirection: "column",
          overflow: "hidden",
        }}
      >
        {/* リストヘッダー */}
        <div style={{ padding: "10px 12px", borderBottom: "1px solid #EDF2F7" }}>
          <div style={{ fontWeight: 600, fontSize: 14, color: "#2D3748", marginBottom: 4 }}>
            {folderName} <span style={{ fontWeight: 400, color: "#A0AEC0" }}>{total}</span>
          </div>
        </div>

        {/* リスト */}
        <div style={{ flex: 1, overflowY: "auto" }}>
          {/* サブフォルダ + 上の階層へ */}
          {showFolderSection && (
            <div style={{ padding: "8px 12px", borderBottom: "1px solid #EDF2F7" }}>
              <div style={{ display: "flex", flexDirection: "column", gap: 4 }}>
                {/* 上の階層へ戻る */}
                {parentTarget !== null && (
                  <div
                    onClick={() => onFolderClick(parentTarget)}
                    style={{
                      padding: "6px 10px",
                      border: "1px dashed #CBD5E0",
                      borderRadius: 4,
                      cursor: "pointer",
                      backgroundColor: "#fff",
                      display: "flex",
                      alignItems: "center",
                      gap: 6,
                      transition: "background-color 0.1s",
                    }}
                    onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = "#F7FAFC")}
                    onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = "#fff")}
                  >
                    <span style={{ fontSize: 14, color: "#718096", lineHeight: 1 }}>←</span>
                    <span style={{ fontSize: 12, fontWeight: 500, color: "#4A5568" }}>
                      {parentTarget === "all" ? t('LBL_ALL_DOCUMENTS') : folders.find((f) => f.id === parentTarget)?.name || ""}
                    </span>
                  </div>
                )}
                {subFolders.map((folder) => (
                  <div
                    key={folder.id}
                    onClick={() => onFolderClick(folder.id)}
                    style={{
                      padding: "6px 10px",
                      border: "1px solid #E2E8F0",
                      borderRadius: 4,
                      cursor: "pointer",
                      backgroundColor: "#F7FAFC",
                      display: "flex",
                      justifyContent: "space-between",
                      alignItems: "center",
                      transition: "background-color 0.1s",
                    }}
                    onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = "#EDF2F7")}
                    onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = "#F7FAFC")}
                  >
                    <span style={{ fontSize: 12, fontWeight: 500, color: "#2D3748" }}>
                      {folder.name}
                    </span>
                    <span style={{ fontSize: 11, color: "#A0AEC0" }}>{folder.count}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {isLoading && records.length === 0 ? (
            <div style={{ padding: 30, textAlign: "center", color: "#A0AEC0", fontSize: 13 }}>
              {t('LBL_LOADING')}
            </div>
          ) : records.length === 0 ? (
            <div style={{ padding: 30, textAlign: "center", color: "#A0AEC0", fontSize: 13 }}>
              {t('LBL_NO_DOCUMENTS')}
            </div>
          ) : (
            records.map((rec) => {
              const isSelected = selectedId === rec.id;
              return (
                <div
                  key={rec.id}
                  onClick={() => setSelectedId(rec.id)}
                  style={{
                    display: "flex",
                    alignItems: "flex-start",
                    gap: 8,
                    padding: "10px 12px",
                    cursor: "pointer",
                    backgroundColor: isSelected ? "#EBF8FF" : "transparent",
                    borderBottom: "1px solid #F7FAFC",
                    transition: "background-color 0.1s",
                  }}
                  onMouseEnter={(e) => {
                    if (!isSelected) e.currentTarget.style.backgroundColor = "#F7FAFC";
                  }}
                  onMouseLeave={(e) => {
                    if (!isSelected) e.currentTarget.style.backgroundColor = "";
                  }}
                >
                  <FileIcon
                    filetype={rec.filetype}
                    filelocationtype={rec.filelocationtype}
                    filename={rec.filename}
                    size="sm"
                  />
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div
                      style={{
                        fontWeight: 500,
                        fontSize: 13,
                        color: "#2D3748",
                        whiteSpace: "nowrap",
                        overflow: "hidden",
                        textOverflow: "ellipsis",
                      }}
                    >
                      {rec.title}
                    </div>
                    <div style={{ fontSize: 11, color: "#A0AEC0", marginTop: 2 }}>
                      {getFileTypeLabel(rec)} ・ {formatFileSize(rec.filesize)} ・{" "}
                      {formatShortDate(rec.modifiedtime)}
                    </div>
                  </div>
                  <StarButton recordId={rec.id} starred={rec.starred} />
                </div>
              );
            })
          )}
        </div>
      </div>

      {/* 右パネル: プレビュー + 情報 */}
      <DocumentsPreviewPanel document={selectedDoc} isLoading={detailLoading} onEdit={onEdit} />
    </div>
  );
};
