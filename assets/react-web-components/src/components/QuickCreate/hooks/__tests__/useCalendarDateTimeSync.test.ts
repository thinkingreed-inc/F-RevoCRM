import { describe, it, expect, vi } from "vitest";
import { renderHook, act } from "@testing-library/react";
import { useCalendarDateTimeSync } from "../useCalendarDateTimeSync";
import { FieldValue } from "../../../../types/field";

// useCalendarFields の parseDateTimeValue / combineDateTimeValue と同じ形式（YYYY-MM-DDTHH:MM）
const parseDateTimeValue = (value: string | undefined) => {
  if (!value) return { date: "", time: "" };
  if (value.includes("T")) {
    const [date, time] = value.split("T");
    return { date, time: time || "" };
  }
  return { date: value, time: "" };
};
const combineDateTimeValue = (date: string, time: string) =>
  !date ? "" : !time ? date : `${date}T${time}`;

const setup = (
  formData: Record<string, FieldValue>,
  { isAllDay, isEditMode }: { isAllDay: boolean; isEditMode: boolean },
) => {
  const onFieldChange = vi.fn();
  const { result } = renderHook(() =>
    useCalendarDateTimeSync({
      formData,
      onFieldChange,
      defaultCallDuration: 5,
      defaultOtherEventDuration: 60,
      activeTab: "Events",
      isEditMode,
      isAllDay,
      parseDateTimeValue,
      combineDateTimeValue,
    }),
  );
  const changeStart = (value: string) => {
    act(() => {
      // 編集モードは日時の欄にフォーカスしてから連動が始まる
      result.current.handleDateTimeFieldFocus();
      result.current.handleDateStartChange(value);
    });
  };
  const dueDate = () =>
    onFieldChange.mock.calls
      .filter(([name]) => name === "due_date")
      .at(-1)?.[1];
  return { changeStart, dueDate, onFieldChange };
};

describe("useCalendarDateTimeSync 開始日の変更で終了日が連動する", () => {
  describe.each([
    { mode: "新規", isEditMode: false },
    { mode: "編集", isEditMode: true },
  ])("終日オン（$mode）", ({ isEditMode }) => {
    it.each([
      {
        case: "同日の終日は、新しい開始日と同じ日になる",
        start: "2026-11-05",
        due: "2026-11-05",
        newStart: "2026-12-10",
        expected: "2026-12-10",
      },
      {
        case: "複数日の終日は、日数の差を保つ",
        start: "2026-11-10",
        due: "2026-11-12",
        newStart: "2026-11-20",
        expected: "2026-11-22",
      },
      {
        case: "月をまたいでも日数の差を保つ",
        start: "2026-11-29",
        due: "2026-11-30",
        newStart: "2026-12-31",
        expected: "2027-01-01",
      },
      {
        case: "終日にする前の遅い時刻が残っていても、翌日に繰り上がらない",
        start: "2026-11-05T23:30",
        due: "2026-11-05T23:59",
        newStart: "2026-12-10",
        expected: "2026-12-10",
      },
      {
        case: "時刻が残っていても日付の差（画面に見えている日数）を保つ",
        start: "2026-11-05T23:30",
        due: "2026-11-06T00:30",
        newStart: "2026-12-10",
        expected: "2026-12-11",
      },
      {
        case: "終了日が開始日より前なら、新しい開始日にそろえる",
        start: "2026-11-05",
        due: "2026-11-03",
        newStart: "2026-12-10",
        expected: "2026-12-10",
      },
      {
        case: "終了日が空なら、新しい開始日にそろえる",
        start: "2026-11-05",
        due: "",
        newStart: "2026-12-10",
        expected: "2026-12-10",
      },
    ])("$case", ({ start, due, newStart, expected }) => {
      const { changeStart, dueDate } = setup(
        { date_start: start, due_date: due, is_allday: true },
        { isAllDay: true, isEditMode },
      );
      changeStart(newStart);
      expect(dueDate()).toBe(expected);
    });
  });

  describe("終日オフ（既存の動き）", () => {
    it.each([
      {
        case: "開始＋既定の時間（60分）になる",
        start: "2026-11-05T16:41",
        newStart: "2026-12-10T16:41",
        expected: "2026-12-10T17:41",
      },
      {
        case: "既定の時間で日をまたぐときは翌日になる",
        start: "2026-11-05T23:30",
        newStart: "2026-12-10T23:30",
        expected: "2026-12-11T00:30",
      },
    ])("$case", ({ start, newStart, expected }) => {
      const { changeStart, dueDate } = setup(
        { date_start: start, due_date: start },
        { isAllDay: false, isEditMode: false },
      );
      changeStart(newStart);
      expect(dueDate()).toBe(expected);
    });
  });

  it("編集モードで日時の欄にフォーカスする前は、終日でも終了日を変えない", () => {
    const onFieldChange = vi.fn();
    const { result } = renderHook(() =>
      useCalendarDateTimeSync({
        formData: { date_start: "2026-11-05", due_date: "2026-11-05" },
        onFieldChange,
        defaultCallDuration: 5,
        defaultOtherEventDuration: 60,
        activeTab: "Events",
        isEditMode: true,
        isAllDay: true,
        parseDateTimeValue,
        combineDateTimeValue,
      }),
    );
    act(() => result.current.handleDateStartChange("2026-12-10"));
    expect(onFieldChange).toHaveBeenCalledWith("date_start", "2026-12-10");
    expect(onFieldChange).not.toHaveBeenCalledWith(
      "due_date",
      expect.anything(),
    );
  });
});
