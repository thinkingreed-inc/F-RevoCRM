import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { QuickCreate } from "./QuickCreate";

/**
 * ToDo（Calendarタブ）のフィールド定義
 * ToDoの完了日（due_date）は日付のみ入力（時刻フィールドなし）
 */
const mockCalendarFields = [
  {
    name: "subject",
    label: "件名",
    uitype: "2",
    mandatory: true,
    readonly: false,
  },
  {
    name: "date_start",
    label: "開始日時",
    uitype: "23",
    mandatory: true,
    readonly: false,
  },
  {
    name: "due_date",
    label: "終了日",
    uitype: "23",
    mandatory: true,
    readonly: false,
  },
];

/** 活動（Eventsタブ）のフィールド定義 */
const mockEventsFields = [
  {
    name: "subject",
    label: "件名",
    uitype: "2",
    mandatory: true,
    readonly: false,
  },
  {
    name: "date_start",
    label: "開始日時",
    uitype: "23",
    mandatory: true,
    readonly: false,
  },
  {
    name: "due_date",
    label: "終了日時",
    uitype: "23",
    mandatory: true,
    readonly: false,
  },
];

const mockSave = vi.fn();
const mockClearError = vi.fn();

const mockUseQuickCreateSave = vi.fn(() => ({
  save: mockSave,
  isSaving: false,
  error: null,
  clearError: mockClearError,
}));

const mockUseCalendarFields = vi.fn((activeTab: string) => ({
  calendarFields: mockCalendarFields,
  eventsFields: mockEventsFields,
  currentFields:
    activeTab === "Calendar" ? mockCalendarFields : mockEventsFields,
  loading: false,
  error: null,
  editViewUrl: "index.php?module=Calendar&view=Edit",
  availableUsers: [],
  timeOptions: [
    { value: "09:00", label: "09:00" },
    { value: "14:30", label: "14:30" },
    { value: "15:00", label: "15:00" },
  ],
  // 実装（useCalendarFields）と同じロジック
  parseDateTimeValue: (value?: string) => {
    if (!value) return { date: "", time: "" };
    if (value.includes("T")) {
      const [datePart, timePart] = value.split("T");
      return { date: datePart, time: timePart || "" };
    }
    return { date: value, time: "" };
  },
  combineDateTimeValue: (date: string, time: string) => {
    if (!date) return "";
    if (!time) return date;
    return `${date}T${time}`;
  },
  parseReminderValue: () => ({ days: 0, hours: 0, minutes: 0 }),
  combineReminderValue: () => 0,
  transformInitialDataForEdit: (data: Record<string, unknown>) => data,
}));

vi.mock("./hooks/useQuickCreateFields", () => ({
  useQuickCreateFields: () => ({
    fields: [],
    loading: false,
    error: null,
    editViewUrl: null,
    moduleLabel: null,
    picklistDependency: undefined,
  }),
}));

vi.mock("./hooks/useQuickCreateSave", () => ({
  useQuickCreateSave: () => mockUseQuickCreateSave(),
}));

vi.mock("./hooks/useRecordData", () => ({
  useRecordData: () => ({ data: null, loading: false, error: null }),
}));

vi.mock("./hooks/useCalendarFields", () => ({
  useCalendarFields: (params: { activeTab: string }) =>
    mockUseCalendarFields(params.activeTab),
}));

const END_DATE_ERROR = "終了日時は開始日時より後に設定してください";

describe("QuickCreate (calendar variant) の日付範囲バリデーション", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockSave.mockResolvedValue({
      success: true,
      recordId: "123",
      recordLabel: "テストToDo",
      module: "Calendar",
    });
  });

  describe("ToDo（due_date が日付のみ）", () => {
    it("開始日時と終了日が同一日・開始 14:30 でも保存できる", async () => {
      // クイック作成メニュー（右上＋ボタン）や概要画面からの起動では
      // due_date が日付のみ（YYYY-MM-DD）で初期化される。
      // date-only 文字列を UTC 解釈すると JST では 9:00 扱いとなり、
      // 開始時刻が 9:00 以降のとき誤って NG 判定されていた。
      const user = userEvent.setup();

      render(
        <QuickCreate
          module="Calendar"
          isOpen={true}
          initialData={{
            subject: "テストToDo",
            date_start: "2026-08-17T14:30",
            due_date: "2026-08-17",
          }}
        />,
      );

      await user.click(screen.getByRole("button", { name: /保存/i }));

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });
      expect(screen.queryByText(END_DATE_ERROR)).not.toBeInTheDocument();
    });

    it("開始日が終了日より後の場合はエラーになり保存されない", async () => {
      const user = userEvent.setup();

      render(
        <QuickCreate
          module="Calendar"
          isOpen={true}
          initialData={{
            subject: "テストToDo",
            date_start: "2026-08-18T09:00",
            due_date: "2026-08-17",
          }}
        />,
      );

      await user.click(screen.getByRole("button", { name: /保存/i }));

      expect(await screen.findByText(END_DATE_ERROR)).toBeInTheDocument();
      expect(mockSave).not.toHaveBeenCalled();
    });
  });

  describe("活動（due_date が日時）", () => {
    it("終了日時が開始日時より前の場合はエラーになり保存されない", async () => {
      const user = userEvent.setup();

      render(
        <QuickCreate
          module="Events"
          isOpen={true}
          initialData={{
            subject: "テスト活動",
            date_start: "2026-08-17T15:00",
            due_date: "2026-08-17T14:30",
          }}
        />,
      );

      await user.click(screen.getByRole("button", { name: /保存/i }));

      expect(await screen.findByText(END_DATE_ERROR)).toBeInTheDocument();
      expect(mockSave).not.toHaveBeenCalled();
    });

    it("終了日時が開始日時より後の場合は保存できる", async () => {
      const user = userEvent.setup();

      render(
        <QuickCreate
          module="Events"
          isOpen={true}
          initialData={{
            subject: "テスト活動",
            date_start: "2026-08-17T14:30",
            due_date: "2026-08-17T15:00",
          }}
        />,
      );

      await user.click(screen.getByRole("button", { name: /保存/i }));

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });
      expect(screen.queryByText(END_DATE_ERROR)).not.toBeInTheDocument();
    });
  });
});

describe("QuickCreate (calendar variant) の終日フラグとタブの関係", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockSave.mockResolvedValue({ success: true, recordId: "123" });
  });

  it("終日エリアからの起動でも ToDo タブには開始時刻の入力欄が表示される", async () => {
    // カレンダーの終日エリアクリックでは initialData に is_allday=true が入る。
    // 終日は活動（Events）のみの概念で ToDo には終日チェックボックスも無いため、
    // ToDo タブで時刻入力欄が消えると解除する手段が無くなる。
    render(
      <QuickCreate
        module="Calendar"
        isOpen={true}
        initialData={{
          subject: "終日エリアからのToDo",
          date_start: "2026-08-17",
          due_date: "2026-08-17",
          is_allday: true,
        }}
      />,
    );

    await waitFor(() => {
      expect(screen.getByDisplayValue("終日エリアからのToDo")).toBeVisible();
    });

    // ToDo の完了日は日付のみ入力のため、時刻入力欄は開始日時の1つだけ
    expect(screen.getAllByPlaceholderText("--:--")).toHaveLength(1);
  });

  it("終日の活動から ToDo タブに切り替えると開始時刻の入力欄が表示される", async () => {
    const user = userEvent.setup();

    render(
      <QuickCreate
        module="Events"
        isOpen={true}
        initialData={{
          subject: "終日エリアからの起動",
          date_start: "2026-08-17",
          due_date: "2026-08-17",
          is_allday: true,
        }}
      />,
    );

    await waitFor(() => {
      expect(screen.getByDisplayValue("終日エリアからの起動")).toBeVisible();
    });
    expect(screen.queryAllByPlaceholderText("--:--")).toHaveLength(0);

    await user.click(screen.getByRole("button", { name: /ToDo/i }));

    expect(screen.getAllByPlaceholderText("--:--")).toHaveLength(1);
  });

  it("活動タブで終日の場合は時刻入力欄が表示されない", async () => {
    render(
      <QuickCreate
        module="Events"
        isOpen={true}
        initialData={{
          subject: "終日の活動",
          date_start: "2026-08-17",
          due_date: "2026-08-17",
          is_allday: true,
        }}
      />,
    );

    await waitFor(() => {
      expect(screen.getByDisplayValue("終日の活動")).toBeVisible();
    });

    expect(screen.queryAllByPlaceholderText("--:--")).toHaveLength(0);
  });
});
