import React, { useEffect, useMemo, useState } from "react";
import type {
  SharedLink,
  SharedLinkInfo,
  SharedLinkInput,
  SharedLinkOpenMode,
  SharedLinkParam,
  SharedLinkSaveResult,
  SharedLinkTarget,
  SharedLinkValueType,
} from "@/types/sharedLinks";
import { FilterableSelect } from "./FilterableSelect";
import type { FilterableOption } from "./FilterableSelect";
import {
  createEmptyLink,
  hasTarget,
  targetKey,
  toInput,
  toPayload,
  toTargetType,
  validateSharedLink,
} from "./utils/sharedLinkForm";
import type { TargetKind } from "./utils/sharedLinkForm";

interface SharedLinkEditModalProps {
  isOpen: boolean;
  info: SharedLinkInfo;
  /** 編集対象。null なら新規追加 */
  link: SharedLink | null;
  isSaving: boolean;
  /** 保存する。入力に誤りがあれば saved=false と項目ごとのエラー（文言）を返す */
  onSave: (input: SharedLinkInput) => Promise<SharedLinkSaveResult>;
  onClose: () => void;
}

const labelStyle: React.CSSProperties = {
  display: "block",
  fontSize: 12,
  fontWeight: 600,
  color: "#4A5568",
  marginBottom: 4,
};

const inputStyle: React.CSSProperties = {
  width: "100%",
  padding: "6px 8px",
  fontSize: 13,
  border: "1px solid #CBD5E0",
  borderRadius: 4,
  boxSizing: "border-box",
};

const noteStyle: React.CSSProperties = {
  fontSize: 12,
  color: "#718096",
  marginTop: 4,
};

const sectionStyle: React.CSSProperties = { marginBottom: 16 };

const smallButtonStyle: React.CSSProperties = {
  padding: "4px 10px",
  fontSize: 12,
  border: "1px solid #CBD5E0",
  borderRadius: 4,
  backgroundColor: "#fff",
  color: "#2D3748",
  cursor: "pointer",
  whiteSpace: "nowrap",
};

const radioLabelStyle: React.CSSProperties = {
  display: "inline-flex",
  alignItems: "center",
  gap: 4,
  marginRight: 16,
  fontSize: 13,
  cursor: "pointer",
};

/** 公開範囲の対象の種類ごとの文言のキー */
const TARGET_TYPE_LABELS: Record<SharedLinkTarget["type"], string> = {
  Users: "LBL_TARGET_USERS",
  Groups: "LBL_TARGET_GROUPS",
  Roles: "LBL_TARGET_ROLES",
  RoleAndSubordinates: "LBL_TARGET_ROLE_AND_SUBORDINATES",
};

/** 共有リンクの追加・編集モーダル */
export const SharedLinkEditModal: React.FC<SharedLinkEditModalProps> = ({
  isOpen,
  info,
  link,
  isSaving,
  onSave,
  onClose,
}) => {
  const t = (key: string) => info.labels[key] || key;
  const { limits } = info;

  const [form, setForm] = useState<SharedLinkInput>(createEmptyLink);
  /** 項目ごとのエラー（表示する文言） */
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [targetKind, setTargetKind] = useState<TargetKind>("Roles");
  const [targetId, setTargetId] = useState("");
  const [includeSubordinates, setIncludeSubordinates] = useState(false);

  useEffect(() => {
    if (!isOpen) return;
    setForm(link ? toInput(link) : createEmptyLink());
    setErrors({});
    setTargetKind("Roles");
    setTargetId("");
    setIncludeSubordinates(false);
  }, [isOpen, link]);

  const userFieldOptions = useMemo<FilterableOption[]>(
    () => info.user_fields.map((f) => ({ value: f.name, label: f.label })),
    [info.user_fields],
  );

  const targetOptions = useMemo<FilterableOption[]>(() => {
    if (targetKind === "Roles") {
      return info.roles.map((r) => ({
        value: r.id,
        label: r.name,
        depth: r.depth,
      }));
    }
    const list = targetKind === "Groups" ? info.groups : info.users;
    return list.map((o) => ({ value: o.id, label: o.name }));
  }, [info, targetKind]);

  if (!isOpen) return null;

  const update = <K extends keyof SharedLinkInput>(
    key: K,
    value: SharedLinkInput[K],
  ) => setForm((current) => ({ ...current, [key]: value }));

  const updateParam = (index: number, patch: Partial<SharedLinkParam>) =>
    setForm((current) => ({
      ...current,
      params: current.params.map((param, i) =>
        i === index ? { ...param, ...patch } : param,
      ),
    }));

  const addParam = () =>
    setForm((current) => ({
      ...current,
      params: [...current.params, { name: "", value_type: "fixed", value: "" }],
    }));

  const removeParam = (index: number) =>
    setForm((current) => ({
      ...current,
      params: current.params.filter((_, i) => i !== index),
    }));

  const changeOpenMode = (mode: SharedLinkOpenMode) =>
    setForm((current) => ({
      ...current,
      open_mode: mode,
      // 別ウィンドウに切り替えたときに、未入力なら既定のサイズを入れる
      window_width:
        mode === "window"
          ? (current.window_width ?? limits.default_window_width)
          : current.window_width,
      window_height:
        mode === "window"
          ? (current.window_height ?? limits.default_window_height)
          : current.window_height,
    }));

  const newTarget: SharedLinkTarget | null =
    targetId === ""
      ? null
      : { type: toTargetType(targetKind, includeSubordinates), id: targetId };
  const isDuplicateTarget =
    newTarget !== null && hasTarget(form.targets, newTarget);

  const addTarget = () => {
    if (newTarget === null || isDuplicateTarget) return;
    setForm((current) => ({
      ...current,
      targets: [...current.targets, newTarget],
    }));
    setTargetId("");
  };

  const removeTarget = (target: SharedLinkTarget) =>
    setForm((current) => ({
      ...current,
      targets: current.targets.filter(
        (t) => targetKey(t) !== targetKey(target),
      ),
    }));

  /** 対象の表示名。見つからなければ削除済み */
  const targetName = (target: SharedLinkTarget): string | null => {
    let found: { name: string } | undefined;
    if (target.type === "Users") {
      found = info.users.find((u) => u.id === target.id);
    } else if (target.type === "Groups") {
      found = info.groups.find((g) => g.id === target.id);
    } else {
      found = info.roles.find((r) => r.id === target.id);
    }
    if (found) return found.name;
    // 無効になったユーザーなど、選択肢に無いが保存済みの対象
    const saved = link?.targets.find((t) => targetKey(t) === targetKey(target));
    return saved?.name ?? null;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const clientErrors = validateSharedLink(
      form,
      limits,
      info.user_fields.map((f) => f.name),
    );
    if (Object.keys(clientErrors).length > 0) {
      const messages: Record<string, string> = {};
      for (const [field, key] of Object.entries(clientErrors)) {
        messages[field] = t(key);
      }
      setErrors(messages);
      return;
    }
    try {
      const result = await onSave(toPayload(form));
      if (!result.saved) {
        setErrors(result.errors);
      }
    } catch (err) {
      setErrors({ _: err instanceof Error ? err.message : String(err) });
    }
  };

  const fieldError = (key: string) =>
    errors[key] ? (
      <div
        role="alert"
        style={{ fontSize: 12, color: "#C53030", marginTop: 4 }}
      >
        {errors[key]}
      </div>
    ) : null;

  /** 公開範囲の対象ごとのエラー（targets.0 など）をまとめて出す */
  const targetErrors = form.targets
    .map((target, index) => {
      const message = errors[`targets.${index}`];
      if (!message) return null;
      return `${t(TARGET_TYPE_LABELS[target.type])}: ${targetName(target) ?? t("LBL_DELETED_TARGET")} — ${message}`;
    })
    .filter((m): m is string => m !== null);

  const hasErrors = Object.keys(errors).length > 0;

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        backgroundColor: "rgba(0,0,0,0.4)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: 100001,
      }}
      onClick={onClose}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-label={link ? t("LBL_EDIT_LINK") : t("LBL_ADD_LINK")}
        style={{
          backgroundColor: "#fff",
          borderRadius: 8,
          width: 720,
          maxWidth: "95vw",
          maxHeight: "90vh",
          display: "flex",
          flexDirection: "column",
          boxShadow: "0 10px 40px rgba(0,0,0,0.25)",
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <form
          onSubmit={handleSubmit}
          noValidate
          style={{ display: "flex", flexDirection: "column", minHeight: 0 }}
        >
          <div
            style={{
              display: "flex",
              alignItems: "center",
              justifyContent: "space-between",
              padding: "14px 20px",
              borderBottom: "1px solid #E2E8F0",
            }}
          >
            <h3
              style={{
                margin: 0,
                fontSize: 15,
                fontWeight: 600,
                color: "#2D3748",
              }}
            >
              {link ? t("LBL_EDIT_LINK") : t("LBL_ADD_LINK")}
            </h3>
            <button
              type="button"
              onClick={onClose}
              aria-label={t("LBL_CLOSE")}
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

          <div style={{ padding: 20, overflowY: "auto" }}>
            {hasErrors && (
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
                {errors._ ?? t("LBL_ERROR_HAS_ERRORS")}
              </div>
            )}

            <div style={sectionStyle}>
              <label style={labelStyle} htmlFor="shared-link-title">
                {t("LBL_TITLE")} <span style={{ color: "#E53E3E" }}>*</span>
              </label>
              <input
                id="shared-link-title"
                type="text"
                value={form.title}
                maxLength={limits.title_max}
                onChange={(e) => update("title", e.target.value)}
                style={inputStyle}
              />
              {fieldError("title")}
            </div>

            <div style={sectionStyle}>
              <label style={labelStyle} htmlFor="shared-link-url">
                {t("LBL_URL")} <span style={{ color: "#E53E3E" }}>*</span>
              </label>
              <input
                id="shared-link-url"
                type="url"
                value={form.url}
                maxLength={limits.url_max}
                placeholder="https://"
                onChange={(e) => update("url", e.target.value)}
                style={inputStyle}
              />
              {fieldError("url")}
            </div>

            <div style={sectionStyle}>
              <label style={labelStyle} htmlFor="shared-link-description">
                {t("LBL_DESCRIPTION")}
              </label>
              <textarea
                id="shared-link-description"
                value={form.description}
                maxLength={limits.description_max}
                rows={2}
                onChange={(e) => update("description", e.target.value)}
                style={{ ...inputStyle, resize: "vertical" }}
              />
              {fieldError("description")}
            </div>

            <fieldset style={{ ...sectionStyle, border: "none", padding: 0 }}>
              <legend style={labelStyle}>{t("LBL_METHOD")}</legend>
              {(["GET", "POST"] as const).map((method) => (
                <label key={method} style={radioLabelStyle}>
                  <input
                    type="radio"
                    name="shared-link-method"
                    checked={form.method === method}
                    onChange={() => update("method", method)}
                  />
                  {method}
                </label>
              ))}
              {form.method === "POST" && (
                <div role="note" style={noteStyle}>
                  {t("LBL_POST_CSP_NOTE")}
                </div>
              )}
            </fieldset>

            <fieldset style={{ ...sectionStyle, border: "none", padding: 0 }}>
              <legend style={labelStyle}>{t("LBL_OPEN_MODE")}</legend>
              <label style={radioLabelStyle}>
                <input
                  type="radio"
                  name="shared-link-open-mode"
                  checked={form.open_mode === "tab"}
                  onChange={() => changeOpenMode("tab")}
                />
                {t("LBL_OPEN_MODE_TAB")}
              </label>
              <label style={radioLabelStyle}>
                <input
                  type="radio"
                  name="shared-link-open-mode"
                  checked={form.open_mode === "window"}
                  onChange={() => changeOpenMode("window")}
                />
                {t("LBL_OPEN_MODE_WINDOW")}
              </label>
              {form.open_mode === "window" && (
                <div style={{ display: "flex", gap: 16, marginTop: 8 }}>
                  {(
                    [
                      ["window_width", "LBL_WINDOW_WIDTH"],
                      ["window_height", "LBL_WINDOW_HEIGHT"],
                    ] as const
                  ).map(([key, labelKey]) => (
                    <div key={key} style={{ width: 160 }}>
                      <label style={labelStyle} htmlFor={`shared-link-${key}`}>
                        {t(labelKey)}{" "}
                        <span style={{ color: "#E53E3E" }}>*</span>
                      </label>
                      <input
                        id={`shared-link-${key}`}
                        type="number"
                        min={limits.window_min}
                        max={limits.window_max}
                        value={form[key] ?? ""}
                        onChange={(e) =>
                          update(
                            key,
                            e.target.value === ""
                              ? null
                              : Number(e.target.value),
                          )
                        }
                        style={inputStyle}
                      />
                      {fieldError(key)}
                    </div>
                  ))}
                </div>
              )}
              {form.open_mode === "window" && (
                <div style={noteStyle}>{t("LBL_WINDOW_SIZE_NOTE")}</div>
              )}
            </fieldset>

            <div style={sectionStyle}>
              <div style={labelStyle}>{t("LBL_PARAMS")}</div>
              {form.params.map((param, index) => (
                <div
                  key={index}
                  data-testid="shared-link-param-row"
                  style={{
                    display: "flex",
                    gap: 8,
                    alignItems: "flex-start",
                    marginBottom: 8,
                  }}
                >
                  <div style={{ width: 160, flexShrink: 0 }}>
                    <input
                      type="text"
                      aria-label={t("LBL_PARAM_NAME")}
                      placeholder={t("LBL_PARAM_NAME")}
                      value={param.name}
                      maxLength={limits.param_name_max}
                      onChange={(e) =>
                        updateParam(index, { name: e.target.value })
                      }
                      style={inputStyle}
                    />
                    {fieldError(`params.${index}.name`)}
                  </div>
                  <div
                    role="radiogroup"
                    aria-label={t("LBL_PARAM_VALUE_TYPE")}
                    style={{ flexShrink: 0, paddingTop: 5 }}
                  >
                    {(["fixed", "user"] as SharedLinkValueType[]).map(
                      (type) => (
                        <label
                          key={type}
                          style={{ ...radioLabelStyle, marginRight: 8 }}
                        >
                          <input
                            type="radio"
                            name={`shared-link-param-${index}-type`}
                            checked={param.value_type === type}
                            onChange={() =>
                              // 値の種類を切り替えたら値は空に戻す
                              updateParam(index, {
                                value_type: type,
                                value: "",
                              })
                            }
                          />
                          {t(
                            type === "fixed"
                              ? "LBL_VALUE_TYPE_FIXED"
                              : "LBL_VALUE_TYPE_USER",
                          )}
                        </label>
                      ),
                    )}
                  </div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    {param.value_type === "fixed" ? (
                      <input
                        type="text"
                        aria-label={t("LBL_PARAM_VALUE")}
                        placeholder={t("LBL_PARAM_VALUE")}
                        value={param.value}
                        maxLength={limits.param_value_max}
                        onChange={(e) =>
                          updateParam(index, { value: e.target.value })
                        }
                        style={inputStyle}
                      />
                    ) : (
                      <FilterableSelect
                        options={userFieldOptions}
                        value={param.value}
                        onChange={(value) => updateParam(index, { value })}
                        placeholder={t("LBL_SELECT_USER_FIELD")}
                        filterPlaceholder={t("LBL_SEARCH")}
                        ariaLabel={t("LBL_PARAM_VALUE")}
                        missingLabel={t("LBL_DELETED_FIELD")}
                        invalid={Boolean(errors[`params.${index}.value`])}
                      />
                    )}
                    {fieldError(`params.${index}.value`)}
                  </div>
                  <button
                    type="button"
                    onClick={() => removeParam(index)}
                    aria-label={t("LBL_REMOVE")}
                    title={t("LBL_REMOVE")}
                    style={{ ...smallButtonStyle, color: "#C53030" }}
                  >
                    ×
                  </button>
                </div>
              ))}
              <button
                type="button"
                onClick={addParam}
                disabled={form.params.length >= limits.params_max}
                style={smallButtonStyle}
              >
                ＋ {t("LBL_ADD_PARAM")}
              </button>
              {fieldError("params")}
              <div style={noteStyle}>{t("LBL_PARAMS_NOTE")}</div>
            </div>

            <fieldset style={{ ...sectionStyle, border: "none", padding: 0 }}>
              <legend style={labelStyle}>{t("LBL_VISIBILITY")}</legend>
              <label style={radioLabelStyle}>
                <input
                  type="radio"
                  name="shared-link-visibility"
                  checked={form.visibility === "all"}
                  onChange={() => update("visibility", "all")}
                />
                {t("LBL_VISIBILITY_ALL")}
              </label>
              <label style={radioLabelStyle}>
                <input
                  type="radio"
                  name="shared-link-visibility"
                  checked={form.visibility === "targets"}
                  onChange={() => update("visibility", "targets")}
                />
                {t("LBL_VISIBILITY_TARGETS")}
              </label>

              {form.visibility === "targets" && (
                <div
                  style={{
                    marginTop: 8,
                    padding: 12,
                    border: "1px solid #E2E8F0",
                    borderRadius: 6,
                    backgroundColor: "#F7FAFC",
                  }}
                >
                  <div
                    style={{
                      display: "flex",
                      gap: 8,
                      alignItems: "center",
                      flexWrap: "wrap",
                    }}
                  >
                    <select
                      aria-label={t("LBL_TARGET_TYPE")}
                      value={targetKind}
                      onChange={(e) => {
                        setTargetKind(e.target.value as TargetKind);
                        setTargetId("");
                        setIncludeSubordinates(false);
                      }}
                      style={{ ...inputStyle, width: 120 }}
                    >
                      <option value="Roles">{t("LBL_TARGET_ROLES")}</option>
                      <option value="Groups">{t("LBL_TARGET_GROUPS")}</option>
                      <option value="Users">{t("LBL_TARGET_USERS")}</option>
                    </select>
                    <FilterableSelect
                      options={targetOptions}
                      value={targetId}
                      onChange={setTargetId}
                      placeholder={t("LBL_SELECT_TARGET")}
                      filterPlaceholder={t("LBL_SEARCH")}
                      ariaLabel={t("LBL_TARGET")}
                      style={{ flex: 1, minWidth: 240 }}
                    />
                    {targetKind === "Roles" && (
                      <label style={{ ...radioLabelStyle, marginRight: 0 }}>
                        <input
                          type="checkbox"
                          checked={includeSubordinates}
                          onChange={(e) =>
                            setIncludeSubordinates(e.target.checked)
                          }
                        />
                        {t("LBL_INCLUDE_SUBORDINATES")}
                      </label>
                    )}
                    <button
                      type="button"
                      onClick={addTarget}
                      disabled={newTarget === null || isDuplicateTarget}
                      title={
                        isDuplicateTarget
                          ? t("LBL_ERROR_TARGET_DUPLICATED")
                          : undefined
                      }
                      style={smallButtonStyle}
                    >
                      {t("LBL_ADD_TARGET")}
                    </button>
                  </div>

                  <div
                    data-testid="shared-link-targets"
                    style={{
                      display: "flex",
                      flexWrap: "wrap",
                      gap: 6,
                      marginTop: 10,
                    }}
                  >
                    {form.targets.length === 0 && (
                      <span style={{ fontSize: 12, color: "#718096" }}>
                        {t("LBL_NO_TARGETS")}
                      </span>
                    )}
                    {form.targets.map((target) => {
                      const name = targetName(target);
                      return (
                        <span
                          key={targetKey(target)}
                          style={{
                            display: "inline-flex",
                            alignItems: "center",
                            gap: 4,
                            padding: "2px 4px 2px 10px",
                            borderRadius: 12,
                            fontSize: 12,
                            backgroundColor:
                              name === null ? "#FED7D7" : "#EBF8FF",
                            color: name === null ? "#C53030" : "#2B6CB0",
                          }}
                        >
                          {t(TARGET_TYPE_LABELS[target.type])}:{" "}
                          {name ?? t("LBL_DELETED_TARGET")}
                          <button
                            type="button"
                            onClick={() => removeTarget(target)}
                            aria-label={`${t("LBL_REMOVE")} ${name ?? ""}`}
                            style={{
                              border: "none",
                              background: "transparent",
                              cursor: "pointer",
                              color: "inherit",
                              fontSize: 14,
                              lineHeight: 1,
                              padding: "0 4px",
                            }}
                          >
                            ×
                          </button>
                        </span>
                      );
                    })}
                  </div>
                  {fieldError("targets")}
                  {targetErrors.map((message) => (
                    <div
                      key={message}
                      role="alert"
                      style={{ fontSize: 12, color: "#C53030", marginTop: 4 }}
                    >
                      {message}
                    </div>
                  ))}
                </div>
              )}
            </fieldset>

            <div style={sectionStyle}>
              <label style={{ ...radioLabelStyle, fontWeight: 600 }}>
                <input
                  type="checkbox"
                  checked={form.noreferrer}
                  onChange={(e) => update("noreferrer", e.target.checked)}
                />
                {t("LBL_NOREFERRER")}
              </label>
              <div style={noteStyle}>{t("LBL_NOREFERRER_NOTE")}</div>
            </div>

            <div style={{ marginBottom: 4 }}>
              <label style={{ ...radioLabelStyle, fontWeight: 600 }}>
                <input
                  type="checkbox"
                  checked={form.active}
                  onChange={(e) => update("active", e.target.checked)}
                />
                {t("LBL_ACTIVE")}
              </label>
              <div style={noteStyle}>{t("LBL_ACTIVE_NOTE")}</div>
            </div>
          </div>

          <div
            style={{
              display: "flex",
              justifyContent: "flex-end",
              gap: 8,
              padding: "12px 20px",
              borderTop: "1px solid #E2E8F0",
            }}
          >
            <button
              type="button"
              onClick={onClose}
              style={{ ...smallButtonStyle, padding: "6px 16px", fontSize: 13 }}
            >
              {t("LBL_CANCEL")}
            </button>
            <button
              type="submit"
              disabled={isSaving}
              style={{
                ...smallButtonStyle,
                padding: "6px 16px",
                fontSize: 13,
                backgroundColor: "#3182CE",
                borderColor: "#3182CE",
                color: "#fff",
                opacity: isSaving ? 0.6 : 1,
              }}
            >
              {isSaving ? t("LBL_SAVING") : t("LBL_SAVE")}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
