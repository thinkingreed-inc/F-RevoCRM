import React, { useCallback, useMemo, useState } from "react";
import type {
  SharedLink,
  SharedLinkInput,
  SharedLinkTargetView,
} from "@/types/sharedLinks";
import { useSharedLinks } from "./hooks/useSharedLinks";
import { SharedLinkEditModal } from "./SharedLinkEditModal";
import { SharedLinkSetHeader, SharedLinkSetTabs } from "./SharedLinkSets";

/** 一覧の公開範囲に名前を並べる件数（超えた分は「ほか n 件」） */
const VISIBLE_TARGETS = 3;

const TARGET_TYPE_LABELS: Record<SharedLinkTargetView["type"], string> = {
  Users: "LBL_TARGET_USERS",
  Groups: "LBL_TARGET_GROUPS",
  Roles: "LBL_TARGET_ROLES",
  RoleAndSubordinates: "LBL_TARGET_ROLE_AND_SUBORDINATES",
};

const buttonStyle: React.CSSProperties = {
  padding: "4px 10px",
  fontSize: 12,
  border: "1px solid #CBD5E0",
  borderRadius: 4,
  backgroundColor: "#fff",
  color: "#2D3748",
  cursor: "pointer",
};

/**
 * 共有リンクの管理画面
 *
 * ダッシュボードの共有リンクのウィジェット（リンクのまとまり。複数作れる）と、
 * それぞれに表示するリンクを管理する。
 * システム管理者のみアクセスできる（サーバー側で判定）。
 */
export const SharedLinksPage: React.FC = () => {
  const {
    info,
    sets,
    links: allLinks,
    isLoading,
    isSaving,
    error,
    setError,
    save,
    remove,
    reorder,
    saveSet,
    removeSet,
  } = useSharedLinks();

  /** 選んだウィジェット。未選択・消えたときは先頭を選んでいるものとして扱う（描画のたびに決める） */
  const [chosenSetId, setSelectedSetId] = useState<number | null>(null);
  const selectedSetId = sets.some((set) => set.id === chosenSetId)
    ? chosenSetId
    : (sets[0]?.id ?? null);

  const selectedIndex = sets.findIndex((set) => set.id === selectedSetId);
  const selectedSet = selectedIndex >= 0 ? sets[selectedIndex] : null;
  const links = useMemo(
    () => allLinks.filter((link) => link.set_id === selectedSetId),
    [allLinks, selectedSetId],
  );
  const counts = useMemo(() => {
    const result: Record<number, number> = {};
    for (const link of allLinks) {
      result[link.set_id] = (result[link.set_id] ?? 0) + 1;
    }
    return result;
  }, [allLinks]);

  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<SharedLink | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const t = useCallback(
    (key: string) => (info ? info.labels[key] || key : key),
    [info],
  );

  const openModal = (link: SharedLink | null) => {
    setEditTarget(link);
    setModalOpen(true);
    setMessage(null);
  };

  const closeModal = () => {
    setModalOpen(false);
    setEditTarget(null);
  };

  const handleSave = useCallback(
    async (input: SharedLinkInput, setId: number) => {
      const result = await save(input, setId, editTarget?.id);
      if (result.saved) {
        setModalOpen(false);
        setEditTarget(null);
        setMessage(t("LBL_SAVED"));
      }
      return result;
    },
    [editTarget, save, t],
  );

  const handleDelete = useCallback(
    async (link: SharedLink) => {
      if (!window.confirm(`${t("LBL_CONFIRM_DELETE")}\n${link.title}`)) return;
      try {
        await remove(link.id);
        setMessage(t("LBL_DELETED"));
      } catch (e) {
        setError(e instanceof Error ? e.message : String(e));
      }
    },
    [remove, setError, t],
  );

  const handleMove = useCallback(
    async (index: number, offset: -1 | 1) => {
      const ids = links.map((link) => link.id);
      const swapWith = index + offset;
      if (swapWith < 0 || swapWith >= ids.length) return;
      [ids[index], ids[swapWith]] = [ids[swapWith], ids[index]];
      try {
        await reorder(ids);
        setMessage(null);
      } catch (e) {
        setError(e instanceof Error ? e.message : String(e));
      }
    },
    [links, reorder, setError],
  );

  const handleDeleteSet = useCallback(async () => {
    if (!selectedSet) return;
    const confirmMessage = t("LBL_CONFIRM_DELETE_WIDGET")
      .replace("%s", selectedSet.title)
      .replace("%s", String(links.length));
    if (!window.confirm(confirmMessage)) return;
    try {
      await removeSet(selectedSet.id);
      setMessage(t("LBL_DELETED"));
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }, [links.length, removeSet, selectedSet, setError, t]);

  const renderVisibility = (link: SharedLink) => {
    if (link.visibility === "all") {
      return t("LBL_VISIBILITY_ALL");
    }
    const names = link.targets.map(
      (target) =>
        `${t(TARGET_TYPE_LABELS[target.type])}: ${target.name ?? t("LBL_DELETED_TARGET")}`,
    );
    const shown = names.slice(0, VISIBLE_TARGETS).join("、");
    const rest = names.length - VISIBLE_TARGETS;
    return rest > 0
      ? `${shown} ${t("LBL_MORE_TARGETS").replace("%s", String(rest))}`
      : shown;
  };

  if (!info) {
    return (
      <div style={{ padding: 24, color: "#718096", fontSize: 13 }}>
        {isLoading ? t("LBL_LOADING") : error}
      </div>
    );
  }

  const thStyle: React.CSSProperties = {
    textAlign: "left",
    padding: "8px 10px",
    fontSize: 12,
    fontWeight: 600,
    color: "#718096",
    borderBottom: "2px solid #E2E8F0",
    whiteSpace: "nowrap",
  };
  const tdStyle: React.CSSProperties = {
    padding: "8px 10px",
    fontSize: 13,
    color: "#2D3748",
    borderBottom: "1px solid #F1F5F9",
    verticalAlign: "top",
  };

  return (
    <div style={{ padding: "16px 20px" }}>
      <div
        style={{
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          marginBottom: 12,
        }}
      >
        <h4
          style={{ margin: 0, fontSize: 16, fontWeight: 600, color: "#2D3748" }}
        >
          {t("LBL_SHARED_LINKS")}
        </h4>
        <button
          type="button"
          onClick={() => openModal(null)}
          disabled={selectedSet === null}
          style={{
            opacity: selectedSet === null ? 0.5 : 1,
            ...buttonStyle,
            padding: "6px 14px",
            fontSize: 13,
            backgroundColor: "#3182CE",
            borderColor: "#3182CE",
            color: "#fff",
          }}
        >
          ＋ {t("LBL_ADD_LINK")}
        </button>
      </div>
      <p style={{ margin: "0 0 12px", fontSize: 12, color: "#718096" }}>
        {t("LBL_SHARED_LINKS_DESCRIPTION")} {t("LBL_WIDGETS_NOTE")}
      </p>

      {error && (
        <div
          role="alert"
          style={{
            marginBottom: 12,
            padding: "8px 12px",
            backgroundColor: "#FED7D7",
            color: "#C53030",
            borderRadius: 4,
            fontSize: 13,
          }}
        >
          {error}
        </div>
      )}
      {message && (
        <div
          role="status"
          style={{
            marginBottom: 12,
            padding: "8px 12px",
            backgroundColor: "#C6F6D5",
            color: "#276749",
            borderRadius: 4,
            fontSize: 13,
          }}
        >
          {message}
        </div>
      )}

      <SharedLinkSetTabs
        info={info}
        sets={sets}
        counts={counts}
        selectedId={selectedSetId}
        isSaving={isSaving}
        onSelect={(id) => {
          setSelectedSetId(id);
          setMessage(null);
        }}
        onCreate={(title) => saveSet(title)}
        onCreated={(id) => {
          setSelectedSetId(id);
          setMessage(t("LBL_SAVED"));
        }}
      />

      {selectedSet && (
        <SharedLinkSetHeader
          info={info}
          set={selectedSet}
          isSaving={isSaving}
          onRename={(title) => saveSet(title, selectedSet.id)}
          onRenamed={() => setMessage(t("LBL_SAVED"))}
          onDelete={handleDeleteSet}
        />
      )}

      {selectedSet && (
        <table
          style={{
            width: "100%",
            borderCollapse: "collapse",
            backgroundColor: "#fff",
          }}
        >
          <thead>
            <tr>
              <th style={thStyle}>{t("LBL_SEQUENCE")}</th>
              <th style={thStyle}>{t("LBL_TITLE")}</th>
              <th style={thStyle}>{t("LBL_URL")}</th>
              <th style={thStyle}>{t("LBL_METHOD")}</th>
              <th style={thStyle}>{t("LBL_OPEN_MODE")}</th>
              <th style={thStyle}>{t("LBL_VISIBILITY")}</th>
              <th style={thStyle}>{t("LBL_STATUS")}</th>
              <th style={thStyle}>{t("LBL_ACTIONS")}</th>
            </tr>
          </thead>
          <tbody>
            {links.length === 0 && (
              <tr>
                <td
                  colSpan={8}
                  style={{ ...tdStyle, textAlign: "center", color: "#718096" }}
                >
                  {isLoading ? t("LBL_LOADING") : t("LBL_NO_LINKS")}
                </td>
              </tr>
            )}
            {links.map((link, index) => (
              <tr key={link.id} data-testid="shared-link-row">
                <td style={{ ...tdStyle, whiteSpace: "nowrap" }}>
                  <button
                    type="button"
                    onClick={() => handleMove(index, -1)}
                    disabled={isSaving || index === 0}
                    aria-label={`${t("LBL_MOVE_UP")} ${link.title}`}
                    title={t("LBL_MOVE_UP")}
                    style={{
                      ...buttonStyle,
                      padding: "2px 6px",
                      marginRight: 4,
                    }}
                  >
                    ↑
                  </button>
                  <button
                    type="button"
                    onClick={() => handleMove(index, 1)}
                    disabled={isSaving || index === links.length - 1}
                    aria-label={`${t("LBL_MOVE_DOWN")} ${link.title}`}
                    title={t("LBL_MOVE_DOWN")}
                    style={{ ...buttonStyle, padding: "2px 6px" }}
                  >
                    ↓
                  </button>
                </td>
                <td style={tdStyle}>
                  {link.has_warning && (
                    <span
                      role="img"
                      aria-label={t("LBL_HAS_WARNING")}
                      title={t("LBL_HAS_WARNING")}
                      style={{ color: "#DD6B20", marginRight: 4 }}
                    >
                      ⚠
                    </span>
                  )}
                  {link.post_blocked && (
                    <span
                      role="img"
                      aria-label={t("LBL_POST_BLOCKED")}
                      title={t("LBL_POST_BLOCKED")}
                      style={{ color: "#C53030", marginRight: 4 }}
                    >
                      ⛔
                    </span>
                  )}
                  {link.title}
                </td>
                <td
                  style={{
                    ...tdStyle,
                    maxWidth: 280,
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                    whiteSpace: "nowrap",
                  }}
                  title={link.url}
                >
                  {link.url}
                </td>
                <td style={tdStyle}>{link.method}</td>
                <td style={{ ...tdStyle, whiteSpace: "nowrap" }}>
                  {link.open_mode === "window"
                    ? `${t("LBL_OPEN_MODE_WINDOW")}（${link.window_width}×${link.window_height}）`
                    : t("LBL_OPEN_MODE_TAB")}
                </td>
                <td style={tdStyle}>{renderVisibility(link)}</td>
                <td style={tdStyle}>
                  <span style={{ color: link.active ? "#2F855A" : "#A0AEC0" }}>
                    {link.active ? t("LBL_ACTIVE") : t("LBL_INACTIVE")}
                  </span>
                </td>
                <td style={{ ...tdStyle, whiteSpace: "nowrap" }}>
                  <button
                    type="button"
                    onClick={() => openModal(link)}
                    style={{ ...buttonStyle, marginRight: 4 }}
                  >
                    {t("LBL_EDIT")}
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDelete(link)}
                    disabled={isSaving}
                    style={{ ...buttonStyle, color: "#C53030" }}
                  >
                    {t("LBL_DELETE")}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      <SharedLinkEditModal
        isOpen={modalOpen}
        info={info}
        link={editTarget}
        sets={sets}
        defaultSetId={editTarget?.set_id ?? selectedSetId}
        isSaving={isSaving}
        onSave={handleSave}
        onClose={closeModal}
      />
    </div>
  );
};
