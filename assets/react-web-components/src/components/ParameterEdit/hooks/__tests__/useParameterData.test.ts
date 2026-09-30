import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderHook, act } from "@testing-library/react";
import { useParameterData } from "../useParameterData";
import type { ParameterSaveApiResponse } from "../../types";

const mockFetch = vi.fn();
const mockPost = vi.fn();

/** app.request.post の戻り値（vtiger の Deferred 相当）を組み立てる */
const postResult = (error: unknown, data: ParameterSaveApiResponse) => ({
  then: (callback: (err: unknown, data: ParameterSaveApiResponse) => void) =>
    callback(error, data),
});

/** saveRecord が実際に送信したパラメータを取り出す */
const sentParams = (): Record<string, string> =>
  mockPost.mock.calls[0][0].data as Record<string, string>;

describe("useParameterData", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    // app は vtiger が提供するグローバル。テストでは差し替える
    (globalThis as unknown as { app: unknown }).app = {
      request: { post: mockPost },
    };
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
      mockPost.mockImplementation(() => postResult(null, { success: true }));
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
      expect(params).not.toHaveProperty("value");
      expect(params.id).toBe("2");
      expect(params.description).toBe("備考だけ変更");
      expect(params.secret).toBe("1");
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

      expect(sentParams()).toHaveProperty("value", "");
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

      expect(sentParams().value).toBe("45");
    });

    it("保存に失敗した場合は success:false とエラーメッセージを返す", async () => {
      mockPost.mockImplementation(() =>
        postResult({ message: "Invalid integer value" }, { success: false }),
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
