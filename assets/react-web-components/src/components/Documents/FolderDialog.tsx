import React, { useState, useEffect, useCallback } from "react";
import type { Folder } from "./types/documents";
import { useOptionalTranslation } from "../../hooks/useTranslation";

interface FolderDialogProps {
  isOpen: boolean;
  mode: "create" | "edit";
  folder?: Folder | null;
  parentFolderId: number;
  folders: Folder[];
  onSave: (data: { foldername: string; folderdesc: string; parent_folderid: number; savemode?: string; folderid?: number }) => void;
  onDelete?: (folderId: number) => void;
  onClose: () => void;
}

export const FolderDialog: React.FC<FolderDialogProps> = ({
  isOpen,
  mode,
  folder,
  parentFolderId,
  folders,
  onSave,
  onDelete,
  onClose,
}) => {
  const { t } = useOptionalTranslation();
  const [name, setName] = useState("");
  const [desc, setDesc] = useState("");
  const [parentId, setParentId] = useState(parentFolderId);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (isOpen) {
      if (mode === "edit" && folder) {
        setName(folder.name);
        setDesc(folder.description || "");
        setParentId(folder.parent_id);
      } else {
        setName("");
        setDesc("");
        setParentId(parentFolderId);
      }
      setError(null);
    }
  }, [isOpen, mode, folder, parentFolderId]);

  const handleSave = useCallback(() => {
    const trimmedName = name.trim();
    if (!trimmedName) {
      setError(t('LBL_FOLDER_NAME_REQUIRED'));
      return;
    }
    onSave({
      foldername: trimmedName,
      folderdesc: desc.trim(),
      parent_folderid: parentId,
      ...(mode === "edit" && folder ? { savemode: "edit", folderid: folder.id } : {}),
    });
  }, [name, desc, parentId, mode, folder, onSave]);

  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === "Enter") handleSave();
      if (e.key === "Escape") onClose();
    },
    [handleSave, onClose]
  );

  if (!isOpen) return null;

  // 親フォルダ選択肢（自分自身と子孫は除外）
  const getDescendantIds = (id: number): number[] => {
    const children = folders.filter((f) => f.parent_id === id);
    return children.reduce<number[]>((acc, c) => [...acc, c.id, ...getDescendantIds(c.id)], []);
  };
  const excludeIds = mode === "edit" && folder ? [folder.id, ...getDescendantIds(folder.id)] : [];
  const parentOptions = folders.filter((f) => !excludeIds.includes(f.id));

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        backgroundColor: "rgba(0,0,0,0.3)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: 1000,
      }}
      onClick={onClose}
    >
      <div
        style={{
          backgroundColor: "#fff",
          borderRadius: 8,
          padding: 24,
          width: 400,
          maxWidth: "90vw",
          boxShadow: "0 8px 30px rgba(0,0,0,0.15)",
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <h3 style={{ margin: "0 0 16px", fontSize: 16, fontWeight: 600, color: "#2D3748" }}>
          {mode === "create" ? t('LBL_ADD_FOLDER_TITLE') : t('LBL_EDIT_FOLDER_TITLE')}
        </h3>

        {error && (
          <div style={{ padding: "6px 10px", backgroundColor: "#FED7D7", color: "#C53030", borderRadius: 4, fontSize: 13, marginBottom: 12 }}>
            {error}
          </div>
        )}

        <div style={{ marginBottom: 12 }}>
          <label style={{ display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4 }}>
            {t('LBL_FOLDER_NAME_LABEL')} <span style={{ color: "#E53E3E" }}>*</span>
          </label>
          <input
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            onKeyDown={handleKeyDown}
            autoFocus
            style={{
              width: "100%",
              padding: "6px 10px",
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              fontSize: 14,
              outline: "none",
              boxSizing: "border-box",
            }}
          />
        </div>

        <div style={{ marginBottom: 12 }}>
          <label style={{ display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4 }}>
            {t('LBL_FOLDER_DESCRIPTION_LABEL')}
          </label>
          <textarea
            value={desc}
            onChange={(e) => setDesc(e.target.value)}
            rows={2}
            style={{
              width: "100%",
              padding: "6px 10px",
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              fontSize: 13,
              outline: "none",
              boxSizing: "border-box",
              resize: "vertical",
            }}
          />
        </div>

        <div style={{ marginBottom: 20 }}>
          <label style={{ display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4 }}>
            {t('LBL_PARENT_FOLDER')}
          </label>
          <select
            value={parentId}
            onChange={(e) => setParentId(Number(e.target.value))}
            style={{
              width: "100%",
              padding: "6px 10px",
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              fontSize: 13,
              outline: "none",
              boxSizing: "border-box",
            }}
          >
            <option value={0}>{t('LBL_ROOT_FOLDER')}</option>
            {parentOptions.map((f) => (
              <option key={f.id} value={f.id}>
                {f.name}
              </option>
            ))}
          </select>
        </div>

        <div style={{ display: "flex", justifyContent: "space-between", gap: 8 }}>
          <div>
            {mode === "edit" && folder && folder.name !== "Default" && onDelete && (
              <button
                onClick={() => {
                  if (window.confirm(t('LBL_CONFIRM_DELETE_FOLDER', folder.name))) {
                    onDelete(folder.id);
                  }
                }}
                style={{
                  padding: "6px 14px",
                  backgroundColor: "#fff",
                  color: "#E53E3E",
                  border: "1px solid #FEB2B2",
                  borderRadius: 4,
                  fontSize: 13,
                  cursor: "pointer",
                }}
              >
                {t('LBL_DELETE')}
              </button>
            )}
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <button
              onClick={onClose}
              style={{
                padding: "6px 14px",
                backgroundColor: "#fff",
                color: "#4A5568",
                border: "1px solid #E2E8F0",
                borderRadius: 4,
                fontSize: 13,
                cursor: "pointer",
              }}
            >
              {t('LBL_CANCEL')}
            </button>
            <button
              onClick={handleSave}
              style={{
                padding: "6px 14px",
                backgroundColor: "#4299E1",
                color: "#fff",
                border: "none",
                borderRadius: 4,
                fontSize: 13,
                cursor: "pointer",
              }}
            >
              {mode === "create" ? t('LBL_ADD') : t('LBL_SAVE')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
