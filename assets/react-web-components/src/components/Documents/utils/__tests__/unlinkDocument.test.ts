import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { unlinkDocument } from "../unlinkDocument";

/**
 * 紐づけ解除（TS-13 ドキュメント関連付け）
 *
 * 解除できなかったことを成功扱いにすると、画面上は消えたのに紐づけが残る。
 * 解除0件・権限なし・エラー応答をそれぞれ失敗として返せることを担保する。
 */

const mockFetch = vi.fn();
global.fetch = mockFetch as unknown as typeof fetch;

function jsonResponse(body: unknown) {
  const text = JSON.stringify(body);
  return {
    ok: true,
    status: 200,
    text: async () => text,
    json: async () => JSON.parse(text),
  } as unknown as Response;
}

/** 直近のリクエストのボディ */
function lastBody(): URLSearchParams {
  const call = mockFetch.mock.calls[mockFetch.mock.calls.length - 1];
  return new URLSearchParams(String((call[1] as RequestInit).body));
}

const PARAMS = {
  parentModule: "ServiceContracts",
  parentId: 42,
  recordId: 100,
};

describe("unlinkDocument", () => {
  beforeEach(() => {
    mockFetch.mockReset();
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("RelationAPI の unlink を親レコードとドキュメントID付きで呼ぶ", async () => {
    mockFetch.mockResolvedValue(
      jsonResponse({ success: true, result: { unlinked: 1, denied: 0 } }),
    );

    const result = await unlinkDocument(PARAMS);

    const body = lastBody();
    expect(body.get("module")).toBe("Documents");
    expect(body.get("api")).toBe("RelationAPI");
    expect(body.get("mode")).toBe("unlink");
    expect(body.get("parent_module")).toBe("ServiceContracts");
    expect(body.get("parent_id")).toBe("42");
    expect(body.getAll("records[]")).toEqual(["100"]);
    expect(result.ok).toBe(true);
    expect(result.unlinked).toBe(1);
  });

  it("解除0件は失敗として返す", async () => {
    mockFetch.mockResolvedValue(
      jsonResponse({ success: true, result: { unlinked: 0, denied: 0 } }),
    );

    const result = await unlinkDocument(PARAMS);

    expect(result.ok).toBe(false);
    expect(result.unlinked).toBe(0);
  });

  it("参照権限が無い場合は denied を返す", async () => {
    mockFetch.mockResolvedValue(
      jsonResponse({ success: true, result: { unlinked: 0, denied: 1 } }),
    );

    const result = await unlinkDocument(PARAMS);

    expect(result.ok).toBe(false);
    expect(result.denied).toBe(1);
  });

  it("エラー応答はメッセージ付きで失敗として返す", async () => {
    mockFetch.mockResolvedValue(
      jsonResponse({
        success: false,
        error: { message: "紐づけ先のレコードが見つかりません" },
      }),
    );

    const result = await unlinkDocument(PARAMS);

    expect(result.ok).toBe(false);
    expect(result.message).toBe("紐づけ先のレコードが見つかりません");
  });

  it("通信に失敗しても例外を投げず失敗として返す", async () => {
    mockFetch.mockRejectedValue(new Error("Network down"));

    const result = await unlinkDocument(PARAMS);

    expect(result.ok).toBe(false);
    expect(result.message).toBe("Network down");
  });
});
