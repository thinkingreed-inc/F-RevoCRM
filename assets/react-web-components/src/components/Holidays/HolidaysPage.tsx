import React, { useCallback, useMemo, useRef, useState } from "react";
import { useHolidays } from "./hooks/useHolidays";
import { HolidayEditModal } from "./HolidayEditModal";
import type { HolidayFormValues, HolidayRecord } from "./types/holidays";

interface HolidaysPageProps {
  /** 初期表示年（テンプレートから渡される） */
  year?: number;
}

/** 種別ごとのバッジ色 */
const TYPE_COLORS: Record<string, { color: string; bg: string }> = {
  national: { color: "#C53030", bg: "#FED7D7" },
  company: { color: "#2B6CB0", bg: "#BEE3F8" },
  other: { color: "#4A5568", bg: "#E2E8F0" },
};

/**
 * 休祝日マスタの管理画面
 *
 * 営業日計算を行う機能から共通で参照されるマスタを管理する。
 * システム管理者のみアクセスできる（サーバー側で判定）。
 */
export const HolidaysPage: React.FC<HolidaysPageProps> = ({ year }) => {
  const {
    info,
    records,
    availableYears,
    year: selectedYear,
    isLoading,
    isSaving,
    error,
    setYear,
    setError,
    save,
    remove,
    generate,
    importOfficial,
    saveWeeklyHolidays,
  } = useHolidays(year);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<HolidayRecord | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  /** 週休の編集中の状態（null は info の値をそのまま使う） */
  const [weeklyDraft, setWeeklyDraft] = useState<number[] | null>(null);

  const t = useCallback(
    (key: string) => (info ? info.labels[key] || key : key),
    [info],
  );

  /** 年の選択肢（登録済みの年 + 過去2年〜先5年。将来分の祝日を事前登録できるようにする） */
  const yearOptions = useMemo(() => {
    const years = new Set<number>(availableYears);
    const base = info?.current_year ?? new Date().getFullYear();
    for (let i = -2; i <= 5; i++) {
      years.add(base + i);
    }
    years.add(selectedYear);
    return Array.from(years).sort((a, b) => b - a);
  }, [availableYears, info, selectedYear]);

  const weekdayLabel = useCallback(
    (weekday: number) => info?.weekday_labels?.[weekday] ?? String(weekday),
    [info],
  );

  const handleSave = useCallback(
    async (values: HolidayFormValues) => {
      try {
        await save(values);
        setModalOpen(false);
        setEditTarget(null);
        setMessage(null);
      } catch (e) {
        setError(e instanceof Error ? e.message : String(e));
      }
    },
    [save, setError],
  );

  const handleDelete = useCallback(
    async (record: HolidayRecord) => {
      const confirmMessage = `${t("LBL_CONFIRM_DELETE")}\n${record.holiday_date} ${record.holiday_name}`;
      if (!window.confirm(confirmMessage)) return;
      try {
        await remove(record.holidayid);
        setMessage(null);
      } catch (e) {
        setError(e instanceof Error ? e.message : String(e));
      }
    },
    [remove, setError, t],
  );

  const handleImportOfficial = useCallback(
    async (file?: File | null) => {
      if (!file && !window.confirm(t("LBL_CONFIRM_IMPORT_OFFICIAL"))) {
        return;
      }
      try {
        const result = await importOfficial(file ?? null);
        setMessage(
          (t("LBL_IMPORT_RESULT") || "")
            .replace("%s", String(result.year_from ?? ""))
            .replace("%s", String(result.year_to ?? ""))
            .replace("%s", String(result.added))
            .replace("%s", String(result.updated))
            .replace("%s", String(result.removed)),
        );
      } catch (e) {
        setError(e instanceof Error ? e.message : String(e));
      }
    },
    [importOfficial, setError, t],
  );

  const handleGenerate = useCallback(async () => {
    if (!info) return;
    if (selectedYear < info.supported_from_year) {
      setError(
        (t("LBL_YEAR_NOT_SUPPORTED") || "%s").replace(
          "%s",
          String(info.supported_from_year),
        ),
      );
      return;
    }
    if (
      !window.confirm(
        (t("LBL_CONFIRM_GENERATE") || "%s").replace("%s", String(selectedYear)),
      )
    ) {
      return;
    }
    try {
      const result = await generate(selectedYear);
      setMessage(
        (t("LBL_GENERATE_RESULT") || "%s / %s")
          .replace("%s", String(result.registered))
          .replace("%s", String(result.skipped)),
      );
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }, [generate, info, selectedYear, setError, t]);

  /** 表示中の週休（編集中はその内容、未編集なら保存済みの設定） */
  const weeklyHolidays = useMemo(
    () => weeklyDraft ?? info?.weekly_holidays ?? [],
    [weeklyDraft, info],
  );

  const toggleWeekday = useCallback(
    (weekday: number) => {
      setWeeklyDraft((current) => {
        const base = current ?? info?.weekly_holidays ?? [];
        return base.includes(weekday)
          ? base.filter((day) => day !== weekday)
          : [...base, weekday].sort((a, b) => a - b);
      });
    },
    [info],
  );

  const handleSaveWeeklyHolidays = useCallback(async () => {
    try {
      await saveWeeklyHolidays(weeklyHolidays);
      // 保存後は info の値（週休の色分けに使う）を正とする
      setWeeklyDraft(null);
      setMessage(t("LBL_SETTINGS_SAVED"));
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    }
  }, [saveWeeklyHolidays, setError, t, weeklyHolidays]);

  if (!info) {
    return (
      <div style={{ padding: 24, color: "#718096", fontSize: 13 }}>
        {isLoading ? "..." : error}
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
  };

  return (
    <div style={{ padding: "16px 20px", backgroundColor: "#fff" }}>
      {/* ヘッダー */}
      <div
        style={{
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          flexWrap: "wrap",
          gap: 8,
          marginBottom: 8,
        }}
      >
        <h4
          style={{ margin: 0, fontSize: 16, fontWeight: 600, color: "#2D3748" }}
        >
          {t("LBL_HOLIDAYS")}
        </h4>
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <select
            value={selectedYear}
            onChange={(e) => setYear(Number(e.target.value))}
            style={{
              padding: "5px 8px",
              fontSize: 13,
              border: "1px solid #CBD5E0",
              borderRadius: 4,
            }}
          >
            {yearOptions.map((y) => (
              <option key={y} value={y}>
                {y}
                {t("LBL_YEAR_SUFFIX")}
              </option>
            ))}
          </select>
          <button
            type="button"
            onClick={() => handleImportOfficial(null)}
            disabled={isSaving}
            style={{
              padding: "5px 12px",
              fontSize: 13,
              border: "1px solid #3182CE",
              borderRadius: 4,
              backgroundColor: "#fff",
              color: "#2B6CB0",
              cursor: isSaving ? "wait" : "pointer",
            }}
          >
            {t("LBL_IMPORT_OFFICIAL")}
          </button>
          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            disabled={isSaving}
            style={{
              padding: "5px 12px",
              fontSize: 13,
              border: "1px solid #CBD5E0",
              borderRadius: 4,
              backgroundColor: "#fff",
              cursor: isSaving ? "wait" : "pointer",
            }}
          >
            {t("LBL_IMPORT_CSV_FILE")}
          </button>
          <input
            ref={fileInputRef}
            type="file"
            accept=".csv,text/csv"
            style={{ display: "none" }}
            onChange={(e) => {
              const file = e.target.files?.[0];
              e.target.value = "";
              if (file) handleImportOfficial(file);
            }}
          />
          <button
            type="button"
            onClick={handleGenerate}
            disabled={isSaving}
            title={t("LBL_GENERATE_NOTE")}
            style={{
              padding: "5px 12px",
              fontSize: 13,
              border: "1px solid #CBD5E0",
              borderRadius: 4,
              backgroundColor: "#fff",
              cursor: isSaving ? "wait" : "pointer",
            }}
          >
            {t("LBL_GENERATE_NATIONAL_HOLIDAYS")}
          </button>
          <button
            type="button"
            onClick={() => {
              setEditTarget(null);
              setModalOpen(true);
            }}
            style={{
              padding: "5px 14px",
              fontSize: 13,
              border: "none",
              borderRadius: 4,
              backgroundColor: "#3182CE",
              color: "#fff",
              fontWeight: 500,
              cursor: "pointer",
            }}
          >
            + {t("LBL_ADD_HOLIDAY")}
          </button>
        </div>
      </div>

      <div style={{ fontSize: 12, color: "#718096", marginBottom: 6 }}>
        {t("LBL_OFFICIAL_SOURCE_NOTE")}
        {info.official_csv_url && (
          <>
            {" "}
            <a
              href={info.official_csv_url}
              target="_blank"
              rel="noreferrer"
              style={{ color: "#3182CE" }}
            >
              {info.official_csv_url}
            </a>
          </>
        )}
      </div>
      <div style={{ fontSize: 12, color: "#718096", marginBottom: 8 }}>
        {t("LBL_WEEKLY_HOLIDAY_NOTE")}
      </div>

      {/* 週休の設定 */}
      <div
        style={{
          display: "flex",
          alignItems: "center",
          flexWrap: "wrap",
          gap: 10,
          marginBottom: 12,
          padding: "8px 12px",
          backgroundColor: "#F7FAFC",
          border: "1px solid #E2E8F0",
          borderRadius: 4,
        }}
      >
        <span style={{ fontSize: 13, fontWeight: 600, color: "#4A5568" }}>
          {t("LBL_WEEKLY_HOLIDAYS")}
        </span>
        <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
          {info.weekday_labels.map((label, weekday) => (
            <label
              key={weekday}
              style={{
                display: "inline-flex",
                alignItems: "center",
                gap: 4,
                fontSize: 13,
                color: weeklyHolidays.includes(weekday) ? "#E53E3E" : "#4A5568",
                cursor: "pointer",
                margin: 0,
                fontWeight: 400,
              }}
            >
              <input
                type="checkbox"
                checked={weeklyHolidays.includes(weekday)}
                onChange={() => toggleWeekday(weekday)}
                style={{ margin: 0 }}
              />
              {label}
            </label>
          ))}
        </div>
        {weeklyHolidays.length === 0 && (
          <span style={{ fontSize: 12, color: "#A0AEC0" }}>
            {t("LBL_WEEKLY_HOLIDAY_NONE")}
          </span>
        )}
        <button
          type="button"
          onClick={handleSaveWeeklyHolidays}
          disabled={isSaving || weeklyDraft === null}
          style={{
            // 休祝日の追加・編集モーダルの保存ボタンと同じ配色（緑・白文字）
            padding: "4px 12px",
            fontSize: 12,
            border: "none",
            borderRadius: 4,
            backgroundColor:
              isSaving || weeklyDraft === null ? "#A0AEC0" : "#38A169",
            color: "#fff",
            cursor: isSaving || weeklyDraft === null ? "default" : "pointer",
          }}
        >
          {isSaving ? t("LBL_SAVING") : t("LBL_SAVE")}
        </button>
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

      {/* 一覧 */}
      <table style={{ width: "100%", borderCollapse: "collapse" }}>
        <thead>
          <tr>
            <th style={{ ...thStyle, width: 150 }}>{t("LBL_HOLIDAY_DATE")}</th>
            <th style={thStyle}>{t("LBL_HOLIDAY_NAME")}</th>
            <th style={{ ...thStyle, width: 140 }}>{t("LBL_DAY_TYPE")}</th>
            <th style={{ ...thStyle, width: 120 }}>{t("LBL_HOLIDAY_TYPE")}</th>
            <th style={thStyle}>{t("LBL_DESCRIPTION")}</th>
            <th style={{ ...thStyle, width: 90 }}></th>
          </tr>
        </thead>
        <tbody>
          {isLoading && (
            <tr>
              <td colSpan={6} style={{ ...tdStyle, color: "#A0AEC0" }}>
                {t("LBL_LOADING")}
              </td>
            </tr>
          )}
          {!isLoading && records.length === 0 && (
            <tr>
              <td
                colSpan={6}
                style={{
                  ...tdStyle,
                  color: "#A0AEC0",
                  textAlign: "center",
                  padding: 32,
                }}
              >
                {t("LBL_NO_HOLIDAYS")}
              </td>
            </tr>
          )}
          {!isLoading &&
            records.map((record) => {
              const typeColor =
                TYPE_COLORS[record.holiday_type] || TYPE_COLORS.other;
              const dayTypeLabel =
                info.day_types.find((o) => o.value === record.day_type)
                  ?.label || record.day_type;
              const holidayTypeLabel =
                info.holiday_types.find((o) => o.value === record.holiday_type)
                  ?.label || record.holiday_type;
              const isWeekend = info.weekly_holidays.includes(record.weekday);
              return (
                <tr key={record.holidayid}>
                  <td style={tdStyle}>
                    {record.holiday_date}
                    <span
                      style={{
                        marginLeft: 6,
                        color: isWeekend ? "#E53E3E" : "#A0AEC0",
                        fontSize: 12,
                      }}
                    >
                      ({weekdayLabel(record.weekday)})
                    </span>
                  </td>
                  <td style={tdStyle}>{record.holiday_name}</td>
                  <td style={tdStyle}>
                    <span
                      style={{
                        fontSize: 12,
                        color:
                          record.day_type === "workday" ? "#276749" : "#4A5568",
                      }}
                    >
                      {dayTypeLabel}
                    </span>
                  </td>
                  <td style={tdStyle}>
                    <span
                      style={{
                        display: "inline-block",
                        padding: "1px 8px",
                        fontSize: 11,
                        borderRadius: 10,
                        color: typeColor.color,
                        backgroundColor: typeColor.bg,
                      }}
                    >
                      {holidayTypeLabel}
                    </span>
                  </td>
                  <td style={{ ...tdStyle, color: "#718096", fontSize: 12 }}>
                    {record.description}
                  </td>
                  <td style={{ ...tdStyle, whiteSpace: "nowrap" }}>
                    <button
                      type="button"
                      title={t("LBL_EDIT")}
                      onClick={() => {
                        setEditTarget(record);
                        setModalOpen(true);
                      }}
                      style={{
                        border: "none",
                        background: "transparent",
                        color: "#4299E1",
                        cursor: "pointer",
                        fontSize: 12,
                        padding: "0 6px",
                      }}
                    >
                      {t("LBL_EDIT")}
                    </button>
                    <button
                      type="button"
                      title={t("LBL_DELETE")}
                      onClick={() => handleDelete(record)}
                      style={{
                        border: "none",
                        background: "transparent",
                        color: "#E53E3E",
                        cursor: "pointer",
                        fontSize: 12,
                        padding: "0 6px",
                      }}
                    >
                      {t("LBL_DELETE")}
                    </button>
                  </td>
                </tr>
              );
            })}
        </tbody>
      </table>

      {records.length > 0 && (
        <div style={{ marginTop: 10, fontSize: 12, color: "#718096" }}>
          {records.length}
          {t("LBL_COUNT_SUFFIX")}
        </div>
      )}

      <HolidayEditModal
        isOpen={modalOpen}
        info={info}
        record={editTarget}
        defaultYear={selectedYear}
        isSaving={isSaving}
        onSave={handleSave}
        onClose={() => {
          setModalOpen(false);
          setEditTarget(null);
        }}
      />
    </div>
  );
};
