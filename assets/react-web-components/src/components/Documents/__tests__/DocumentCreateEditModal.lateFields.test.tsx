import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { DocumentCreateEditModal } from "../DocumentCreateEditModal";
import { TranslationProvider } from "../../../contexts/TranslationContext";
import type { DocumentDetail } from "../types/documents";

/**
 * 項目定義（GetFields）が開いた後に遅れて届いても、入力済みの内容を消さない
 *
 * 以前は項目定義の到着でフォーム全体を初期化し直していたため、
 * サーバーの応答が遅いと、入力したタイトルや選択したファイルが消えていた
 * （新規ではファイル名がタイトルに入ったまま保存され、編集では元の値に戻る）。
 */

const TRANSLATIONS: Record<string, string> = {
  LBL_DOC_TYPE_FILE: "ファイル",
  LBL_DOC_TYPE_URL: "URL",
  LBL_DOC_TYPE_NOTE: "メモ",
  Title: "タイトル",
  LBL_SAVE: "保存",
};

const FIELDS = [
  {
    name: "extra_note",
    label: "追加メモ",
    uitype: 1,
    displaytype: 1,
    block: "追加情報",
    editable: true,
    defaultValue: "既定のメモ",
  },
];

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

/** GetFields の応答を、テストが resolveFields() を呼ぶまで止めておく */
let resolveFields: () => void = () => {};

function fetchImpl(url: string): Promise<Response> {
  if (String(url).includes("api=GetFields")) {
    return new Promise((resolve) => {
      resolveFields = () => resolve(jsonResponse({ fields: FIELDS }));
    });
  }
  return Promise.resolve(jsonResponse({ success: true, result: {} }));
}

const FOLDERS = [
  {
    id: 1,
    name: "Default",
    description: "",
    parent_id: 0,
    sequence: 1,
    count: 0,
  },
];

function renderModal(mode: "create" | "edit", doc?: DocumentDetail) {
  return render(
    <TranslationProvider module="Documents" initialTranslations={TRANSLATIONS}>
      <DocumentCreateEditModal
        isOpen
        mode={mode}
        document={doc}
        folders={FOLDERS}
        defaultFolderId={1}
        onSave={() => {}}
        onClose={() => {}}
      />
    </TranslationProvider>,
  );
}

function titleInput(): HTMLInputElement {
  return screen.getByTestId("document-title-input") as HTMLInputElement;
}

async function deliverFields() {
  await act(async () => {
    resolveFields();
  });
  await screen.findByText("追加情報");
}

describe("DocumentCreateEditModal - 項目定義が遅れて届く場合", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockImplementation(fetchImpl);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("新規: 入力したタイトルと選択したファイルが残り、既定値も入る", async () => {
    renderModal("create");

    fireEvent.change(titleInput(), { target: { value: "請求書2026" } });
    const file = new File(["hello"], "sample.txt", { type: "text/plain" });
    fireEvent.change(screen.getByTestId("document-file-input"), {
      target: { files: [file] },
    });
    expect(screen.getByText("sample.txt")).toBeTruthy();

    await deliverFields();

    expect(titleInput().value).toBe("請求書2026");
    expect(screen.getByText("sample.txt")).toBeTruthy();
    expect(screen.getByDisplayValue("既定のメモ")).toBeTruthy();
  });

  it("編集: 書き換えたタイトルが元の値に戻らない", async () => {
    const doc = {
      id: 10,
      title: "元のタイトル",
      filename: "sample.txt",
      filelocationtype: "I",
      folderid: 1,
      notecontent: "",
      fileversion: "",
      filestatus: 1,
      compliance: null,
      dynamic_fields: { extra_note: "" },
    } as unknown as DocumentDetail;
    renderModal("edit", doc);

    expect(titleInput().value).toBe("元のタイトル");
    fireEvent.change(titleInput(), { target: { value: "新しいタイトル" } });

    await deliverFields();

    expect(titleInput().value).toBe("新しいタイトル");
  });
});
