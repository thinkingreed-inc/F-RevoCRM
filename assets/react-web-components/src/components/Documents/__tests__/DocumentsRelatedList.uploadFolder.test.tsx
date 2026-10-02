import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { DocumentsRelatedList } from "../DocumentsRelatedList";
import { clearTranslationCache } from "../../../utils/translations";
import type { Folder } from "../types/documents";

/**
 * 関連ドキュメント一覧：ドロップ時の登録先フォルダ選択
 *
 * 仕様（検証する振る舞い）:
 *  1. ドロップすると登録先を選ぶダイアログが開き、対象のファイル数が分かる
 *  2. 「アップロード」を押すまでは1件も送信しない（誤ドロップで登録されない）
 *  3. 選んだフォルダへ全件登録し、親レコードにも紐づける
 *  4. キャンセルすると何も登録しない
 *  5. 前回選んだフォルダを、次のドロップと登録モーダルの初期値に引き継ぐ
 *  6. 書き込めないフォルダは選択不可で残す（階層をたどれるように）
 *  7. Default に書き込めない場合は、選べる先頭のフォルダに寄せる
 *  8. 展開できるファイルが無ければダイアログを出さない
 *  9. 件数の上限を超えた場合はダイアログを出さず、理由を表示する
 *
 * N/A: インデント整形・階層の並び順は utils/__tests__/folderOptions.test.ts で検証済み
 * N/A: 上限超過メッセージの文面・アップロード失敗時の表示は
 *      DocumentsRelatedList.upload.test.tsx と hooks/__tests__/useFileUpload.test.ts の担当
 */

const TRANSLATIONS: Record<string, string> = {
  LBL_UPLOAD_DESTINATION_TITLE: "アップロード先の選択",
  LBL_UPLOAD_TO_FOLDER: "アップロード先",
  LBL_UPLOAD_PREPARING: "ドロップされたファイルを確認しています...",
  LBL_UPLOAD_FILE_COUNT: "%s件のファイルをアップロードします",
  LBL_UPLOAD_START: "アップロード",
  LBL_NO_WRITABLE_FOLDER: "アップロードできるフォルダがありません",
  LBL_CANCEL: "キャンセル",
  LBL_LOADING: "読み込み中...",
  LBL_ADD_DOCUMENT: "+ ドキュメントの追加",
  LBL_MAX_UPLOAD_FILES:
    "一度にアップロードできるファイルは最大%s件です（%s件選択されました）",
  "Folder Name": "フォルダ",
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

function folder(id: number, name: string, extra: Partial<Folder> = {}): Folder {
  return {
    id,
    name,
    description: "",
    parent_id: 0,
    sequence: id,
    count: 0,
    can_edit: true,
    ...extra,
  };
}

/** テストごとに差し替えるフォルダ一覧 */
let folders: Folder[] = [];

function fetchImpl(url: string, init?: RequestInit): Promise<Response> {
  const u = String(url);
  if (u.includes("api=GetTranslations")) {
    return Promise.resolve(
      jsonResponse({
        module: "Documents",
        language: "ja_jp",
        translations: { Documents: TRANSLATIONS },
        timestamp: "",
      }),
    );
  }
  if (u.includes("api=FolderAPI")) {
    return Promise.resolve(
      jsonResponse({
        success: true,
        result: { folders, totalCount: 0, starredCount: 0 },
      }),
    );
  }
  if (u.includes("api=GetFields")) {
    return Promise.resolve(jsonResponse({ fields: [] }));
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

/** 送信された URLSearchParams 形式のリクエストを取り出す */
function paramCalls(predicate: (body: string) => boolean): string[] {
  return mockFetch.mock.calls
    .map(([, init]) => (init as RequestInit | undefined)?.body)
    .filter((body): body is string => typeof body === "string")
    .filter(predicate);
}

function saveCalls(): string[] {
  return paramCalls((body) => body.includes("action=Save"));
}

function duplicateCheckCalls(): string[] {
  return paramCalls((body) => body.includes("api=DuplicateCheck"));
}

/** 分割アップロード（FormData）の呼び出し数 */
function chunkUploadCallCount(): number {
  return mockFetch.mock.calls.filter(
    ([, init]) => (init as RequestInit | undefined)?.body instanceof FormData,
  ).length;
}

async function renderList() {
  const result = render(
    <DocumentsRelatedList parentModule="Potentials" parentId={42} />,
  );
  await waitFor(() =>
    expect(
      mockFetch.mock.calls.some(([u]) =>
        String(u).includes("api=GetTranslations"),
      ),
    ).toBe(true),
  );
  return result;
}

/** ルート要素（D&D のイベントを受ける） */
function dropZone(container: HTMLElement): Element {
  return container.firstElementChild as Element;
}

function dropFiles(container: HTMLElement, files: File[]) {
  fireEvent.drop(dropZone(container), {
    dataTransfer: { files, types: ["Files"] },
  });
}

/** ダイアログのフォルダ選択を取得する（開くまで待つ） */
async function findFolderSelect(): Promise<HTMLSelectElement> {
  const select = (await screen.findByLabelText(
    "アップロード先",
  )) as HTMLSelectElement;
  await waitFor(() => expect(select.disabled).toBe(false));
  return select;
}

function uploadButton(): HTMLButtonElement {
  return screen.getByRole("button", { name: "アップロード" });
}

describe("DocumentsRelatedList - ドロップ時の登録先フォルダ選択", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    clearTranslationCache();
    folders = [
      folder(1, "Default"),
      folder(7, "契約書"),
      folder(8, "見積", { parent_id: 7 }),
    ];
    mockFetch.mockImplementation(fetchImpl);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("ドロップするとフォルダ選択ダイアログが開き、件数と選択肢を出す", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(3));

    expect(
      await screen.findByRole("dialog", { name: "アップロード先の選択" }),
    ).toBeInTheDocument();
    expect(
      await screen.findByText("3件のファイルをアップロードします"),
    ).toBeInTheDocument();

    const select = await findFolderSelect();
    expect(select.value).toBe("1");
    expect(
      Array.from(select.options).map((option) => option.textContent),
      // 子フォルダは枝記号 + ノーブレークスペース(U+00A0)でインデントされる
    ).toEqual(["Default", "契約書", "\u2514\u00A0見積"]);
  });

  it("アップロードを押すまでは1件も送信しない", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(3));

    await findFolderSelect();
    expect(saveCalls()).toHaveLength(0);
    expect(duplicateCheckCalls()).toHaveLength(0);
    expect(chunkUploadCallCount()).toBe(0);
  });

  it("選んだフォルダへ、複数ファイルをまとめて登録する", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(3));

    const select = await findFolderSelect();
    fireEvent.change(select, { target: { value: "7" } });
    fireEvent.click(uploadButton());

    await waitFor(() => expect(saveCalls()).toHaveLength(3));
    saveCalls().forEach((body) => {
      expect(body).toContain("folderid=7");
      expect(body).toContain("sourceModule=Potentials");
      expect(body).toContain("sourceRecord=42");
    });
    // 同名チェックも同じフォルダに対して行う
    expect(duplicateCheckCalls()).toHaveLength(1);
    expect(duplicateCheckCalls()[0]).toContain("folderid=7");
    // 登録を始めたらダイアログは閉じる
    await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
  });

  it("子フォルダも選べる", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(1));

    const select = await findFolderSelect();
    fireEvent.change(select, { target: { value: "8" } });
    fireEvent.click(uploadButton());

    await waitFor(() => expect(saveCalls()).toHaveLength(1));
    expect(saveCalls()[0]).toContain("folderid=8");
  });

  it("キャンセルすると何も登録しない", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(3));

    await findFolderSelect();
    fireEvent.click(screen.getByRole("button", { name: "キャンセル" }));

    await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
    expect(saveCalls()).toHaveLength(0);
    expect(chunkUploadCallCount()).toBe(0);
  });

  it("前回選んだフォルダを、次のドロップの初期値にする", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(1));

    fireEvent.change(await findFolderSelect(), { target: { value: "7" } });
    fireEvent.click(uploadButton());
    await waitFor(() => expect(saveCalls()).toHaveLength(1));

    dropFiles(container, makeFiles(1));
    expect((await findFolderSelect()).value).toBe("7");
  });

  it("前回選んだフォルダを、ドキュメントの追加モーダルの初期値にする", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(1));

    fireEvent.change(await findFolderSelect(), { target: { value: "7" } });
    fireEvent.click(uploadButton());
    await waitFor(() => expect(saveCalls()).toHaveLength(1));

    fireEvent.click(screen.getByTestId("documents-related-add"));
    const modalSelect = (await screen.findByLabelText(
      "フォルダ",
    )) as HTMLSelectElement;
    expect(modalSelect.value).toBe("7");
  });

  it("書き込めないフォルダは選択不可で残す", async () => {
    folders = [
      folder(1, "Default"),
      folder(9, "参照専用", { can_edit: false }),
    ];
    const { container } = await renderList();
    dropFiles(container, makeFiles(1));

    const select = await findFolderSelect();
    const readonlyOption = Array.from(select.options).find(
      (option) => option.textContent === "参照専用",
    );
    expect(readonlyOption?.disabled).toBe(true);
    expect(select.value).toBe("1");
  });

  it("Default に書き込めない場合は、選べる先頭のフォルダに寄せる", async () => {
    folders = [folder(1, "Default", { can_edit: false }), folder(7, "契約書")];
    const { container } = await renderList();
    dropFiles(container, makeFiles(1));

    const select = await findFolderSelect();
    await waitFor(() => expect(select.value).toBe("7"));

    fireEvent.click(uploadButton());
    await waitFor(() => expect(saveCalls()).toHaveLength(1));
    expect(saveCalls()[0]).toContain("folderid=7");
  });

  it("書き込めるフォルダが1つも無ければアップロードさせない", async () => {
    folders = [folder(1, "Default", { can_edit: false })];
    const { container } = await renderList();
    dropFiles(container, makeFiles(1));

    await findFolderSelect();
    expect(
      await screen.findByText("アップロードできるフォルダがありません"),
    ).toBeInTheDocument();
    expect(uploadButton()).toBeDisabled();
  });

  it("ファイルが無いドロップではダイアログを出さない", async () => {
    const { container } = await renderList();
    dropFiles(container, []);

    await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
    expect(saveCalls()).toHaveLength(0);
  });

  it("上限を超えた場合はダイアログを出さず、理由を表示する", async () => {
    const { container } = await renderList();
    dropFiles(container, makeFiles(501));

    expect(
      await screen.findByText(
        "一度にアップロードできるファイルは最大500件です（501件選択されました）",
      ),
    ).toBeInTheDocument();
    expect(screen.queryByRole("dialog")).toBeNull();
    expect(chunkUploadCallCount()).toBe(0);
  });
});
