import React, { useState, useCallback, useRef, useEffect } from "react";
import type { SortConfig, FilterType, DocumentRecord, ViewMode, Folder, DocumentDetail } from "./types/documents";
import { DocumentsFolderTree } from "./DocumentsFolderTree";
import { DocumentsListView } from "./DocumentsListView";
import { DocumentsGridView } from "./DocumentsGridView";
import { DocumentsPreviewView } from "./DocumentsPreviewView";
import { FolderDialog } from "./FolderDialog";
import { DocumentDetailModal } from "./DocumentDetailModal";
import { DocumentCreateEditModal } from "./DocumentCreateEditModal";
import { useDocumentsList } from "./hooks/useDocumentsList";
import { useDocumentDetail } from "./hooks/useDocumentDetail";
import { useFolderTree } from "./hooks/useFolderTree";
import { useViewMode } from "./hooks/useViewMode";
import { useFileUpload } from "./hooks/useFileUpload";
import { TranslationProvider } from "../../contexts/TranslationContext";
import { useOptionalTranslation } from "../../hooks/useTranslation";

interface DocumentsPageProps {
  folderId?: number;
  userId?: string;
  initialViewMode?: string;
}

const VIEW_MODE_ICONS: Record<ViewMode, string> = {
  list: "☰",
  grid: "⊞",
  preview: "⊟",
};

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as any).csrfMagicName;
  const csrfToken = (window as any).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

// スマホ判定用フック
function useIsMobile(breakpoint = 768) {
  const [isMobile, setIsMobile] = useState(window.innerWidth <= breakpoint);
  useEffect(() => {
    const handler = () => setIsMobile(window.innerWidth <= breakpoint);
    window.addEventListener("resize", handler);
    return () => window.removeEventListener("resize", handler);
  }, [breakpoint]);
  return isMobile;
}

export const DocumentsPage: React.FC<DocumentsPageProps> = (props) => (
  <TranslationProvider module="Documents">
    <DocumentsPageInner {...props} />
  </TranslationProvider>
);

const DocumentsPageInner: React.FC<DocumentsPageProps> = ({
  folderId: initialFolderId,
  initialViewMode,
}) => {
  const { t } = useOptionalTranslation();
  const { viewMode, setViewMode } = useViewMode(initialViewMode);
  const isMobile = useIsMobile();
  const [mobileDrawerOpen, setMobileDrawerOpen] = useState(false);
  const [selectedFolderId, setSelectedFolderId] = useState<number | "all">(initialFolderId || "all");
  const [filterType, setFilterType] = useState<FilterType>("all");
  const [page, setPage] = useState(1);
  const [sort, setSort] = useState<SortConfig>({ field: "modifiedtime", order: "DESC" });
  const [searchKeyword, setSearchKeyword] = useState("");
  const [searchInput, setSearchInput] = useState("");
  const searchDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // 電帳法フィルター
  const [complianceFilter, setComplianceFilter] = useState(false);
  const [complianceCategoryFilter, setComplianceCategoryFilter] = useState("");
  const [complianceStatusFilter, setComplianceStatusFilter] = useState("");
  const [unrealatedFilter, setUnrelatedFilter] = useState("");

  const { folders, totalCount, starredCount, reload: reloadFolders } = useFolderTree();

  const { records, total, isLoading, reload: reloadList } = useDocumentsList({
    folderId: selectedFolderId,
    filterType,
    searchKeyword,
    sort,
    page,
    pageLimit: 20,
    complianceFilter,
    documentCategory: complianceCategoryFilter,
    complianceStatus: complianceStatusFilter,
    hasRelatedRecord: unrealatedFilter,
  });

  // 検索入力のdebounce
  useEffect(() => {
    if (searchDebounceRef.current) {
      clearTimeout(searchDebounceRef.current);
    }
    searchDebounceRef.current = setTimeout(() => {
      setSearchKeyword(searchInput);
      setPage(1);
    }, 300);
    return () => {
      if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
    };
  }, [searchInput]);

  // フォルダダイアログ
  const [folderDialogOpen, setFolderDialogOpen] = useState(false);
  const [folderDialogMode, setFolderDialogMode] = useState<"create" | "edit">("create");
  const [folderDialogParentId, setFolderDialogParentId] = useState(0);
  const [folderDialogTarget, setFolderDialogTarget] = useState<Folder | null>(null);

  // 詳細モーダル
  const [detailModalRecordId, setDetailModalRecordId] = useState<number | null>(null);
  const { document: detailDocument, isLoading: detailLoading, reload: reloadDetail } = useDocumentDetail(detailModalRecordId);

  // 登録/編集モーダル
  const [createEditModalOpen, setCreateEditModalOpen] = useState(false);
  const [createEditMode, setCreateEditMode] = useState<"create" | "edit">("create");
  const [editTargetDoc, setEditTargetDoc] = useState<DocumentDetail | null>(null);

  // D&D
  const [isDragging, setIsDragging] = useState(false);
  const dragCountRef = useRef(0);
  const { isUploading, progress, error: uploadError, upload } = useFileUpload(() => {
    reloadList();
    reloadFolders();
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

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    dragCountRef.current = 0;
    setIsDragging(false);
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      const targetFolder = typeof selectedFolderId === "number" ? selectedFolderId : 1;
      upload(files, targetFolder);
    }
  }, [upload, selectedFolderId]);

  const handleFolderSelect = useCallback((id: number | "all") => {
    setSelectedFolderId(id);
    setPage(1);
  }, []);

  const handleFilterTypeChange = useCallback((type: FilterType) => {
    setFilterType(type);
    setPage(1);
  }, []);

  const handleRecordClick = useCallback((record: DocumentRecord) => {
    if (viewMode !== "preview") {
      setDetailModalRecordId(record.id);
    }
  }, [viewMode]);

  const handleSortChange = useCallback((newSort: SortConfig) => {
    setSort(newSort);
    setPage(1);
  }, []);

  // フォルダ操作
  const handleFolderCreate = useCallback((parentId: number) => {
    setFolderDialogMode("create");
    setFolderDialogParentId(parentId);
    setFolderDialogTarget(null);
    setFolderDialogOpen(true);
  }, []);

  const handleFolderEdit = useCallback((folder: Folder) => {
    setFolderDialogMode("edit");
    setFolderDialogParentId(folder.parent_id);
    setFolderDialogTarget(folder);
    setFolderDialogOpen(true);
  }, []);

  const handleFolderSave = useCallback(async (data: {
    foldername: string;
    folderdesc: string;
    parent_folderid: number;
    savemode?: string;
    folderid?: number;
  }) => {
    const csrf = getCsrfToken();
    const bodyParams = new URLSearchParams();
    if (csrf) bodyParams.append(csrf.name, csrf.value);
    bodyParams.append("module", "Documents");
    bodyParams.append("api", "FolderAPI");
    bodyParams.append("mode", "save");
    bodyParams.append("foldername", data.foldername);
    bodyParams.append("folderdesc", data.folderdesc);
    bodyParams.append("parent_folderid", String(data.parent_folderid));
    if (data.savemode) bodyParams.append("savemode", data.savemode);
    if (data.folderid) bodyParams.append("folderid", String(data.folderid));

    try {
      const response = await fetch("index.php", {
        method: "POST",
        credentials: "same-origin",
        headers: { Accept: "application/json", "Content-Type": "application/x-www-form-urlencoded" },
        body: bodyParams.toString(),
      });
      const result = await response.json();
      if (result.error) {
        alert(result.error.message || t('LBL_FOLDER_SAVE_FAILED'));
        return;
      }
      setFolderDialogOpen(false);
      reloadFolders();
      reloadList();
    } catch {
      alert(t('LBL_FOLDER_SAVE_FAILED'));
    }
  }, [reloadFolders, reloadList, t]);

  const handleFolderDelete = useCallback(async (folderId: number) => {
    const csrf = getCsrfToken();
    const bodyParams = new URLSearchParams();
    if (csrf) bodyParams.append(csrf.name, csrf.value);
    bodyParams.append("module", "Documents");
    bodyParams.append("api", "FolderAPI");
    bodyParams.append("mode", "delete");
    bodyParams.append("folderid", String(folderId));

    try {
      const response = await fetch("index.php", {
        method: "POST",
        credentials: "same-origin",
        headers: { Accept: "application/json", "Content-Type": "application/x-www-form-urlencoded" },
        body: bodyParams.toString(),
      });
      const result = await response.json();
      if (result.error) {
        alert(result.error.message || t('LBL_FOLDER_DELETE_FAILED'));
        return;
      }
      setFolderDialogOpen(false);
      if (selectedFolderId === folderId) {
        setSelectedFolderId("all");
      }
      reloadFolders();
      reloadList();
    } catch {
      alert(t('LBL_FOLDER_DELETE_FAILED'));
    }
  }, [selectedFolderId, reloadFolders, reloadList, t]);

  // パンくずリスト
  const selectedFolder = folders.find((f) => f.id === selectedFolderId);
  const getBreadcrumb = (): { id: number; name: string }[] => {
    if (selectedFolderId === "all") return [];
    const path: { id: number; name: string }[] = [];
    let current = folders.find((f) => f.id === selectedFolderId);
    while (current) {
      path.unshift({ id: current.id, name: current.name });
      current = current.parent_id > 0 ? folders.find((f) => f.id === current!.parent_id) : undefined;
    }
    return path;
  };
  const breadcrumb = getBreadcrumb();

  const viewModeTitles: Record<string, string> = {
    list: t('LBL_VIEW_LIST'),
    grid: t('LBL_VIEW_GRID'),
    preview: t('LBL_VIEW_PREVIEW'),
  };

  return (
    <div
      style={{ display: "flex", flexDirection: "column", height: "calc(100vh - 120px)", backgroundColor: "#fff" }}
      onDragEnter={handleDragEnter}
      onDragLeave={handleDragLeave}
      onDragOver={handleDragOver}
      onDrop={handleDrop}
    >
      {/* ツールバー */}
      <div style={{ display: "flex", alignItems: "center", gap: isMobile ? 6 : 8, padding: isMobile ? "4px 8px" : "8px 16px", borderBottom: "1px solid #E2E8F0" }}>
        {/* スマホ: フォルダドロワー開閉ボタン */}
        {isMobile && (
          <button
            onClick={() => setMobileDrawerOpen(!mobileDrawerOpen)}
            style={{
              border: "none", background: "none", cursor: "pointer",
              fontSize: 18, padding: "2px 4px", color: "#4A5568", lineHeight: 1, flexShrink: 0,
            }}
          >
            ≡
          </button>
        )}

        {/* PC: パンくず */}
        {!isMobile && (
          <div style={{ display: "flex", alignItems: "center", gap: 4, fontSize: 13, color: "#718096", flex: 1, overflow: "hidden" }}>
            <span
              onClick={() => { handleFolderSelect("all"); handleFilterTypeChange("all"); }}
              style={{ cursor: "pointer", color: "#4299E1" }}
            >
              {t('LBL_DOCUMENTS')}
            </span>
            {breadcrumb.map((item) => (
              <React.Fragment key={item.id}>
                <span style={{ color: "#CBD5E0" }}>&gt;</span>
                <span
                  onClick={() => handleFolderSelect(item.id)}
                  style={{ cursor: "pointer", color: item.id === selectedFolderId ? "#2D3748" : "#4299E1", fontWeight: item.id === selectedFolderId ? 600 : 400 }}
                >
                  {item.name}
                </span>
              </React.Fragment>
            ))}
            {filterType === "starred" && <><span style={{ color: "#CBD5E0" }}>&gt;</span><span style={{ fontWeight: 600 }}>{t('LBL_STARRED')}</span></>}
          </div>
        )}

        {/* 全文検索バー */}
        <div style={{ position: "relative", flex: isMobile ? 1 : "0 1 300px", minWidth: isMobile ? 0 : 200, maxWidth: isMobile ? undefined : 360 }}>
          <input
            type="text"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
                setSearchKeyword(searchInput);
                setPage(1);
              }
            }}
            placeholder={t('LBL_SEARCH_DOCUMENTS')}
            style={{
              width: "100%",
              padding: "5px 30px 5px 28px",
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              fontSize: 13,
              outline: "none",
              boxSizing: "border-box",
            }}
          />
          <svg
            width="14" height="14" viewBox="0 0 24 24" fill="none"
            stroke="#A0AEC0" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
            style={{ position: "absolute", left: 8, top: "50%", transform: "translateY(-50%)", pointerEvents: "none" }}
          >
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          {searchInput && (
            <button
              onClick={() => { setSearchInput(""); setSearchKeyword(""); setPage(1); }}
              style={{
                position: "absolute", right: 6, top: "50%", transform: "translateY(-50%)",
                border: "none", background: "none", cursor: "pointer", fontSize: 14,
                color: "#A0AEC0", padding: "0 2px", lineHeight: 1,
              }}
            >
              ×
            </button>
          )}
        </div>

        {/* 電帳法フィルター（PCのみ） */}
        {!isMobile && (
          <div style={{ display: "flex", alignItems: "center", gap: 4 }}>
            <label style={{ display: "flex", alignItems: "center", gap: 3, fontSize: 12, color: "#718096", cursor: "pointer", whiteSpace: "nowrap" }}>
              <input
                type="checkbox"
                checked={complianceFilter}
                onChange={(e) => {
                  setComplianceFilter(e.target.checked);
                  if (!e.target.checked) {
                    setComplianceCategoryFilter("");
                    setComplianceStatusFilter("");
                    setUnrelatedFilter("");
                  }
                  setPage(1);
                }}
              />
              {t('LBL_COMPLIANCE_FILTER_LABEL')}
            </label>
            {complianceFilter && (
              <>
                <select
                  value={complianceCategoryFilter}
                  onChange={(e) => { setComplianceCategoryFilter(e.target.value); setPage(1); }}
                  style={{ padding: "3px 4px", border: "1px solid #E2E8F0", borderRadius: 3, fontSize: 11, color: "#4A5568" }}
                >
                  <option value="">{t('LBL_ALL_CATEGORIES')}</option>
                  <option value="invoice">{t('LBL_CATEGORY_INVOICE')}</option>
                  <option value="receipt">{t('LBL_CATEGORY_RECEIPT')}</option>
                  <option value="contract">{t('LBL_CATEGORY_CONTRACT')}</option>
                  <option value="estimate">{t('LBL_CATEGORY_ESTIMATE')}</option>
                  <option value="order">{t('LBL_CATEGORY_ORDER')}</option>
                  <option value="delivery">{t('LBL_CATEGORY_DELIVERY')}</option>
                  <option value="other">{t('LBL_CATEGORY_OTHER')}</option>
                </select>
                <select
                  value={complianceStatusFilter}
                  onChange={(e) => { setComplianceStatusFilter(e.target.value); setPage(1); }}
                  style={{ padding: "3px 4px", border: "1px solid #E2E8F0", borderRadius: 3, fontSize: 11, color: "#4A5568" }}
                >
                  <option value="">{t('LBL_ALL_STATUSES')}</option>
                  <option value="compliant">{t('LBL_STATUS_COMPLIANT')}</option>
                  <option value="non_compliant">{t('LBL_STATUS_NON_COMPLIANT')}</option>
                </select>
                <select
                  value={unrealatedFilter}
                  onChange={(e) => { setUnrelatedFilter(e.target.value); setPage(1); }}
                  style={{ padding: "3px 4px", border: "1px solid #E2E8F0", borderRadius: 3, fontSize: 11, color: "#4A5568" }}
                >
                  <option value="">{t('LBL_ALL_RECORDS')}</option>
                  <option value="false">{t('LBL_UNRELATED_ONLY')}</option>
                </select>
              </>
            )}
          </div>
        )}

        {/* 表示モード切替（スマホでは非表示） */}
        {!isMobile && (
          <div style={{ display: "flex", gap: 2, border: "1px solid #E2E8F0", borderRadius: 4, overflow: "hidden" }}>
            {(["list", "grid", "preview"] as ViewMode[]).map((mode) => (
              <button
                key={mode}
                onClick={() => setViewMode(mode)}
                style={{
                  padding: "4px 10px",
                  border: "none",
                  backgroundColor: viewMode === mode ? "#4299E1" : "#fff",
                  color: viewMode === mode ? "#fff" : "#718096",
                  cursor: "pointer",
                  fontSize: 14,
                  lineHeight: 1,
                }}
                title={viewModeTitles[mode]}
              >
                {VIEW_MODE_ICONS[mode]}
              </button>
            ))}
          </div>
        )}

        {/* 新規追加ボタン */}
        <button
          onClick={() => {
            setCreateEditMode("create");
            setEditTargetDoc(null);
            setCreateEditModalOpen(true);
          }}
          style={{
            padding: isMobile ? "5px 10px" : "5px 14px",
            backgroundColor: "#4299E1",
            color: "#fff",
            border: "none",
            borderRadius: 4,
            fontSize: 13,
            cursor: "pointer",
            whiteSpace: "nowrap",
            flexShrink: 0,
          }}
        >
          {isMobile ? "+" : t('LBL_ADD_DOCUMENT')}
        </button>
      </div>

      {/* メインエリア */}
      <div style={{ flex: 1, display: "flex", overflow: "hidden", position: "relative" }}>
        {/* スマホ: フォルダドロワー（オーバーレイ） */}
        {isMobile && mobileDrawerOpen && (
          <>
            <div
              onClick={() => setMobileDrawerOpen(false)}
              style={{
                position: "absolute", inset: 0, backgroundColor: "rgba(0,0,0,0.3)",
                zIndex: 30,
              }}
            />
            <div style={{
              position: "absolute", left: 0, top: 0, bottom: 0,
              width: "80%", maxWidth: 320, backgroundColor: "#fff",
              zIndex: 31, boxShadow: "2px 0 8px rgba(0,0,0,0.15)",
              overflow: "auto",
            }}>
              <DocumentsFolderTree
                folders={folders}
                totalCount={totalCount}
                starredCount={starredCount}
                selectedFolderId={selectedFolderId}
                filterType={filterType}
                onFolderSelect={(id) => { handleFolderSelect(id); setMobileDrawerOpen(false); }}
                onFilterTypeChange={(type) => { handleFilterTypeChange(type); setMobileDrawerOpen(false); }}
                onFolderCreate={handleFolderCreate}
                onFolderEdit={handleFolderEdit}
                onFolderDelete={handleFolderDelete}
              />
            </div>
          </>
        )}

        {/* PC: フォルダツリー（常時表示） */}
        {!isMobile && (
          <DocumentsFolderTree
            folders={folders}
            totalCount={totalCount}
            starredCount={starredCount}
            selectedFolderId={selectedFolderId}
            filterType={filterType}
            onFolderSelect={handleFolderSelect}
            onFilterTypeChange={handleFilterTypeChange}
            onFolderCreate={handleFolderCreate}
            onFolderEdit={handleFolderEdit}
            onFolderDelete={handleFolderDelete}
          />
        )}

        {/* スマホ時はグリッド固定、PC時は選択モードで表示 */}
        {(isMobile || viewMode === "grid") && (
          <DocumentsGridView
            records={records}
            total={total}
            page={page}
            pageLimit={20}
            isLoading={isLoading}
            folders={folders}
            selectedFolderId={selectedFolderId}
            onPageChange={setPage}
            onRecordClick={handleRecordClick}
            onFolderClick={handleFolderSelect}
          />
        )}
        {!isMobile && viewMode === "list" && (
          <DocumentsListView
            records={records}
            total={total}
            page={page}
            pageLimit={20}
            sort={sort}
            isLoading={isLoading}
            folders={folders}
            selectedFolderId={selectedFolderId}
            onSortChange={handleSortChange}
            onPageChange={setPage}
            onRecordClick={handleRecordClick}
            onFolderClick={handleFolderSelect}
          />
        )}
        {!isMobile && viewMode === "preview" && (
          <DocumentsPreviewView
            records={records}
            total={total}
            isLoading={isLoading}
            folderName={selectedFolder?.name || t('LBL_ALL_DOCUMENTS')}
            folders={folders}
            selectedFolderId={selectedFolderId}
            onFolderClick={handleFolderSelect}
            onEdit={(doc) => {
              setCreateEditMode("edit");
              setEditTargetDoc(doc);
              setCreateEditModalOpen(true);
            }}
          />
        )}

        {isDragging && (
          <div style={{
            position: "absolute", inset: 0, backgroundColor: "rgba(49, 130, 206, 0.08)",
            border: "2px dashed #3182CE", borderRadius: 8, display: "flex",
            alignItems: "center", justifyContent: "center", zIndex: 20, pointerEvents: "none",
          }}>
            <div style={{ fontSize: 16, color: "#3182CE", fontWeight: 600 }}>
              {t('LBL_DROP_FILES_HERE')}
            </div>
          </div>
        )}

        {isUploading && (
          <div style={{
            position: "absolute", bottom: 0, left: 220, right: 0, padding: "8px 16px",
            backgroundColor: "#EBF8FF", borderTop: "1px solid #BEE3F8", zIndex: 21,
          }}>
            <div style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 13 }}>
              <span>{t('LBL_UPLOADING_PROGRESS', progress)}</span>
              <div style={{ flex: 1, height: 4, backgroundColor: "#BEE3F8", borderRadius: 2 }}>
                <div style={{ width: `${progress}%`, height: "100%", backgroundColor: "#3182CE", borderRadius: 2, transition: "width 0.2s" }} />
              </div>
            </div>
          </div>
        )}

        {uploadError && (
          <div style={{
            position: "absolute", bottom: 0, left: 220, right: 0, padding: "8px 16px",
            backgroundColor: "#FED7D7", borderTop: "1px solid #FEB2B2", color: "#C53030", fontSize: 13, zIndex: 21,
          }}>
            {uploadError}
          </div>
        )}
      </div>

      {/* フォルダ作成/編集ダイアログ */}
      <FolderDialog
        isOpen={folderDialogOpen}
        mode={folderDialogMode}
        folder={folderDialogTarget}
        parentFolderId={folderDialogParentId}
        folders={folders}
        onSave={handleFolderSave}
        onDelete={handleFolderDelete}
        onClose={() => setFolderDialogOpen(false)}
      />

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
        onDelete={async (recordId) => {
          const csrf = getCsrfToken();
          if (!csrf) return;
          const bodyParams = new URLSearchParams();
          bodyParams.append(csrf.name, csrf.value);
          bodyParams.append("module", "Documents");
          bodyParams.append("action", "DeleteAjax");
          bodyParams.append("record", String(recordId));
          try {
            await fetch("index.php", {
              method: "POST", credentials: "same-origin",
              headers: { Accept: "application/json", "Content-Type": "application/x-www-form-urlencoded" },
              body: bodyParams.toString(),
            });
            setDetailModalRecordId(null);
            reloadList();
            reloadFolders();
          } catch {
            alert(t('LBL_DELETE_FAILED'));
          }
        }}
      />

      {/* 登録/編集モーダル */}
      <DocumentCreateEditModal
        isOpen={createEditModalOpen}
        mode={createEditMode}
        document={editTargetDoc}
        folders={folders}
        defaultFolderId={typeof selectedFolderId === "number" ? selectedFolderId : 1}
        onSave={() => {
          setCreateEditModalOpen(false);
          setEditTargetDoc(null);
          reloadList();
          reloadFolders();
          // 編集した場合は詳細も更新
          if (detailModalRecordId) reloadDetail();
        }}
        onClose={() => {
          setCreateEditModalOpen(false);
          setEditTargetDoc(null);
        }}
      />
    </div>
  );
};
