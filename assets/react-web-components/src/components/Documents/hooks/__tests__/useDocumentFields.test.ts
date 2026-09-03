import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderHook, waitFor } from "@testing-library/react";
import { useDocumentFields } from "../useDocumentFields";

/** fetch のモック。GetFields API に応答する */
const mockFetch = vi.fn();
global.fetch = mockFetch as unknown as typeof fetch;

/** GetFields が返す項目（編集可能な項目と読み取り専用の入力期限） */
const API_FIELDS = [
  {
    name: "receipt_date",
    label: "受領日",
    uitype: "5",
    displaytype: 1,
    editable: true,
    block: "スキャナ保存",
  },
  {
    name: "input_deadline",
    label: "入力期限",
    uitype: "5",
    displaytype: 2,
    editable: false,
    block: "スキャナ保存",
  },
];

let requestedUrls: string[] = [];

beforeEach(() => {
  requestedUrls = [];
  mockFetch.mockImplementation((url: string) => {
    requestedUrls.push(url);
    // view=detail のときだけ読み取り専用項目を返すサーバー側の挙動を模す
    const includesReadonly = url.includes("view=detail");
    const fields = includesReadonly
      ? API_FIELDS
      : API_FIELDS.filter((f) => f.displaytype !== 2);
    return Promise.resolve({
      ok: true,
      status: 200,
      json: async () => ({ fields }),
    } as unknown as Response);
  });
});

afterEach(() => {
  vi.clearAllMocks();
});

describe("useDocumentFields", () => {
  it("詳細表示（includeReadonly）では view=detail を要求し、読み取り専用項目も返す", async () => {
    const { result } = renderHook(() => useDocumentFields(1886, true));

    await waitFor(() => expect(result.current.isLoading).toBe(false));

    expect(requestedUrls[0]).toContain("view=detail");
    expect(requestedUrls[0]).toContain("record=1886");
    expect(result.current.fields.map((f) => f.name)).toEqual([
      "receipt_date",
      "input_deadline",
    ]);

    const deadline = result.current.fields.find(
      (f) => f.name === "input_deadline",
    );
    expect(deadline?.label).toBe("入力期限");
    expect(deadline?.displaytype).toBe("2");
    expect(deadline?.blockLabel).toBe("スキャナ保存");
  });

  it("編集時は view=edit を要求し、読み取り専用項目は含めない", async () => {
    const { result } = renderHook(() => useDocumentFields(1886, false));

    await waitFor(() => expect(result.current.isLoading).toBe(false));

    expect(requestedUrls[0]).toContain("view=edit");
    expect(result.current.fields.map((f) => f.name)).toEqual(["receipt_date"]);
  });
});
