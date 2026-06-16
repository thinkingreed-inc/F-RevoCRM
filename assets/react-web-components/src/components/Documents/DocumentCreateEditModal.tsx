import React, { useState, useEffect, useCallback, useRef } from "react";
import type { DocumentDetail, Folder } from "./types/documents";

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

const DOC_TYPE_LABELS: Record<DocType, string> = {
  I: "ファイル",
  E: "URL",
};

const labelStyle: React.CSSProperties = {
  display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4,
};
const inputStyle: React.CSSProperties = {
  width: "100%", padding: "7px 10px", border: "1px solid #E2E8F0", borderRadius: 4, fontSize: 14, outline: "none", boxSizing: "border-box",
};

export const DocumentCreateEditModal: React.FC<DocumentCreateEditModalProps> = ({
  isOpen, mode, document: doc, folders, defaultFolderId, parentModule, parentId, onSave, onClose,
}) => {
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
      } else {
        setDocType("I");
        setTitle("");
        setFilename("");
        setFolderid(defaultFolderId || 1);
        setNotecontent("");
        setFileversion("");
        setFilestatus(true);
      }
    }
  }, [isOpen, mode, doc, defaultFolderId]);

  const handleFileSelect = useCallback((file: File) => {
    setSelectedFile(file);
    if (!title) {
      setTitle(file.name.replace(/\.[^.]+$/, ""));
    }
  }, [title]);

  const handleSave = useCallback(async () => {
    if (!title.trim()) {
      setError("タイトルを入力してください");
      return;
    }
    if (docType === "I" && mode === "create" && !selectedFile) {
      setError("ファイルを選択してください");
      return;
    }
    if (docType === "E" && !filename.trim()) {
      setError("URLを入力してください");
      return;
    }

    setIsSaving(true);
    setError(null);

    try {
      const csrf = getCsrfToken();
      if (!csrf) throw new Error("CSRFトークンが取得できません");

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
        formData.append("fileversion", fileversion);
        formData.append("filename", selectedFile, selectedFile.name);
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
          throw new Error("保存に失敗しました");
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
        bodyParams.append("fileversion", fileversion);
        if (docType === "E") {
          bodyParams.append("filename", filename.trim());
        }
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
          throw new Error("保存に失敗しました");
        }
      }

      onSave();
    } catch (e: any) {
      setError(e.message || "保存に失敗しました");
    } finally {
      setIsSaving(false);
    }
  }, [title, docType, filename, folderid, notecontent, fileversion, filestatus, selectedFile, mode, doc, parentModule, parentId, onSave]);

  if (!isOpen) return null;

  return (
    <div
      style={{ position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.4)", display: "flex", alignItems: "center", justifyContent: "center", zIndex: 1000 }}
      onClick={onClose}
    >
      <div
        style={{ backgroundColor: "#fff", borderRadius: 8, width: 640, maxWidth: "95vw", maxHeight: "90vh", display: "flex", flexDirection: "column", boxShadow: "0 10px 40px rgba(0,0,0,0.2)" }}
        onClick={(e) => e.stopPropagation()}
      >
        {/* ヘッダー */}
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "16px 20px", borderBottom: "1px solid #E2E8F0" }}>
          <h3 style={{ margin: 0, fontSize: 16, fontWeight: 600, color: "#2D3748" }}>
            {mode === "create" ? "ドキュメントの追加" : "ドキュメントの編集"}
          </h3>
          <button onClick={onClose} style={{ width: 28, height: 28, border: "none", backgroundColor: "transparent", fontSize: 18, color: "#A0AEC0", cursor: "pointer" }}>×</button>
        </div>

        {/* コンテンツ */}
        <div style={{ flex: 1, overflowY: "auto", padding: 20 }}>
          {error && (
            <div style={{ padding: "8px 12px", backgroundColor: "#FED7D7", color: "#C53030", borderRadius: 4, fontSize: 13, marginBottom: 16 }}>{error}</div>
          )}

          {/* ドキュメント種別（新規のみ） */}
          {mode === "create" && (
            <div style={{ marginBottom: 16 }}>
              <label style={labelStyle}>ドキュメント種別</label>
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
                    {DOC_TYPE_LABELS[type]}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* 基本情報 */}
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "12px 16px", marginBottom: 16 }}>
            <div>
              <label style={labelStyle}>タイトル <span style={{ color: "#E53E3E" }}>*</span></label>
              <input type="text" value={title} onChange={(e) => setTitle(e.target.value)} style={inputStyle} />
            </div>

            <div>
              <label style={labelStyle}>フォルダ</label>
              <select value={folderid} onChange={(e) => setFolderid(Number(e.target.value))} style={inputStyle}>
                {folders.map((f) => (
                  <option key={f.id} value={f.id}>{f.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label style={labelStyle}>バージョン</label>
              <input type="text" value={fileversion} onChange={(e) => setFileversion(e.target.value)} style={inputStyle} />
            </div>

            <div>
              <label style={labelStyle}>ステータス</label>
              <label style={{ display: "flex", alignItems: "center", gap: 6, fontSize: 13, color: "#4A5568", cursor: "pointer" }}>
                <input type="checkbox" checked={filestatus} onChange={(e) => setFilestatus(e.target.checked)} /> 有効
              </label>
            </div>
          </div>

          {/* ファイル / URL 入力 */}
          {docType === "I" && (
            <div style={{ marginBottom: 16 }}>
              <label style={labelStyle}>ファイル {mode === "create" && <span style={{ color: "#E53E3E" }}>*</span>}</label>
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
                    現在のファイル: {doc.filename}（変更する場合はファイルを選択）
                  </div>
                ) : (
                  <div style={{ fontSize: 13, color: "#A0AEC0" }}>
                    ファイルをドラッグ&ドロップ または クリックして選択
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

          {/* メモ */}
          <div style={{ marginBottom: 8 }}>
            <label style={labelStyle}>メモ</label>
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
            キャンセル
          </button>
          <button
            onClick={handleSave}
            disabled={isSaving}
            style={{
              padding: "7px 20px", backgroundColor: isSaving ? "#A0AEC0" : "#38A169", color: "#fff",
              border: "none", borderRadius: 4, fontSize: 14, cursor: isSaving ? "wait" : "pointer",
            }}
          >
            {isSaving ? "保存中..." : "保存"}
          </button>
        </div>
      </div>
    </div>
  );
};
