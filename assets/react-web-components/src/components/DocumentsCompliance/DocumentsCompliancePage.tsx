import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useComplianceSettings } from "./hooks/useComplianceSettings";
import type {
  CategoryModules,
  DeadlinePolicy,
  DeadlineSettings,
} from "./types/documentsCompliance";

/** 設定のタブ */
type TabKey = "deadline" | "transaction";

/** タブの定義（ラベルは言語ファイルから取得する） */
const TABS: Array<{ key: TabKey; labelKey: string }> = [
  { key: "deadline", labelKey: "LBL_INPUT_DEADLINE_SETTINGS" },
  { key: "transaction", labelKey: "LBL_TRANSACTION_MODULE_SETTINGS" },
];

/**
 * 電子帳簿保存法設定の管理画面
 *
 * スキャナ保存の入力期限の計算方針を設定する。
 * システム管理者のみアクセスできる（サーバー側で判定）。
 */
export const DocumentsCompliancePage: React.FC = () => {
  const {
    info,
    isLoading,
    isSaving,
    error,
    setError,
    save,
    recalculate,
    saveCategoryModules,
    recheckCompliance,
  } = useComplianceSettings();

  const [draft, setDraft] = useState<DeadlineSettings | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  /** 書類区分ごとの取引モジュールの編集中の状態（null は保存済みの内容） */
  const [modulesDraft, setModulesDraft] = useState<CategoryModules | null>(
    null,
  );
  /** 表示中のタブ */
  const [activeTab, setActiveTab] = useState<TabKey>("deadline");

  // 読み込み後に編集用の状態へ取り込む
  useEffect(() => {
    if (info) setDraft(info.settings);
  }, [info]);

  const t = useCallback(
    (key: string) => (info ? info.labels[key] || key : key),
    [info],
  );

  const isDirty = useMemo(() => {
    if (!info || !draft) return false;
    return (
      draft.policy !== info.settings.policy ||
      draft.business_days !== info.settings.business_days ||
      draft.cycle_months !== info.settings.cycle_months ||
      draft.warning_days !== info.settings.warning_days
    );
  }, [draft, info]);

  const handleSave = useCallback(async () => {
    if (!draft) return;
    try {
      await save(draft);
      setMessage(t("LBL_SETTINGS_SAVED"));
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }, [draft, save, setError, t]);

  const categoryModules = useMemo(
    () => modulesDraft ?? info?.category_modules ?? {},
    [modulesDraft, info],
  );

  const toggleCategoryModule = useCallback(
    (category: string, moduleName: string) => {
      setModulesDraft((current) => {
        const base = current ?? info?.category_modules ?? {};
        const modules = base[category] ?? [];
        const next = modules.includes(moduleName)
          ? modules.filter((name) => name !== moduleName)
          : [...modules, moduleName];
        return { ...base, [category]: next };
      });
    },
    [info],
  );

  const handleSaveCategoryModules = useCallback(async () => {
    try {
      await saveCategoryModules(categoryModules);
      setModulesDraft(null);
      setMessage(t("LBL_CATEGORY_MODULES_SAVED"));
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }, [categoryModules, saveCategoryModules, setError, t]);

  const handleRecheck = useCallback(async () => {
    if (!window.confirm(t("LBL_CONFIRM_RECHECK"))) return;
    try {
      const result = await recheckCompliance();
      setMessage(
        (t("LBL_RECHECK_RESULT") || "%s / %s / %s")
          .replace("%s", String(result.checked))
          .replace("%s", String(result.compliant))
          .replace("%s", String(result.non_compliant)),
      );
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }, [recheckCompliance, setError, t]);

  const handleRecalculate = useCallback(async () => {
    if (!window.confirm(t("LBL_CONFIRM_RECALCULATE"))) return;
    try {
      const result = await recalculate();
      setMessage(
        (t("LBL_RECALCULATE_RESULT") || "%s / %s")
          .replace("%s", String(result.checked))
          .replace("%s", String(result.updated)),
      );
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }, [recalculate, setError, t]);

  if (!info || !draft) {
    return (
      <div style={{ padding: 24, color: "#718096", fontSize: 13 }}>
        {isLoading ? "..." : error}
      </div>
    );
  }

  const labelStyle: React.CSSProperties = {
    display: "block",
    fontSize: 13,
    fontWeight: 600,
    color: "#4A5568",
    marginBottom: 4,
  };
  const noteStyle: React.CSSProperties = {
    fontSize: 12,
    color: "#718096",
    marginTop: 4,
  };
  const numberInputStyle: React.CSSProperties = {
    width: 80,
    padding: "5px 8px",
    fontSize: 13,
    border: "1px solid #CBD5E0",
    borderRadius: 4,
  };
  const rowStyle: React.CSSProperties = {
    marginBottom: 16,
    paddingBottom: 16,
    borderBottom: "1px solid #F1F5F9",
  };

  /** 数値項目の変更（空欄は0にせず入力途中として扱う） */
  const changeNumber = (key: keyof DeadlineSettings, value: string) => {
    const parsed = value === "" ? 0 : Number(value);
    if (Number.isNaN(parsed)) return;
    setDraft({ ...draft, [key]: parsed } as DeadlineSettings);
  };

  return (
    <div style={{ padding: "16px 20px", backgroundColor: "#fff" }}>
      {/* タブ */}
      <div
        style={{
          display: "flex",
          gap: 4,
          marginBottom: 12,
          borderBottom: "1px solid #E2E8F0",
        }}
      >
        {TABS.map((tab) => {
          const isActive = activeTab === tab.key;
          return (
            <button
              key={tab.key}
              type="button"
              onClick={() => setActiveTab(tab.key)}
              style={{
                padding: "7px 16px",
                fontSize: 13,
                fontWeight: isActive ? 600 : 400,
                color: isActive ? "#2B6CB0" : "#4A5568",
                backgroundColor: "transparent",
                border: "none",
                borderBottom: isActive
                  ? "2px solid #3182CE"
                  : "2px solid transparent",
                marginBottom: -1,
                cursor: "pointer",
              }}
            >
              {t(tab.labelKey)}
            </button>
          );
        })}
      </div>

      {error && (
        <div
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

      {activeTab === "deadline" && (
        <>
          <div
            style={{
              display: "flex",
              alignItems: "center",
              justifyContent: "flex-end",
              flexWrap: "wrap",
              gap: 8,
              marginBottom: 8,
            }}
          >
            <button
              type="button"
              onClick={handleSave}
              disabled={isSaving || !isDirty}
              style={{
                // 休祝日マスタの保存ボタンと同じ配色にそろえる（緑・白文字）
                padding: "5px 16px",
                fontSize: 13,
                border: "none",
                borderRadius: 4,
                backgroundColor: isSaving || !isDirty ? "#A0AEC0" : "#38A169",
                color: "#fff",
                fontWeight: 500,
                cursor: isSaving || !isDirty ? "default" : "pointer",
              }}
            >
              {isSaving ? t("LBL_SAVING") : t("LBL_SAVE")}
            </button>
          </div>

          <div style={{ fontSize: 12, color: "#718096", marginBottom: 12 }}>
            {t("LBL_SETTINGS_NOTE")}{" "}
            <a href={info.holidays_url} style={{ color: "#3182CE" }}>
              {t("LBL_HOLIDAYS_LINK")}
            </a>
          </div>

          {/* 方針 */}
          <div style={rowStyle}>
            <span style={labelStyle}>{t("LBL_POLICY")}</span>
            {info.policies.map((option) => (
              <label
                key={option.value}
                style={{
                  display: "block",
                  marginBottom: 8,
                  fontSize: 13,
                  fontWeight: 400,
                  color: "#2D3748",
                  cursor: "pointer",
                }}
              >
                <input
                  type="radio"
                  name="input_deadline_policy"
                  value={option.value}
                  checked={draft.policy === option.value}
                  onChange={() =>
                    setDraft({
                      ...draft,
                      policy: option.value as DeadlinePolicy,
                    })
                  }
                  style={{ marginRight: 6 }}
                />
                {option.label}
                <span
                  style={{ ...noteStyle, marginLeft: 22, display: "block" }}
                >
                  {option.description}
                </span>
              </label>
            ))}
          </div>

          {/* 猶予の営業日数 */}
          <div style={rowStyle}>
            <span style={labelStyle}>{t("LBL_BUSINESS_DAYS")}</span>
            <input
              type="number"
              min={1}
              max={info.max_days}
              value={draft.business_days || ""}
              onChange={(e) => changeNumber("business_days", e.target.value)}
              style={numberInputStyle}
            />
            <span style={{ marginLeft: 6, fontSize: 13, color: "#4A5568" }}>
              {t("LBL_DAY_SUFFIX")}
            </span>
            <div style={noteStyle}>{t("LBL_BUSINESS_DAYS_NOTE")}</div>
          </div>

          {/* 業務処理サイクル（cycle のときだけ使う） */}
          <div style={rowStyle}>
            <span style={labelStyle}>{t("LBL_CYCLE_MONTHS")}</span>
            <input
              type="number"
              min={1}
              max={info.max_cycle_months}
              value={draft.cycle_months || ""}
              onChange={(e) => changeNumber("cycle_months", e.target.value)}
              disabled={draft.policy !== "cycle"}
              style={{
                ...numberInputStyle,
                backgroundColor: draft.policy !== "cycle" ? "#F7FAFC" : "#fff",
              }}
            />
            <span style={{ marginLeft: 6, fontSize: 13, color: "#4A5568" }}>
              {t("LBL_MONTH_SUFFIX")}
            </span>
            <div style={noteStyle}>{t("LBL_CYCLE_MONTHS_NOTE")}</div>
          </div>

          {/* 期限間近とする営業日数 */}
          <div style={rowStyle}>
            <span style={labelStyle}>{t("LBL_WARNING_DAYS")}</span>
            <input
              type="number"
              min={1}
              max={info.max_days}
              value={draft.warning_days || ""}
              onChange={(e) => changeNumber("warning_days", e.target.value)}
              style={numberInputStyle}
            />
            <span style={{ marginLeft: 6, fontSize: 13, color: "#4A5568" }}>
              {t("LBL_DAY_SUFFIX")}
            </span>
            <div style={noteStyle}>{t("LBL_WARNING_DAYS_NOTE")}</div>
          </div>

          {/* 保存済みの設定で計算した例 */}
          <div
            style={{
              padding: "8px 12px",
              marginBottom: 16,
              backgroundColor: "#F7FAFC",
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              fontSize: 13,
              color: "#2D3748",
            }}
          >
            {(t("LBL_EXAMPLE") || "%s / %s")
              .replace("%s", info.example.receipt_date)
              .replace("%s", info.example.input_deadline ?? "-")}
          </div>

          {/* 既存の入力期限の再計算 */}
          <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
            <button
              type="button"
              onClick={handleRecalculate}
              disabled={isSaving}
              style={{
                padding: "5px 12px",
                fontSize: 13,
                border: "1px solid #CBD5E0",
                borderRadius: 4,
                backgroundColor: "#fff",
                color: "#2B6CB0",
                cursor: isSaving ? "wait" : "pointer",
                whiteSpace: "nowrap",
              }}
            >
              {t("LBL_RECALCULATE")}
            </button>
            <span style={{ fontSize: 12, color: "#718096" }}>
              {t("LBL_RECALCULATE_NOTE")}
            </span>
          </div>
        </>
      )}

      {activeTab === "transaction" && (
        <>
          {/* 書類区分ごとの取引レコードの判定 */}
          <div>
            <div
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "flex-end",
                flexWrap: "wrap",
                gap: 8,
                marginBottom: 6,
              }}
            >
              <button
                type="button"
                onClick={handleSaveCategoryModules}
                disabled={isSaving || modulesDraft === null}
                style={{
                  padding: "5px 16px",
                  fontSize: 13,
                  border: "none",
                  borderRadius: 4,
                  backgroundColor:
                    isSaving || modulesDraft === null ? "#A0AEC0" : "#38A169",
                  color: "#fff",
                  fontWeight: 500,
                  cursor:
                    isSaving || modulesDraft === null ? "default" : "pointer",
                }}
              >
                {isSaving ? t("LBL_SAVING") : t("LBL_SAVE")}
              </button>
            </div>
            <div style={{ ...noteStyle, marginBottom: 10 }}>
              {t("LBL_TRANSACTION_MODULE_NOTE")}
              <br />
              {t("LBL_NO_MODULE_SELECTED_NOTE")}
            </div>

            <div style={{ overflowX: "auto" }}>
              <table style={{ borderCollapse: "collapse", fontSize: 13 }}>
                <thead>
                  <tr>
                    <th
                      style={{
                        textAlign: "left",
                        padding: "6px 10px",
                        fontSize: 12,
                        fontWeight: 600,
                        color: "#718096",
                        borderBottom: "2px solid #E2E8F0",
                        whiteSpace: "nowrap",
                      }}
                    >
                      {t("LBL_DOCUMENT_CATEGORY")}
                    </th>
                    {info.document_categories.map((category) => (
                      <th
                        key={category.value}
                        style={{
                          padding: "6px 10px",
                          fontSize: 12,
                          fontWeight: 600,
                          color: "#718096",
                          borderBottom: "2px solid #E2E8F0",
                          whiteSpace: "nowrap",
                        }}
                      >
                        {category.label}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {info.relatable_modules.map((module) => (
                    <tr key={module.value}>
                      <td
                        style={{
                          padding: "5px 10px",
                          color: "#2D3748",
                          borderBottom: "1px solid #F1F5F9",
                          whiteSpace: "nowrap",
                        }}
                      >
                        {module.label}
                      </td>
                      {info.document_categories.map((category) => {
                        const checked = (
                          categoryModules[category.value] ?? []
                        ).includes(module.value);
                        return (
                          <td
                            key={category.value}
                            style={{
                              padding: "5px 10px",
                              textAlign: "center",
                              borderBottom: "1px solid #F1F5F9",
                              backgroundColor: checked ? "#F0FFF4" : undefined,
                            }}
                          >
                            <input
                              type="checkbox"
                              checked={checked}
                              onChange={() =>
                                toggleCategoryModule(
                                  category.value,
                                  module.value,
                                )
                              }
                              title={`${category.label} / ${module.label}`}
                              style={{ margin: 0, cursor: "pointer" }}
                            />
                          </td>
                        );
                      })}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div
              style={{
                display: "flex",
                alignItems: "center",
                gap: 10,
                marginTop: 12,
              }}
            >
              <button
                type="button"
                onClick={handleRecheck}
                disabled={isSaving}
                style={{
                  padding: "5px 12px",
                  fontSize: 13,
                  border: "1px solid #CBD5E0",
                  borderRadius: 4,
                  backgroundColor: "#fff",
                  color: "#2B6CB0",
                  cursor: isSaving ? "wait" : "pointer",
                  whiteSpace: "nowrap",
                }}
              >
                {t("LBL_RECHECK_COMPLIANCE")}
              </button>
              <span style={{ fontSize: 12, color: "#718096" }}>
                {t("LBL_RECHECK_NOTE")}
              </span>
            </div>
          </div>
        </>
      )}
    </div>
  );
};
