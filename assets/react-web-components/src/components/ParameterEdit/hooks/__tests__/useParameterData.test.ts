import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderHook, act } from "@testing-library/react";
import { useParameterData } from "../useParameterData";
import type { ParameterSaveApiResponse } from "../../types";

const mockFetch = vi.fn();

/** Save API のレスポンスを差し込む */
const givenSaveResponse = (
  body: ParameterSaveApiResponse,
  ok = true,
  status = 200,
) => {
  mockFetch.mockResolvedValue({ ok, status, json: async () => body });
};

/** saveRecord が実際に送信したパラメータを取り出す */
const sentParams = (): URLSearchParams => {
  const call = mockFetch.mock.calls.find(([, init]) => init?.method === "POST");
  return new URLSearchParams(String(call?.[1]?.body ?? ""));
};

describe("useParameterData", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    // csrf-magic が定義するグローバル。fetch では自分でトークンを付ける
    window.csrfMagicName = "__vtrftk";
    window.csrfMagicToken = "test-token";
    Object.defineProperty(window, "location", {
      value: {
        origin: "http://localhost",
        pathname: "/frevocrm/index.php",
      },
      writable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe("fetchRecord", () => {
    it("レコードを取得して data に保持する", async () => {
      const record = {
        id: 2,
        key: "USER_LOCK_TIME",
        value: "30",
        type: "integer",
        secret: 0,
        description: "ロック時間",
      };
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => ({ result: record }),
      });

      const { result } = renderHook(() => useParameterData());

      await act(async () => {
        await result.current.fetchRecord(2);
      });

      expect(result.current.data).toEqual(record);
      expect(result.current.error).toBeNull();
      // Settings 配下の API を parent 付きで呼ぶ
      expect(mockFetch.mock.calls[0][0]).toContain(
        "module=Parameters&parent=Settings&api=GetRecord&id=2",
      );
    });

    it("エラーレスポンスの場合は error にメッセージを保持する", async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 403,
        json: async () => ({ error: { message: "権限がありません" } }),
      });

      const { result } = renderHook(() => useParameterData());

      await act(async () => {
        await result.current.fetchRecord(2);
      });

      expect(result.current.data).toBeNull();
      expect(result.current.error).toBe("権限がありません");
    });
  });

  describe("saveRecord", () => {
    beforeEach(() => {
      givenSaveResponse({ saved: true });
    });

    it("value を省略した場合は value を送信しない（既存値を維持させる）", async () => {
      const { result } = renderHook(() => useParameterData());

      await act(async () => {
        await result.current.saveRecord({
          id: 2,
          description: "備考だけ変更",
          secret: 1,
        });
      });

      const params = sentParams();
      expect(params.has("value")).toBe(false);
      expect(params.get("id")).toBe("2");
      expect(params.get("description")).toBe("備考だけ変更");
      expect(params.get("secret")).toBe("1");
      // csrf-magic のトークンを自分で付けていること
      expect(params.get("__vtrftk")).toBe("test-token");
    });

    it("value に空文字を渡した場合は空文字として送信する", async () => {
      const { result } = renderHook(() => useParameterData());

      await act(async () => {
        await result.current.saveRecord({
          id: 2,
          value: "",
          description: "",
        });
      });

      expect(sentParams().get("value")).toBe("");
    });

    it("description を省略した場合は description を送信しない", async () => {
      const { result } = renderHook(() => useParameterData());

      await act(async () => {
        await result.current.saveRecord({ id: 2, description: undefined });
      });

      expect(sentParams().has("description")).toBe(false);
    });

    it("value を渡した場合はその値を送信する", async () => {
      const { result } = renderHook(() => useParameterData());

      await act(async () => {
        await result.current.saveRecord({
          id: 2,
          value: "45",
          description: "説明",
        });
      });

      expect(sentParams().get("value")).toBe("45");
    });

    it("保存に失敗した場合は success:false とエラーメッセージを返す", async () => {
      // エラー時は Vtiger_Response が { success: false, error: {...} } を返す
      givenSaveResponse(
        { success: false, error: { message: "Invalid integer value" } },
        false,
        400,
      );

      const { result } = renderHook(() => useParameterData());

      let response;
      await act(async () => {
        response = await result.current.saveRecord({
          id: 2,
          value: "abc",
          description: "",
        });
      });

      expect(response).toEqual({
        success: false,
        error: "Invalid integer value",
      });
      expect(result.current.error).toBe("Invalid integer value");
    });
  });
});
