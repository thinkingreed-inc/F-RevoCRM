import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { deleteDocument, extractDeleteError } from "../deleteDocument";

/**
 * 削除の応答判定（TS-05 / TS-14）
 *
 * 応答を見ずに成功扱いにすると、削除できていないのに画面が閉じてしまう。
 */

const mockFetch = vi.fn();
global.fetch = mockFetch as unknown as typeof fetch;

function response(body: string, ok = true) {
  return {
    ok,
    status: ok ? 200 : 500,
    text: async () => body,
  } as unknown as Response;
}

describe("extractDeleteError", () => {
  it("success:false のメッセージを取り出す", () => {
    expect(
      extractDeleteError(
        JSON.stringify({
          success: false,
          error: { message: "電帳法対象のドキュメントは削除できません" },
        }),
      ),
    ).toBe("電帳法対象のドキュメントは削除できません");
  });

  it("error だけがある場合も取り出す", () => {
    expect(
      extractDeleteError(JSON.stringify({ error: { code: "DENIED" } })),
    ).toBe("DENIED");
  });

  it("成功の応答は null", () => {
    expect(
      extractDeleteError(JSON.stringify({ success: true, result: {} })),
    ).toBeNull();
  });

  it("JSON でない応答（リダイレクトのHTML等）は成功扱い", () => {
    expect(extractDeleteError("<html>...</html>")).toBeNull();
    expect(extractDeleteError("")).toBeNull();
  });
});

describe("deleteDocument", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("削除できた場合は ok を返す", async () => {
    mockFetch.mockResolvedValue(
      response(JSON.stringify({ success: true, result: {} })),
    );

    await expect(deleteDocument(1)).resolves.toEqual({ ok: true, message: "" });
  });

  it("サーバーが拒否した場合はメッセージを返す", async () => {
    mockFetch.mockResolvedValue(
      response(
        JSON.stringify({
          success: false,
          error: { message: "電帳法対象のドキュメントは削除できません" },
        }),
      ),
    );

    await expect(deleteDocument(1)).resolves.toEqual({
      ok: false,
      message: "電帳法対象のドキュメントは削除できません",
    });
  });

  it("HTTP エラーは理由不明の失敗として返す", async () => {
    mockFetch.mockResolvedValue(response("", false));

    await expect(deleteDocument(1)).resolves.toEqual({
      ok: false,
      message: "",
    });
  });

  it("DeleteAjax に対象レコードを渡す", async () => {
    mockFetch.mockResolvedValue(response(JSON.stringify({ success: true })));

    await deleteDocument(42);

    const body = new URLSearchParams(
      (mockFetch.mock.calls[0][1] as RequestInit).body as string,
    );
    expect(body.get("module")).toBe("Documents");
    expect(body.get("action")).toBe("DeleteAjax");
    expect(body.get("record")).toBe("42");
  });
});
