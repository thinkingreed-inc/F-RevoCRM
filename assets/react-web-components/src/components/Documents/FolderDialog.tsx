import React, { useState, useEffect, useCallback } from "react";
import type { Folder, FolderPermission, PermissionTargets } from "./types/documents";
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

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as any).csrfMagicName;
  const csrfToken = (window as any).csrfMagicToken;
  if (csrfName && csrfToken) return { name: csrfName, value: csrfToken };
  return null;
}

const inputStyle: React.CSSProperties = {
  width: "100%", padding: "6px 10px", border: "1px solid #E2E8F0",
  borderRadius: 4, fontSize: 13, outline: "none", boxSizing: "border-box",
};

type PermType = "view" | "edit";
type TargetType = "everyone" | "user" | "role" | "group";

interface PermRow {
  key: string;
  permission_type: PermType;
  target_type: TargetType;
  target_id: string | number | null;
  target_name?: string | null;
}

export const FolderDialog: React.FC<FolderDialogProps> = ({
  isOpen, mode, folder, parentFolderId, folders, onSave, onDelete, onClose,
}) => {
  const { t } = useOptionalTranslation();
  const [name, setName] = useState("");
  const [desc, setDesc] = useState("");
  const [parentId, setParentId] = useState(parentFolderId);
  const [error, setError] = useState<string | null>(null);

  // 権限設定
  const [permRows, setPermRows] = useState<PermRow[]>([]);
  const [targets, setTargets] = useState<PermissionTargets | null>(null);
  const [permLoading, setPermLoading] = useState(false);
  const [permSaving, setPermSaving] = useState(false);
  const [permMessage, setPermMessage] = useState<string | null>(null);
  const [isAdmin, setIsAdmin] = useState(false);

  // 管理者判定
  useEffect(() => {
    const u = (window as any).userIsAdmin;
    // Fallback: check for admin marker in DOM
    if (u !== undefined) {
      setIsAdmin(!!u);
    } else {
      const meta = document.querySelector('meta[name="user-is-admin"]');
      setIsAdmin(meta?.getAttribute("content") === "1");
      // Last resort: check if Settings menu is visible
      if (!meta) {
        const settingsLink = document.querySelector('[href*="parent=Settings"]');
        setIsAdmin(!!settingsLink);
      }
    }
  }, []);

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
      setPermMessage(null);
    }
  }, [isOpen, mode, folder, parentFolderId]);

  // 権限付与先候補の取得（1回のみ）
  useEffect(() => {
    if (!isOpen || !isAdmin || targets) return;
    (async () => {
      try {
        const res = await fetch(`index.php?module=Documents&api=FolderAPI&mode=getPermissionTargets`, { credentials: "same-origin", headers: { Accept: "application/json" } });
        const data = await res.json();
        const result = data.result || data;
        setTargets({ users: result.users || [], roles: result.roles || [], groups: result.groups || [] });
      } catch { /* ignore */ }
    })();
  }, [isOpen, isAdmin, targets]);

  // 権限設定の読み込み（editモード時）
  useEffect(() => {
    if (!isOpen || !isAdmin || mode !== "edit" || !folder) return;
    setPermLoading(true);
    (async () => {
      try {
        const res = await fetch(`index.php?module=Documents&api=FolderAPI&mode=getPermissions&folderid=${folder.id}`, { credentials: "same-origin", headers: { Accept: "application/json" } });
        const data = await res.json();
        const perms: FolderPermission[] = (data.result || data).permissions || [];
        setPermRows(perms.map((p, i) => ({
          key: `e${i}`,
          permission_type: p.permission_type as PermType,
          target_type: p.target_type as TargetType,
          target_id: p.target_id,
          target_name: p.target_name,
        })));
      } catch { /* ignore */ }
      setPermLoading(false);
    })();
  }, [isOpen, isAdmin, mode, folder]);

  // 新規作成時はデフォルト権限をセット
  useEffect(() => {
    if (isOpen && mode === "create" && isAdmin) {
      setPermRows([
        { key: "d1", permission_type: "edit", target_type: "everyone", target_id: null },
      ]);
    }
  }, [isOpen, mode, isAdmin]);

  const handleSave = useCallback(async () => {
    const trimmedName = name.trim();
    if (!trimmedName) {
      setError(t("LBL_FOLDER_NAME_REQUIRED"));
      return;
    }
    onSave({
      foldername: trimmedName,
      folderdesc: desc.trim(),
      parent_folderid: parentId,
      ...(mode === "edit" && folder ? { savemode: "edit", folderid: folder.id } : {}),
    });

    // 権限保存（editモードかつ管理者のみ）
    if (isAdmin && mode === "edit" && folder) {
      await savePermissions(folder.id);
    }
  }, [name, desc, parentId, mode, folder, onSave, isAdmin, permRows, t]);

  const savePermissions = useCallback(async (folderId: number) => {
    setPermSaving(true);
    try {
      const csrf = getCsrfToken();
      const body = new URLSearchParams();
      if (csrf) body.append(csrf.name, csrf.value);
      body.append("module", "Documents");
      body.append("api", "FolderAPI");
      body.append("mode", "savePermissions");
      body.append("folderid", String(folderId));
      body.append("permissions", JSON.stringify(permRows.map((r) => ({
        permission_type: r.permission_type,
        target_type: r.target_type,
        target_id: r.target_id,
      }))));
      await fetch("index.php", {
        method: "POST", credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded", Accept: "application/json" },
        body: body.toString(),
      });
    } catch { /* ignore */ }
    setPermSaving(false);
  }, [permRows]);

  const addPermRow = useCallback(() => {
    setPermRows((prev) => [...prev, {
      key: `n${Date.now()}`,
      permission_type: "view",
      target_type: "everyone",
      target_id: null,
    }]);
  }, []);

  const removePermRow = useCallback((key: string) => {
    setPermRows((prev) => prev.filter((r) => r.key !== key));
  }, []);

  const updatePermRow = useCallback((key: string, field: string, value: any) => {
    setPermRows((prev) => prev.map((r) => {
      if (r.key !== key) return r;
      const updated = { ...r, [field]: value };
      if (field === "target_type") {
        updated.target_id = value === "everyone" ? null : "";
        updated.target_name = null;
      }
      return updated;
    }));
  }, []);

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === "Escape") onClose();
  }, [onClose]);

  if (!isOpen) return null;

  const getDescendantIds = (id: number): number[] => {
    const children = folders.filter((f) => f.parent_id === id);
    return children.reduce<number[]>((acc, c) => [...acc, c.id, ...getDescendantIds(c.id)], []);
  };
  const excludeIds = mode === "edit" && folder ? [folder.id, ...getDescendantIds(folder.id)] : [];
  const parentOptions = folders.filter((f) => !excludeIds.includes(f.id));

  const targetTypeLabels: Record<TargetType, string> = {
    everyone: t("LBL_TARGET_EVERYONE"),
    user: t("LBL_TARGET_USER"),
    role: t("LBL_TARGET_ROLE"),
    group: t("LBL_TARGET_GROUP"),
  };

  return (
    <div
      style={{ position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.3)", display: "flex", alignItems: "center", justifyContent: "center", zIndex: 1000 }}
      onClick={onClose}
    >
      <div
        style={{ backgroundColor: "#fff", borderRadius: 8, padding: 24, width: isAdmin ? 600 : 400, maxWidth: "90vw", maxHeight: "85vh", overflowY: "auto", boxShadow: "0 8px 30px rgba(0,0,0,0.15)" }}
        onClick={(e) => e.stopPropagation()}
      >
        <h3 style={{ margin: "0 0 16px", fontSize: 16, fontWeight: 600, color: "#2D3748" }}>
          {mode === "create" ? t("LBL_ADD_FOLDER_TITLE") : t("LBL_EDIT_FOLDER_TITLE")}
        </h3>

        {error && (
          <div style={{ padding: "6px 10px", backgroundColor: "#FED7D7", color: "#C53030", borderRadius: 4, fontSize: 13, marginBottom: 12 }}>{error}</div>
        )}

        {/* 基本情報 */}
        <div style={{ marginBottom: 12 }}>
          <label style={{ display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4 }}>
            {t("LBL_FOLDER_NAME_LABEL")} <span style={{ color: "#E53E3E" }}>*</span>
          </label>
          <input type="text" value={name} onChange={(e) => setName(e.target.value)} onKeyDown={handleKeyDown} autoFocus style={inputStyle} />
        </div>

        <div style={{ marginBottom: 12 }}>
          <label style={{ display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4 }}>{t("LBL_FOLDER_DESCRIPTION_LABEL")}</label>
          <textarea value={desc} onChange={(e) => setDesc(e.target.value)} rows={2} style={{ ...inputStyle, resize: "vertical" }} />
        </div>

        <div style={{ marginBottom: 20 }}>
          <label style={{ display: "block", fontSize: 13, fontWeight: 500, color: "#4A5568", marginBottom: 4 }}>{t("LBL_PARENT_FOLDER")}</label>
          <select value={parentId} onChange={(e) => setParentId(Number(e.target.value))} style={inputStyle}>
            <option value={0}>{t("LBL_ROOT_FOLDER")}</option>
            {parentOptions.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
          </select>
        </div>

        {/* 権限設定（管理者のみ） */}
        {isAdmin && (
          <div style={{ borderTop: "1px solid #E2E8F0", paddingTop: 16, marginBottom: 16 }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 10 }}>
              <label style={{ fontSize: 14, fontWeight: 600, color: "#2D3748" }}>{t("LBL_FOLDER_PERMISSIONS")}</label>
              <button
                onClick={addPermRow}
                style={{ padding: "3px 10px", fontSize: 12, border: "1px solid #E2E8F0", borderRadius: 4, backgroundColor: "#fff", color: "#4299E1", cursor: "pointer" }}
              >
                + {t("LBL_ADD_PERMISSION")}
              </button>
            </div>

            {permLoading ? (
              <div style={{ padding: 12, textAlign: "center", color: "#A0AEC0", fontSize: 13 }}>{t("LBL_LOADING")}</div>
            ) : permRows.length === 0 ? (
              <div style={{ padding: 12, textAlign: "center", color: "#A0AEC0", fontSize: 13 }}>
                {t("LBL_ADD_PERMISSION")}
              </div>
            ) : (
              <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                {permRows.map((row) => (
                  <div key={row.key} style={{ display: "flex", gap: 6, alignItems: "center", padding: "6px 8px", backgroundColor: "#F7FAFC", borderRadius: 4, border: "1px solid #EDF2F7" }}>
                    {/* 権限種別 */}
                    <select
                      value={row.permission_type}
                      onChange={(e) => updatePermRow(row.key, "permission_type", e.target.value)}
                      style={{ padding: "4px 6px", border: "1px solid #E2E8F0", borderRadius: 3, fontSize: 12, width: 90 }}
                    >
                      <option value="view">{t("LBL_PERMISSION_VIEW")}</option>
                      <option value="edit">{t("LBL_PERMISSION_EDIT")}</option>
                    </select>

                    {/* 対象種別 */}
                    <select
                      value={row.target_type}
                      onChange={(e) => updatePermRow(row.key, "target_type", e.target.value)}
                      style={{ padding: "4px 6px", border: "1px solid #E2E8F0", borderRadius: 3, fontSize: 12, width: 90 }}
                    >
                      {(["everyone", "user", "role", "group"] as TargetType[]).map((tt) => (
                        <option key={tt} value={tt}>{targetTypeLabels[tt]}</option>
                      ))}
                    </select>

                    {/* 対象ID選択 */}
                    {row.target_type !== "everyone" && targets && (
                      <select
                        value={row.target_id ?? ""}
                        onChange={(e) => updatePermRow(row.key, "target_id", e.target.value || null)}
                        style={{ flex: 1, padding: "4px 6px", border: "1px solid #E2E8F0", borderRadius: 3, fontSize: 12, minWidth: 0 }}
                      >
                        <option value="">{t("LBL_SELECT_TARGET")}</option>
                        {row.target_type === "user" && targets.users.map((u) => (
                          <option key={u.id} value={u.id}>{u.name}</option>
                        ))}
                        {row.target_type === "role" && targets.roles.map((r) => (
                          <option key={r.id} value={r.id}>{r.name}</option>
                        ))}
                        {row.target_type === "group" && targets.groups.map((g) => (
                          <option key={g.id} value={g.id}>{g.name}</option>
                        ))}
                      </select>
                    )}

                    {row.target_type === "everyone" && (
                      <div style={{ flex: 1 }} />
                    )}

                    {/* 削除ボタン */}
                    <button
                      onClick={() => removePermRow(row.key)}
                      style={{ padding: "2px 8px", border: "1px solid #FEB2B2", borderRadius: 3, backgroundColor: "#fff", color: "#E53E3E", cursor: "pointer", fontSize: 11, flexShrink: 0 }}
                    >
                      ×
                    </button>
                  </div>
                ))}
              </div>
            )}

            {permMessage && (
              <div style={{ marginTop: 8, fontSize: 12, color: "#38A169" }}>{permMessage}</div>
            )}
          </div>
        )}

        {/* フッターボタン */}
        <div style={{ display: "flex", justifyContent: "space-between", gap: 8 }}>
          <div>
            {mode === "edit" && folder && folder.name !== "Default" && onDelete && (
              <button
                onClick={() => { if (window.confirm(t("LBL_CONFIRM_DELETE_FOLDER", folder.name))) onDelete(folder.id); }}
                style={{ padding: "6px 14px", backgroundColor: "#fff", color: "#E53E3E", border: "1px solid #FEB2B2", borderRadius: 4, fontSize: 13, cursor: "pointer" }}
              >
                {t("LBL_DELETE")}
              </button>
            )}
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <button onClick={onClose} style={{ padding: "6px 14px", backgroundColor: "#fff", color: "#4A5568", border: "1px solid #E2E8F0", borderRadius: 4, fontSize: 13, cursor: "pointer" }}>
              {t("LBL_CANCEL")}
            </button>
            <button
              onClick={handleSave}
              disabled={permSaving}
              style={{ padding: "6px 14px", backgroundColor: "#4299E1", color: "#fff", border: "none", borderRadius: 4, fontSize: 13, cursor: permSaving ? "wait" : "pointer" }}
            >
              {mode === "create" ? t("LBL_ADD") : t("LBL_SAVE")}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
