import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderHook, act } from "@testing-library/react";
import { useOverlapCheck } from "../useOverlapCheck";

const mockFetch = vi.fn();
global.fetch = mockFetch;

/** 成功レスポンスを組み立てる */
function okResponse(message: string) {
  return {
    ok: true,
    status: 200,
    json: async () => ({ success: true, result: { message } }),
  };
}

describe("useOverlapCheck", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    (window as any).csrfMagicName = "__vtrftk";
    (window as any).csrfMagicToken = "sid:dummy,1790000000";
  });

  afterEach(() => {
    vi.restoreAllMocks();
    delete (window as any).csrfMagicName;
    delete (window as any).csrfMagicToken;
  });

  describe("チェック対象の判定", () => {
    it("module が Events 以外の場合は問い合わせず null を返す", async () => {
      const { result } = renderHook(() => useOverlapCheck("Calendar"));

      let message: string | null = "dummy";
      await act(async () => {
        message = await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
        });
      });

      expect(message).toBeNull();
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it("module が空の場合は問い合わせず null を返す", async () => {
      const { result } = renderHook(() => useOverlapCheck(""));

      let message: string | null = "dummy";
      await act(async () => {
        message = await result.current.checkOverlap({
          date_start: "2026-10-15",
        });
      });

      expect(message).toBeNull();
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it("開始日時が未入力の場合は問い合わせず null を返す", async () => {
      const { result } = renderHook(() => useOverlapCheck("Events"));

      let message: string | null = "dummy";
      await act(async () => {
        message = await result.current.checkOverlap({ date_start: "" });
      });

      expect(message).toBeNull();
      expect(mockFetch).not.toHaveBeenCalled();
    });
  });

  describe("重複判定", () => {
    it("重複が無い場合は null を返す", async () => {
      mockFetch.mockResolvedValue(okResponse(""));

      const { result } = renderHook(() => useOverlapCheck("Events"));

      let message: string | null = "dummy";
      await act(async () => {
        message = await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
          due_date: "2026-10-15",
          time_end: "11:00",
          assigned_user_id: 1,
        });
      });

      expect(message).toBeNull();
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it("重複がある場合はメッセージHTMLを返す", async () => {
      mockFetch.mockResolvedValue(
        okResponse("<div>期間が重複している活動</div>"),
      );

      const { result } = renderHook(() => useOverlapCheck("Events"));

      let message: string | null = null;
      await act(async () => {
        message = await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
          due_date: "2026-10-15",
          time_end: "11:00",
          assigned_user_id: 1,
        });
      });

      expect(message).toBe("<div>期間が重複している活動</div>");
    });
  });

  describe("リクエスト内容", () => {
    it("Calendar モジュールの FetchOverlapEventsBeforeSave を呼ぶ", async () => {
      mockFetch.mockResolvedValue(okResponse(""));

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await act(async () => {
        await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
          due_date: "2026-10-15",
          time_end: "11:00",
        });
      });

      const [url, init] = mockFetch.mock.calls[0];
      expect(url).toContain("module=Calendar");
      expect(url).toContain("action=FetchOverlapEventsBeforeSave");
      expect(init.method).toBe("POST");

      const body = new URLSearchParams(init.body as string);
      expect(body.get("__vtrftk")).toBe("sid:dummy,1790000000");
      expect(body.get("date_start")).toBe("2026-10-15");
      expect(body.get("time_start")).toBe("10:00");
      expect(body.get("due_date")).toBe("2026-10-15");
      expect(body.get("time_end")).toBe("11:00");
    });

    it("date_start が日時形式の場合は日付と時刻に分割して送る", async () => {
      mockFetch.mockResolvedValue(okResponse(""));

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await act(async () => {
        await result.current.checkOverlap({
          date_start: "2026-10-15T10:00",
          due_date: "2026-10-15T11:00",
        });
      });

      const body = new URLSearchParams(mockFetch.mock.calls[0][1].body);
      expect(body.get("date_start")).toBe("2026-10-15");
      expect(body.get("time_start")).toBe("10:00");
      expect(body.get("due_date")).toBe("2026-10-15");
      expect(body.get("time_end")).toBe("11:00");
    });

    it("招待者は selectedusers[] 形式で送る", async () => {
      mockFetch.mockResolvedValue(okResponse(""));

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await act(async () => {
        await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
          selectedusers: [5, 6],
        });
      });

      const body = new URLSearchParams(mockFetch.mock.calls[0][1].body);
      expect(body.getAll("selectedusers[]")).toEqual(["5", "6"]);
    });

    it("終日の場合は is_allday を on として送る", async () => {
      mockFetch.mockResolvedValue(okResponse(""));

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await act(async () => {
        await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
          is_allday: true,
        });
      });

      const body = new URLSearchParams(mockFetch.mock.calls[0][1].body);
      expect(body.get("is_allday")).toBe("on");
    });

    it("空の値は送らない", async () => {
      mockFetch.mockResolvedValue(okResponse(""));

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await act(async () => {
        await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
          record_id: "",
          recurringtype: undefined,
        });
      });

      const body = new URLSearchParams(mockFetch.mock.calls[0][1].body);
      expect(body.has("record_id")).toBe(false);
      expect(body.has("recurringtype")).toBe(false);
    });
  });

  describe("エラー処理", () => {
    it("CSRFトークンが取得できない場合はエラーを投げる", async () => {
      delete (window as any).csrfMagicToken;

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await expect(
        result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
        }),
      ).rejects.toThrow();
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it("HTTPエラーの場合はエラーを投げる", async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: async () => ({ success: false }),
      });

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await expect(
        result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
        }),
      ).rejects.toThrow();
    });

    it("success が false の場合はエラーを投げる", async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({
          success: false,
          error: { message: "重複チェックに失敗しました" },
        }),
      });

      const { result } = renderHook(() => useOverlapCheck("Events"));

      await expect(
        result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
        }),
      ).rejects.toThrow("重複チェックに失敗しました");
    });
  });

  describe("isChecking", () => {
    it("問い合わせ完了後は false に戻る", async () => {
      mockFetch.mockResolvedValue(okResponse(""));

      const { result } = renderHook(() => useOverlapCheck("Events"));

      expect(result.current.isChecking).toBe(false);

      await act(async () => {
        await result.current.checkOverlap({
          date_start: "2026-10-15",
          time_start: "10:00",
        });
      });

      expect(result.current.isChecking).toBe(false);
    });
  });
});
