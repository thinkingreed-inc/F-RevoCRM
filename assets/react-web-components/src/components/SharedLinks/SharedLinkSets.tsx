import React, { useEffect, useState } from "react";
import type {
  SharedLinkInfo,
  SharedLinkSaveSetResult,
  SharedLinkSet,
} from "@/types/sharedLinks";
import { validateWidgetTitle } from "./utils/sharedLinkForm";

/**
 * ウィジェット（リンクのまとまり）の切り替えと編集
 *
 * - SharedLinkSetTabs: ウィジェットをタブで並べて切り替える。「ウィジェットを作る」もここ
 * - SharedLinkSetHeader: 選んだウィジェットの見出しの変更・削除
 */

const buttonStyle: React.CSSProperties = {
  padding: "4px 10px",
  fontSize: 12,
  border: "1px solid #CBD5E0",
  borderRadius: 4,
  backgroundColor: "#fff",
  color: "#2D3748",
  cursor: "pointer",
  whiteSpace: "nowrap",
};

const primaryButtonStyle: React.CSSProperties = {
  ...buttonStyle,
  padding: "6px 14px",
  fontSize: 13,
  backgroundColor: "#3182CE",
  borderColor: "#3182CE",
  color: "#fff",
};

const inputStyle: React.CSSProperties = {
  width: 280,
  maxWidth: "100%",
  padding: "6px 8px",
  fontSize: 13,
  border: "1px solid #CBD5E0",
  borderRadius: 4,
  boxSizing: "border-box",
};

const errorStyle: React.CSSProperties = {
  fontSize: 12,
  color: "#C53030",
  marginTop: 4,
};

/**
 * 見出しの入力と保存（作る・変えるで共通）
 *
 * 画面で検証してから送り、サーバーのエラー（名前の重なりなど）も出す。
 */
const TitleForm: React.FC<{
  info: SharedLinkInfo;
  initialTitle: string;
  submitLabel: string;
  isSaving: boolean;
  /** 前後の空白を除いて元と同じなら保存できない */
  requireChange: boolean;
  onSubmit: (title: string) => Promise<SharedLinkSaveSetResult>;
  onDone: (result: Extract<SharedLinkSaveSetResult, { saved: true }>) => void;
  onCancel?: () => void;
  autoFocus?: boolean;
}> = ({
  info,
  initialTitle,
  submitLabel,
  isSaving,
  requireChange,
  onSubmit,
  onDone,
  onCancel,
  autoFocus,
}) => {
  const t = (key: string) => info.labels[key] || key;
  const [title, setTitle] = useState(initialTitle);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setTitle(initialTitle);
    setError(null);
  }, [initialTitle]);

  const unchanged = requireChange && title.trim() === initialTitle;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const clientError = validateWidgetTitle(title, info.limits);
    if (clientError) {
      setError(t(clientError));
      return;
    }
    try {
      const result = await onSubmit(title.trim());
      if (result.saved) {
        setError(null);
        onDone(result);
      } else {
        setError(Object.values(result.errors)[0] ?? t("LBL_ERROR_HAS_ERRORS"));
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <label
        htmlFor={`shared-link-set-title-${submitLabel}`}
        style={{
          display: "block",
          fontSize: 12,
          fontWeight: 600,
          color: "#4A5568",
          marginBottom: 4,
        }}
      >
        {t("LBL_WIDGET_TITLE")}
      </label>
      <div
        style={{
          display: "flex",
          gap: 8,
          alignItems: "center",
          flexWrap: "wrap",
        }}
      >
        <input
          id={`shared-link-set-title-${submitLabel}`}
          type="text"
          value={title}
          maxLength={info.limits.widget_title_max}
          aria-invalid={error !== null}
          autoFocus={autoFocus}
          onChange={(e) => {
            setTitle(e.target.value);
            setError(null);
          }}
          style={{ ...inputStyle, borderColor: error ? "#E53E3E" : "#CBD5E0" }}
        />
        <button
          type="submit"
          disabled={isSaving || unchanged}
          style={{
            ...primaryButtonStyle,
            opacity: isSaving || unchanged ? 0.5 : 1,
            cursor: isSaving || unchanged ? "default" : "pointer",
          }}
        >
          {submitLabel}
        </button>
        {onCancel && (
          <button type="button" onClick={onCancel} style={buttonStyle}>
            {t("LBL_CANCEL")}
          </button>
        )}
      </div>
      {error && (
        <div role="alert" style={errorStyle}>
          {error}
        </div>
      )}
      <div style={{ fontSize: 12, color: "#718096", marginTop: 4 }}>
        {t("LBL_WIDGET_TITLE_NOTE")}
      </div>
    </form>
  );
};

interface SetTabsProps {
  info: SharedLinkInfo;
  sets: SharedLinkSet[];
  /** ウィジェットごとのリンクの件数 */
  counts: Record<number, number>;
  selectedId: number | null;
  isSaving: boolean;
  onSelect: (id: number) => void;
  onCreate: (title: string) => Promise<SharedLinkSaveSetResult>;
  onCreated: (id: number) => void;
}

/** ウィジェットのタブと「ウィジェットを作る」 */
export const SharedLinkSetTabs: React.FC<SetTabsProps> = ({
  info,
  sets,
  counts,
  selectedId,
  isSaving,
  onSelect,
  onCreate,
  onCreated,
}) => {
  const t = (key: string) => info.labels[key] || key;
  const [creating, setCreating] = useState(false);

  return (
    <div style={{ marginBottom: 12 }}>
      <div
        style={{
          display: "flex",
          alignItems: "flex-end",
          gap: 4,
          flexWrap: "wrap",
          borderBottom: "1px solid #E2E8F0",
        }}
      >
        <div
          role="tablist"
          aria-label={t("LBL_WIDGETS")}
          style={{ display: "flex", gap: 4, flexWrap: "wrap" }}
        >
          {sets.map((set) => {
            const selected = set.id === selectedId;
            return (
              <button
                key={set.id}
                type="button"
                role="tab"
                aria-selected={selected}
                onClick={() => onSelect(set.id)}
                style={{
                  padding: "8px 14px",
                  fontSize: 13,
                  fontWeight: selected ? 600 : 400,
                  border: "1px solid",
                  borderColor: selected ? "#E2E8F0" : "transparent",
                  borderBottomColor: selected ? "#fff" : "transparent",
                  borderRadius: "6px 6px 0 0",
                  marginBottom: -1,
                  backgroundColor: selected ? "#fff" : "transparent",
                  color: selected ? "#2B6CB0" : "#4A5568",
                  cursor: "pointer",
                }}
              >
                {set.title}
                <span
                  style={{
                    marginLeft: 6,
                    padding: "0 6px",
                    borderRadius: 10,
                    fontSize: 11,
                    backgroundColor: selected ? "#EBF8FF" : "#EDF2F7",
                    color: selected ? "#2B6CB0" : "#718096",
                  }}
                >
                  {counts[set.id] ?? 0}
                </span>
              </button>
            );
          })}
        </div>
        {!creating && (
          <button
            type="button"
            onClick={() => setCreating(true)}
            style={{ ...buttonStyle, marginBottom: 6, marginLeft: 4 }}
          >
            ＋ {t("LBL_CREATE_WIDGET")}
          </button>
        )}
      </div>
      {creating && (
        <div
          style={{
            marginTop: 8,
            padding: "12px 14px",
            border: "1px solid #E2E8F0",
            borderRadius: 6,
            backgroundColor: "#F7FAFC",
          }}
        >
          <TitleForm
            info={info}
            initialTitle=""
            submitLabel={t("LBL_CREATE")}
            isSaving={isSaving}
            requireChange={false}
            onSubmit={onCreate}
            onDone={(result) => {
              setCreating(false);
              onCreated(result.id);
            }}
            onCancel={() => setCreating(false)}
            autoFocus
          />
        </div>
      )}
      {sets.length === 0 && !creating && (
        <p style={{ margin: "12px 0", fontSize: 13, color: "#718096" }}>
          {t("LBL_NO_WIDGETS")}
        </p>
      )}
    </div>
  );
};

interface SetHeaderProps {
  info: SharedLinkInfo;
  set: SharedLinkSet;
  isSaving: boolean;
  onRename: (title: string) => Promise<SharedLinkSaveSetResult>;
  onRenamed: () => void;
  onDelete: () => void;
}

/** 選んだウィジェットの見出しの変更・削除 */
export const SharedLinkSetHeader: React.FC<SetHeaderProps> = ({
  info,
  set,
  isSaving,
  onRename,
  onRenamed,
  onDelete,
}) => {
  const t = (key: string) => info.labels[key] || key;

  return (
    <div
      data-testid="shared-link-set-header"
      style={{
        display: "flex",
        justifyContent: "space-between",
        alignItems: "flex-start",
        gap: 12,
        flexWrap: "wrap",
        marginBottom: 12,
        padding: "12px 14px",
        border: "1px solid #E2E8F0",
        borderRadius: 6,
        backgroundColor: "#F7FAFC",
      }}
    >
      <TitleForm
        info={info}
        initialTitle={set.title}
        submitLabel={t("LBL_SAVE")}
        isSaving={isSaving}
        requireChange
        onSubmit={onRename}
        onDone={onRenamed}
      />
      <div style={{ display: "flex", gap: 4, paddingTop: 20 }}>
        <button
          type="button"
          onClick={onDelete}
          disabled={isSaving}
          style={{ ...buttonStyle, color: "#C53030" }}
        >
          {t("LBL_DELETE_WIDGET")}
        </button>
      </div>
    </div>
  );
};
