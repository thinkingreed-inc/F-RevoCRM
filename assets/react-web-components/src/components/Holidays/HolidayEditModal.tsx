import React, { useEffect, useState } from "react";
import type {
  DayType,
  HolidayFormValues,
  HolidayInfo,
  HolidayRecord,
  HolidayType,
} from "./types/holidays";

interface HolidayEditModalProps {
  isOpen: boolean;
  info: HolidayInfo;
  /** 編集対象。null なら新規追加 */
  record: HolidayRecord | null;
  /** 新規追加時の初期年 */
  defaultYear: number;
  isSaving: boolean;
  onSave: (values: HolidayFormValues) => void;
  onClose: () => void;
}

const labelStyle: React.CSSProperties = {
  display: "block",
  fontSize: 12,
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

/** 休祝日の追加・編集モーダル */
export const HolidayEditModal: React.FC<HolidayEditModalProps> = ({
  isOpen,
  info,
  record,
  defaultYear,
  isSaving,
  onSave,
  onClose,
}) => {
  const t = (key: string) => info.labels[key] || key;
  const [date, setDate] = useState("");
  const [name, setName] = useState("");
  const [dayType, setDayType] = useState<DayType>("holiday");
  const [holidayType, setHolidayType] = useState<HolidayType>("company");
  const [description, setDescription] = useState("");
  const [validationError, setValidationError] = useState<string | null>(null);

  useEffect(() => {
    if (!isOpen) return;
    setValidationError(null);
    if (record) {
      setDate(record.holiday_date);
      setName(record.holiday_name);
      setDayType(record.day_type);
      setHolidayType(record.holiday_type);
      setDescription(record.description || "");
    } else {
      setDate(`${defaultYear}-01-01`);
      setName("");
      setDayType("holiday");
      setHolidayType("company");
      setDescription("");
    }
  }, [isOpen, record, defaultYear]);

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!date) {
      setValidationError(t("LBL_INVALID_DATE"));
      return;
    }
    if (!name.trim()) {
      setValidationError(t("LBL_NAME_REQUIRED"));
      return;
    }
    onSave({
      holidayid: record?.holidayid,
      holiday_date: date,
      holiday_name: name.trim(),
      day_type: dayType,
      holiday_type: holidayType,
      description: description,
    });
  };

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
        style={{
          backgroundColor: "#fff",
          borderRadius: 8,
          width: 460,
          maxWidth: "95vw",
          boxShadow: "0 10px 40px rgba(0,0,0,0.25)",
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <form onSubmit={handleSubmit}>
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
              {record ? t("LBL_EDIT_HOLIDAY") : t("LBL_ADD_HOLIDAY")}
            </h3>
            <button
              type="button"
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

          <div style={{ padding: 20 }}>
            {validationError && (
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
                {validationError}
              </div>
            )}

            <div style={{ marginBottom: 14 }}>
              <label style={labelStyle}>
                {t("LBL_HOLIDAY_DATE")}{" "}
                <span style={{ color: "#E53E3E" }}>*</span>
              </label>
              <input
                type="date"
                value={date}
                onChange={(e) => setDate(e.target.value)}
                style={inputStyle}
                required
              />
            </div>

            <div style={{ marginBottom: 14 }}>
              <label style={labelStyle}>
                {t("LBL_HOLIDAY_NAME")}{" "}
                <span style={{ color: "#E53E3E" }}>*</span>
              </label>
              <input
                type="text"
                value={name}
                maxLength={200}
                onChange={(e) => setName(e.target.value)}
                style={inputStyle}
                required
              />
            </div>

            <div style={{ display: "flex", gap: 12, marginBottom: 14 }}>
              <div style={{ flex: 1 }}>
                <label style={labelStyle}>{t("LBL_DAY_TYPE")}</label>
                <select
                  value={dayType}
                  onChange={(e) => setDayType(e.target.value as DayType)}
                  style={inputStyle}
                >
                  {info.day_types.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
              <div style={{ flex: 1 }}>
                <label style={labelStyle}>{t("LBL_HOLIDAY_TYPE")}</label>
                <select
                  value={holidayType}
                  onChange={(e) =>
                    setHolidayType(e.target.value as HolidayType)
                  }
                  style={inputStyle}
                >
                  {info.holiday_types.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div>
              <label style={labelStyle}>{t("LBL_DESCRIPTION")}</label>
              <textarea
                value={description}
                rows={2}
                onChange={(e) => setDescription(e.target.value)}
                style={{ ...inputStyle, resize: "vertical" }}
              />
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
              disabled={isSaving}
              style={{
                padding: "6px 16px",
                fontSize: 13,
                border: "1px solid #CBD5E0",
                borderRadius: 4,
                backgroundColor: "#fff",
                cursor: "pointer",
              }}
            >
              {t("LBL_CANCEL")}
            </button>
            <button
              type="submit"
              disabled={isSaving}
              style={{
                padding: "6px 16px",
                fontSize: 13,
                border: "none",
                borderRadius: 4,
                backgroundColor: isSaving ? "#A0AEC0" : "#38A169",
                color: "#fff",
                fontWeight: 500,
                cursor: isSaving ? "wait" : "pointer",
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
