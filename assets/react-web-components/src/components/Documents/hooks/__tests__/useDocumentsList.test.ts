import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderHook, waitFor } from "@testing-library/react";
import { useDocumentsList } from "../useDocumentsList";

/**
 * 一覧の絞り込みパラメータ（TS-03 / TS-08 / TS-10）
 *
 * ListAPI へ渡す絞り込み条件が漏れなく送られることを担保する。
 * 特に入力期限状態（input_deadline_status）は「期限超過だけを出す」操作の要。
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

/** 直近のリクエストのボディを URLSearchParams として読む */
function lastRequestParams(): URLSearchParams {
  const call = mockFetch.mock.calls[mockFetch.mock.calls.length - 1];
  const init = call[1] as RequestInit;
  return new URLSearchParams(String(init.body));
}

describe("useDocumentsList - 絞り込みパラメータ", () => {
  beforeEach(() => {
    mockFetch.mockReset();
    mockFetch.mockImplementation(() =>
      Promise.resolve(
        jsonResponse({ success: true, result: { records: [], total: 0 } }),
      ),
    );
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("入力期限状態を指定すると input_deadline_status を送る", async () => {
    renderHook(() =>
      useDocumentsList({
        complianceFilter: true,
        inputDeadlineStatus: "overdue",
      }),
    );
    await waitFor(() => expect(mockFetch).toHaveBeenCalled());

    const params = lastRequestParams();
    expect(params.get("api")).toBe("ListAPI");
    expect(params.get("input_deadline_status")).toBe("overdue");
  });

  it("入力期限状態が未指定なら input_deadline_status を送らない", async () => {
    renderHook(() =>
      useDocumentsList({ complianceFilter: true, inputDeadlineStatus: "" }),
    );
    await waitFor(() => expect(mockFetch).toHaveBeenCalled());

    expect(lastRequestParams().has("input_deadline_status")).toBe(false);
  });

  it("入力期限状態を変えると再取得する", async () => {
    const { rerender } = renderHook(
      (props: { inputDeadlineStatus: string }) => useDocumentsList(props),
      { initialProps: { inputDeadlineStatus: "warning" } },
    );
    await waitFor(() => expect(mockFetch).toHaveBeenCalledTimes(1));
    expect(lastRequestParams().get("input_deadline_status")).toBe("warning");

    rerender({ inputDeadlineStatus: "overdue" });
    await waitFor(() => expect(mockFetch).toHaveBeenCalledTimes(2));
    expect(lastRequestParams().get("input_deadline_status")).toBe("overdue");
  });

  it("他の電帳法フィルターと併用できる", async () => {
    renderHook(() =>
      useDocumentsList({
        complianceFilter: true,
        documentCategory: "invoice",
        complianceStatus: "non_compliant",
        hasRelatedRecord: "false",
        inputDeadlineStatus: "overdue",
      }),
    );
    await waitFor(() => expect(mockFetch).toHaveBeenCalled());

    const params = lastRequestParams();
    expect(params.get("compliance_filter")).toBe("1");
    expect(params.get("document_category")).toBe("invoice");
    expect(params.get("compliance_status")).toBe("non_compliant");
    expect(params.get("has_related_record")).toBe("false");
    expect(params.get("input_deadline_status")).toBe("overdue");
  });
});
