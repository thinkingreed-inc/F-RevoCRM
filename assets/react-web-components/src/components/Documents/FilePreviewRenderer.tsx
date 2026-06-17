import React, { useEffect, useState, useCallback } from "react";
import { FileIcon, getFileCategory } from "./FileIcon";
import { useOptionalTranslation } from "../../hooks/useTranslation";

interface FilePreviewRendererProps {
  filetype: string | null;
  filelocationtype: string;
  filename: string;
  downloadUrl: string;
  previewUrl: string;
  title: string;
  maxHeight?: number;
  /** trueにするとマウント時に拡大モーダルを即時表示する */
  expandOnMount?: boolean;
  /** 拡大モーダルが閉じられたときのコールバック */
  onExpandClose?: () => void;
}

function getExtension(filename: string | null): string {
  if (!filename) return "";
  const parts = filename.split(".");
  return parts.length > 1 ? (parts.pop()?.toLowerCase() || "") : "";
}

/** サイトのベースURL（末尾スラッシュ付き）を取得 */
function getSiteBaseUrl(): string {
  const href = window.location.href;
  const idx = href.indexOf("index.php");
  if (idx !== -1) return href.substring(0, idx);
  return href.substring(0, href.lastIndexOf("/") + 1);
}

/** プレビュー可能なファイルかどうか */
function isPreviewable(ext: string, category: string, filelocationtype: string, downloadUrl: string): boolean {
  if (filelocationtype !== "I" || !downloadUrl) return false;
  if (category === "image" || category === "video" || category === "audio") return true;
  if (["pdf", "xlsx", "pptx", "docx", "odt", "ods", "odp", "fodt"].includes(ext)) return true;
  if (category === "text" || ["txt", "csv", "ics", "log", "json", "xml"].includes(ext)) return true;
  return false;
}

/** 拡大ボタン（iframeの上に重ねられないため、プレビューの外に配置） */
const ExpandButton: React.FC<{ onClick: () => void }> = ({ onClick }) => {
  const { t } = useOptionalTranslation();
  return (
    <button
      onClick={onClick}
      title={t('LBL_EXPAND')}
      style={{
        padding: "4px 10px", border: "1px solid #E2E8F0", borderRadius: 4,
        backgroundColor: "#fff", cursor: "pointer",
        display: "inline-flex", alignItems: "center", gap: 4,
        fontSize: 12, color: "#4A5568", lineHeight: 1,
      }}
      onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = "#EDF2F7"; }}
      onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = "#fff"; }}
    >
      {"\u26F6"} {t('LBL_EXPAND')}
    </button>
  );
};

/** 全画面プレビューモーダル */
const FullscreenModal: React.FC<{
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
}> = ({ isOpen, onClose, title, children }) => {
  const { t } = useOptionalTranslation();
  useEffect(() => {
    if (!isOpen) return;
    const handleKey = (e: KeyboardEvent) => { if (e.key === "Escape") onClose(); };
    window.addEventListener("keydown", handleKey);
    return () => { window.removeEventListener("keydown", handleKey); };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <>
      {/* オーバーレイ（全画面） */}
      <div
        style={{ position: "fixed", inset: 0, zIndex: 100001, backgroundColor: "rgba(0,0,0,0.5)" }}
        onClick={onClose}
      />
      {/* モーダル本体 */}
      <div
        style={{
          position: "fixed", top: "3%", left: "3%", right: "3%", bottom: "3%",
          zIndex: 100002,
          backgroundColor: "#fff", borderRadius: 8,
          boxShadow: "0 10px 40px rgba(0,0,0,0.3)",
          display: "flex", flexDirection: "column",
          overflow: "hidden",
        }}
      >
        {/* ヘッダー */}
        <div
          style={{
            display: "flex", alignItems: "center", justifyContent: "space-between",
            padding: "10px 16px", borderBottom: "1px solid #E2E8F0",
            backgroundColor: "#F7FAFC", flexShrink: 0,
          }}
        >
          <div style={{ fontSize: 14, fontWeight: 600, color: "#2D3748", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap", flex: 1, marginRight: 16 }}>
            {title}
          </div>
          <button
            onClick={onClose}
            style={{
              border: "1px solid #E2E8F0", background: "#fff",
              color: "#4A5568", fontSize: 13, cursor: "pointer",
              padding: "6px 20px", borderRadius: 4, lineHeight: 1,
              display: "flex", alignItems: "center", gap: 6, flexShrink: 0,
            }}
            onMouseEnter={(e) => { e.currentTarget.style.background = "#EDF2F7"; }}
            onMouseLeave={(e) => { e.currentTarget.style.background = "#fff"; }}
          >
            × {t('LBL_CLOSE')}
          </button>
        </div>
        {/* コンテンツ */}
        <div style={{ flex: "1 1 0", minHeight: 0, overflow: "hidden" }}>
          {children}
        </div>
      </div>
    </>
  );
};

/** DOCX プレビュー（サーバー側HTML変換） */
const DocxPreview: React.FC<{ recordId: string; maxHeight: number | string }> = ({ recordId, maxHeight }) => {
  const { t } = useOptionalTranslation();
  const [html, setHtml] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    fetch(`index.php?module=Documents&action=PreviewContent&record=${recordId}`)
      .then((res) => res.ok ? res.json() : Promise.reject())
      .then((data) => { if (data.success && data.result?.html) setHtml(data.result.html); })
      .catch(() => setHtml(null))
      .finally(() => setLoading(false));
  }, [recordId]);

  if (loading) return <div style={{ display: "flex", alignItems: "center", justifyContent: "center", padding: 40, color: "#A0AEC0" }}>{t('LBL_LOADING')}</div>;
  if (!html) return <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>{t('LBL_PREVIEW_LOAD_FAILED')}</div>;

  const styles = `<style>
    .docx-preview table { border-collapse: collapse; width: 100%; margin: 8px 0; }
    .docx-preview th, .docx-preview td { border: 1px solid #CBD5E0; padding: 6px 10px; }
    .docx-preview h3 { color: #2B6CB0; margin: 16px 0 8px; }
    .docx-preview p { margin: 4px 0; }
  </style>`;
  return (
    <div
      className="docx-preview"
      style={{ width: "100%", height: maxHeight, overflow: "auto", padding: 16, fontSize: 13, lineHeight: 1.6, color: "#2D3748" }}
      dangerouslySetInnerHTML={{ __html: styles + html }}
    />
  );
};

/** プレビューコンテンツ（通常・拡大共通） */
const PreviewContent: React.FC<{
  ext: string;
  category: string;
  filetype: string | null;
  filelocationtype: string;
  filename: string;
  downloadUrl: string;
  title: string;
  maxHeight: number | string;
  isTextFile: boolean;
  textContent: string | null;
  textLoading: boolean;
}> = ({ ext, category, filetype, filelocationtype, filename, downloadUrl, title, maxHeight, isTextFile, textContent, textLoading }) => {
  const { t } = useOptionalTranslation();

  if (filelocationtype !== "I" || !downloadUrl) {
    return (
      <div style={{ display: "flex", alignItems: "center", justifyContent: "center", padding: 40 }}>
        <FileIcon filetype={filetype} filelocationtype={filelocationtype} filename={filename} size="lg" />
      </div>
    );
  }

  // 画像
  if (category === "image") {
    return (
      <img
        src={downloadUrl}
        alt={title}
        style={{ maxWidth: "100%", maxHeight, borderRadius: 4, objectFit: "contain" }}
      />
    );
  }

  // PDF
  if (ext === "pdf" || category === "pdf") {
    const baseUrl = getSiteBaseUrl();
    const pdfjsUrl = `libraries/jquery/pdfjs/web/viewer.html?file=${encodeURIComponent(baseUrl + downloadUrl)}`;
    return <iframe src={pdfjsUrl} title={title} style={{ width: "100%", height: maxHeight, border: "none" }} />;
  }

  // Excel (XLSX)
  if (ext === "xlsx") {
    const baseUrl = getSiteBaseUrl();
    const viewerUrl = `libraries/jquery/luckysheet/viewer.html?file=${encodeURIComponent(baseUrl + downloadUrl)}`;
    return <iframe src={viewerUrl} title={title} style={{ width: "100%", height: maxHeight, border: "none" }} />;
  }

  // PowerPoint (PPTX)
  if (ext === "pptx") {
    const baseUrl = getSiteBaseUrl();
    const viewerUrl = `libraries/jquery/pptxjs/viewer.html?file=${encodeURIComponent(baseUrl + downloadUrl)}`;
    return <iframe src={viewerUrl} title={title} style={{ width: "100%", height: maxHeight, border: "none" }} />;
  }

  // Word (DOCX)
  if (ext === "docx") {
    const recordMatch = downloadUrl.match(/record=(\d+)/);
    if (recordMatch) {
      return <DocxPreview recordId={recordMatch[1]} maxHeight={maxHeight} />;
    }
  }

  // OpenDocument
  const openDocExts = ["odt", "ods", "odp", "fodt"];
  if (openDocExts.includes(ext)) {
    const viewerUrl = `libraries/jquery/Viewer.js/#../../../${downloadUrl}`;
    return <iframe src={viewerUrl} title={title} style={{ width: "100%", height: maxHeight, border: "none" }} />;
  }

  // 動画
  if (category === "video") {
    return (
      <video controls style={{ maxWidth: "100%", maxHeight, borderRadius: 4 }}>
        <source src={downloadUrl} type={filetype || undefined} />
      </video>
    );
  }

  // 音声
  if (category === "audio") {
    return (
      <div style={{ display: "flex", alignItems: "center", justifyContent: "center", padding: 40, width: "100%" }}>
        <audio controls style={{ width: "100%", maxWidth: 500 }}>
          <source src={downloadUrl} type={filetype || undefined} />
        </audio>
      </div>
    );
  }

  // テキスト系
  if (isTextFile) {
    if (textLoading) {
      return <div style={{ display: "flex", alignItems: "center", justifyContent: "center", padding: 40, color: "#A0AEC0" }}>{t('LBL_LOADING')}</div>;
    }
    if (textContent !== null) {
      return (
        <div style={{ width: "100%", height: maxHeight, overflow: "auto", padding: 16, textAlign: "left" }}>
          <pre style={{ margin: 0, fontSize: 12, lineHeight: 1.6, color: "#2D3748", whiteSpace: "pre-wrap", wordBreak: "break-all", fontFamily: "'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace" }}>
            {textContent}
          </pre>
        </div>
      );
    }
  }

  // その他
  return (
    <div style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 12, padding: 40 }}>
      <FileIcon filetype={filetype} filelocationtype={filelocationtype} filename={filename} size="lg" />
      <span style={{ fontSize: 12, color: "#A0AEC0" }}>{t('LBL_PREVIEW_NOT_SUPPORTED')}</span>
    </div>
  );
};

export const FilePreviewRenderer: React.FC<FilePreviewRendererProps> = ({
  filetype,
  filelocationtype,
  filename,
  downloadUrl,
  previewUrl: _,
  title,
  maxHeight = 400,
  expandOnMount = false,
  onExpandClose,
}) => {
  const { t } = useOptionalTranslation();
  const [textContent, setTextContent] = useState<string | null>(null);
  const [textLoading, setTextLoading] = useState(false);
  const [expanded, setExpanded] = useState(expandOnMount);

  const ext = getExtension(filename);
  const category = getFileCategory(filetype, filelocationtype);
  const isTextFile = category === "text" || ["txt", "csv", "ics", "log", "json", "xml", "html", "css", "js", "php", "py", "sh"].includes(ext);
  const canExpand = isPreviewable(ext, category, filelocationtype, downloadUrl);

  useEffect(() => {
    if (filelocationtype !== "I" || !downloadUrl || !isTextFile) return;
    setTextLoading(true);
    fetch(downloadUrl)
      .then((res) => { if (!res.ok) throw new Error("fetch failed"); return res.text(); })
      .then((text) => { setTextContent(text.length > 10000 ? text.substring(0, 10000) + "\n" + t('LBL_TEXT_TRUNCATED') : text); })
      .catch(() => setTextContent(null))
      .finally(() => setTextLoading(false));
  }, [downloadUrl, filelocationtype, isTextFile]);

  const handleExpand = useCallback(() => setExpanded(true), []);
  const handleClose = useCallback(() => { setExpanded(false); onExpandClose?.(); }, [onExpandClose]);

  const commonProps = { ext, category, filetype, filelocationtype, filename, downloadUrl, title, isTextFile, textContent, textLoading };

  return (
    <>
      <div style={{ width: "100%" }}>
        {canExpand && (
          <div style={{ display: "flex", justifyContent: "flex-end", padding: "4px 4px 4px 0" }}>
            <ExpandButton onClick={handleExpand} />
          </div>
        )}
        <PreviewContent {...commonProps} maxHeight={maxHeight} />
      </div>

      <FullscreenModal isOpen={expanded} onClose={handleClose} title={title}>
        <PreviewContent {...commonProps} maxHeight="100%" />
      </FullscreenModal>
    </>
  );
};
