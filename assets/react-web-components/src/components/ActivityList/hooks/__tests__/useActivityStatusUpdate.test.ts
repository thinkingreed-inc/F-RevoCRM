import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderHook, act } from "@testing-library/react";
import { useActivityStatusUpdate } from "../useActivityStatusUpdate";

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

/**
 * CSRFトークン用の hidden input を DOM に用意する
 */
const setupCsrfToken = (token = "dummy-token") => {
  const input = document.createElement("input");
  input.type = "hidden";
  input.name = "__vtrftk";
  input.value = token;
  document.body.appendChild(input);
};

/**
 * fetch に渡された FormData を取得する
 */
const getSentFormData = (): FormData => {
  const [, init] = mockFetch.mock.calls[0];
  return init.body as FormData;
};

describe("useActivityStatusUpdate", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = "";
    setupCsrfToken();
    mockFetch.mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ success: true, result: {} }),
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = "";
  });

  describe("activitytype の送信", () => {
    it("行動(Meeting)のステータス更新時に activitytype を送信する", async () => {
      const { result } = renderHook(() => useActivityStatusUpdate());

      await act(async () => {
        await result.current.updateStatus(
          "165",
          "eventstatus",
          "Held",
          "Meeting",
        );
      });

      expect(getSentFormData().get("activitytype")).toBe("Meeting");
    });

    it("行動(Call)のステータス更新時に activitytype を送信する", async () => {
      const { result } = renderHook(() => useActivityStatusUpdate());

      await act(async () => {
        await result.current.updateStatus("160", "eventstatus", "Held", "Call");
      });

      expect(getSentFormData().get("activitytype")).toBe("Call");
    });

    it("ToDo(Task)のステータス更新時に activitytype を送信する", async () => {
      const { result } = renderHook(() => useActivityStatusUpdate());

      await act(async () => {
        await result.current.updateStatus(
          "163",
          "taskstatus",
          "Completed",
          "Task",
        );
      });

      expect(getSentFormData().get("activitytype")).toBe("Task");
    });
  });

  describe("calendarModule の判定", () => {
    it("Meeting は calendarModule=Events で送信する", async () => {
      const { result } = renderHook(() => useActivityStatusUpdate());

      await act(async () => {
        await result.current.updateStatus(
          "165",
          "eventstatus",
          "Held",
          "Meeting",
        );
      });

      expect(getSentFormData().get("calendarModule")).toBe("Events");
    });

    it("Task は calendarModule=Calendar で送信する", async () => {
      const { result } = renderHook(() => useActivityStatusUpdate());

      await act(async () => {
        await result.current.updateStatus(
          "163",
          "taskstatus",
          "Completed",
          "Task",
        );
      });

      expect(getSentFormData().get("calendarModule")).toBe("Calendar");
    });
  });
});
