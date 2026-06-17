import React, { useState, useEffect, useCallback, useRef, useMemo } from "react";
import type { DocumentDetail, Folder } from "./types/documents";
import { useOptionalTranslation } from "../../hooks/useTranslation";
import { useDocumentFields, DocFieldInfo } from "./hooks/useDocumentFields";

function useIsMobile(breakpoint = 768) {
  const [isMobile, setIsMobile] = useState(window.innerWidth <= breakpoint);
  useEffect(() => {
    const handler = () => setIsMobile(window.innerWidth <= breakpoint);
    window.addEventListener("resize", handler);
    return () => window.removeEventListener("resize", handler);
  }, [breakpoint]);
  return isMobile;
}
import { FieldRenderer } from "../FieldRenderer";
import { FieldInfo, FieldValue } from "../../types/field";

interface DocumentCreateEditModalProps {
  isOpen: boolean;
  mode: "create" | "edit";
  document?: DocumentDetail | null;
  folders: Folder[];
  defaultFolderId: number;
  parentModule?: string;
  parentId?: number;
  onSave: () => void;
  onClose: () => void;
}

type DocType = "I" | "E";

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as any).csrfMagicName;
  const csrfToken = (window as any).csrfMagicToken;
  if (csrfName && csrfToken) return { name: csrfName, value: csrfToken };
  return null;
}

const labelStyle: React.CSSProperties = {
  display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4,
};
const inputStyle: React.CSSProperties = {
  width: "100%", padding: "7px 10px", border: "1px solid #E2E8F0", borderRadius: 4, fontSize: 14, outline: "none", boxSizing: "border-box",
};

/** Core document field names handled by hardcoded UI (special behavior: file upload, folder select, etc.) */
const CORE_FIELD_NAMES = new Set([
  "notes_title", "filename", "filelocationtype", "folderid", "notecontent",
  "filestatus", "fileversion", "note_no", "modifiedtime", "createdtime",
  "modifiedby", "assigned_user_id", "source",
]);

/** Convert DocFieldInfo to FieldInfo for use with FieldRenderer */
function convertToFieldInfo(docField: DocFieldInfo): FieldInfo {
  return {
    name: docField.name,
    label: docField.label,
    uitype: docField.uitype,
    mandatory: docField.mandatory,
    readonly: !docField.editable,
    fieldinfo: {
      type: docField.type,
      defaultvalue: docField.defaultvalue || undefined,
      editable: docField.editable,
      displaytype: docField.displaytype || "1",
    },
    picklistValues: docField.picklistValues,
    referenceModules: docField.referenceModules,
    referenceModuleLabels: docField.referenceModuleLabels,
  };
}

export const DocumentCreateEditModal: React.FC<DocumentCreateEditModalProps> = ({
  isOpen, mode, document: doc, folders, defaultFolderId, parentModule, parentId, onSave, onClose,
}) => {
  const { t } = useOptionalTranslation();
  const isMobile = useIsMobile();
  const { fields: fieldDefs, isLoading: fieldsLoading } = useDocumentFields(doc?.id);

  const docTypeLabels: Record<DocType, string> = {
    I: t('LBL_DOC_TYPE_FILE'),
    E: "URL",
  };

  // Group dynamic (non-core) fields by block label
  const dynamicBlockFields = useMemo(() => {
    const blocks: Record<string, DocFieldInfo[]> = {};
    for (const f of fieldDefs) {
      if (CORE_FIELD_NAMES.has(f.name)) continue;
      if (String(f.displaytype) === "2") continue; // system-managed, skip in edit
      if (!f.blockLabel) continue;
      if (!blocks[f.blockLabel]) blocks[f.blockLabel] = [];
      blocks[f.blockLabel].push(f);
    }
    return blocks;
  }, [fieldDefs]);

  const [docType, setDocType] = useState<DocType>("I");
  const [title, setTitle] = useState("");
  const [filename, setFilename] = useState("");
  const [folderid, setFolderid] = useState(defaultFolderId || 1);
  const [notecontent, setNotecontent] = useState("");
  const [fileversion, setFileversion] = useState("");
  const [filestatus, setFilestatus] = useState(true);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isDragging, setIsDragging] = useState(false);

  // Dynamic field values (compliance, scanner, etc.)
  const [dynamicFields, setDynamicFields] = useState<Record<string, any>>({});
  const handleDynamicFieldChange = useCallback((fieldName: string, value: FieldValue) => {
    setDynamicFields((prev) => ({ ...prev, [fieldName]: value }));
  }, []);

  useEffect(() => {
    if (isOpen) {
      setError(null);
      setSelectedFile(null);
      if (mode === "edit" && doc) {
        setDocType((doc.filelocationtype as DocType) || "I");
        setTitle(doc.title || "");
        setFilename(doc.filename || "");
        setFolderid(doc.folderid || 1);
        setNotecontent(doc.notecontent || "");
        setFileversion(doc.fileversion || "");
        setFilestatus(doc.filestatus === 1);
        // Populate dynamic fields from doc.dynamic_fields (returned by DetailAPI)
        const dynValues: Record<string, any> = {};
        const df = doc.dynamic_fields;
        // First, load all values from dynamic_fields (空文字も保持する)
        if (df) {
          for (const [key, val] of Object.entries(df)) {
            if (val !== undefined && val !== null) {
              dynValues[key] = String(val);
            }
          }
        }
        // Fallback: load from compliance object for backward compatibility
        const c = doc.compliance;
        if (c) {
          for (const key of ["document_category", "preservation_type", "receipt_date", "scan_resolution_dpi", "scan_color_type", "original_paper_size"]) {
            if (dynValues[key] === undefined) {
              const val = (c as any)[key];
              if (val !== undefined && val !== null && val !== "") {
                dynValues[key] = String(val);
              }
            }
          }
        }
        setDynamicFields(dynValues);
      } else {
        setDocType("I");
        setTitle("");
        setFilename("");
        setFolderid(defaultFolderId || 1);
        setNotecontent("");
        setFileversion("");
        setFilestatus(true);
        // Set defaults from field definitions
        const defaults: Record<string, any> = {};
        for (const f of fieldDefs) {
          if (CORE_FIELD_NAMES.has(f.name)) continue;
          if (f.defaultvalue) {
            defaults[f.name] = f.defaultvalue;
          }
        }
        setDynamicFields(defaults);
      }
    }
  }, [isOpen, mode, doc, defaultFolderId, fieldDefs]);

  const handleFileSelect = useCallback((file: File) => {
    setSelectedFile(file);
    if (!title) {
      setTitle(file.name.replace(/\.[^.]+$/, ""));
    }
  }, [title]);

  /** Append dynamic field values to form params for the main Save action.
   *  空文字もそのまま送信する（vtiger Saveはnullのみスキップするため、
   *  空文字を送れば値を明示的にクリアできる）。 */
  const appendDynamicFields = useCallback((append: (key: string, val: string) => void) => {
    for (const [key, val] of Object.entries(dynamicFields)) {
      if (val === undefined || val === null) continue;
      append(key, typeof val === "boolean" ? (val ? "1" : "0") : String(val));
    }
  }, [dynamicFields]);

  const handleSave = useCallback(async () => {
    if (!title.trim()) {
      setError(t('LBL_TITLE_REQUIRED'));
      return;
    }
    if (docType === "I" && mode === "create" && !selectedFile) {
      setError(t('LBL_FILE_REQUIRED'));
      return;
    }
    if (docType === "E" && !filename.trim()) {
      setError(t('LBL_URL_REQUIRED'));
      return;
    }

    setIsSaving(true);
    setError(null);

    try {
      const csrf = getCsrfToken();
      if (!csrf) throw new Error(t('LBL_CSRF_TOKEN_ERROR'));

      if (docType === "I" && selectedFile) {
        // ファイルアップロード (FormData)
        const formData = new FormData();
        formData.append(csrf.name, csrf.value);
        formData.append("module", "Documents");
        formData.append("action", "Save");
        formData.append("notes_title", title.trim());
        formData.append("filelocationtype", "I");
        formData.append("filestatus", filestatus ? "1" : "0");
        formData.append("folderid", String(folderid));
        formData.append("notecontent", notecontent);
        formData.append("filename", selectedFile, selectedFile.name);
        appendDynamicFields((k, v) => formData.append(k, v));
        if (mode === "edit" && doc) {
          formData.append("record", String(doc.id));
        }
        if (mode === "create" && parentModule && parentId) {
          formData.append("relationOperation", "true");
          formData.append("sourceModule", parentModule);
          formData.append("sourceRecord", String(parentId));
        }

        const response = await fetch("index.php", {
          method: "POST",
          credentials: "same-origin",
          body: formData,
        });
        const text = await response.text();
        // Save action redirects, check for error
        if (text.includes("error") && text.includes('"success":false')) {
          throw new Error(t('LBL_SAVE_FAILED'));
        }

        // 電帳法メタデータ保存（書類区分が指定されている場合）
        if (dynamicFields["document_category"] && mode === "edit" && doc) {
          await saveComplianceMetadata(doc.id, csrf);
        }
      } else {
        // 非ファイル (URLSearchParams)
        const bodyParams = new URLSearchParams();
        bodyParams.append(csrf.name, csrf.value);
        bodyParams.append("module", "Documents");
        bodyParams.append("action", "Save");
        bodyParams.append("notes_title", title.trim());
        bodyParams.append("filelocationtype", docType);
        bodyParams.append("filestatus", filestatus ? "1" : "0");
        bodyParams.append("folderid", String(folderid));
        bodyParams.append("notecontent", notecontent);
        if (docType === "E") {
          bodyParams.append("filename", filename.trim());
        }
        appendDynamicFields((k, v) => bodyParams.append(k, v));
        if (mode === "edit" && doc) {
          bodyParams.append("record", String(doc.id));
        }
        if (mode === "create" && parentModule && parentId) {
          bodyParams.append("relationOperation", "true");
          bodyParams.append("sourceModule", parentModule);
          bodyParams.append("sourceRecord", String(parentId));
        }

        const response = await fetch("index.php", {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: bodyParams.toString(),
        });
        const text = await response.text();
        if (text.includes('"success":false')) {
          throw new Error(t('LBL_SAVE_FAILED'));
        }

        // 電帳法メタデータ保存（書類区分が指定されている場合）
        if (dynamicFields["document_category"] && mode === "edit" && doc) {
          await saveComplianceMetadata(doc.id, csrf);
        }
      }

      onSave();
    } catch (e: any) {
      setError(e.message || t('LBL_SAVE_FAILED'));
    } finally {
      setIsSaving(false);
    }
  }, [title, docType, filename, folderid, notecontent, filestatus, selectedFile, mode, doc, parentModule, parentId, onSave, dynamicFields, appendDynamicFields, t]);

  const saveComplianceMetadata = useCallback(async (notesId: number, csrf: { name: string; value: string }) => {
    const params = new URLSearchParams();
    params.append(csrf.name, csrf.value);
    params.append("module", "Documents");
    params.append("api", "ComplianceAPI");
    params.append("mode", "save_compliance");
    params.append("notesid", String(notesId));
    // Send all dynamic field values to ComplianceAPI
    for (const [key, val] of Object.entries(dynamicFields)) {
      if (val !== undefined && val !== null && val !== "") {
        params.append(key, typeof val === "boolean" ? (val ? "1" : "0") : String(val));
      }
    }
    await fetch("index.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString(),
    });
  }, [dynamicFields]);

  if (!isOpen) return null;

  const gridCols = isMobile ? "1fr" : "1fr 1fr";

  return (
    <div
      style={isMobile
        ? { position: "fixed", inset: 0, backgroundColor: "#fff", zIndex: 100001, display: "flex", flexDirection: "column" }
        : { position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.4)", display: "flex", alignItems: "center", justifyContent: "center", zIndex: 100001 }
      }
      onClick={isMobile ? undefined : onClose}
    >
      <div
        style={isMobile
          ? { flex: 1, display: "flex", flexDirection: "column", overflow: "hidden" }
          : { backgroundColor: "#fff", borderRadius: 8, width: 1100, maxWidth: "95vw", maxHeight: "90vh", display: "flex", flexDirection: "column", boxShadow: "0 10px 40px rgba(0,0,0,0.2)" }
        }
        onClick={isMobile ? undefined : (e) => e.stopPropagation()}
      >
        {/* ヘッダー */}
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: isMobile ? "10px 12px" : "16px 20px", borderBottom: "1px solid #E2E8F0", flexShrink: 0 }}>
          {isMobile ? (
            <button onClick={onClose} style={{ border: "none", background: "none", fontSize: 20, color: "#4A5568", cursor: "pointer", padding: "2px 6px", lineHeight: 1 }}>←</button>
          ) : null}
          <h3 style={{ margin: 0, fontSize: isMobile ? 14 : 16, fontWeight: 600, color: "#2D3748", flex: 1 }}>
            {mode === "create" ? t('LBL_DOCUMENT_CREATE') : t('LBL_DOCUMENT_EDIT')}
          </h3>
          {!isMobile && (
            <button onClick={onClose} style={{ width: 28, height: 28, border: "none", backgroundColor: "transparent", fontSize: 18, color: "#A0AEC0", cursor: "pointer" }}>×</button>
          )}
        </div>

        {/* コンテンツ */}
        <div style={{ flex: 1, overflowY: "auto", padding: isMobile ? "12px 16px" : 20 }}>
          {error && (
            <div style={{ padding: "8px 12px", backgroundColor: "#FED7D7", color: "#C53030", borderRadius: 4, fontSize: 13, marginBottom: 16 }}>{error}</div>
          )}

          {/* ドキュメント種別（新規のみ） */}
          {mode === "create" && (
            <div style={{ marginBottom: 16 }}>
              <label style={labelStyle}>{t('LBL_DOCUMENT_TYPE')}</label>
              <div style={{ display: "flex", gap: 8 }}>
                {(["I", "E"] as DocType[]).map((type) => (
                  <button
                    key={type}
                    onClick={() => setDocType(type)}
                    style={{
                      flex: 1, padding: "8px 12px", border: `2px solid ${docType === type ? "#4299E1" : "#E2E8F0"}`,
                      borderRadius: 6, backgroundColor: docType === type ? "#EBF8FF" : "#fff",
                      color: docType === type ? "#2B6CB0" : "#718096", fontSize: 13, fontWeight: 500, cursor: "pointer",
                    }}
                  >
                    {docTypeLabels[type]}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* 基本情報 */}
          <div style={{ display: "grid", gridTemplateColumns: gridCols, gap: "12px 16px", marginBottom: 16 }}>
            <div>
              <label style={labelStyle}>{t('Title')} <span style={{ color: "#E53E3E" }}>*</span></label>
              <input type="text" value={title} onChange={(e) => setTitle(e.target.value)} style={inputStyle} />
            </div>

            <div>
              <label style={labelStyle}>{t('Folder Name')}</label>
              <select value={folderid} onChange={(e) => setFolderid(Number(e.target.value))} style={inputStyle}>
                {folders.map((f) => (
                  <option key={f.id} value={f.id}>{f.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label style={labelStyle}>{t('Version')}</label>
              <input
                type="text"
                value={fileversion || (mode === "create" ? "1" : "")}
                readOnly
                style={{ ...inputStyle, backgroundColor: "#F7FAFC", color: "#718096", cursor: "default" }}
                title={t('LBL_VERSION_AUTO')}
              />
            </div>

            <div>
              <label style={labelStyle}>{t('LBL_STATUS')}</label>
              <label style={{ display: "flex", alignItems: "center", gap: 6, fontSize: 13, color: "#4A5568", cursor: "pointer" }}>
                <input type="checkbox" checked={filestatus} onChange={(e) => setFilestatus(e.target.checked)} /> {t('LBL_STATUS_ACTIVE')}
              </label>
            </div>
          </div>

          {/* ファイル / URL 入力 */}
          {docType === "I" && (
            <div style={{ marginBottom: 16 }}>
              <label style={labelStyle}>{t('LBL_FILE_LABEL')} {mode === "create" && <span style={{ color: "#E53E3E" }}>*</span>}</label>
              <div
                onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
                onDragLeave={() => setIsDragging(false)}
                onDrop={(e) => {
                  e.preventDefault(); setIsDragging(false);
                  const file = e.dataTransfer.files[0];
                  if (file) handleFileSelect(file);
                }}
                onClick={() => fileInputRef.current?.click()}
                style={{
                  border: `2px dashed ${isDragging ? "#4299E1" : "#E2E8F0"}`,
                  borderRadius: 6, padding: 20, textAlign: "center", cursor: "pointer",
                  backgroundColor: isDragging ? "#EBF8FF" : "#F7FAFC",
                  transition: "all 0.15s",
                }}
              >
                <input
                  ref={fileInputRef}
                  type="file"
                  style={{ display: "none" }}
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleFileSelect(file);
                  }}
                />
                {selectedFile ? (
                  <div style={{ fontSize: 13, color: "#2D3748" }}>
                    <span style={{ fontWeight: 500 }}>{selectedFile.name}</span>
                    <span style={{ color: "#A0AEC0", marginLeft: 8 }}>
                      ({(selectedFile.size / 1024).toFixed(0)} KB)
                    </span>
                  </div>
                ) : mode === "edit" && doc?.filename ? (
                  <div style={{ fontSize: 13, color: "#718096" }}>
                    {t('LBL_CURRENT_FILE', doc.filename)}
                  </div>
                ) : (
                  <div style={{ fontSize: 13, color: "#A0AEC0" }}>
                    {t('LBL_DRAG_DROP_OR_CLICK')}
                  </div>
                )}
              </div>
            </div>
          )}

          {docType === "E" && (
            <div style={{ marginBottom: 16 }}>
              <label style={labelStyle}>URL <span style={{ color: "#E53E3E" }}>*</span></label>
              <input
                type="url"
                value={filename}
                onChange={(e) => setFilename(e.target.value)}
                placeholder="https://example.com/document"
                style={inputStyle}
              />
            </div>
          )}

          {/* Dynamic field blocks (compliance, scanner, etc.) */}
          {!fieldsLoading && Object.entries(dynamicBlockFields).map(([blockLabel, blockFields]) => (
            <div key={blockLabel} style={{ marginBottom: 16, paddingTop: 12, borderTop: "1px solid #E2E8F0" }}>
              <div style={{ fontSize: 13, fontWeight: 600, color: "#4A5568", marginBottom: 10 }}>
                {blockLabel}
              </div>
              <div style={{ display: "grid", gridTemplateColumns: gridCols, gap: "12px 16px" }}>
                {blockFields.map((field) => {
                  const fieldInfo = convertToFieldInfo(field);
                  return (
                    <FieldRenderer
                      key={field.name}
                      field={fieldInfo}
                      value={dynamicFields[field.name] ?? field.defaultvalue ?? ""}
                      onChange={(name: string, val: FieldValue) => handleDynamicFieldChange(name, val)}
                      module="Documents"
                    />
                  );
                })}
              </div>
            </div>
          ))}

          {/* メモ */}
          <div style={{ marginBottom: 8 }}>
            <label style={labelStyle}>{t('Note')}</label>
            <textarea
              value={notecontent}
              onChange={(e) => setNotecontent(e.target.value)}
              rows={4}
              style={{ ...inputStyle, resize: "vertical" }}
            />
          </div>
        </div>

        {/* フッター */}
        <div style={{ display: "flex", justifyContent: "flex-end", gap: 8, padding: "14px 20px", borderTop: "1px solid #E2E8F0" }}>
          <button
            onClick={onClose}
            disabled={isSaving}
            style={{ padding: "7px 20px", backgroundColor: "#fff", color: "#4A5568", border: "1px solid #E2E8F0", borderRadius: 4, fontSize: 14, cursor: "pointer" }}
          >
            {t('LBL_CANCEL')}
          </button>
          <button
            onClick={handleSave}
            disabled={isSaving}
            style={{
              padding: "7px 20px", backgroundColor: isSaving ? "#A0AEC0" : "#38A169", color: "#fff",
              border: "none", borderRadius: 4, fontSize: 14, cursor: isSaving ? "wait" : "pointer",
            }}
          >
            {isSaving ? t('LBL_SAVING') : t('LBL_SAVE')}
          </button>
        </div>
      </div>
    </div>
  );
};
