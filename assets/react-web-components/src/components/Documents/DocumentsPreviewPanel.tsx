import React, { useState, useEffect } from "react";
import type { DocumentDetail } from "./types/documents";
import { FilePreviewRenderer } from "./FilePreviewRenderer";

interface DocumentsPreviewPanelProps {
  document: DocumentDetail | null;
  isLoading: boolean;
  onEdit?: (doc: DocumentDetail) => void;
  onBack?: () => void;
}

function formatFileSize(bytes: number): string {
  if (!bytes || bytes === 0) return "—";
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

function getLocationLabel(type: string): string {
  switch (type) {
    case "I": return "内部";
    case "E": return "外部";
    case "W": return "Web";
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

const InfoRow: React.FC<{ label: string; value: string }> = ({ label, value }) => (
  <div style={{ display: "flex", gap: 8, padding: "4px 0", fontSize: 13 }}>
    <span style={{ width: 100, flexShrink: 0, color: "#718096" }}>{label}</span>
    <span style={{ color: "#2D3748", wordBreak: "break-all" }}>{value}</span>
  </div>
);

export const DocumentsPreviewPanel: React.FC<DocumentsPreviewPanelProps> = ({
  document: doc,
  isLoading,
  onEdit,
  onBack,
}) => {
  const isMobile = useIsMobile();
  const [showMobilePreview, setShowMobilePreview] = useState(false);

  if (isLoading) {
    return (
      <div style={{ flex: 1, display: "flex", alignItems: "center", justifyContent: "center", color: "#A0AEC0" }}>
        読み込み中...
      </div>
    );
  }

  if (!doc) {
    return (
      <div style={{ flex: 1, display: "flex", alignItems: "center", justifyContent: "center", color: "#A0AEC0", flexDirection: "column", gap: 8, padding: 40 }}>
        <div style={{ fontSize: 14 }}>ドキュメントを選択してください</div>
        <div style={{ fontSize: 12 }}>左のリストからドキュメントをクリックするとプレビューが表示されます</div>
      </div>
    );
  }

  const folderPathStr = doc.folder_path?.map((f) => f.name).join(" / ") || doc.foldername;

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden" }}>
      {/* ヘッダー */}
      <div style={{
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        padding: isMobile ? "8px 12px" : "10px 16px",
        borderBottom: "1px solid #EDF2F7",
        flexWrap: isMobile ? "wrap" : "nowrap",
        gap: isMobile ? 6 : 0,
      }}>
        <div style={{ display: "flex", alignItems: "center", gap: 8, flex: isMobile ? "1 1 100%" : undefined }}>
          {onBack && (
            <button
              onClick={onBack}
              style={{ padding: "4px 10px", border: "1px solid #E2E8F0", borderRadius: 4, fontSize: 12, color: "#4A5568", backgroundColor: "#fff", cursor: "pointer" }}
            >
              ← 一覧
            </button>
          )}
          <div style={{ fontSize: 12, color: "#A0AEC0", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{folderPathStr}</div>
        </div>
        <div style={{ display: "flex", gap: 6, flexShrink: 0 }}>
          <button
            onClick={() => onEdit?.(doc)}
            style={{ padding: "4px 12px", border: "1px solid #E2E8F0", borderRadius: 4, fontSize: 12, color: "#4A5568", backgroundColor: "#fff", cursor: "pointer" }}
          >
            編集
          </button>
          {doc.filelocationtype === "I" && doc.download_url && (
            <a
              href={doc.download_url}
              style={{ padding: "4px 12px", border: "none", borderRadius: 4, fontSize: 12, color: "#fff", textDecoration: "none", backgroundColor: "#38A169" }}
            >
              ダウンロード
            </a>
          )}
        </div>
      </div>

      {/* スマホ: プレビュー拡大モーダル（FilePreviewRenderer の FullscreenModal を利用） */}
      {isMobile && showMobilePreview && (
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

      {/* コンテンツ */}
      <div style={{ flex: 1, overflowY: "auto", padding: isMobile ? 12 : 16 }}>
        {/* PC: プレビューエリア直接表示 / スマホ: プレビューボタン */}
        {isMobile ? (
          doc.filelocationtype === "I" && doc.download_url ? (
            <button
              onClick={() => setShowMobilePreview(true)}
              style={{
                width: "100%", padding: "10px 0", marginBottom: 12,
                border: "1px solid #4299E1", borderRadius: 6, fontSize: 14,
                color: "#4299E1", backgroundColor: "#EBF8FF", cursor: "pointer",
                display: "flex", alignItems: "center", justifyContent: "center", gap: 6,
              }}
            >
              &#x26F6; プレビュー
            </button>
          ) : null
        ) : (
          <div style={{
            backgroundColor: "#F7FAFC",
            borderRadius: 8,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            marginBottom: 20,
            minHeight: 160,
            padding: doc.filetype?.startsWith("image/") ? 40 : 0,
            overflow: "hidden",
          }}>
            <FilePreviewRenderer
              filetype={doc.filetype}
              filelocationtype={doc.filelocationtype}
              filename={doc.filename}
              downloadUrl={doc.download_url}
              previewUrl={doc.preview_url}
              title={doc.title}
              maxHeight={400}
            />
          </div>
        )}

        {/* ドキュメント情報 */}
        <h3 style={{ fontSize: isMobile ? 15 : 16, fontWeight: 600, color: "#2D3748", margin: "0 0 4px" }}>{doc.title}</h3>
        <div style={{ fontSize: 12, color: "#A0AEC0", marginBottom: 12 }}>{doc.filename}</div>

        <div style={{ borderTop: "1px solid #EDF2F7", paddingTop: 12 }}>
          <InfoRow label="ファイル種別" value={doc.filetype || "—"} />
          <InfoRow label="フォルダ" value={folderPathStr} />
          <InfoRow label="担当" value={doc.assigned_user_name} />
          <InfoRow label="更新日時" value={doc.modifiedtime} />
          <InfoRow label="サイズ" value={formatFileSize(doc.filesize)} />
          <InfoRow label="ダウンロード種別" value={getLocationLabel(doc.filelocationtype)} />
          {doc.filedownloadcount > 0 && (
            <InfoRow label="DL回数" value={`${doc.filedownloadcount}回`} />
          )}
        </div>

        {/* メモ */}
        {doc.notecontent && (
          <div style={{ marginTop: 12, borderTop: "1px solid #EDF2F7", paddingTop: 12 }}>
            <div style={{ fontSize: 12, fontWeight: 600, color: "#718096", marginBottom: 6 }}>メモ</div>
            <div style={{ fontSize: 13, color: "#4A5568", lineHeight: 1.6, whiteSpace: "pre-wrap" }}>
              {doc.notecontent}
            </div>
          </div>
        )}

        {/* 関連レコード */}
        {doc.related_records && doc.related_records.length > 0 && (
          <div style={{ marginTop: 12, borderTop: "1px solid #EDF2F7", paddingTop: 12 }}>
            <div style={{ fontSize: 12, fontWeight: 600, color: "#718096", marginBottom: 6 }}>関連レコード</div>
            <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
              {doc.related_records.map((rel) => (
                <a
                  key={rel.id}
                  href={`index.php?module=${rel.module}&view=Detail&record=${rel.id}`}
                  style={{
                    padding: "3px 10px",
                    backgroundColor: "#EDF2F7",
                    borderRadius: 12,
                    fontSize: 12,
                    color: "#4A5568",
                    textDecoration: "none",
                  }}
                >
                  [{rel.module}] {rel.label}
                </a>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
