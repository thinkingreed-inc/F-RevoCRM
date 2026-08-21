import React, { useState } from "react";
import type { Folder } from "./types/documents";

interface DocumentsBulkActionBarProps {
  /** 選択中のドキュメントID */
  selectedIds: number[];
  /** 移動先に選べるフォルダ */
  folders: Folder[];
  /** 選択を解除する */
  onClear: () => void;
  /** 操作が完了したときに呼ばれる（一覧の再読み込みに使う） */
  onCompleted: (message: string) => void;
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

interface BulkResult {
  total: number;
  deleted?: number;
  moved?: number;
  denied?: number;
  skipped?: number;
  failed?: number;
  /** 電帳法対象のため削除できなかった件数 */
  blocked?: number;
}

/** 一括操作 API を呼ぶ */
async function callBulkApi(
  mode: "delete" | "move",
  recordIds: number[],
  extra: Record<string, string> = {},
): Promise<BulkResult> {
  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "Documents");
  body.append("api", "BulkAction");
  body.append("mode", mode);
  for (const [key, value] of Object.entries(extra)) {
    body.append(key, value);
  }
  for (const id of recordIds) {
    body.append("records[]", String(id));
  }

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: body.toString(),
  });
  const data = await response.json();
  if (data.success === false || data.error) {
    throw new Error(data.error?.message || "Bulk action failed");
  }
  return (data.result || data) as BulkResult;
}

/**
 * 一覧で選択したドキュメントをまとめて操作するバー
 *
 * 縦のスペースを増やさないよう、一覧のヘッダー行に差し込んで使う1行のUI。
 * 選択が1件以上あるときだけ表示する。
 */
export const DocumentsBulkActionBar: React.FC<DocumentsBulkActionBarProps> = ({
  selectedIds,
  folders,
  onClear,
  onCompleted,
  t,
}) => {
  const [isRunning, setIsRunning] = useState(false);
  const [moveOpen, setMoveOpen] = useState(false);
  const [targetFolderId, setTargetFolderId] = useState<string>("");
  const [error, setError] = useState<string | null>(null);

  if (selectedIds.length === 0) return null;

  /** 処理結果を利用者向けの文にする（対象外があれば内訳を添える） */
  const describe = (result: BulkResult, mode: "delete" | "move"): string => {
    const done =
      mode === "delete" ? (result.deleted ?? 0) : (result.moved ?? 0);
    let message =
      mode === "delete"
        ? t("LBL_BULK_DELETE_RESULT", done)
        : t("LBL_BULK_MOVE_RESULT", done);
    if (result.blocked)
      message += t("LBL_BULK_SKIPPED_COMPLIANCE", result.blocked);
    if (result.denied) message += t("LBL_BULK_SKIPPED_DENIED", result.denied);
    if (result.skipped) message += t("LBL_BULK_SKIPPED_SAME", result.skipped);
    if (result.failed) message += t("LBL_BULK_FAILED", result.failed);
    return message;
  };

  const run = async (
    mode: "delete" | "move",
    extra: Record<string, string> = {},
  ) => {
    setIsRunning(true);
    setError(null);
    try {
      const result = await callBulkApi(mode, selectedIds, extra);
      setMoveOpen(false);
      setTargetFolderId("");
      onCompleted(describe(result, mode));
    } catch (e) {
      setError(e instanceof Error ? e.message : t("LBL_BULK_ACTION_FAILED"));
    } finally {
      setIsRunning(false);
    }
  };

  const handleDelete = () => {
    if (!window.confirm(t("LBL_BULK_CONFIRM_DELETE", selectedIds.length)))
      return;
    run("delete");
  };

  const handleMove = () => {
    if (!targetFolderId) return;
    run("move", { folderid: targetFolderId });
  };

  const buttonStyle: React.CSSProperties = {
    padding: "3px 10px",
    fontSize: 12,
    lineHeight: 1.5,
    borderRadius: 4,
    border: "1px solid #CBD5E0",
    backgroundColor: "#fff",
    color: "#2D3748",
    cursor: isRunning ? "default" : "pointer",
    opacity: isRunning ? 0.6 : 1,
    whiteSpace: "nowrap",
  };

  return (
    <div
      role="region"
      aria-label={t("LBL_SELECTED_DOCUMENTS", selectedIds.length)}
      style={{
        display: "flex",
        alignItems: "center",
        gap: 8,
        minHeight: 26,
        fontWeight: 400,
      }}
    >
      <span
        style={{
          fontSize: 12,
          fontWeight: 600,
          color: "#2B6CB0",
          whiteSpace: "nowrap",
        }}
      >
        {t("LBL_SELECTED_DOCUMENTS", selectedIds.length)}
      </span>

      <button
        type="button"
        onClick={handleDelete}
        disabled={isRunning}
        style={{ ...buttonStyle, color: "#C53030", borderColor: "#FC8181" }}
      >
        {t("LBL_BULK_DELETE")}
      </button>

      {!moveOpen && (
        <button
          type="button"
          onClick={() => setMoveOpen(true)}
          disabled={isRunning}
          style={buttonStyle}
        >
          {t("LBL_BULK_MOVE")}
        </button>
      )}

      {/* 移動先の選択（同じ行に収める） */}
      {moveOpen && (
        <>
          <select
            id="bulk-move-folder"
            aria-label={t("LBL_BULK_MOVE_TARGET")}
            value={targetFolderId}
            onChange={(e) => setTargetFolderId(e.target.value)}
            style={{
              padding: "2px 6px",
              fontSize: 12,
              maxWidth: 180,
              border: "1px solid #CBD5E0",
              borderRadius: 4,
            }}
          >
            <option value="">{t("LBL_BULK_MOVE_TARGET")}</option>
            {folders
              .filter((f) => f.can_edit !== false)
              .map((f) => (
                <option key={f.id} value={f.id}>
                  {f.name}
                </option>
              ))}
          </select>
          <button
            type="button"
            onClick={handleMove}
            disabled={isRunning || !targetFolderId}
            style={{
              ...buttonStyle,
              backgroundColor: targetFolderId ? "#2B6CB0" : "#A0AEC0",
              borderColor: "transparent",
              color: "#fff",
              cursor: targetFolderId && !isRunning ? "pointer" : "default",
            }}
          >
            {t("LBL_BULK_MOVE_APPLY")}
          </button>
          <button
            type="button"
            onClick={() => {
              setMoveOpen(false);
              setTargetFolderId("");
            }}
            disabled={isRunning}
            aria-label={t("LBL_CANCEL")}
            title={t("LBL_CANCEL")}
            style={{
              ...buttonStyle,
              border: "none",
              padding: "2px 4px",
              color: "#A0AEC0",
            }}
          >
            ×
          </button>
        </>
      )}

      <button
        type="button"
        onClick={onClear}
        disabled={isRunning}
        style={{ ...buttonStyle, border: "none", color: "#4A5568" }}
      >
        {t("LBL_BULK_CLEAR_SELECTION")}
      </button>

      {error && (
        <span
          style={{ fontSize: 12, color: "#C53030", whiteSpace: "nowrap" }}
          role="alert"
        >
          {error}
        </span>
      )}
    </div>
  );
};
