import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { DocumentCreateEditModal } from "../DocumentCreateEditModal";
import { TranslationProvider } from "../../../contexts/TranslationContext";

/**
 * ドキュメント登録画面のドロップ受付（単体ファイルのみ）
 *
 * フォルダ・複数ファイルはこの画面では扱えないため、
 * サーバーへ送る前に理由の分かるメッセージを出して弾く。
 */

const SINGLE_FILE_ONLY_JA =
  "この画面ではファイルを1件だけ指定できます（%s件が指定されました）。複数のファイルは一覧画面へのドラッグ&ドロップで登録してください";
const FOLDER_NOT_ALLOWED_JA =
  "この画面ではフォルダを指定できません。ファイルを1件だけ指定してください。フォルダごと登録する場合は一覧画面へドラッグ&ドロップしてください";
const DRAG_DROP_JA = "ファイル1件をドラッグ&ドロップ または クリックして選択";

const TRANSLATIONS: Record<string, string> = {
  LBL_UPLOAD_ERR_SINGLE_FILE_ONLY: SINGLE_FILE_ONLY_JA,
  LBL_UPLOAD_ERR_FOLDER_NOT_ALLOWED: FOLDER_NOT_ALLOWED_JA,
  LBL_DRAG_DROP_OR_CLICK: DRAG_DROP_JA,
  Title: "タイトル",
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

/** 項目定義。ブロックの見出しで読み込み完了を判定するために1件だけ返す */
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
  return Promise.resolve(jsonResponse({ success: true, result: {} }));
}

function textFile(name: string): File {
  return new File(["x"], name, { type: "text/plain" });
}

/** ファイルのエントリ（webkitGetAsEntry の戻り値） */
function fileEntry(name: string) {
  return { isFile: true, isDirectory: false, name };
}

/** フォルダのエントリ */
function dirEntry(name: string) {
  return { isFile: false, isDirectory: true, name };
}

function dataTransfer(entries: unknown[], files: File[]) {
  return {
    items: entries.map((entry) => ({
      kind: "file",
      webkitGetAsEntry: () => entry,
    })),
    files,
    types: ["Files"],
  };
}

function renderModal() {
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
        onSave={() => {}}
        onClose={() => {}}
      />
    </TranslationProvider>,
  );
}

/**
 * ドロップ領域（案内文を含む枠）
 *
 * 項目定義の読み込みで入力欄が作り直されるため、
 * 読み込みが終わってからドロップする。
 */
async function dropZone(): Promise<Element> {
  await screen.findByText("追加情報");
  const hint = await screen.findByText(DRAG_DROP_JA);
  return hint.parentElement as Element;
}

describe("DocumentCreateEditModal - 単体ファイル以外のドロップ", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockImplementation(fetchImpl);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("フォルダをドロップするとフォルダ不可のメッセージを出し、ファイルを選ばない", async () => {
    renderModal();
    const zone = await dropZone();

    // フォルダは files には中身のないファイルとして入る
    fireEvent.drop(zone, {
      dataTransfer: dataTransfer([dirEntry("親")], [new File([], "親")]),
    });

    expect(await screen.findByText(FOLDER_NOT_ALLOWED_JA)).toBeInTheDocument();
    // 案内文が残る＝ファイルは選ばれていない
    expect(screen.getByText(DRAG_DROP_JA)).toBeInTheDocument();
  });

  it("複数ファイルをドロップすると件数入りのメッセージを出し、ファイルを選ばない", async () => {
    renderModal();
    const zone = await dropZone();

    fireEvent.drop(zone, {
      dataTransfer: dataTransfer(
        [fileEntry("a.txt"), fileEntry("b.txt"), fileEntry("c.txt")],
        [textFile("a.txt"), textFile("b.txt"), textFile("c.txt")],
      ),
    });

    expect(
      await screen.findByText(
        "この画面ではファイルを1件だけ指定できます（3件が指定されました）。複数のファイルは一覧画面へのドラッグ&ドロップで登録してください",
      ),
    ).toBeInTheDocument();
    // 翻訳キーが素のまま出ない
    expect(screen.queryByText("LBL_UPLOAD_ERR_SINGLE_FILE_ONLY")).toBeNull();
    expect(screen.getByText(DRAG_DROP_JA)).toBeInTheDocument();
  });

  it("ファイル1件のドロップはこれまでどおり受け付ける", async () => {
    renderModal();
    const zone = await dropZone();

    fireEvent.drop(zone, {
      dataTransfer: dataTransfer([fileEntry("a.txt")], [textFile("a.txt")]),
    });

    // 選択したファイル名が表示され、エラーは出ない
    expect(await screen.findByText("a.txt")).toBeInTheDocument();
    expect(screen.queryByText(FOLDER_NOT_ALLOWED_JA)).toBeNull();
    expect(screen.queryByText(/1件だけ指定できます/)).toBeNull();
  });

  it("ファイル選択ダイアログで複数選ばれた場合も弾く", async () => {
    const { container } = renderModal();
    await dropZone();
    const input = container.querySelector(
      'input[type="file"]',
    ) as HTMLInputElement;

    // input[type=file] は multiple を付けない（複数選択させない）
    expect(input.multiple).toBe(false);

    // 何らかの経路で複数入った場合も送信前に弾く
    fireEvent.change(input, {
      target: { files: [textFile("a.txt"), textFile("b.txt")] },
    });

    expect(
      await screen.findByText(
        "この画面ではファイルを1件だけ指定できます（2件が指定されました）。複数のファイルは一覧画面へのドラッグ&ドロップで登録してください",
      ),
    ).toBeInTheDocument();
  });

  it("ファイルを含まないドロップでは何も起きない", async () => {
    renderModal();
    const zone = await dropZone();

    fireEvent.drop(zone, {
      dataTransfer: { items: [{ kind: "string" }], files: [], types: ["text"] },
    });

    await waitFor(() =>
      expect(screen.getByText(DRAG_DROP_JA)).toBeInTheDocument(),
    );
    expect(screen.queryByText(FOLDER_NOT_ALLOWED_JA)).toBeNull();
    expect(screen.queryByText(/1件だけ指定できます/)).toBeNull();
  });
});
