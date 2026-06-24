import React, { useState } from "react";
import type { FileVersionEntry, AuditLogEntry } from "./types/documents";
import { useOptionalTranslation } from "../../hooks/useTranslation";

interface ComplianceHistoryModalProps {
  isOpen: boolean;
  title: string;
  fileVersions: FileVersionEntry[];
  auditLog: AuditLogEntry[];
  onClose: () => void;
}

type TabType = "versions" | "audit";

function formatFileSize(bytes: number): string {
  if (!bytes || bytes === 0) return "—";
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

function getActionTypeLabel(t: (key: string) => string, key: string): string {
  const labels: Record<string, string> = {
    create: t("LBL_ACTION_CREATE"),
    update: t("LBL_ACTION_UPDATE"),
    delete: t("LBL_ACTION_DELETE"),
    restore: t("LBL_ACTION_RESTORE"),
    download: t("Download"),
    verify: t("LBL_ACTION_VERIFY"),
  };
  return labels[key] || key;
}

const ACTION_COLORS: Record<string, string> = {
  create: "#38A169",
  update: "#D69E2E",
  delete: "#E53E3E",
  restore: "#805AD5",
  download: "#4299E1",
  verify: "#319795",
};

export const ComplianceHistoryModal: React.FC<ComplianceHistoryModalProps> = ({
  isOpen,
  title,
  fileVersions,
  auditLog,
  onClose,
}) => {
  const { t } = useOptionalTranslation();
  const [activeTab, setActiveTab] = useState<TabType>("versions");

  if (!isOpen) return null;

  const tabStyle = (tab: TabType): React.CSSProperties => ({
    padding: "8px 20px",
    fontSize: 13,
    fontWeight: activeTab === tab ? 600 : 400,
    color: activeTab === tab ? "#2D3748" : "#718096",
    borderBottom: activeTab === tab ? "2px solid #4299E1" : "2px solid transparent",
    cursor: "pointer",
    backgroundColor: "transparent",
    border: "none",
    borderBottomWidth: 2,
    borderBottomStyle: "solid",
    borderBottomColor: activeTab === tab ? "#4299E1" : "transparent",
  });

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        backgroundColor: "rgba(0,0,0,0.5)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: 100002,
      }}
      onClick={onClose}
    >
      <div
        style={{
          backgroundColor: "#fff",
          borderRadius: 8,
          width: 720,
          maxWidth: "95vw",
          maxHeight: "85vh",
          display: "flex",
          flexDirection: "column",
          boxShadow: "0 10px 40px rgba(0,0,0,0.25)",
        }}
        onClick={(e) => e.stopPropagation()}
      >
        {/* ヘッダー */}
        <div
          style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            padding: "14px 20px",
            borderBottom: "1px solid #E2E8F0",
          }}
        >
          <div>
            <h3 style={{ margin: 0, fontSize: 15, fontWeight: 600, color: "#2D3748" }}>
              {title}
            </h3>
            <div style={{ fontSize: 12, color: "#A0AEC0", marginTop: 2 }}>
              {t("LBL_HISTORY_SUMMARY", t("LBL_FILE_VERSIONS"), fileVersions.length, t("LBL_AUDIT_LOG"), auditLog.length)}
            </div>
          </div>
          <button
            onClick={onClose}
            style={{
              width: 28,
              height: 28,
              border: "none",
              backgroundColor: "transparent",
              fontSize: 18,
              color: "#A0AEC0",
              cursor: "pointer",
            }}
          >
            ×
          </button>
        </div>

        {/* タブ */}
        <div style={{ display: "flex", borderBottom: "1px solid #E2E8F0", padding: "0 20px" }}>
          <button onClick={() => setActiveTab("versions")} style={tabStyle("versions")}>
            {t("LBL_FILE_VERSIONS")}
            <span
              style={{
                marginLeft: 6,
                fontSize: 11,
                backgroundColor: "#EDF2F7",
                padding: "1px 6px",
                borderRadius: 8,
                color: "#718096",
              }}
            >
              {fileVersions.length}
            </span>
          </button>
          <button onClick={() => setActiveTab("audit")} style={tabStyle("audit")}>
            {t("LBL_AUDIT_LOG")}
            <span
              style={{
                marginLeft: 6,
                fontSize: 11,
                backgroundColor: "#EDF2F7",
                padding: "1px 6px",
                borderRadius: 8,
                color: "#718096",
              }}
            >
              {auditLog.length}
            </span>
          </button>
        </div>

        {/* コンテンツ */}
        <div style={{ flex: 1, overflowY: "auto", padding: 20 }}>
          {/* ファイルバージョンタブ */}
          {activeTab === "versions" && (
            <div>
              {fileVersions.length === 0 && (
                <div style={{ padding: "40px 20px", textAlign: "center", color: "#A0AEC0" }}>
                  <div style={{ fontSize: 32, marginBottom: 8 }}>—</div>
                  <div style={{ fontSize: 14 }}>{t("LBL_NO_FILE_VERSIONS")}</div>
                </div>
              )}
              {fileVersions.map((v) => (
                <div
                  key={v.version_number}
                  style={{
                    display: "flex",
                    alignItems: "stretch",
                    marginBottom: 12,
                    borderRadius: 6,
                    border: v.is_current ? "1px solid #C6F6D5" : "1px solid #E2E8F0",
                    backgroundColor: v.is_current ? "#F0FFF4" : "#fff",
                    overflow: "hidden",
                  }}
                >
                  {/* 左: バージョン番号 */}
                  <div
                    style={{
                      width: 60,
                      display: "flex",
                      flexDirection: "column",
                      alignItems: "center",
                      justifyContent: "center",
                      backgroundColor: v.is_current ? "#C6F6D5" : "#EDF2F7",
                      padding: "12px 0",
                      flexShrink: 0,
                    }}
                  >
                    <div
                      style={{
                        fontSize: 18,
                        fontWeight: 700,
                        color: v.is_current ? "#22543D" : "#4A5568",
                      }}
                    >
                      v{v.version_number}
                    </div>
                    {v.is_current && (
                      <div style={{ fontSize: 9, fontWeight: 600, color: "#276749", marginTop: 2 }}>
                        {t("LBL_VERSION_CURRENT")}
                      </div>
                    )}
                  </div>

                  {/* 中: 情報 */}
                  <div style={{ flex: 1, padding: "10px 14px" }}>
                    <div
                      style={{
                        display: "flex",
                        justifyContent: "space-between",
                        alignItems: "center",
                        marginBottom: 4,
                      }}
                    >
                      <div style={{ fontSize: 13, fontWeight: 500, color: "#2D3748" }}>
                        {v.change_reason || (v.version_number === 1 ? t("LBL_ACTION_CREATE") : "")}
                      </div>
                      {v.download_url && (
                        <a
                          href={v.download_url}
                          style={{
                            fontSize: 12,
                            color: "#4299E1",
                            textDecoration: "none",
                            padding: "3px 12px",
                            border: "1px solid #BEE3F8",
                            borderRadius: 4,
                            backgroundColor: "#EBF8FF",
                            fontWeight: 500,
                          }}
                        >
                          {t("LBL_DOWNLOAD_VERSION")}
                        </a>
                      )}
                    </div>
                    <div style={{ display: "flex", gap: 16, fontSize: 12, color: "#718096" }}>
                      <span>{v.created_at?.substring(0, 16)}</span>
                      <span>{v.creator_name}</span>
                      <span>{formatFileSize(v.file_size)}</span>
                    </div>
                    <div
                      style={{
                        marginTop: 4,
                        fontSize: 11,
                        fontFamily: "monospace",
                        color: "#A0AEC0",
                      }}
                    >
                      SHA-256: {v.file_hash}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* 変更履歴タブ */}
          {activeTab === "audit" && auditLog.length === 0 && (
            <div style={{ padding: "40px 20px", textAlign: "center", color: "#A0AEC0" }}>
              <div style={{ fontSize: 32, marginBottom: 8 }}>—</div>
              <div style={{ fontSize: 14 }}>{t("LBL_NO_AUDIT_LOG")}</div>
            </div>
          )}
          {activeTab === "audit" && auditLog.length > 0 && (
            <table
              style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}
            >
              <thead>
                <tr>
                  <th
                    style={{
                      textAlign: "left",
                      padding: "8px 10px",
                      fontSize: 12,
                      fontWeight: 600,
                      color: "#718096",
                      borderBottom: "2px solid #E2E8F0",
                      width: 140,
                    }}
                  >
                    {t("LBL_COL_DATETIME")}
                  </th>
                  <th
                    style={{
                      textAlign: "left",
                      padding: "8px 10px",
                      fontSize: 12,
                      fontWeight: 600,
                      color: "#718096",
                      borderBottom: "2px solid #E2E8F0",
                      width: 80,
                    }}
                  >
                    {t("LBL_COL_ACTION")}
                  </th>
                  <th
                    style={{
                      textAlign: "left",
                      padding: "8px 10px",
                      fontSize: 12,
                      fontWeight: 600,
                      color: "#718096",
                      borderBottom: "2px solid #E2E8F0",
                      width: 110,
                    }}
                  >
                    {t("LBL_COL_PERFORMER")}
                  </th>
                  <th
                    style={{
                      textAlign: "left",
                      padding: "8px 10px",
                      fontSize: 12,
                      fontWeight: 600,
                      color: "#718096",
                      borderBottom: "2px solid #E2E8F0",
                    }}
                  >
                    {t("LBL_COL_DETAIL")}
                  </th>
                </tr>
              </thead>
              <tbody>
                {auditLog.map((entry) => {
                  const actionColor = ACTION_COLORS[entry.action_type] || "#718096";
                  const changes =
                    entry.action_detail &&
                    typeof entry.action_detail === "object" &&
                    entry.action_detail.changes;
                  const reason =
                    entry.action_detail &&
                    typeof entry.action_detail === "object" &&
                    entry.action_detail.reason;

                  return (
                    <tr
                      key={entry.audit_id}
                      style={{ borderBottom: "1px solid #F7FAFC" }}
                    >
                      <td
                        style={{
                          padding: "8px 10px",
                          color: "#718096",
                          fontSize: 12,
                          whiteSpace: "nowrap",
                        }}
                      >
                        {entry.performed_at?.substring(0, 16)}
                      </td>
                      <td style={{ padding: "8px 10px" }}>
                        <span
                          style={{
                            display: "inline-block",
                            padding: "2px 8px",
                            backgroundColor: actionColor + "18",
                            color: actionColor,
                            borderRadius: 4,
                            fontSize: 11,
                            fontWeight: 600,
                          }}
                        >
                          {getActionTypeLabel(t, entry.action_type)}
                        </span>
                      </td>
                      <td
                        style={{
                          padding: "8px 10px",
                          color: "#4A5568",
                          fontSize: 12,
                        }}
                      >
                        {entry.performer_name}
                      </td>
                      <td style={{ padding: "8px 10px", fontSize: 12 }}>
                        {changes && (
                          <div style={{ color: "#2D3748" }}>
                            {(
                              changes as Array<{
                                field: string;
                                old_value: string;
                                new_value: string;
                              }>
                            ).map((c, i) => (
                              <div key={i} style={{ marginBottom: 2 }}>
                                <span style={{ color: "#718096" }}>{c.field}:</span>{" "}
                                <span style={{ textDecoration: "line-through", color: "#E53E3E" }}>
                                  {c.old_value}
                                </span>{" "}
                                →{" "}
                                <span style={{ color: "#38A169", fontWeight: 500 }}>
                                  {c.new_value}
                                </span>
                              </div>
                            ))}
                          </div>
                        )}
                        {reason && (
                          <div style={{ color: "#718096", fontStyle: "italic" }}>
                            {reason}
                          </div>
                        )}
                        {entry.ip_address && (
                          <div
                            style={{
                              color: "#CBD5E0",
                              fontSize: 11,
                              marginTop: 2,
                            }}
                          >
                            IP: {entry.ip_address}
                          </div>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
};
