import React, { useCallback, useEffect, useRef, useState } from "react";
import type { DocumentRecord } from "./types/documents";
import { FileIcon } from "./FileIcon";

interface DocumentSelectModalProps {
  isOpen: boolean;
  /** 親レコードのモジュール */
  parentModule: string;
  /** 親レコードID（このレコードに紐づいていないドキュメントを候補にする） */
  parentId: number;
  onClose: () => void;
  /** 紐づけが完了したときに呼ばれる（件数を渡す） */
  onLinked: (count: number) => void;
  t: (key: string, ...args: (string | number)[]) => string;
}

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

/** index.php へ POST して JSON を受け取る */
async function postJson<T>(
  params: Record<string, string>,
  arrayParams: Record<string, string[]> = {},
  signal?: AbortSignal,
): Promise<T> {
  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  for (const [key, value] of Object.entries(params)) {
    body.append(key, value);
  }
  for (const [key, values] of Object.entries(arrayParams)) {
    for (const value of values) {
      body.append(`${key}[]`, value);
    }
  }

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: body.toString(),
    signal,
  });
  const data = await response.json();
  if (data.success === false || data.error) {
    throw new Error(data.error?.message || "Request failed");
  }
  return (data.result || data) as T;
}

const PAGE_LIMIT = 10;

/**
 * 既存ドキュメントを選択して親レコードに紐づけるモーダル
 *
 * 候補は親レコードにまだ紐づいていない有効なドキュメント。
 */
export const DocumentSelectModal: React.FC<DocumentSelectModalProps> = ({
  isOpen,
  parentModule,
  parentId,
  onClose,
  onLinked,
  t,
}) => {
  const [records, setRecords] = useState<DocumentRecord[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [searchInput, setSearchInput] = useState("");
  const [searchKeyword, setSearchKeyword] = useState("");
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isLinking, setIsLinking] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortRef = useRef<AbortController | null>(null);
  const searchDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // 開くたびに状態を初期化する
  useEffect(() => {
    if (isOpen) {
      setPage(1);
      setSearchInput("");
      setSearchKeyword("");
      setSelectedIds([]);
      setError(null);
    }
  }, [isOpen]);

  // 検索の入力を間引く
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

  // 候補の取得
  useEffect(() => {
    if (!isOpen) return;
    if (abortRef.current) abortRef.current.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    (async () => {
      setIsLoading(true);
      setError(null);
      try {
        const params: Record<string, string> = {
          module: "Documents",
          api: "ListAPI",
          exclude_parent_id: String(parentId),
          active_only: "true",
          sort_by: "modifiedtime",
          sort_order: "DESC",
          page: String(page),
          pageLimit: String(PAGE_LIMIT),
        };
        if (searchKeyword) params.search_keyword = searchKeyword;
        const result = await postJson<{
          records: DocumentRecord[];
          total: number;
        }>(params, {}, controller.signal);
        setRecords(result.records || []);
        setTotal(result.total || 0);
      } catch (e) {
        if ((e as Error).name !== "AbortError") {
          setError(e instanceof Error ? e.message : String(e));
        }
      } finally {
        setIsLoading(false);
      }
    })();

    return () => controller.abort();
  }, [isOpen, parentId, page, searchKeyword]);

  const toggle = useCallback((recordId: number) => {
    setSelectedIds((current) =>
      current.includes(recordId)
        ? current.filter((id) => id !== recordId)
        : [...current, recordId],
    );
  }, []);

  const handleLink = useCallback(async () => {
    if (selectedIds.length === 0) return;
    setIsLinking(true);
    setError(null);
    try {
      const result = await postJson<{ linked: number }>(
        {
          module: "Documents",
          api: "RelationAPI",
          mode: "link",
          parent_module: parentModule,
          parent_id: String(parentId),
        },
        { records: selectedIds.map(String) },
      );
      onLinked(result.linked ?? selectedIds.length);
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setIsLinking(false);
    }
  }, [onLinked, parentId, parentModule, selectedIds]);

  if (!isOpen) return null;

  const totalPages = Math.max(1, Math.ceil(total / PAGE_LIMIT));
  const tdStyle: React.CSSProperties = {
    padding: "6px 8px",
    fontSize: 13,
    color: "#2D3748",
    borderBottom: "1px solid #F1F5F9",
  };

  return (
    <div
      onClick={onClose}
      style={{
        position: "fixed",
        inset: 0,
        backgroundColor: "rgba(0,0,0,0.4)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: 1050,
      }}
    >
      <div
        onClick={(e) => e.stopPropagation()}
        style={{
          width: "min(720px, 94vw)",
          maxHeight: "86vh",
          display: "flex",
          flexDirection: "column",
          backgroundColor: "#fff",
          borderRadius: 6,
          boxShadow: "0 10px 30px rgba(0,0,0,0.25)",
        }}
      >
        {/* ヘッダー */}
        <div
          style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            padding: "12px 16px",
            borderBottom: "1px solid #E2E8F0",
          }}
        >
          <h4
            style={{
              margin: 0,
              fontSize: 15,
              fontWeight: 600,
              color: "#2D3748",
            }}
          >
            {t("LBL_SELECT_DOCUMENTS")}
          </h4>
          <button
            type="button"
            onClick={onClose}
            aria-label={t("LBL_CLOSE")}
            style={{
              border: "none",
              background: "transparent",
              fontSize: 18,
              color: "#A0AEC0",
              cursor: "pointer",
            }}
          >
            ×
          </button>
        </div>

        {/* 検索 */}
        <div style={{ padding: "10px 16px" }}>
          <input
            type="text"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            placeholder={t("LBL_SEARCH_DOCUMENTS")}
            style={{
              width: "100%",
              padding: "6px 10px",
              fontSize: 13,
              border: "1px solid #CBD5E0",
              borderRadius: 4,
              boxSizing: "border-box",
            }}
          />
        </div>

        {error && (
          <div
            style={{
              margin: "0 16px 10px",
              padding: "8px 12px",
              backgroundColor: "#FED7D7",
              color: "#C53030",
              borderRadius: 4,
              fontSize: 13,
            }}
          >
            {error}
          </div>
        )}

        {/* 候補一覧 */}
        <div style={{ flex: 1, overflowY: "auto", padding: "0 16px" }}>
          <table style={{ width: "100%", borderCollapse: "collapse" }}>
            <tbody>
              {isLoading && (
                <tr>
                  <td colSpan={3} style={{ ...tdStyle, color: "#A0AEC0" }}>
                    {t("LBL_LOADING")}
                  </td>
                </tr>
              )}
              {!isLoading && records.length === 0 && (
                <tr>
                  <td
                    colSpan={3}
                    style={{
                      ...tdStyle,
                      color: "#A0AEC0",
                      textAlign: "center",
                      padding: 28,
                    }}
                  >
                    {t("LBL_NO_DOCUMENTS_TO_LINK")}
                  </td>
                </tr>
              )}
              {!isLoading &&
                records.map((record) => (
                  <tr key={record.id}>
                    <td style={{ ...tdStyle, width: 32 }}>
                      <input
                        type="checkbox"
                        checked={selectedIds.includes(record.id)}
                        onChange={() => toggle(record.id)}
                        style={{ margin: 0, cursor: "pointer" }}
                      />
                    </td>
                    <td style={tdStyle}>
                      <label
                        style={{
                          display: "flex",
                          alignItems: "center",
                          gap: 6,
                          margin: 0,
                          fontWeight: 400,
                          cursor: "pointer",
                        }}
                      >
                        <FileIcon
                          filetype={record.filetype}
                          filelocationtype={record.filelocationtype}
                          filename={record.filename}
                          size="sm"
                        />
                        <span
                          onClick={() => toggle(record.id)}
                          style={{ cursor: "pointer" }}
                        >
                          {record.title}
                        </span>
                      </label>
                    </td>
                    <td
                      style={{
                        ...tdStyle,
                        width: 160,
                        color: "#718096",
                        fontSize: 12,
                      }}
                    >
                      {record.foldername}
                    </td>
                  </tr>
                ))}
            </tbody>
          </table>
        </div>

        {/* フッター */}
        <div
          style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            gap: 8,
            padding: "10px 16px",
            borderTop: "1px solid #E2E8F0",
          }}
        >
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: 8,
              fontSize: 12,
              color: "#718096",
            }}
          >
            {totalPages > 1 && (
              <>
                <button
                  type="button"
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  disabled={page <= 1}
                  style={{
                    padding: "2px 8px",
                    border: "1px solid #CBD5E0",
                    borderRadius: 3,
                    backgroundColor: "#fff",
                    cursor: page <= 1 ? "not-allowed" : "pointer",
                    opacity: page <= 1 ? 0.4 : 1,
                  }}
                >
                  &lt;
                </button>
                <span>
                  {page} / {totalPages}
                </span>
                <button
                  type="button"
                  onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                  disabled={page >= totalPages}
                  style={{
                    padding: "2px 8px",
                    border: "1px solid #CBD5E0",
                    borderRadius: 3,
                    backgroundColor: "#fff",
                    cursor: page >= totalPages ? "not-allowed" : "pointer",
                    opacity: page >= totalPages ? 0.4 : 1,
                  }}
                >
                  &gt;
                </button>
              </>
            )}
            <span>
              {t("LBL_SELECTED_COUNT")}: {selectedIds.length}
            </span>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <button
              type="button"
              onClick={onClose}
              style={{
                padding: "6px 14px",
                fontSize: 13,
                border: "1px solid #CBD5E0",
                borderRadius: 4,
                backgroundColor: "#fff",
                color: "#4A5568",
                cursor: "pointer",
              }}
            >
              {t("LBL_CANCEL")}
            </button>
            <button
              type="button"
              onClick={handleLink}
              disabled={isLinking || selectedIds.length === 0}
              style={{
                padding: "6px 16px",
                fontSize: 13,
                border: "none",
                borderRadius: 4,
                backgroundColor:
                  isLinking || selectedIds.length === 0 ? "#A0AEC0" : "#38A169",
                color: "#fff",
                fontWeight: 500,
                cursor:
                  isLinking || selectedIds.length === 0 ? "default" : "pointer",
              }}
            >
              {isLinking ? t("LBL_SAVING") : t("LBL_LINK_DOCUMENTS")}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
