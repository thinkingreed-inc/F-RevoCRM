import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
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
/** 期間重複チェック。既定では重複なし（null）を返す */
const mockCheckOverlap = vi.fn<
  (module: string, formData: Record<string, unknown>) => Promise<string | null>
>(async () => null);

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

// 問い合わせ中に isChecking が true になる点まで本物と揃える。
// ここを固定値にすると「チェック中はボタンを無効化する」挙動を検証できない
vi.mock("./hooks/useOverlapCheck", async () => {
  const { useState, useCallback } = await import("react");
  return {
    useOverlapCheck: (module: string) => {
      const [isChecking, setIsChecking] = useState(false);
      const checkOverlap = useCallback(
        async (formData: Record<string, unknown>) => {
          setIsChecking(true);
          try {
            return await mockCheckOverlap(module, formData);
          } finally {
            setIsChecking(false);
          }
        },
        [module],
      );
      return { checkOverlap, isChecking };
    },
  };
});

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
    mockCheckOverlap.mockResolvedValue(null);
  });

  afterEach(() => {
    // Radix Dialog が body に残すスクロールロックを戻す。
    // 残っているとクリックが pointer-events: none で無視され、後続テストが落ちる
    document.body.style.pointerEvents = "";
    document.body.removeAttribute("data-scroll-locked");
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

  describe("期間の重複チェック", () => {
    const OVERLAP_MESSAGE_TEXT = "期間が重複している活動";
    const overlapHtml = `<div>${OVERLAP_MESSAGE_TEXT}</div>`;

    /** 活動タブで重複する日時を入力した QuickCreate を描画する */
    function renderEventsQuickCreate() {
      return render(
        <QuickCreate
          module="Events"
          isOpen={true}
          initialData={{
            subject: "重複する活動",
            date_start: "2026-08-17T14:30",
            due_date: "2026-08-17T15:00",
          }}
        />,
      );
    }

    it("重複が無ければ確認ダイアログを出さずに保存する", async () => {
      const user = userEvent.setup();
      mockCheckOverlap.mockResolvedValue(null);

      renderEventsQuickCreate();

      await user.click(screen.getByRole("button", { name: /保存/i }));

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });
      expect(screen.queryByText(OVERLAP_MESSAGE_TEXT)).not.toBeInTheDocument();
    });

    it("重複がある場合は確認ダイアログを表示し、すぐには保存しない", async () => {
      const user = userEvent.setup();
      mockCheckOverlap.mockResolvedValue(overlapHtml);

      renderEventsQuickCreate();

      await user.click(screen.getByRole("button", { name: /保存/i }));

      expect(await screen.findByText(OVERLAP_MESSAGE_TEXT)).toBeInTheDocument();
      expect(mockSave).not.toHaveBeenCalled();
    });

    it("確認ダイアログで「はい」を選ぶと保存する", async () => {
      const user = userEvent.setup();
      mockCheckOverlap.mockResolvedValue(overlapHtml);

      renderEventsQuickCreate();

      await user.click(screen.getByRole("button", { name: /保存/i }));
      await screen.findByText(OVERLAP_MESSAGE_TEXT);

      await user.click(screen.getByRole("button", { name: "はい" }));

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });
      // 「はい」を選んだあとは重複チェックを繰り返さない
      expect(mockCheckOverlap).toHaveBeenCalledTimes(1);
    });

    it("確認ダイアログで「いいえ」を選ぶと保存しない", async () => {
      const user = userEvent.setup();
      mockCheckOverlap.mockResolvedValue(overlapHtml);

      renderEventsQuickCreate();

      await user.click(screen.getByRole("button", { name: /保存/i }));
      await screen.findByText(OVERLAP_MESSAGE_TEXT);

      await user.click(screen.getByRole("button", { name: "いいえ" }));

      await waitFor(() => {
        expect(
          screen.queryByText(OVERLAP_MESSAGE_TEXT),
        ).not.toBeInTheDocument();
      });
      expect(mockSave).not.toHaveBeenCalled();
    });

    it("確認を表示したままモーダルを閉じると確認状態が残らない", async () => {
      const user = userEvent.setup();
      mockCheckOverlap.mockResolvedValue(overlapHtml);

      const { rerender } = render(
        <QuickCreate
          module="Events"
          isOpen={true}
          initialData={{
            subject: "重複する活動",
            date_start: "2026-08-17T14:30",
            due_date: "2026-08-17T15:00",
          }}
        />,
      );

      await user.click(screen.getByRole("button", { name: /保存/i }));
      await screen.findByText(OVERLAP_MESSAGE_TEXT);

      // 確認を出したままモーダルを閉じる
      rerender(
        <QuickCreate
          module="Events"
          isOpen={false}
          initialData={{
            subject: "重複する活動",
            date_start: "2026-08-17T14:30",
            due_date: "2026-08-17T15:00",
          }}
        />,
      );

      // 開き直したときに前回の確認が残っていないこと
      rerender(
        <QuickCreate
          module="Events"
          isOpen={true}
          initialData={{
            subject: "重複する活動",
            date_start: "2026-08-17T14:30",
            due_date: "2026-08-17T15:00",
          }}
        />,
      );

      await waitFor(() => {
        expect(
          screen.queryByText(OVERLAP_MESSAGE_TEXT),
        ).not.toBeInTheDocument();
      });
      expect(mockSave).not.toHaveBeenCalled();
    });

    it("重複チェックが失敗した場合は保存せずエラーを表示する", async () => {
      const user = userEvent.setup();
      mockCheckOverlap.mockRejectedValue(
        new Error("重複チェックに失敗しました"),
      );

      renderEventsQuickCreate();

      await user.click(screen.getByRole("button", { name: /保存/i }));

      expect(
        await screen.findByText("重複チェックに失敗しました"),
      ).toBeInTheDocument();
      expect(mockSave).not.toHaveBeenCalled();
    });

    it("重複チェックの問い合わせ中は保存ボタンを押せない", async () => {
      const user = userEvent.setup();
      let resolveCheck: (message: string | null) => void = () => {};
      mockCheckOverlap.mockImplementation(
        () =>
          new Promise<string | null>((resolve) => {
            resolveCheck = resolve;
          }),
      );

      renderEventsQuickCreate();

      const saveButton = screen.getByRole("button", { name: /保存/i });
      await user.click(saveButton);

      // 応答待ちの間に押せてしまうと、重複確認を経ずに二重登録される
      await waitFor(() => {
        expect(saveButton).toBeDisabled();
      });

      resolveCheck(null);

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalledTimes(1);
      });
    });

    it("問い合わせ中に保存を連打しても保存は一度だけ走る", async () => {
      const user = userEvent.setup();
      let resolveCheck: (message: string | null) => void = () => {};
      mockCheckOverlap.mockImplementation(
        () =>
          new Promise<string | null>((resolve) => {
            resolveCheck = resolve;
          }),
      );

      renderEventsQuickCreate();

      const saveButton = screen.getByRole("button", { name: /保存/i });
      await user.click(saveButton);
      await user.click(saveButton);
      await user.click(saveButton);

      expect(mockCheckOverlap).toHaveBeenCalledTimes(1);

      resolveCheck(null);

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalledTimes(1);
      });
    });

    /** 応答を手動で解決できるようにした checkOverlap を仕込む */
    function deferCheckOverlap() {
      let resolveCheck: (message: string | null) => void = () => {};
      let rejectCheck: (reason: Error) => void = () => {};
      mockCheckOverlap.mockImplementation(
        () =>
          new Promise<string | null>((resolve, reject) => {
            resolveCheck = resolve;
            rejectCheck = reject;
          }),
      );
      return {
        resolve: (message: string | null) => resolveCheck(message),
        reject: (reason: Error) => rejectCheck(reason),
      };
    }

    const eventsProps = {
      module: "Events" as const,
      initialData: {
        subject: "重複する活動",
        date_start: "2026-08-17T14:30",
        due_date: "2026-08-17T15:00",
      },
    };

    it("問い合わせ中にモーダルを閉じると、開き直したときに確認が残らない", async () => {
      const user = userEvent.setup();
      const deferred = deferCheckOverlap();

      const { rerender } = render(<QuickCreate {...eventsProps} isOpen={true} />);
      await user.click(screen.getByRole("button", { name: /保存/i }));

      // 応答を待っている途中で閉じる
      rerender(<QuickCreate {...eventsProps} isOpen={false} />);

      // 閉じたあとに「重複あり」が返ってくる
      deferred.resolve(overlapHtml);
      await new Promise((r) => setTimeout(r, 0));

      rerender(<QuickCreate {...eventsProps} isOpen={true} />);

      await waitFor(() => {
        expect(
          screen.queryByText(OVERLAP_MESSAGE_TEXT),
        ).not.toBeInTheDocument();
      });
      expect(mockSave).not.toHaveBeenCalled();
    });

    it("問い合わせ中にモーダルを閉じると、開き直したときにエラーが残らない", async () => {
      const user = userEvent.setup();
      const deferred = deferCheckOverlap();

      const { rerender } = render(<QuickCreate {...eventsProps} isOpen={true} />);
      await user.click(screen.getByRole("button", { name: /保存/i }));

      rerender(<QuickCreate {...eventsProps} isOpen={false} />);

      // 閉じたあとに問い合わせが失敗する
      deferred.reject(new Error("重複チェックに失敗しました"));
      await new Promise((r) => setTimeout(r, 0));

      rerender(<QuickCreate {...eventsProps} isOpen={true} />);

      await waitFor(() => {
        expect(
          screen.queryByText("重複チェックに失敗しました"),
        ).not.toBeInTheDocument();
      });
    });

    // ToDo（Calendar）が重複チェックの対象外であることは
    // useOverlapCheck の単体テスト（module が Events 以外なら問い合わせない）で担保する
  });
});
