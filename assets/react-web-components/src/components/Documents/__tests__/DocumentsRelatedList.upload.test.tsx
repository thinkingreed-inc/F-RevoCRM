import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { DocumentsRelatedList } from "../DocumentsRelatedList";
import { clearTranslationCache } from "../../../utils/translations";

const MAX_UPLOAD_FILES_JA =
  "一度にアップロードできるファイルは最大%s件です（%s件選択されました）";
const UPLOADING_COUNT_JA = "アップロード中... %s / %s件（%s%）";
const UPLOAD_START_JA = "アップロード";

const mockFetch = vi.fn();
global.fetch = mockFetch as unknown as typeof fetch;

function jsonResponse(body: unknown) {
  const text = JSON.stringify(body);
  return {
    ok: true,
    status: 200,
    statusText: "OK",
    text: async () => text,
    json: async () => JSON.parse(text),
  } as unknown as Response;
}

/** 翻訳・フォルダツリー・関連一覧のいずれにも応答する */
function fetchImpl(url: string, init?: RequestInit): Promise<Response> {
  const u = String(url);
  if (u.includes("api=GetTranslations")) {
    return Promise.resolve(
      jsonResponse({
        module: "Documents",
        language: "ja_jp",
        translations: {
          Documents: {
            LBL_MAX_UPLOAD_FILES: MAX_UPLOAD_FILES_JA,
            LBL_UPLOADING_COUNT: UPLOADING_COUNT_JA,
            LBL_UPLOAD_START: UPLOAD_START_JA,
          },
        },
        timestamp: "",
      }),
    );
  }
  if (u.includes("api=FolderAPI")) {
    return Promise.resolve(
      jsonResponse({
        success: true,
        result: { folders: [], totalCount: 0, starredCount: 0 },
      }),
    );
  }
  const body = init?.body;
  if (typeof body === "string" && body.includes("api=ListAPI")) {
    return Promise.resolve(
      jsonResponse({ success: true, result: { records: [], total: 0 } }),
    );
  }
  if (typeof body === "string" && body.includes("api=DuplicateCheck")) {
    return Promise.resolve(
      jsonResponse({ success: true, result: { duplicates: [] } }),
    );
  }
  if (body instanceof FormData) {
    // ChunkUpload API。init は chunk_size を必ず返す（0 や未定義だと送信側が進まない）
    if (String(body.get("mode")) === "init") {
      return Promise.resolve(
        jsonResponse({
          success: true,
          result: { upload_id: "upload-1", chunk_size: 1024 },
        }),
      );
    }
    return Promise.resolve(jsonResponse({ success: true, result: {} }));
  }
  return Promise.resolve(jsonResponse({ success: true, result: {} }));
}

function makeFiles(count: number): File[] {
  return Array.from(
    { length: count },
    (_, i) => new File(["x"], `file-${i}.txt`, { type: "text/plain" }),
  );
}

function dropFiles(target: Element, files: File[]) {
  fireEvent.drop(target, { dataTransfer: { files, types: ["Files"] } });
}

describe("DocumentsRelatedList - 最大ファイル数エラーの表示", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // モジュールレベルのキャッシュをテスト間で共有しない
    clearTranslationCache();
    mockFetch.mockImplementation(fetchImpl);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("上限+1件ドロップすると、件数入りの翻訳済みメッセージを表示する", async () => {
    const { container } = render(
      <DocumentsRelatedList parentModule="Accounts" parentId={1} />,
    );
    // 翻訳の読み込みを待つ
    await waitFor(() =>
      expect(
        mockFetch.mock.calls.some(([u]) =>
          String(u).includes("api=GetTranslations"),
        ),
      ).toBe(true),
    );
    const uploadCallCount = () =>
      mockFetch.mock.calls.filter(
        ([, init]) =>
          (init as RequestInit | undefined)?.body instanceof FormData,
      ).length;

    dropFiles(container.firstElementChild as Element, makeFiles(501));

    expect(
      await screen.findByText(
        "一度にアップロードできるファイルは最大500件です（501件選択されました）",
      ),
    ).toBeInTheDocument();
    // 翻訳キーが素のまま出ない
    expect(screen.queryByText("LBL_MAX_UPLOAD_FILES")).toBeNull();
    // 1件も送信しない
    expect(uploadCallCount()).toBe(0);
  });

  it("上限以内ではエラーを表示しない", async () => {
    const { container } = render(
      <DocumentsRelatedList parentModule="Accounts" parentId={1} />,
    );
    await waitFor(() =>
      expect(
        mockFetch.mock.calls.some(([u]) =>
          String(u).includes("api=GetTranslations"),
        ),
      ).toBe(true),
    );

    dropFiles(container.firstElementChild as Element, makeFiles(3));

    // 登録先フォルダを選んでから送信する
    fireEvent.click(
      await screen.findByRole("button", { name: UPLOAD_START_JA }),
    );

    await waitFor(() =>
      expect(
        mockFetch.mock.calls.some(
          ([, init]) =>
            (init as RequestInit | undefined)?.body instanceof FormData,
        ),
      ).toBe(true),
    );
    expect(screen.queryByText(/最大500件です/)).toBeNull();
    expect(screen.queryByText("LBL_MAX_UPLOAD_FILES")).toBeNull();
  });
});
