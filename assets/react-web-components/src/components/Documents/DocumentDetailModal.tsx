import React, { useState, useEffect } from "react";
import type { DocumentDetail } from "./types/documents";
import { FilePreviewRenderer } from "./FilePreviewRenderer";

interface DocumentDetailModalProps {
  isOpen: boolean;
  document: DocumentDetail | null;
  isLoading: boolean;
  onClose: () => void;
  onEdit: (doc: DocumentDetail) => void;
  onDelete: (recordId: number) => void;
}

function formatFileSize(bytes: number): string {
  if (!bytes || bytes === 0) return "—";
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

function getLocationLabel(type: string): string {
  switch (type) {
    case "I": return "ファイル";
    case "E": return "URL";
    default: return type;
  }
}

function useIsMobile(breakpoint = 768) {
  const [isMobile, setIsMobile] = useState(window.innerWidth <= breakpoint);
  useEffect(() => {
    const handler = () => setIsMobile(window.innerWidth <= breakpoint);
    window.addEventListener("resize", handler);
    return () => window.removeEventListener("resize", handler);
  }, [breakpoint]);
  return isMobile;
}

const InfoRow: React.FC<{ label: string; value: React.ReactNode }> = ({ label, value }) => (
  <tr>
    <td style={{ padding: "6px 12px 6px 0", fontSize: 13, color: "#718096", whiteSpace: "nowrap", verticalAlign: "top", width: 110 }}>{label}</td>
    <td style={{ padding: "6px 0", fontSize: 13, color: "#2D3748", wordBreak: "break-all" }}>{value}</td>
  </tr>
);

export const DocumentDetailModal: React.FC<DocumentDetailModalProps> = ({
  isOpen,
  document: doc,
  isLoading,
  onClose,
  onEdit,
  onDelete,
}) => {
  const isMobile = useIsMobile();
  const [showMobilePreview, setShowMobilePreview] = useState(false);

  if (!isOpen) return null;

  const folderPathStr = doc?.folder_path?.map((f) => f.name).join(" / ") || doc?.foldername || "";

  // スマホ: フルスクリーンモーダル（プレビューはボタンで拡大モーダルとして呼び出し）
  if (isMobile) {
    return (
      <div style={{ position: "fixed", inset: 0, backgroundColor: "#fff", zIndex: 100001, display: "flex", flexDirection: "column" }}>
        {/* プレビュー拡大モーダル（FilePreviewRenderer の FullscreenModal を利用） */}
        {showMobilePreview && doc && (
          <FilePreviewRenderer
            filetype={doc.filetype}
            filelocationtype={doc.filelocationtype}
            filename={doc.filename}
            downloadUrl={doc.download_url}
            previewUrl={doc.preview_url}
            title={doc.title}
            expandOnMount
            onExpandClose={() => setShowMobilePreview(false)}
          />
        )}

        {/* ヘッダー */}
        <div style={{ display: "flex", alignItems: "center", padding: "10px 12px", borderBottom: "1px solid #E2E8F0", gap: 8, flexShrink: 0 }}>
          <button
            onClick={onClose}
            style={{ border: "none", background: "none", fontSize: 20, color: "#4A5568", cursor: "pointer", padding: "2px 6px", lineHeight: 1 }}
          >
            ←
          </button>
          <div style={{ flex: 1, fontSize: 14, fontWeight: 600, color: "#2D3748", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
            {doc?.title || "ドキュメント詳細"}
          </div>
        </div>

        {/* コンテンツ */}
        <div style={{ flex: 1, overflowY: "auto" }}>
          {isLoading ? (
            <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>読み込み中...</div>
          ) : !doc ? (
            <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>ドキュメントが見つかりません</div>
          ) : (
            <div style={{ padding: "12px 16px" }}>
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "#2D3748", margin: "0 0 2px" }}>{doc.title}</h3>
              <div style={{ fontSize: 12, color: "#A0AEC0", marginBottom: 12 }}>{doc.filename}</div>

              {/* プレビューボタン → 拡大モーダルで表示 */}
              {doc.filelocationtype === "I" && doc.download_url && (
                <button
                  onClick={() => setShowMobilePreview(true)}
                  style={{
                    width: "100%", padding: "10px 0", marginBottom: 16,
                    border: "1px solid #4299E1", borderRadius: 6, fontSize: 14,
                    color: "#4299E1", backgroundColor: "#EBF8FF", cursor: "pointer",
                    display: "flex", alignItems: "center", justifyContent: "center", gap: 6,
                  }}
                >
                  &#x26F6; プレビュー
                </button>
              )}

              <table style={{ width: "100%", borderCollapse: "collapse" }}>
                <tbody>
                  <InfoRow label="ドキュメントNo" value={doc.note_no} />
                  <InfoRow label="ファイル種別" value={doc.filetype || "—"} />
                  <InfoRow label="サイズ" value={formatFileSize(doc.filesize)} />
                  <InfoRow label="ダウンロード種別" value={getLocationLabel(doc.filelocationtype)} />
                  <InfoRow label="バージョン" value={doc.fileversion || "—"} />
                  <InfoRow label="フォルダ" value={folderPathStr} />
                  <InfoRow label="担当" value={doc.assigned_user_name} />
                  <InfoRow label="作成日時" value={doc.createdtime} />
                  <InfoRow label="更新日時" value={doc.modifiedtime} />
                  {doc.modified_by_name && <InfoRow label="最終更新者" value={doc.modified_by_name} />}
                  <InfoRow label="DL回数" value={`${doc.filedownloadcount}回`} />
                  <InfoRow label="ステータス" value={doc.filestatus === 1 ? "有効" : "無効"} />
                </tbody>
              </table>

              {/* メモ */}
              {doc.notecontent && (
                <div style={{ marginTop: 12, paddingTop: 12, borderTop: "1px solid #EDF2F7" }}>
                  <div style={{ fontSize: 12, fontWeight: 600, color: "#718096", marginBottom: 6 }}>メモ</div>
                  <div style={{ fontSize: 13, color: "#4A5568", lineHeight: 1.6, whiteSpace: "pre-wrap" }}>
                    {doc.notecontent}
                  </div>
                </div>
              )}

              {/* 関連レコード */}
              {doc.related_records && doc.related_records.length > 0 && (
                <div style={{ marginTop: 12, paddingTop: 12, borderTop: "1px solid #EDF2F7" }}>
                  <div style={{ fontSize: 12, fontWeight: 600, color: "#718096", marginBottom: 8 }}>関連レコード</div>
                  <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
                    {doc.related_records.map((rel) => (
                      <a
                        key={rel.id}
                        href={`index.php?module=${rel.module}&view=Detail&record=${rel.id}`}
                        style={{ padding: "3px 10px", backgroundColor: "#EDF2F7", borderRadius: 12, fontSize: 12, color: "#4A5568", textDecoration: "none" }}
                      >
                        {rel.label}
                      </a>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        {/* フッターアクションバー */}
        {doc && (
          <div style={{ display: "flex", gap: 8, padding: "10px 16px", borderTop: "1px solid #E2E8F0", flexShrink: 0 }}>
            <button
              onClick={() => onEdit(doc)}
              style={{ flex: 1, padding: "10px 0", border: "1px solid #E2E8F0", borderRadius: 6, fontSize: 14, color: "#4A5568", backgroundColor: "#fff", cursor: "pointer" }}
            >
              編集
            </button>
            {doc.filelocationtype === "I" && doc.download_url && (
              <a
                href={doc.download_url}
                style={{ flex: 1, padding: "10px 0", borderRadius: 6, fontSize: 14, color: "#fff", backgroundColor: "#38A169", textDecoration: "none", border: "none", textAlign: "center" }}
              >
                ダウンロード
              </a>
            )}
            <button
              onClick={() => {
                if (window.confirm("このドキュメントを削除しますか？")) onDelete(doc.id);
              }}
              style={{ padding: "10px 16px", border: "1px solid #FEB2B2", borderRadius: 6, fontSize: 14, color: "#E53E3E", backgroundColor: "#fff", cursor: "pointer" }}
            >
              削除
            </button>
          </div>
        )}
      </div>
    );
  }

  // PC: 中央モーダル
  return (
    <div
      style={{ position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.4)", display: "flex", alignItems: "center", justifyContent: "center", zIndex: 100001 }}
      onClick={onClose}
    >
      <div
        style={{ backgroundColor: "#fff", borderRadius: 8, width: 800, maxWidth: "95vw", maxHeight: "90vh", display: "flex", flexDirection: "column", boxShadow: "0 10px 40px rgba(0,0,0,0.2)" }}
        onClick={(e) => e.stopPropagation()}
      >
        {/* ヘッダー */}
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "16px 20px", borderBottom: "1px solid #E2E8F0" }}>
          <div style={{ fontSize: 12, color: "#A0AEC0" }}>{folderPathStr}</div>
          <div style={{ display: "flex", gap: 6, alignItems: "center" }}>
            {doc && (
              <>
                <button
                  onClick={() => onEdit(doc)}
                  style={{ padding: "5px 14px", border: "1px solid #E2E8F0", borderRadius: 4, fontSize: 13, color: "#4A5568", backgroundColor: "#fff", cursor: "pointer" }}
                >
                  編集
                </button>
                {doc.filelocationtype === "I" && doc.download_url && (
                  <a
                    href={doc.download_url}
                    style={{ padding: "5px 14px", borderRadius: 4, fontSize: 13, color: "#fff", backgroundColor: "#38A169", textDecoration: "none", border: "none" }}
                  >
                    ダウンロード
                  </a>
                )}
                <button
                  onClick={() => {
                    if (window.confirm("このドキュメントを削除しますか？")) onDelete(doc.id);
                  }}
                  style={{ padding: "5px 14px", border: "1px solid #FEB2B2", borderRadius: 4, fontSize: 13, color: "#E53E3E", backgroundColor: "#fff", cursor: "pointer" }}
                >
                  削除
                </button>
              </>
            )}
            <button
              onClick={onClose}
              style={{ width: 28, height: 28, border: "none", backgroundColor: "transparent", fontSize: 18, color: "#A0AEC0", cursor: "pointer", lineHeight: 1 }}
            >
              ×
            </button>
          </div>
        </div>

        {/* コンテンツ */}
        <div style={{ flex: 1, overflowY: "auto", padding: 20 }}>
          {isLoading ? (
            <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>読み込み中...</div>
          ) : !doc ? (
            <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>ドキュメントが見つかりません</div>
          ) : (
            <div style={{ display: "flex", gap: 24 }}>
              {/* 左：プレビュー */}
              <div style={{ flex: 1 }}>
                <div style={{ backgroundColor: "#F7FAFC", borderRadius: 8, display: "flex", alignItems: "center", justifyContent: "center", minHeight: 200, marginBottom: 16, padding: doc.filetype?.startsWith("image/") ? 40 : 0, overflow: "hidden" }}>
                  <FilePreviewRenderer
                    filetype={doc.filetype}
                    filelocationtype={doc.filelocationtype}
                    filename={doc.filename}
                    downloadUrl={doc.download_url}
                    previewUrl={doc.preview_url}
                    title={doc.title}
                    maxHeight={350}
                  />
                </div>
                {doc.notecontent && (
                  <div>
                    <div style={{ fontSize: 12, fontWeight: 600, color: "#718096", marginBottom: 6 }}>メモ</div>
                    <div style={{ fontSize: 13, color: "#4A5568", lineHeight: 1.6, whiteSpace: "pre-wrap", backgroundColor: "#F7FAFC", borderRadius: 6, padding: 12 }}>
                      {doc.notecontent}
                    </div>
                  </div>
                )}
              </div>

              {/* 右：情報 */}
              <div style={{ width: 300, flexShrink: 0 }}>
                <h3 style={{ fontSize: 16, fontWeight: 600, color: "#2D3748", margin: "0 0 4px" }}>{doc.title}</h3>
                <div style={{ fontSize: 12, color: "#A0AEC0", marginBottom: 16 }}>{doc.filename}</div>

                <table style={{ width: "100%", borderCollapse: "collapse" }}>
                  <tbody>
                    <InfoRow label="ドキュメントNo" value={doc.note_no} />
                    <InfoRow label="ファイル種別" value={doc.filetype || "—"} />
                    <InfoRow label="サイズ" value={formatFileSize(doc.filesize)} />
                    <InfoRow label="ダウンロード種別" value={getLocationLabel(doc.filelocationtype)} />
                    <InfoRow label="バージョン" value={doc.fileversion || "—"} />
                    <InfoRow label="フォルダ" value={folderPathStr} />
                    <InfoRow label="担当" value={doc.assigned_user_name} />
                    <InfoRow label="作成日時" value={doc.createdtime} />
                    <InfoRow label="更新日時" value={doc.modifiedtime} />
                    {doc.modified_by_name && <InfoRow label="最終更新者" value={doc.modified_by_name} />}
                    <InfoRow label="DL回数" value={`${doc.filedownloadcount}回`} />
                    <InfoRow label="ステータス" value={doc.filestatus === 1 ? "有効" : "無効"} />
                  </tbody>
                </table>

                {/* 関連レコード */}
                {doc.related_records && doc.related_records.length > 0 && (
                  <div style={{ marginTop: 16, paddingTop: 12, borderTop: "1px solid #EDF2F7" }}>
                    <div style={{ fontSize: 12, fontWeight: 600, color: "#718096", marginBottom: 8 }}>関連レコード</div>
                    <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
                      {doc.related_records.map((rel) => (
                        <a
                          key={rel.id}
                          href={`index.php?module=${rel.module}&view=Detail&record=${rel.id}`}
                          style={{ padding: "3px 10px", backgroundColor: "#EDF2F7", borderRadius: 12, fontSize: 12, color: "#4A5568", textDecoration: "none" }}
                        >
                          {rel.label}
                        </a>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
