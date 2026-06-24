import React from "react";
import { useOptionalTranslation } from "../../hooks/useTranslation";
import type { DocumentRecord, SortConfig, Folder, ComplianceStatus } from "./types/documents";
import { FileIcon } from "./FileIcon";
import { StarButton } from "./StarButton";

interface DocumentsListViewProps {
  records: DocumentRecord[];
  total: number;
  page: number;
  pageLimit: number;
  sort: SortConfig;
  isLoading: boolean;
  folders: Folder[];
  selectedFolderId: number | "all";
  onSortChange: (sort: SortConfig) => void;
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

function formatDate(dateStr: string): string {
  if (!dateStr) return "";
  return dateStr.substring(0, 10);
}

function truncate(str: string | null, len: number): string {
  if (!str) return "";
  return str.length > len ? str.substring(0, len) + "..." : str;
}

function complianceStatusIcon(status: ComplianceStatus | null | undefined, labels: Record<string, string>, notes?: string | null): React.ReactNode {
  if (!status) return null;
  if (status === "compliant") return <span title={labels.compliant} style={{ color: "#38A169", fontSize: 14 }}>●</span>;
  if (status === "non_compliant") {
    const tooltip = notes ? `${labels.non_compliant}: ${notes}` : labels.non_compliant;
    return <span title={tooltip} style={{ color: "#E53E3E", fontSize: 14 }}>●</span>;
  }
  return null;
}

const SortableHeader: React.FC<{
  label: string;
  field: string;
  sort: SortConfig;
  onSort: (sort: SortConfig) => void;
  style?: React.CSSProperties;
}> = ({ label, field, sort, onSort, style }) => {
  const isActive = sort.field === field;
  return (
    <th
      onClick={() =>
        onSort({
          field,
          order: isActive && sort.order === "DESC" ? "ASC" : "DESC",
        })
      }
      style={{
        ...style,
        cursor: "pointer",
        userSelect: "none",
        whiteSpace: "nowrap",
        padding: "8px 6px",
        textAlign: "left",
        fontSize: 12,
        fontWeight: 600,
        color: "#4A5568",
        borderBottom: "2px solid #E2E8F0",
      }}
    >
      {label}
      {isActive && (
        <span style={{ marginLeft: 4, fontSize: 10 }}>
          {sort.order === "ASC" ? "▲" : "▼"}
        </span>
      )}
    </th>
  );
};

export const DocumentsListView: React.FC<DocumentsListViewProps> = ({
  records,
  total,
  page,
  pageLimit,
  sort,
  isLoading,
  folders,
  selectedFolderId,
  onSortChange,
  onPageChange,
  onRecordClick,
  onFolderClick,
}) => {
  const { t } = useOptionalTranslation();
  const totalPages = Math.ceil(total / pageLimit);

  const documentCategoryLabels: Record<string, string> = {
    invoice: t('LBL_CATEGORY_INVOICE'),
    receipt: t('LBL_CATEGORY_RECEIPT'),
    contract: t('LBL_CATEGORY_CONTRACT'),
    estimate: t('LBL_CATEGORY_ESTIMATE'),
    order: t('LBL_CATEGORY_ORDER'),
    delivery: t('LBL_CATEGORY_DELIVERY'),
    other: t('LBL_CATEGORY_OTHER'),
  };
  const complianceStatusLabels: Record<string, string> = {
    compliant: t('LBL_STATUS_COMPLIANT'),
    non_compliant: t('LBL_STATUS_NON_COMPLIANT'),
  };

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

  // フォルダセクションを表示するか（サブフォルダがあるか、または上の階層に戻れるか）
  const showFolderSection = subFolders.length > 0 || parentTarget !== null;

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column" }}>
      <div style={{ flex: 1, overflowX: "auto" }}>
        {/* サブフォルダ + 上の階層へ */}
        {showFolderSection && (
          <div style={{ padding: "12px 16px", borderBottom: "1px solid #EDF2F7" }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(160px, 1fr))", gap: 8 }}>
              {/* 上の階層へ戻るカード */}
              {parentTarget !== null && (
                <div
                  onClick={() => onFolderClick(parentTarget)}
                  style={{
                    padding: "10px 14px",
                    border: "1px dashed #CBD5E0",
                    borderRadius: 6,
                    cursor: "pointer",
                    backgroundColor: "#fff",
                    transition: "box-shadow 0.15s, background-color 0.15s",
                    display: "flex",
                    alignItems: "center",
                    gap: 6,
                  }}
                  onMouseEnter={(e) => { e.currentTarget.style.boxShadow = "0 2px 8px rgba(0,0,0,0.08)"; e.currentTarget.style.backgroundColor = "#F7FAFC"; }}
                  onMouseLeave={(e) => { e.currentTarget.style.boxShadow = ""; e.currentTarget.style.backgroundColor = "#fff"; }}
                >
                  <span style={{ fontSize: 16, color: "#718096", lineHeight: 1 }}>←</span>
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
                    padding: "10px 14px",
                    border: "1px solid #E2E8F0",
                    borderRadius: 6,
                    cursor: "pointer",
                    backgroundColor: "#F7FAFC",
                    transition: "box-shadow 0.15s",
                  }}
                  onMouseEnter={(e) => (e.currentTarget.style.boxShadow = "0 2px 8px rgba(0,0,0,0.08)")}
                  onMouseLeave={(e) => (e.currentTarget.style.boxShadow = "")}
                >
                  <div style={{ fontWeight: 500, fontSize: 13, color: "#2D3748", marginBottom: 2 }}>
                    {folder.name}
                  </div>
                  <div style={{ fontSize: 11, color: "#A0AEC0" }}>{t('LBL_FOLDER_COUNT', folder.count)}</div>
                </div>
              ))}
            </div>
          </div>
        )}

        <table
          style={{
            width: "100%",
            borderCollapse: "collapse",
            fontSize: 13,
          }}
        >
          <thead>
            <tr>
              <th style={{ width: 36, padding: "8px 4px", borderBottom: "2px solid #E2E8F0" }}>
                <input type="checkbox" disabled />
              </th>
              <th style={{ width: 32, padding: "8px 2px", borderBottom: "2px solid #E2E8F0" }} />
              <th style={{ width: 40, padding: "8px 2px", borderBottom: "2px solid #E2E8F0" }} />
              <SortableHeader label={t('Title')} field="title" sort={sort} onSort={onSortChange} />
              <SortableHeader label={t('File Type')} field="filetype" sort={sort} onSort={onSortChange} style={{ width: 100 }} />
              <SortableHeader label={t('Folder Name')} field="foldername" sort={sort} onSort={onSortChange} style={{ width: 120 }} />
              <SortableHeader label={t('Assigned To')} field="assigned_user_id" sort={sort} onSort={onSortChange} style={{ width: 100 }} />
              <SortableHeader label={t('Modified Time')} field="modifiedtime" sort={sort} onSort={onSortChange} style={{ width: 100 }} />
              <SortableHeader label={t('File Size')} field="filesize" sort={sort} onSort={onSortChange} style={{ width: 80 }} />
              <th style={{ width: 40, padding: "8px 4px", textAlign: "center", fontSize: 12, fontWeight: 600, color: "#4A5568", borderBottom: "2px solid #E2E8F0" }}>
                {t('LBL_COLUMN_COMPLIANCE')}
              </th>
              <th style={{ width: 70, padding: "8px 6px", textAlign: "left", fontSize: 12, fontWeight: 600, color: "#4A5568", borderBottom: "2px solid #E2E8F0" }}>
                {t('LBL_COLUMN_CATEGORY')}
              </th>
              <th style={{ padding: "8px 6px", textAlign: "left", fontSize: 12, fontWeight: 600, color: "#4A5568", borderBottom: "2px solid #E2E8F0" }}>
                {t('Note')}
              </th>
            </tr>
          </thead>
          <tbody>
            {isLoading && records.length === 0 && (
              <tr>
                <td colSpan={12} style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>
                  {t('LBL_LOADING')}
                </td>
              </tr>
            )}
            {!isLoading && records.length === 0 && (
              <tr>
                <td colSpan={12} style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>
                  {t('LBL_NO_DOCUMENTS')}
                </td>
              </tr>
            )}
            {records.map((rec) => (
              <tr
                key={rec.id}
                style={{
                  borderBottom: "1px solid #EDF2F7",
                  cursor: "pointer",
                }}
                onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = "#F7FAFC")}
                onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = "")}
              >
                <td style={{ padding: "6px 4px", textAlign: "center" }}>
                  <input type="checkbox" onClick={(e) => e.stopPropagation()} />
                </td>
                <td style={{ padding: "6px 2px", textAlign: "center" }}>
                  <StarButton recordId={rec.id} starred={rec.starred} />
                </td>
                <td style={{ padding: "6px 4px" }} onClick={() => onRecordClick(rec)}>
                  <FileIcon filetype={rec.filetype} filelocationtype={rec.filelocationtype} filename={rec.filename} size="sm" />
                </td>
                <td
                  style={{ padding: "6px 6px", fontWeight: 500, color: "#2D3748" }}
                  onClick={() => onRecordClick(rec)}
                >
                  {rec.title}
                </td>
                <td style={{ padding: "6px 6px", color: "#718096" }} onClick={() => onRecordClick(rec)}>
                  {rec.filetype ? rec.filetype.split("/").pop() : rec.filelocationtype === "E" ? "URL" : "—"}
                </td>
                <td style={{ padding: "6px 6px", color: "#718096" }} onClick={() => onRecordClick(rec)}>
                  {rec.foldername}
                </td>
                <td style={{ padding: "6px 6px", color: "#718096" }} onClick={() => onRecordClick(rec)}>
                  {rec.assigned_user_name}
                </td>
                <td style={{ padding: "6px 6px", color: "#718096" }} onClick={() => onRecordClick(rec)}>
                  {formatDate(rec.modifiedtime)}
                </td>
                <td style={{ padding: "6px 6px", color: "#718096" }} onClick={() => onRecordClick(rec)}>
                  {formatFileSize(rec.filesize)}
                </td>
                <td style={{ padding: "6px 4px", textAlign: "center" }} onClick={() => onRecordClick(rec)}>
                  {complianceStatusIcon(rec.compliance?.compliance_status, complianceStatusLabels, rec.compliance?.compliance_notes)}
                </td>
                <td style={{ padding: "6px 6px", color: "#718096", fontSize: 12 }} onClick={() => onRecordClick(rec)}>
                  {rec.compliance ? (documentCategoryLabels[rec.compliance.document_category] || "") : ""}
                </td>
                <td style={{ padding: "6px 6px", color: "#A0AEC0", fontSize: 12 }} onClick={() => onRecordClick(rec)}>
                  {truncate(rec.notecontent, 30)}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* ページネーション */}
      <div
        style={{
          display: "flex",
          justifyContent: "flex-end",
          alignItems: "center",
          gap: 8,
          padding: "8px 12px",
          borderTop: "1px solid #E2E8F0",
          fontSize: 13,
          color: "#718096",
        }}
      >
        <span>
          {(page - 1) * pageLimit + 1} - {Math.min(page * pageLimit, total)} / {total}
        </span>
        <button
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
          style={{
            padding: "2px 8px",
            border: "1px solid #E2E8F0",
            borderRadius: 3,
            background: "#fff",
            cursor: page <= 1 ? "not-allowed" : "pointer",
            opacity: page <= 1 ? 0.4 : 1,
          }}
        >
          &lt;
        </button>
        <button
          disabled={page >= totalPages}
          onClick={() => onPageChange(page + 1)}
          style={{
            padding: "2px 8px",
            border: "1px solid #E2E8F0",
            borderRadius: 3,
            background: "#fff",
            cursor: page >= totalPages ? "not-allowed" : "pointer",
            opacity: page >= totalPages ? 0.4 : 1,
          }}
        >
          &gt;
        </button>
      </div>
    </div>
  );
};
