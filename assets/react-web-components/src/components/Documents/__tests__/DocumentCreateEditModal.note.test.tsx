import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { DocumentCreateEditModal } from "../DocumentCreateEditModal";
import { TranslationProvider } from "../../../contexts/TranslationContext";

/**
 * メモだけのドキュメント（旧UIの「Webドキュメント」= type=W）
 *
 * ファイルもURLも持たないドキュメントを新UIからも登録できるようにしている。
 * 画面上は「メモ」種別だが、保存時は filelocationtype = I（ファイル無し）で送る。
 */

const NOTE_REQUIRED_JA = "メモを入力してください";

const TRANSLATIONS: Record<string, string> = {
  LBL_DOC_TYPE_FILE: "ファイル",
  LBL_DOC_TYPE_URL: "URL",
  LBL_DOC_TYPE_NOTE: "メモ",
  LBL_NOTE_REQUIRED: NOTE_REQUIRED_JA,
  LBL_DOCUMENT_TYPE: "ドキュメント種別",
  Title: "タイトル",
  Note: "メモ",
  LBL_SAVE: "保存",
};

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

const FIELDS = [
  {
    name: "extra_note",
    label: "追加メモ",
    uitype: 1,
    displaytype: 1,
    block: "追加情報",
    editable: true,
  },
];

/** 保存時に送られた本文を集める */
const savedBodies: string[] = [];

function fetchImpl(url: string, init?: RequestInit): Promise<Response> {
  if (String(url).includes("api=GetFields")) {
    return Promise.resolve(jsonResponse({ fields: FIELDS }));
  }
  const body = init?.body;
  if (body instanceof FormData && String(body.get("mode")) === "info") {
    return Promise.resolve(
      jsonResponse({
        success: true,
        result: {
          chunk_size: 1024,
          max_size: 10 * 1024 * 1024,
          max_size_label: "10 MB",
          single_request_limit: 1024,
        },
      }),
    );
  }
  if (typeof body === "string") {
    savedBodies.push(body);
  }
  return Promise.resolve(jsonResponse({ success: true, result: {} }));
}

function renderModal(onSave = () => {}) {
  return render(
    <TranslationProvider module="Documents" initialTranslations={TRANSLATIONS}>
      <DocumentCreateEditModal
        isOpen
        mode="create"
        folders={[
          {
            id: 1,
            name: "Default",
            description: "",
            parent_id: 0,
            sequence: 1,
            count: 0,
          },
        ]}
        defaultFolderId={1}
        onSave={onSave}
        onClose={() => {}}
      />
    </TranslationProvider>,
  );
}

/** 種別ボタンを押す（項目定義の読み込み後） */
async function selectNoteType() {
  await screen.findByText("追加情報");
  fireEvent.click(screen.getByRole("button", { name: "メモ" }));
}

function setTitle(value: string) {
  fireEvent.change(screen.getByTestId("document-title-input"), {
    target: { value },
  });
}

function noteTextarea(): HTMLTextAreaElement {
  const textareas = document.querySelectorAll("textarea");
  return textareas[textareas.length - 1] as HTMLTextAreaElement;
}

describe("DocumentCreateEditModal - メモだけのドキュメント", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    savedBodies.length = 0;
    mockFetch.mockImplementation(fetchImpl);
    // 保存は CSRF トークンを要求する（本体が画面に埋め込む値）
    (window as { csrfMagicName?: string }).csrfMagicName = "__csrf_magic";
    (window as { csrfMagicToken?: string }).csrfMagicToken = "sid:test";
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("種別に「メモ」を選べる", async () => {
    renderModal();
    await selectNoteType();

    expect(screen.getByRole("button", { name: "メモ" })).toBeTruthy();
  });

  it("メモが空なら保存せずにエラーを出す", async () => {
    renderModal();
    await selectNoteType();
    setTitle("メモだけの資料");

    fireEvent.click(screen.getByText("保存"));

    expect(await screen.findByText(NOTE_REQUIRED_JA)).toBeTruthy();
    expect(savedBodies).toHaveLength(0);
  });

  it("ファイル無しのドキュメント（filelocationtype=I）として保存する", async () => {
    const onSave = vi.fn();
    renderModal(onSave);
    await selectNoteType();
    setTitle("メモだけの資料");
    fireEvent.change(noteTextarea(), { target: { value: "打ち合わせの記録" } });

    fireEvent.click(screen.getByText("保存"));

    await waitFor(() => expect(savedBodies.length).toBeGreaterThan(0));
    const params = new URLSearchParams(savedBodies[0]);
    expect(params.get("filelocationtype")).toBe("I");
    expect(params.get("notecontent")).toBe("打ち合わせの記録");
    expect(params.get("filename")).toBeNull();
  });
});
