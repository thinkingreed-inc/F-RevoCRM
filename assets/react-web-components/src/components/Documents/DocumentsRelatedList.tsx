import React, { useState, useCallback, useRef, useEffect } from "react";
import type {
  SortConfig,
  DocumentRecord,
  DocumentDetail,
} from "./types/documents";
import { FileIcon } from "./FileIcon";
import { StarButton } from "./StarButton";
import { DocumentDetailModal } from "./DocumentDetailModal";
import { DocumentCreateEditModal } from "./DocumentCreateEditModal";
import { DocumentSelectModal } from "./DocumentSelectModal";
import { useDocumentDetail } from "./hooks/useDocumentDetail";
import { useFolderTree } from "./hooks/useFolderTree";
import { useFileUpload } from "./hooks/useFileUpload";
import { deleteDocument } from "./utils/deleteDocument";
import { unlinkDocument } from "./utils/unlinkDocument";
import {
  UploadProgressBar,
  DuplicateConfirmDialog,
} from "./DocumentsUploadStatus";
import { TranslationProvider } from "../../contexts/TranslationContext";
import { useOptionalTranslation } from "../../hooks/useTranslation";

interface DocumentsRelatedListProps {
  parentModule: string;
  parentId: number;
}

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as any).csrfMagicName;
  const csrfToken = (window as any).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
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

/** 関連ドキュメント一覧を取得するフック（親レコード指定） */
function useRelatedDocumentsList(params: {
  parentModule: string;
  parentId: number;
  sort: SortConfig;
  page: number;
  pageLimit: number;
  searchKeyword: string;
}) {
  const [records, setRecords] = useState<DocumentRecord[]>([]);
  const [total, setTotal] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const abortRef = useRef<AbortController | null>(null);

  const fetchList = useCallback(async () => {
    if (abortRef.current) abortRef.current.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setIsLoading(true);
    try {
      const csrf = getCsrfToken();
      const body = new URLSearchParams();
      if (csrf) body.append(csrf.name, csrf.value);
      body.append("module", "Documents");
      body.append("api", "ListAPI");
      body.append("parent_module", params.parentModule);
      body.append("parent_id", String(params.parentId));
      body.append("sort_by", params.sort.field);
      body.append("sort_order", params.sort.order);
      body.append("page", String(params.page));
      body.append("pageLimit", String(params.pageLimit));
      if (params.searchKeyword) {
        body.append("search_keyword", params.searchKeyword);
      }

      const response = await fetch("index.php", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: body.toString(),
        signal: controller.signal,
      });
      const data = await response.json();
      if (data.success === false || data.error) {
        throw new Error(data.error?.message || "Failed to fetch");
      }
      // EMIT_PURE_JSONの場合はresultラッパーなし、通常レスポンスの場合はresultあり
      const result = data.result || data;
      setRecords(result.records);
      setTotal(result.total);
    } catch (e: any) {
      if (e.name !== "AbortError") {
        console.error("Related documents fetch error:", e);
      }
    } finally {
      setIsLoading(false);
    }
  }, [
    params.parentModule,
    params.parentId,
    params.sort.field,
    params.sort.order,
    params.page,
    params.pageLimit,
    params.searchKeyword,
  ]);

  useEffect(() => {
    fetchList();
  }, [fetchList]);

  return { records, total, isLoading, reload: fetchList };
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

export const DocumentsRelatedList: React.FC<DocumentsRelatedListProps> = (
  props,
) => (
  <TranslationProvider module="Documents">
    <DocumentsRelatedListInner {...props} />
  </TranslationProvider>
);

const DocumentsRelatedListInner: React.FC<DocumentsRelatedListProps> = ({
  parentModule,
  parentId,
}) => {
  const { t } = useOptionalTranslation();
  const [page, setPage] = useState(1);
  const [sort, setSort] = useState<SortConfig>({
    field: "modifiedtime",
    order: "DESC",
  });
  const [searchInput, setSearchInput] = useState("");
  const [searchKeyword, setSearchKeyword] = useState("");
  const searchDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pageLimit = 20;

  const { records, total, isLoading, reload } = useRelatedDocumentsList({
    parentModule,
    parentId,
    sort,
    page,
    pageLimit,
    searchKeyword,
  });

  const { folders } = useFolderTree();

  // 検索 debounce
  useEffect(() => {
    if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
    searchDebounceRef.current = setTimeout(() => {
      setSearchKeyword(searchInput);
      setPage(1);
    }, 300);
    return () => {
      if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
    };
  }, [searchInput]);

  // 詳細モーダル
  const [detailModalRecordId, setDetailModalRecordId] = useState<number | null>(
    null,
  );
  const {
    document: detailDocument,
    isLoading: detailLoading,
    reload: reloadDetail,
  } = useDocumentDetail(detailModalRecordId);

  // 登録/編集モーダル
  const [createEditModalOpen, setCreateEditModalOpen] = useState(false);
  const [createEditMode, setCreateEditMode] = useState<"create" | "edit">(
    "create",
  );
  const [editTargetDoc, setEditTargetDoc] = useState<DocumentDetail | null>(
    null,
  );

  // 既存ドキュメントの紐づけモーダル
  const [selectModalOpen, setSelectModalOpen] = useState(false);

  // D&D
  const [isDragging, setIsDragging] = useState(false);
  const dragCountRef = useRef(0);
  const {
    isUploading,
    uploadProgress,
    error: uploadError,
    errorArgs: uploadErrorArgs,
    duplicatePrompt,
    respondDuplicate,
    cancel: cancelUpload,
    uploadDrop,
  } = useFileUpload(() => {
    reload();
  });

  const handleDragEnter = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    dragCountRef.current++;
    if (dragCountRef.current === 1) setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    dragCountRef.current--;
    if (dragCountRef.current === 0) setIsDragging(false);
  }, []);

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
  }, []);

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();
      dragCountRef.current = 0;
      setIsDragging(false);
      // フォルダのドロップも階層ごと登録する
      uploadDrop(e.dataTransfer, 1, parentModule, parentId);
    },
    [uploadDrop, parentModule, parentId],
  );

  const handleSortChange = useCallback((newSort: SortConfig) => {
    setSort(newSort);
    setPage(1);
  }, []);

  const totalPages = Math.ceil(total / pageLimit);

  const handleDelete = useCallback(
    async (recordId: number) => {
      try {
        const result = await deleteDocument(recordId);
        if (!result.ok) {
          // 削除できなかったことを必ず伝える（電帳法対象など）
          alert(result.message || t("LBL_DELETE_FAILED"));
          return;
        }
        setDetailModalRecordId(null);
        reload();
      } catch {
        alert(t("LBL_DELETE_FAILED"));
      }
    },
    [reload, t],
  );

  /**
   * このレコードとの紐づけを解除する（ドキュメント自体は消さない）
   *
   * 電帳法対象のドキュメントは取引レコードとの紐づけが適合の条件になるため、
   * 解除で不適合になり得ることを確認時に伝える。
   */
  const handleUnlink = useCallback(
    async (rec: DocumentRecord) => {
      const note = rec.compliance
        ? "\n\n" + t("LBL_UNLINK_COMPLIANCE_NOTE")
        : "";
      if (!window.confirm(t("LBL_UNLINK_CONFIRM", rec.title) + note)) {
        return;
      }
      const result = await unlinkDocument({
        parentModule,
        parentId,
        recordId: rec.id,
      });
      if (!result.ok) {
        alert(
          result.denied > 0
            ? t("LBL_UNLINK_DENIED")
            : result.message || t("LBL_UNLINK_FAILED"),
        );
        return;
      }
      reload();
    },
    [parentModule, parentId, reload, t],
  );

  return (
    <div
      style={{ position: "relative" }}
      onDragEnter={handleDragEnter}
      onDragLeave={handleDragLeave}
      onDragOver={handleDragOver}
      onDrop={handleDrop}
    >
      {/* ツールバー */}
      <div
        style={{
          display: "flex",
          alignItems: "center",
          gap: 8,
          padding: "8px 12px",
          borderBottom: "1px solid #E2E8F0",
        }}
      >
        {/* 検索 */}
        <div style={{ position: "relative", flex: "0 1 280px", minWidth: 160 }}>
          <input
            type="text"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                if (searchDebounceRef.current)
                  clearTimeout(searchDebounceRef.current);
                setSearchKeyword(searchInput);
                setPage(1);
              }
            }}
            placeholder={t("LBL_SEARCH_DOCUMENTS")}
            style={{
              width: "100%",
              padding: "5px 28px 5px 28px",
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              fontSize: 13,
              outline: "none",
              boxSizing: "border-box",
            }}
          />
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="#A0AEC0"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            style={{
              position: "absolute",
              left: 8,
              top: "50%",
              transform: "translateY(-50%)",
              pointerEvents: "none",
            }}
          >
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
          {searchInput && (
            <button
              onClick={() => {
                setSearchInput("");
                setSearchKeyword("");
                setPage(1);
              }}
              style={{
                position: "absolute",
                right: 6,
                top: "50%",
                transform: "translateY(-50%)",
                border: "none",
                background: "none",
                cursor: "pointer",
                fontSize: 14,
                color: "#A0AEC0",
                padding: "0 2px",
                lineHeight: 1,
              }}
            >
              ×
            </button>
          )}
        </div>

        <div style={{ flex: 1 }} />

        {/* 件数表示 */}
        <span style={{ fontSize: 12, color: "#718096", whiteSpace: "nowrap" }}>
          {total > 0
            ? t(
                "LBL_PAGINATION_INFO",
                (page - 1) * pageLimit + 1,
                Math.min(page * pageLimit, total),
                total,
              )
            : t("LBL_PAGINATION_ZERO")}
        </span>

        {/* ページネーション */}
        {totalPages > 1 && (
          <div style={{ display: "flex", gap: 2 }}>
            <button
              disabled={page <= 1}
              onClick={() => setPage(page - 1)}
              style={{
                padding: "2px 8px",
                border: "1px solid #E2E8F0",
                borderRadius: 3,
                background: "#fff",
                cursor: page <= 1 ? "not-allowed" : "pointer",
                opacity: page <= 1 ? 0.4 : 1,
                fontSize: 12,
              }}
            >
              &lt;
            </button>
            <button
              disabled={page >= totalPages}
              onClick={() => setPage(page + 1)}
              style={{
                padding: "2px 8px",
                border: "1px solid #E2E8F0",
                borderRadius: 3,
                background: "#fff",
                cursor: page >= totalPages ? "not-allowed" : "pointer",
                opacity: page >= totalPages ? 0.4 : 1,
                fontSize: 12,
              }}
            >
              &gt;
            </button>
          </div>
        )}

        {/* 既存ドキュメントの紐づけボタン */}
        <button
          onClick={() => setSelectModalOpen(true)}
          style={{
            padding: "5px 14px",
            backgroundColor: "#fff",
            color: "#2B6CB0",
            border: "1px solid #CBD5E0",
            borderRadius: 4,
            fontSize: 13,
            cursor: "pointer",
            whiteSpace: "nowrap",
          }}
        >
          {t("LBL_SELECT_DOCUMENTS")}
        </button>

        {/* 新規追加ボタン */}
        <button
          onClick={() => {
            setCreateEditMode("create");
            setEditTargetDoc(null);
            setCreateEditModalOpen(true);
          }}
          style={{
            padding: "5px 14px",
            backgroundColor: "#4299E1",
            color: "#fff",
            border: "none",
            borderRadius: 4,
            fontSize: 13,
            cursor: "pointer",
            whiteSpace: "nowrap",
          }}
        >
          {t("LBL_ADD_DOCUMENT")}
        </button>
      </div>

      {/* テーブル */}
      <div style={{ overflowX: "auto" }}>
        <table
          style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}
        >
          <thead>
            <tr>
              <th
                style={{
                  width: 32,
                  padding: "8px 2px",
                  borderBottom: "2px solid #E2E8F0",
                }}
              />
              <th
                style={{
                  width: 40,
                  padding: "8px 2px",
                  borderBottom: "2px solid #E2E8F0",
                }}
              />
              <SortableHeader
                label={t("Title")}
                field="title"
                sort={sort}
                onSort={handleSortChange}
              />
              <SortableHeader
                label={t("Folder Name")}
                field="foldername"
                sort={sort}
                onSort={handleSortChange}
                style={{ width: 120 }}
              />
              <SortableHeader
                label={t("Assigned To")}
                field="assigned_user_id"
                sort={sort}
                onSort={handleSortChange}
                style={{ width: 100 }}
              />
              <SortableHeader
                label={t("Modified Time")}
                field="modifiedtime"
                sort={sort}
                onSort={handleSortChange}
                style={{ width: 110 }}
              />
              <SortableHeader
                label={t("File Size")}
                field="filesize"
                sort={sort}
                onSort={handleSortChange}
                style={{ width: 80 }}
              />
              <th
                style={{
                  width: 80,
                  padding: "8px 6px",
                  textAlign: "center",
                  fontSize: 12,
                  fontWeight: 600,
                  color: "#4A5568",
                  borderBottom: "2px solid #E2E8F0",
                }}
              >
                {t("LBL_COLUMN_ACTIONS")}
              </th>
            </tr>
          </thead>
          <tbody>
            {isLoading && records.length === 0 && (
              <tr>
                <td
                  colSpan={8}
                  style={{ padding: 32, textAlign: "center", color: "#A0AEC0" }}
                >
                  {t("LBL_LOADING")}
                </td>
              </tr>
            )}
            {!isLoading && records.length === 0 && (
              <tr>
                <td
                  colSpan={8}
                  style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}
                >
                  <div style={{ marginBottom: 8 }}>{t("LBL_NO_DOCUMENTS")}</div>
                  <div style={{ fontSize: 12 }}>{t("LBL_DROP_HELP_TEXT")}</div>
                </td>
              </tr>
            )}
            {records.map((rec) => (
              <tr
                key={rec.id}
                style={{ borderBottom: "1px solid #EDF2F7", cursor: "pointer" }}
                onMouseEnter={(e) =>
                  (e.currentTarget.style.backgroundColor = "#F7FAFC")
                }
                onMouseLeave={(e) =>
                  (e.currentTarget.style.backgroundColor = "")
                }
              >
                <td style={{ padding: "6px 2px", textAlign: "center" }}>
                  <StarButton recordId={rec.id} starred={rec.starred} />
                </td>
                <td
                  style={{ padding: "6px 4px" }}
                  onClick={() => setDetailModalRecordId(rec.id)}
                >
                  <FileIcon
                    filetype={rec.filetype}
                    filelocationtype={rec.filelocationtype}
                    filename={rec.filename}
                    size="sm"
                  />
                </td>
                <td
                  style={{
                    padding: "6px 6px",
                    fontWeight: 500,
                    color: "#2D3748",
                  }}
                  onClick={() => setDetailModalRecordId(rec.id)}
                >
                  {rec.title}
                  {rec.filename && rec.filename !== rec.title && (
                    <div
                      style={{
                        fontSize: 11,
                        color: "#A0AEC0",
                        fontWeight: 400,
                        marginTop: 1,
                      }}
                    >
                      {rec.filename}
                    </div>
                  )}
                </td>
                <td
                  style={{ padding: "6px 6px", color: "#718096" }}
                  onClick={() => setDetailModalRecordId(rec.id)}
                >
                  {rec.foldername}
                </td>
                <td
                  style={{ padding: "6px 6px", color: "#718096" }}
                  onClick={() => setDetailModalRecordId(rec.id)}
                >
                  {rec.assigned_user_name}
                </td>
                <td
                  style={{ padding: "6px 6px", color: "#718096" }}
                  onClick={() => setDetailModalRecordId(rec.id)}
                >
                  {formatDate(rec.modifiedtime)}
                </td>
                <td
                  style={{ padding: "6px 6px", color: "#718096" }}
                  onClick={() => setDetailModalRecordId(rec.id)}
                >
                  {formatFileSize(rec.filesize)}
                </td>
                <td style={{ padding: "6px 6px", textAlign: "center" }}>
                  <div
                    style={{
                      display: "flex",
                      gap: 6,
                      justifyContent: "center",
                    }}
                  >
                    {rec.download_url && (
                      <a
                        href={rec.download_url}
                        onClick={(e) => e.stopPropagation()}
                        title={t("Download")}
                        style={{
                          color: "#718096",
                          fontSize: 14,
                          textDecoration: "none",
                        }}
                      >
                        ⬇
                      </a>
                    )}
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleUnlink(rec);
                      }}
                      aria-label={t("LBL_UNLINK_DOCUMENT")}
                      title={t("LBL_UNLINK_DOCUMENT")}
                      style={{
                        border: "none",
                        background: "none",
                        cursor: "pointer",
                        color: "#718096",
                        padding: 0,
                        lineHeight: 1,
                        display: "flex",
                        alignItems: "center",
                      }}
                    >
                      {/* 鎖が切れた形（削除ではなく紐づけ解除であることを示す） */}
                      <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      >
                        <path d="M18.84 12.16a4 4 0 0 0 0-5.66l-1.34-1.34a4 4 0 0 0-5.66 0L10.5 6.5" />
                        <path d="M5.16 11.84a4 4 0 0 0 0 5.66l1.34 1.34a4 4 0 0 0 5.66 0l1.34-1.34" />
                        <line x1="2" y1="2" x2="22" y2="22" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* D&D オーバーレイ */}
      {isDragging && (
        <div
          style={{
            position: "absolute",
            inset: 0,
            backgroundColor: "rgba(49, 130, 206, 0.08)",
            border: "2px dashed #3182CE",
            borderRadius: 8,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 20,
            pointerEvents: "none",
          }}
        >
          <div style={{ fontSize: 14, color: "#3182CE", fontWeight: 600 }}>
            {t("LBL_DROP_FILES_HERE")}
          </div>
        </div>
      )}

      {/* アップロード進捗（関連リストは絶対配置しない） */}
      {isUploading && (
        <div style={{ position: "relative", paddingBottom: 40 }}>
          <UploadProgressBar
            progress={uploadProgress}
            onCancel={cancelUpload}
          />
        </div>
      )}

      {uploadError && (
        <div
          style={{
            padding: "8px 12px",
            backgroundColor: "#FED7D7",
            borderTop: "1px solid #FEB2B2",
            color: "#C53030",
            fontSize: 13,
          }}
        >
          {t(uploadError, ...uploadErrorArgs)}
        </div>
      )}

      {/* 同名ファイルの上書き確認 */}
      {duplicatePrompt && (
        <DuplicateConfirmDialog
          prompt={duplicatePrompt}
          onRespond={respondDuplicate}
        />
      )}

      {/* 詳細モーダル */}
      <DocumentDetailModal
        isOpen={detailModalRecordId !== null}
        document={detailDocument}
        isLoading={detailLoading}
        onClose={() => setDetailModalRecordId(null)}
        onEdit={(doc) => {
          setDetailModalRecordId(null);
          setTimeout(() => {
            setCreateEditMode("edit");
            setEditTargetDoc(doc);
            setCreateEditModalOpen(true);
          }, 100);
        }}
        onDelete={handleDelete}
        onRelationChanged={(unlinkedModule, unlinkedId) => {
          // このレコードとの紐づけを外した場合だけ一覧から消えるので閉じる
          if (unlinkedModule === parentModule && unlinkedId === parentId) {
            setDetailModalRecordId(null);
          } else {
            reloadDetail();
          }
          reload();
        }}
      />

      {/* 登録/編集モーダル */}
      <DocumentCreateEditModal
        isOpen={createEditModalOpen}
        mode={createEditMode}
        document={editTargetDoc}
        folders={folders}
        defaultFolderId={1}
        parentModule={parentModule}
        parentId={parentId}
        onSave={() => {
          setCreateEditModalOpen(false);
          setEditTargetDoc(null);
          reload();
          if (detailModalRecordId) reloadDetail();
        }}
        onClose={() => {
          setCreateEditModalOpen(false);
          setEditTargetDoc(null);
        }}
      />

      {/* 既存ドキュメントの紐づけモーダル */}
      <DocumentSelectModal
        isOpen={selectModalOpen}
        parentModule={parentModule}
        parentId={parentId}
        onLinked={() => {
          setSelectModalOpen(false);
          reload();
        }}
        onClose={() => setSelectModalOpen(false)}
        t={t}
      />
    </div>
  );
};
