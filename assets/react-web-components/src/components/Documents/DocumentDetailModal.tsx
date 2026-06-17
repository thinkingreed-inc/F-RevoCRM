import React, { useState, useEffect, useMemo } from "react";
import type { DocumentDetail, RelatedRecord } from "./types/documents";
import { FilePreviewRenderer } from "./FilePreviewRenderer";
import { ComplianceHistoryModal } from "./ComplianceHistoryModal";
import { useOptionalTranslation } from "../../hooks/useTranslation";
import { useDocumentFields, DocFieldInfo } from "./hooks/useDocumentFields";

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

function useIsMobile(breakpoint = 768) {
  const [isMobile, setIsMobile] = useState(window.innerWidth <= breakpoint);
  useEffect(() => {
    const handler = () => setIsMobile(window.innerWidth <= breakpoint);
    window.addEventListener("resize", handler);
    return () => window.removeEventListener("resize", handler);
  }, [breakpoint]);
  return isMobile;
}

function getComplianceStatusStyle(t: (key: string) => string, status: string): { bg: string; color: string; label: string } {
  const styles: Record<string, { bg: string; color: string; label: string }> = {
    compliant: { bg: "#e7f4e8", color: "#2e8b46", label: t("LBL_STATUS_COMPLIANT") },
    non_compliant: { bg: "#FED7D7", color: "#822727", label: t("LBL_STATUS_NON_COMPLIANT") },
  };
  return styles[status] || { bg: "#eef2f7", color: "#4a5560", label: status };
}

/** Block accent colors */
const BLOCK_ACCENTS: Record<string, string> = {
  LBL_NOTE_INFORMATION: "#5b8def",
  LBL_DESCRIPTION: "#5fa6c2",
  LBL_FILE_INFORMATION: "#4f9d6b",
  LBL_COMPLIANCE_SECTION: "#c0903e",
  LBL_SCANNER_SECTION: "#8a6fc0",
  LBL_COMPLIANCE_STATUS_SECTION: "#cf6b6b",
};
const DEFAULT_ACCENT = "#6b7280";

/** Core fields handled by hardcoded header / not shown in block cards */
const DETAIL_CORE_FIELDS = new Set([
  "notes_title", "filename", "filelocationtype", "folderid", "notecontent",
  "filestatus", "fileversion", "note_no", "modifiedtime", "createdtime",
  "modifiedby", "assigned_user_id", "source", "filedownloadcount", "filesize",
]);

// ─── FieldCell: renders a single field with type-aware display ───

function getFieldVariant(field: DocFieldInfo): string {
  switch (field.uitype) {
    case "15": case "16": return "pill";
    case "33": return "pills";
    case "56": return "check";
    case "13": case "17": case "11": return "link";
    case "10": case "51": case "57": case "73": case "75": case "76":
    case "77": case "78": case "80": case "81": case "101": return "link";
    case "71": return "plain"; // currency
    case "9": return "plain"; // percent
    case "19": case "21": return "text"; // textarea
    default: return "plain";
  }
}

function getFieldTone(field: DocFieldInfo, rawValue: any): { pillBg: string; pillColor: string } {
  // Special tones for known fields
  if (field.name === "compliance_status") {
    if (rawValue === "compliant") return { pillBg: "#e7f4e8", pillColor: "#2e8b46" };
    if (rawValue === "non_compliant") return { pillBg: "#FED7D7", pillColor: "#822727" };
  }
  if (field.name === "input_deadline_status") {
    if (rawValue === "within") return { pillBg: "#e7f4e8", pillColor: "#2e8b46" };
    if (rawValue === "warning") return { pillBg: "#fdf1e0", pillColor: "#a86a12" };
    if (rawValue === "overdue") return { pillBg: "#FED7D7", pillColor: "#822727" };
  }
  if (field.name === "document_category") return { pillBg: "#e7eefb", pillColor: "#3461bd" };
  return { pillBg: "#eef2f7", pillColor: "#4a5560" };
}

const FieldCell: React.FC<{
  field: DocFieldInfo;
  rawValue: any;
  dynamicFields?: Record<string, any>;
  t: (key: string, ...args: any[]) => string;
  wide?: boolean;
}> = ({ field, rawValue, dynamicFields, t, wide }) => {
  const variant = getFieldVariant(field);
  const strVal = rawValue !== null && rawValue !== undefined ? String(rawValue) : "";
  const isEmpty = strVal === "";
  const tone = getFieldTone(field, rawValue);

  // Resolve picklist display label
  let displayLabel = strVal;
  if ((variant === "pill" || variant === "pills") && field.picklistValues && field.picklistValues.length > 0) {
    const match = field.picklistValues.find((pv) => pv.value === strVal);
    if (match) displayLabel = match.label;
  }

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 5, minWidth: 0, gridColumn: wide ? "1 / -1" : "auto" }}>
      <div style={{ fontSize: 11.5, fontWeight: 600, letterSpacing: ".01em", color: "#959ba3" }}>{field.label}</div>

      {isEmpty ? (
        <div style={{ fontSize: 14, color: "#cfd4da" }}>—</div>
      ) : variant === "plain" ? (
        <div style={{ fontSize: 14, lineHeight: 1.45, color: "#2b2f33", wordBreak: "break-word" }}>
          {field.uitype === "71" ? `¥${Number(rawValue).toLocaleString()}` : field.uitype === "9" ? `${strVal}%` : strVal}
        </div>
      ) : variant === "mono" ? (
        <div style={{ fontSize: 12.5, lineHeight: 1.45, color: "#3a3f44", fontFamily: "ui-monospace,Menlo,Consolas,monospace", wordBreak: "break-all", background: "#f6f7f9", border: "1px solid #edeff2", borderRadius: 5, padding: "7px 9px" }}>{strVal}</div>
      ) : variant === "text" ? (
        <div style={{ fontSize: 13.5, lineHeight: 1.65, color: "#3a3f44", whiteSpace: "pre-wrap", wordBreak: "break-word" }}>{strVal}</div>
      ) : variant === "pill" ? (
        <span style={{ display: "inline-flex", alignItems: "center", height: 24, padding: "0 11px", borderRadius: 13, fontSize: 12.5, fontWeight: 600, width: "fit-content", background: tone.pillBg, color: tone.pillColor }}>{displayLabel}</span>
      ) : variant === "pills" ? (
        <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
          {strVal.split(" |##| ").filter(Boolean).map((p, i) => (
            <span key={i} style={{ display: "inline-flex", alignItems: "center", height: 24, padding: "0 10px", borderRadius: 13, fontSize: 12.5, fontWeight: 600, background: "#eef2f7", color: "#4a5560" }}>{p}</span>
          ))}
        </div>
      ) : variant === "link" ? (
        (() => {
          // Reference type
          if (["10","51","57","73","75","76","77","78","80","81","101"].includes(field.uitype)) {
            const refId = parseInt(strVal, 10);
            const displayName = dynamicFields?.[field.name + "_display"] || strVal;
            const refModule = dynamicFields?.[field.name + "_module"] || "";
            return refId ? (
              <a href={`index.php?module=${refModule}&view=Detail&record=${refId}`} style={{ fontSize: 14, color: "#3b7dd8", textDecoration: "none", wordBreak: "break-all" }} onClick={(e) => e.stopPropagation()}>{displayName}</a>
            ) : <div style={{ fontSize: 14, color: "#cfd4da" }}>—</div>;
          }
          // Email
          if (field.uitype === "13") return <a href={`mailto:${strVal}`} style={{ fontSize: 14, color: "#3b7dd8", textDecoration: "none" }}>{strVal}</a>;
          // URL
          if (field.uitype === "17") return <a href={strVal} target="_blank" rel="noopener noreferrer" style={{ fontSize: 14, color: "#3b7dd8", textDecoration: "none", wordBreak: "break-all" }}>{strVal}</a>;
          // Phone
          if (field.uitype === "11") return <a href={`tel:${strVal}`} style={{ fontSize: 14, color: "#3b7dd8", textDecoration: "none" }}>{strVal}</a>;
          return <div style={{ fontSize: 14, color: "#2b2f33" }}>{strVal}</div>;
        })()
      ) : variant === "check" ? (
        <span style={{ display: "inline-flex", alignItems: "center", gap: 7, fontSize: 14, color: "#2b2f33" }}>
          {(rawValue === "1" || rawValue === true || rawValue === 1) ? (
            <span style={{ display: "inline-flex", alignItems: "center", justifyContent: "center", width: 20, height: 20, borderRadius: 5, background: "#e7f4e8", color: "#2e9e4e", fontSize: 12, fontWeight: 800 }}>✓</span>
          ) : (
            <span style={{ display: "inline-flex", alignItems: "center", justifyContent: "center", width: 20, height: 20, borderRadius: 5, background: "#f3f4f6", color: "#9aa0a8", fontSize: 12, fontWeight: 800 }}>✗</span>
          )}
          {(rawValue === "1" || rawValue === true || rawValue === 1) ? t("LBL_STATUS_ACTIVE") : t("LBL_STATUS_INACTIVE")}
        </span>
      ) : (
        <div style={{ fontSize: 14, color: "#2b2f33" }}>{strVal}</div>
      )}
    </div>
  );
};

// ─── Block card ───

interface BlockDef {
  label: string;
  accent: string;
  fields: Array<{ field: DocFieldInfo; rawValue: any; wide?: boolean }>;
}

const BlockCard: React.FC<{ block: BlockDef; dynamicFields?: Record<string, any>; t: (key: string, ...args: any[]) => string }> = ({ block, dynamicFields, t }) => (
  <div style={{ border: "1px solid #e7e9ec", borderRadius: 10, overflow: "hidden", background: "#fff" }}>
    <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "12px 16px", background: "#fafbfc", borderBottom: "1px solid #eef0f2" }}>
      <span style={{ width: 4, height: 16, borderRadius: 2, background: block.accent }} />
      <span style={{ fontSize: 14, fontWeight: 700, color: "#2b2f33" }}>{block.label}</span>
    </div>
    <div style={{ padding: "16px 18px", display: "grid", gridTemplateColumns: "1fr 1fr", gap: "16px 26px" }}>
      {block.fields.map((f) => (
        <FieldCell key={f.field.name} field={f.field} rawValue={f.rawValue} dynamicFields={dynamicFields} t={t} wide={f.wide} />
      ))}
    </div>
  </div>
);

// ─── Related record card ───

const RelatedRecordCard: React.FC<{ record: RelatedRecord }> = ({ record }) => (
  <a
    href={`index.php?module=${record.module}&view=Detail&record=${record.id}`}
    style={{ display: "block", padding: "10px 12px", backgroundColor: "#F7FAFC", border: "1px solid #E2E8F0", borderRadius: 6, textDecoration: "none", color: "#2D3748", marginBottom: 6, transition: "background-color 0.15s" }}
    onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = "#EDF2F7")}
    onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = "#F7FAFC")}
  >
    <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
      <div style={{ flex: 1 }}>
        <div style={{ fontSize: 11, color: "#718096", fontWeight: 600, marginBottom: 2 }}>{record.module_label || record.module}</div>
        <div style={{ fontSize: 13, color: "#2D3748" }}>{record.label}</div>
        {record.summary && (
          <div style={{ fontSize: 12, color: "#A0AEC0", marginTop: 2 }}>
            {record.summary.date && <span>{record.summary.date}</span>}
            {record.summary.amount && <span style={{ marginLeft: 8 }}>{record.summary.currency_symbol}{Number(record.summary.amount).toLocaleString()}</span>}
          </div>
        )}
      </div>
      <span style={{ color: "#A0AEC0", fontSize: 16 }}>→</span>
    </div>
  </a>
);

// ─── Main component ───

export const DocumentDetailModal: React.FC<DocumentDetailModalProps> = ({
  isOpen, document: doc, isLoading, onClose, onEdit, onDelete,
}) => {
  const { t } = useOptionalTranslation();
  const isMobile = useIsMobile();
  const [showMobilePreview, setShowMobilePreview] = useState(false);
  const [showHistory, setShowHistory] = useState(false);
  const { fields: fieldDefs } = useDocumentFields(doc?.id, true);

  /** Get raw value for a dynamic field */
  const getDynamicFieldValue = (fieldName: string): any => {
    if (!doc) return null;
    const docAny = doc as any;
    if (docAny.dynamic_fields && docAny.dynamic_fields[fieldName] !== undefined && docAny.dynamic_fields[fieldName] !== null) return docAny.dynamic_fields[fieldName];
    const c = doc.compliance as any;
    if (c && c[fieldName] !== undefined) return c[fieldName];
    if (docAny[fieldName] !== undefined) return docAny[fieldName];
    return null;
  };

  /** Build block definitions from field defs */
  const blocks = useMemo((): BlockDef[] => {
    if (!doc) return [];
    const blockMap: Record<string, { label: string; accent: string; fields: BlockDef["fields"] }> = {};

    // Core blocks built from hardcoded fields
    const coreInfo: BlockDef["fields"] = [
      { field: { name: "notes_title", label: t("Title"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.title },
      { field: { name: "folderid", label: t("Folder Name"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.foldername },
      { field: { name: "note_no", label: t("Document No"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.note_no },
      { field: { name: "assigned_user_id", label: t("Assigned To"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.assigned_user_name },
      { field: { name: "createdtime", label: t("Created Time"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.createdtime },
      { field: { name: "modifiedtime", label: t("Modified Time"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.modifiedtime },
    ];
    blockMap["LBL_NOTE_INFORMATION"] = { label: t("LBL_NOTE_INFORMATION"), accent: BLOCK_ACCENTS["LBL_NOTE_INFORMATION"] || DEFAULT_ACCENT, fields: coreInfo };

    // Description block
    if (doc.notecontent) {
      blockMap["LBL_DESCRIPTION"] = { label: t("LBL_DESCRIPTION"), accent: BLOCK_ACCENTS["LBL_DESCRIPTION"] || DEFAULT_ACCENT, fields: [
        { field: { name: "notecontent", label: t("Note"), uitype: "19", blockLabel: "" } as DocFieldInfo, rawValue: doc.notecontent, wide: true },
      ] };
    }

    // File info block
    const fileFields: BlockDef["fields"] = [
      { field: { name: "filelocationtype", label: t("Download Type"), uitype: "16", blockLabel: "", picklistValues: [{ value: "I", label: t("LBL_LOCATION_FILE") }, { value: "E", label: t("LBL_LOCATION_URL") }] } as DocFieldInfo, rawValue: doc.filelocationtype },
      { field: { name: "filestatus", label: t("Active"), uitype: "56", blockLabel: "" } as DocFieldInfo, rawValue: doc.filestatus },
      { field: { name: "filename", label: t("File Name"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.filename },
      { field: { name: "filesize", label: t("File Size"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: formatFileSize(doc.filesize) },
      { field: { name: "fileversion", label: t("Version"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: doc.fileversion || "—" },
      { field: { name: "filedownloadcount", label: t("Download Count"), uitype: "1", blockLabel: "" } as DocFieldInfo, rawValue: t("LBL_DOWNLOAD_COUNT_SUFFIX", doc.filedownloadcount) },
    ];
    blockMap["LBL_FILE_INFORMATION"] = { label: t("LBL_FILE_INFORMATION"), accent: BLOCK_ACCENTS["LBL_FILE_INFORMATION"] || DEFAULT_ACCENT, fields: fileFields };

    // Dynamic blocks from field definitions
    for (const f of fieldDefs) {
      if (DETAIL_CORE_FIELDS.has(f.name)) continue;
      if (!f.blockLabel) continue;
      const rawVal = getDynamicFieldValue(f.name);
      const blockKey = f.blockLabel;
      if (!blockMap[blockKey]) {
        // Find the block label key for accent lookup
        const accentKey = Object.keys(BLOCK_ACCENTS).find((k) => t(k) === f.blockLabel) || "";
        blockMap[blockKey] = { label: f.blockLabel, accent: BLOCK_ACCENTS[accentKey] || DEFAULT_ACCENT, fields: [] };
      }
      const isWide = f.uitype === "19" || f.uitype === "21" || f.name === "file_hash" || f.name === "compliance_notes";
      blockMap[blockKey].fields.push({ field: f, rawValue: rawVal, wide: isWide });
    }

    return Object.values(blockMap).filter((b) => b.fields.length > 0);
  }, [doc, fieldDefs, t]);

  if (!isOpen) return null;

  const folderPathStr = doc?.folder_path?.map((f) => f.name).join(" / ") || doc?.foldername || "";

  // Status badges for sticky header
  const statusBadges: Array<{ label: string; bg: string; color: string }> = [];
  if (doc) {
    statusBadges.push(doc.filestatus === 1
      ? { label: t("LBL_STATUS_ACTIVE"), bg: "#e7f4e8", color: "#2e8b46" }
      : { label: t("LBL_STATUS_INACTIVE"), bg: "#f3f4f6", color: "#6b7280" });
    if (doc.compliance?.document_category) {
      const catKey = doc.compliance.document_category;
      const catField = fieldDefs.find((f) => f.name === "document_category");
      const catLabel = catField?.picklistValues?.find((pv) => pv.value === catKey)?.label
        || ({ invoice: t("LBL_CATEGORY_INVOICE"), receipt: t("LBL_CATEGORY_RECEIPT"), contract: t("LBL_CATEGORY_CONTRACT"), estimate: t("LBL_CATEGORY_ESTIMATE"), order: t("LBL_CATEGORY_ORDER"), delivery: t("LBL_CATEGORY_DELIVERY"), other: t("LBL_CATEGORY_OTHER") } as Record<string, string>)[catKey]
        || catKey;
      statusBadges.push({ label: catLabel, bg: "#e7eefb", color: "#3461bd" });
    }
    if (doc.compliance?.compliance_status) {
      const s = getComplianceStatusStyle(t, doc.compliance.compliance_status);
      statusBadges.push({ label: s.label, bg: s.bg, color: s.color });
    }
  }

  // ─── Mobile ───
  if (isMobile) {
    return (
      <div style={{ position: "fixed", inset: 0, backgroundColor: "#fff", zIndex: 100001, display: "flex", flexDirection: "column" }}>
        {showMobilePreview && doc && (
          <FilePreviewRenderer filetype={doc.filetype} filelocationtype={doc.filelocationtype} filename={doc.filename} downloadUrl={doc.download_url} previewUrl={doc.preview_url} title={doc.title} expandOnMount onExpandClose={() => setShowMobilePreview(false)} />
        )}
        <div style={{ display: "flex", alignItems: "center", padding: "10px 12px", borderBottom: "1px solid #E2E8F0", gap: 8, flexShrink: 0 }}>
          <button onClick={onClose} style={{ border: "none", background: "none", fontSize: 20, color: "#4A5568", cursor: "pointer", padding: "2px 6px", lineHeight: 1 }}>←</button>
          <div style={{ flex: 1, fontSize: 14, fontWeight: 600, color: "#2D3748", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{doc?.title || t("LBL_DOCUMENT_DETAIL")}</div>
        </div>
        <div style={{ flex: 1, overflowY: "auto" }}>
          {isLoading ? <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>{t("LBL_LOADING")}</div>
          : !doc ? <div style={{ padding: 40, textAlign: "center", color: "#A0AEC0" }}>{t("LBL_DOCUMENT_NOT_FOUND")}</div>
          : (
            <div style={{ padding: "12px 16px", display: "flex", flexDirection: "column", gap: 12 }}>
              {doc.compliance?.compliance_status === "non_compliant" && doc.compliance?.compliance_notes && (
                <div style={{ padding: "8px 12px", background: "#FFF5F5", border: "1px solid #FED7D7", borderRadius: 6, fontSize: 12, color: "#822727", display: "flex", gap: 6, alignItems: "flex-start" }}>
                  <span style={{ flexShrink: 0, marginTop: 1 }}>!</span>
                  <div>
                    <div style={{ fontWeight: 600, marginBottom: 2 }}>{t("LBL_NON_COMPLIANT_REASON")}</div>
                    {doc.compliance.compliance_notes.split("; ").map((reason, i) => (
                      <div key={i}>・{reason}</div>
                    ))}
                  </div>
                </div>
              )}
              {doc.filelocationtype === "I" && doc.download_url && (
                <button onClick={() => setShowMobilePreview(true)} style={{ width: "100%", padding: "10px 0", border: "1px solid #4299E1", borderRadius: 6, fontSize: 14, color: "#4299E1", backgroundColor: "#EBF8FF", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center", gap: 6 }}>
                  {"\u26F6 " + t("LBL_PREVIEW")}
                </button>
              )}
              {blocks.map((b) => <BlockCard key={b.label} block={b} dynamicFields={doc.dynamic_fields} t={t} />)}
              {/* Related records */}
              <div style={{ border: "1px solid #e7e9ec", borderRadius: 10, overflow: "hidden", background: "#fff" }}>
                <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "12px 16px", background: "#fafbfc", borderBottom: "1px solid #eef0f2" }}>
                  <span style={{ width: 4, height: 16, borderRadius: 2, background: "#5b8def" }} />
                  <span style={{ fontSize: 14, fontWeight: 700, color: "#2b2f33" }}>{t("LBL_RELATED_RECORDS")}</span>
                </div>
                <div style={{ padding: "12px 16px" }}>
                  {doc.related_records && doc.related_records.length > 0
                    ? doc.related_records.map((rel) => <RelatedRecordCard key={rel.id} record={rel} />)
                    : doc.compliance
                      ? <div style={{ padding: "10px 12px", backgroundColor: "#FFFFF0", border: "1px solid #FEFCBF", borderRadius: 6, fontSize: 12, color: "#744210" }}><div style={{ fontWeight: 600, marginBottom: 2 }}>{t("LBL_NO_RELATED_RECORD")}</div><div>{t("LBL_RELATE_REQUIRED_MSG")}</div></div>
                      : <div style={{ fontSize: 12, color: "#A0AEC0" }}>{t("LBL_NO_RELATED_RECORD_SHORT")}</div>}
                </div>
              </div>
            </div>
          )}
        </div>
        {doc && (
          <div style={{ display: "flex", gap: 8, padding: "10px 16px", borderTop: "1px solid #E2E8F0", flexShrink: 0 }}>
            <button onClick={() => onEdit(doc)} style={{ flex: 1, padding: "10px 0", border: "1px solid #E2E8F0", borderRadius: 6, fontSize: 14, color: "#4A5568", backgroundColor: "#fff", cursor: "pointer" }}>{t("LBL_EDIT")}</button>
            <button onClick={() => setShowHistory(true)} style={{ flex: 1, padding: "10px 0", border: "1px solid #E2E8F0", borderRadius: 6, fontSize: 14, color: "#4A5568", backgroundColor: "#fff", cursor: "pointer" }}>{t("LBL_SHOW_HISTORY")}</button>
            {doc.filelocationtype === "I" && doc.download_url && <a href={doc.download_url} style={{ flex: 1, padding: "10px 0", borderRadius: 6, fontSize: 14, color: "#fff", backgroundColor: "#38A169", textDecoration: "none", border: "none", textAlign: "center" }}>{t("Download")}</a>}
            <button onClick={() => { if (window.confirm(t("LBL_CONFIRM_DELETE"))) onDelete(doc.id); }} style={{ padding: "10px 16px", border: "1px solid #FEB2B2", borderRadius: 6, fontSize: 14, color: "#E53E3E", backgroundColor: "#fff", cursor: "pointer" }}>{t("LBL_DELETE")}</button>
          </div>
        )}
        <ComplianceHistoryModal isOpen={showHistory} title={doc?.title || ""} fileVersions={doc?.file_versions || []} auditLog={doc?.audit_log || []} onClose={() => setShowHistory(false)} />
      </div>
    );
  }

  // ─── PC: Card-based layout ───
  const btnStyle: React.CSSProperties = { display: "inline-flex", alignItems: "center", height: 34, padding: "0 16px", borderRadius: 6, fontSize: 13, fontWeight: 600, cursor: "pointer", background: "#fff", border: "1px solid #cfd4da", color: "#3a3f44" };

  return (
    <div style={{ position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.4)", display: "flex", alignItems: "center", justifyContent: "center", zIndex: 100001 }} onClick={onClose}>
      <div style={{ backgroundColor: "#fff", borderRadius: 12, width: 1180, maxWidth: "95vw", maxHeight: "90vh", display: "flex", flexDirection: "column", boxShadow: "0 24px 60px rgba(20,28,40,.16)", overflow: "hidden", border: "1px solid #e3e6ea" }} onClick={(e) => e.stopPropagation()}>

        {/* Header bar */}
        <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "13px 20px", borderBottom: "1px solid #e9ebee", background: "#fff" }}>
          <span style={{ fontSize: 14, color: "#7b828a", fontWeight: 500 }}>{folderPathStr}</span>
          <div style={{ flex: 1 }} />
          {doc && (
            <>
              <button onClick={() => onEdit(doc)} style={btnStyle}>{t("LBL_EDIT")}</button>
              <button onClick={() => setShowHistory(true)} style={btnStyle}>{t("LBL_SHOW_HISTORY")}</button>
              {doc.filelocationtype === "I" && doc.download_url && (
                <a href={doc.download_url} style={{ ...btnStyle, background: "#57ab5a", border: "1px solid #4f9d52", color: "#fff", textDecoration: "none" }}>{t("Download")}</a>
              )}
              <button onClick={() => { if (window.confirm(t("LBL_CONFIRM_DELETE"))) onDelete(doc.id); }} style={{ ...btnStyle, border: "1px solid #e3b4b4", color: "#cf5a5a" }}>{t("LBL_DELETE")}</button>
            </>
          )}
          <button onClick={onClose} style={{ ...btnStyle, width: 34, padding: 0, justifyContent: "center", color: "#9aa0a8", fontSize: 19, lineHeight: 1 }}>×</button>
        </div>

        {/* Body: Preview | Scrollable cards */}
        <div style={{ display: "flex", flex: 1, minHeight: 0 }}>
          {isLoading ? <div style={{ flex: 1, display: "flex", alignItems: "center", justifyContent: "center", color: "#A0AEC0" }}>{t("LBL_LOADING")}</div>
          : !doc ? <div style={{ flex: 1, display: "flex", alignItems: "center", justifyContent: "center", color: "#A0AEC0" }}>{t("LBL_DOCUMENT_NOT_FOUND")}</div>
          : (
            <>
              {/* Left: Preview */}
              <div style={{ width: 480, flexShrink: 0, borderRight: "1px solid #eef0f2", padding: 18, background: "#fafbfc", display: "flex", flexDirection: "column" }}>
                <div style={{ flex: 1, borderRadius: 10, overflow: "hidden", border: "1px solid #e3e6ea", background: "#fff", display: "flex", flexDirection: "column" }}>
                  <div style={{ flex: 1, minHeight: 0, overflow: "auto", display: "flex", alignItems: "center", justifyContent: "center" }}>
                    <FilePreviewRenderer filetype={doc.filetype} filelocationtype={doc.filelocationtype} filename={doc.filename} downloadUrl={doc.download_url} previewUrl={doc.preview_url} title={doc.title} maxHeight={520} />
                  </div>
                </div>
              </div>

              {/* Right: Scrollable card area */}
              <div style={{ flex: 1, minWidth: 0, overflowY: "auto" }}>
                {/* Sticky title + badges */}
                <div style={{ position: "sticky", top: 0, zIndex: 2, background: "#fff", padding: "18px 22px 14px", borderBottom: "1px solid #eef0f2" }}>
                  <div style={{ fontSize: 19, fontWeight: 700, color: "#23272b", lineHeight: 1.3 }}>{doc.title}</div>
                  <div style={{ display: "flex", alignItems: "center", flexWrap: "wrap", gap: 9, marginTop: 9 }}>
                    <span style={{ fontSize: 13, color: "#8a9099" }}>{doc.filename}</span>
                    {statusBadges.map((b, i) => (
                      <React.Fragment key={i}>
                        {i === 0 && <span style={{ color: "#cfd4da" }}>·</span>}
                        <span style={{ display: "inline-flex", alignItems: "center", height: 22, padding: "0 10px", borderRadius: 11, fontSize: 11.5, fontWeight: 600, background: b.bg, color: b.color }}>{b.label}</span>
                      </React.Fragment>
                    ))}
                  </div>
                  {doc.compliance?.compliance_status === "non_compliant" && doc.compliance?.compliance_notes && (
                    <div style={{ marginTop: 8, padding: "8px 12px", background: "#FFF5F5", border: "1px solid #FED7D7", borderRadius: 6, fontSize: 12, color: "#822727", display: "flex", gap: 6, alignItems: "flex-start" }}>
                      <span style={{ flexShrink: 0, marginTop: 1 }}>!</span>
                      <div>
                        <div style={{ fontWeight: 600, marginBottom: 2 }}>{t("LBL_NON_COMPLIANT_REASON")}</div>
                        {doc.compliance.compliance_notes.split("; ").map((reason, i) => (
                          <div key={i}>・{reason}</div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>

                {/* Cards */}
                <div style={{ padding: "16px 22px 24px", display: "flex", flexDirection: "column", gap: 14 }}>
                  {blocks.map((b) => <BlockCard key={b.label} block={b} dynamicFields={doc.dynamic_fields} t={t} />)}

                  {/* Related records card */}
                  <div style={{ border: "1px solid #e7e9ec", borderRadius: 10, overflow: "hidden", background: "#fff" }}>
                    <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "12px 16px", background: "#fafbfc", borderBottom: "1px solid #eef0f2" }}>
                      <span style={{ width: 4, height: 16, borderRadius: 2, background: "#5b8def" }} />
                      <span style={{ fontSize: 14, fontWeight: 700, color: "#2b2f33" }}>{t("LBL_RELATED_RECORDS")}</span>
                    </div>
                    <div style={{ padding: "12px 16px" }}>
                      {doc.related_records && doc.related_records.length > 0
                        ? doc.related_records.map((rel) => <RelatedRecordCard key={rel.id} record={rel} />)
                        : doc.compliance
                          ? <div style={{ padding: "10px 12px", backgroundColor: "#FFFFF0", border: "1px solid #FEFCBF", borderRadius: 6, fontSize: 12, color: "#744210" }}><div style={{ fontWeight: 600, marginBottom: 2 }}>{t("LBL_NO_RELATED_RECORD")}</div><div>{t("LBL_RELATE_REQUIRED_MSG")}</div></div>
                          : <div style={{ fontSize: 13, color: "#A0AEC0", padding: 4 }}>{t("LBL_NO_RELATED_RECORD_SHORT")}</div>}
                    </div>
                  </div>
                </div>
              </div>
            </>
          )}
        </div>
      </div>

      {/* History modal */}
      <ComplianceHistoryModal isOpen={showHistory} title={doc?.title || ""} fileVersions={doc?.file_versions || []} auditLog={doc?.audit_log || []} onClose={() => setShowHistory(false)} />
    </div>
  );
};
