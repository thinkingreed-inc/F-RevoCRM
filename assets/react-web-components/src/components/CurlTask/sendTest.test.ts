import { describe, it, expect, afterEach } from "vitest";
import { defaultSendTest, stringifyError } from "./sendTest";

const payload = {
  url: "https://example.test/hook",
  method: "POST",
  headers: "",
  body: "{}",
  timeout: "30",
};

describe("defaultSendTest", () => {
  afterEach(() => {
    delete (window as unknown as { app?: unknown }).app;
  });

  it("app.request.post をメソッドとして呼び this を保つ", async () => {
    // F-RevoCRM の app.request.post は内部で this._request を参照する。
    // post を変数へ切り出して呼ぶと this が失われ
    // "this._request is not a function" になるため、その回帰を防ぐ。
    const request = {
      _request: () => undefined,
      post(params: { data: Record<string, string> }) {
        if (typeof (this as typeof request)?._request !== "function") {
          throw new TypeError("this._request is not a function");
        }
        expect(params.data.action).toBe("TestCurlAjax");
        expect(params.data.url).toBe(payload.url);
        return {
          then: (onDone: (err: unknown, data: unknown) => void) =>
            onDone(null, { success: true, http_code: 200 }),
        };
      },
    };
    (window as unknown as { app?: unknown }).app = { request };

    await expect(defaultSendTest()(payload)).resolves.toEqual({
      success: true,
      http_code: 200,
    });
  });

  it("app.request が無い場合はエラーを返す", async () => {
    await expect(defaultSendTest()(payload)).resolves.toEqual({
      success: false,
      error: "app.request is not available",
    });
  });

  it("応答が null の場合はエラーにする", async () => {
    (window as unknown as { app?: unknown }).app = {
      request: {
        post: () => ({
          then: (onDone: (err: unknown, data: unknown) => void) =>
            onDone(null, null),
        }),
      },
    };
    await expect(defaultSendTest()(payload)).resolves.toEqual({
      success: false,
      error: "サーバから応答がありません",
    });
  });
});

describe("stringifyError", () => {
  it("responseText を優先する", () => {
    expect(stringifyError({ responseText: "boom", message: "x" })).toBe("boom");
  });
  it("message にフォールバックする", () => {
    expect(stringifyError({ message: "x" })).toBe("x");
  });
  it("null は既定文言にする", () => {
    expect(stringifyError(null)).toBe("不明なエラー");
  });
});
