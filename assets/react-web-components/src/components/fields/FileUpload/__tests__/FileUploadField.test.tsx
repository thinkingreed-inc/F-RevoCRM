import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import {
  act,
  render,
  screen,
  fireEvent,
  waitFor,
} from "@testing-library/react";
import { FileUploadField } from "../FileUploadField";
import { HANDWRITING_AUTO_SAVE_DELAY_MS } from "../HandwritingPad";
import type { FieldFileInfo } from "@/types/fileUploadField";

const imageFile: FieldFileInfo = {
  id: 45,
  filename: "photo.png",
  filetype: "image/png",
  filesize: 2048,
  is_image: true,
  download_url:
    "index.php?module=Documents&action=DownloadFile&record=45&fileid=46",
};

const pdfFile: FieldFileInfo = {
  id: 50,
  filename: "estimate.pdf",
  filetype: "application/pdf",
  filesize: 4096,
  is_image: false,
  download_url:
    "index.php?module=Documents&action=DownloadFile&record=50&fileid=51",
};

/** ドキュメントの詳細（module=Documents&api=DetailAPI） */
const documentDetail = {
  id: 45,
  title: "photo",
  filename: "photo.png",
  filetype: "image/png",
  filesize: 2048,
  filelocationtype: "I",
  folderid: 1,
  foldername: "Default",
  can_edit: true,
  download_url:
    "index.php?module=Documents&action=DownloadFile&record=45&fileid=46",
  related_records: [],
  file_versions: [],
};

const thumbnail = {
  data_uri: "data:image/png;base64,AAAA",
  width: 320,
  height: 160,
  mime: "image/png",
};

const DRAFT_A = `draft:${"a".repeat(32)}`;

/** mode=upload の応答（一時ファイルに置いた結果） */
function draftResponse(draft: string, filename = "photo.png") {
  return {
    draft,
    filename,
    filetype: "image/png",
    filesize: 2048,
    is_image: true,
    thumbnail,
  };
}

function jsonResponse(data: unknown, ok = true) {
  return Promise.resolve({
    ok,
    status: ok ? 200 : 500,
    json: () => Promise.resolve(data),
  } as Response);
}

/** fetch に渡された URL / 本文から API の mode を取り出す */
function modeOf(input: RequestInfo | URL, init?: RequestInit): string | null {
  if (init?.body instanceof FormData) {
    return String(init.body.get("mode"));
  }
  return new URL(String(input), "http://localhost/").searchParams.get("mode");
}

const mockFetch = vi.fn();

describe("FileUploadField", () => {
  beforeEach(() => {
    mockFetch.mockReset();
    global.fetch = mockFetch as unknown as typeof fetch;
    window.csrfMagicName = "__vtrftk";
    window.csrfMagicToken = "token-1";
  });

  afterEach(() => {
    vi.restoreAllMocks();
    delete window.csrfMagicName;
    delete window.csrfMagicToken;
  });

  describe("詳細", () => {
    it("画像はサムネイルとファイル名を出す", async () => {
      mockFetch.mockImplementation(() =>
        jsonResponse({ file: imageFile, thumbnail }),
      );

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="detail"
          recordId={12}
          file={imageFile}
        />,
      );

      const img = await screen.findByTestId("file-field-thumbnail");
      expect(img).toHaveAttribute("src", thumbnail.data_uri);
      expect(screen.getByTestId("file-field-filename")).toHaveTextContent(
        "photo.png",
      );
      // 押してもダウンロードしない（詳細を出す）
      expect(screen.getByTestId("file-field-filename")).not.toHaveAttribute(
        "href",
      );

      const url = new URL(
        String(mockFetch.mock.calls[0][0]),
        "http://localhost/",
      );
      expect(url.searchParams.get("module")).toBe("Accounts");
      expect(url.searchParams.get("api")).toBe("FileField");
      expect(url.searchParams.get("mode")).toBe("thumbnail");
      expect(url.searchParams.get("document")).toBe("45");
      expect(url.searchParams.get("record")).toBe("12");
    });

    it("サムネイルを押すと、ダウンロードせずにドキュメントの詳細を出す（削除は出さない）", async () => {
      mockFetch.mockImplementation((input: RequestInfo | URL) => {
        const url = new URL(String(input), "http://localhost/");
        if (url.searchParams.get("api") === "DetailAPI") {
          return jsonResponse({ success: true, result: documentDetail });
        }
        return jsonResponse({ file: imageFile, thumbnail });
      });

      // 詳細画面の項目の値の中に置く（vtiger の .fieldValue .value a などの指定を受ける場所）
      render(
        <div className="fieldValue">
          <span className="value">
            <FileUploadField
              module="Accounts"
              fieldName="cf_1"
              mode="detail"
              recordId={12}
              file={{ ...imageFile, viewable: true }}
            />
          </span>
        </div>,
      );
      fireEvent.click(await screen.findByTestId("file-field-thumbnail"));

      const edit = await screen.findByTestId("document-detail-edit");
      expect(edit).toBeInTheDocument();
      // 項目の値の外（body 直下）に出し、項目の値向けのスタイルを受けないようにする
      expect(edit.closest(".fieldValue")).toBeNull();
      expect(edit.closest(".web-components-wrapper")).not.toBeNull();
      const detailCall = mockFetch.mock.calls
        .map(([input]) => new URL(String(input), "http://localhost/"))
        .find((url) => url.searchParams.get("api") === "DetailAPI");
      expect(detailCall?.searchParams.get("record")).toBe("45");
      expect(screen.queryByTestId("document-detail-delete")).toBeNull();
    });

    it("ドキュメント自体を参照できなければ、画像を大きく出すだけにする", async () => {
      const large = { ...thumbnail, data_uri: "data:image/png;base64,LARGE" };
      mockFetch.mockImplementation((input: RequestInfo | URL) => {
        const url = new URL(String(input), "http://localhost/");
        return jsonResponse({
          file: imageFile,
          thumbnail:
            url.searchParams.get("size") === "large" ? large : thumbnail,
        });
      });

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="detail"
          recordId={12}
          file={{ ...imageFile, viewable: false }}
        />,
      );
      fireEvent.click(await screen.findByTestId("file-field-filename"));

      expect(
        await screen.findByTestId("file-field-preview-image"),
      ).toHaveAttribute("src", large.data_uri);
      expect(
        mockFetch.mock.calls.some(([input]) =>
          String(input).includes("api=DetailAPI"),
        ),
      ).toBe(false);
    });

    it("画像でなければサムネイルを取りに行かずファイル名だけ出す", () => {
      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="detail"
          file={pdfFile}
        />,
      );

      expect(screen.getByTestId("file-field-filename")).toHaveTextContent(
        "estimate.pdf",
      );
      expect(
        screen.queryByTestId("file-field-thumbnail"),
      ).not.toBeInTheDocument();
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it("ファイルが無ければ何も出さない", () => {
      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="detail"
          file={null}
        />,
      );
      expect(screen.queryByTestId("file-field-value")).not.toBeInTheDocument();
    });

    it("値（ドキュメントID）だけ分かっていればファイル情報を取得して出す", async () => {
      mockFetch.mockImplementation(() =>
        jsonResponse({ file: pdfFile, thumbnail: null }),
      );

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="detail"
          file={null}
          value="50"
        />,
      );

      expect(
        await screen.findByTestId("file-field-filename"),
      ).toHaveTextContent("estimate.pdf");
    });
  });

  describe("編集（ファイル選択）", () => {
    it("ファイルを選ぶと一時ファイルに置き、それを指す値を返す（ドキュメントはレコードの保存時に作る）", async () => {
      mockFetch.mockImplementation(() => jsonResponse(draftResponse(DRAFT_A)));
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="edit"
          file={null}
          handwriting={false}
          maxUploadSize={1024 * 1024}
          onChange={onChange}
        />,
      );

      const selected = new File(["abc"], "photo.png", { type: "image/png" });
      fireEvent.change(screen.getByTestId("file-field-input"), {
        target: { files: [selected] },
      });

      await waitFor(() =>
        expect(onChange).toHaveBeenCalledWith(
          DRAFT_A,
          expect.objectContaining({ draft: DRAFT_A, filename: "photo.png" }),
        ),
      );
      expect(screen.getByTestId("file-field-thumbnail")).toHaveAttribute(
        "src",
        thumbnail.data_uri,
      );
      // 保存前はドキュメントが無いのでダウンロードのリンクは出さない
      expect(screen.getByTestId("file-field-filename")).not.toHaveAttribute(
        "href",
      );

      const [, init] = mockFetch.mock.calls[0];
      expect(init.method).toBe("POST");
      const body = init.body as FormData;
      expect(body.get("module")).toBe("Accounts");
      expect(body.get("api")).toBe("FileField");
      expect(body.get("mode")).toBe("upload");
      expect(body.get("field")).toBe("cf_1");
      expect(body.get("__vtrftk")).toBe("token-1");
      expect((body.get("file") as File).name).toBe("photo.png");
    });

    it("保存前に選んだ画像は、押すと選んだ画像を大きく出す", async () => {
      mockFetch.mockImplementation(() => jsonResponse(draftResponse(DRAFT_A)));
      const createObjectURL = vi.fn(() => "blob:selected-image");
      Object.defineProperty(URL, "createObjectURL", {
        configurable: true,
        value: createObjectURL,
      });
      Object.defineProperty(URL, "revokeObjectURL", {
        configurable: true,
        value: vi.fn(),
      });

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="edit"
          file={null}
          handwriting={false}
          maxUploadSize={1024 * 1024}
        />,
      );
      fireEvent.change(screen.getByTestId("file-field-input"), {
        target: {
          files: [new File(["abc"], "photo.png", { type: "image/png" })],
        },
      });
      fireEvent.click(await screen.findByTestId("file-field-thumbnail"));

      expect(
        await screen.findByTestId("file-field-preview-image"),
      ).toHaveAttribute("src", "blob:selected-image");
    });

    it("登録に失敗したらメッセージを出し、値は変えない", async () => {
      mockFetch.mockImplementation(() =>
        jsonResponse({
          success: false,
          error: { code: "x", message: "アップロードできません" },
        }),
      );
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="edit"
          file={null}
          handwriting={false}
          maxUploadSize={1024}
          onChange={onChange}
        />,
      );
      fireEvent.change(screen.getByTestId("file-field-input"), {
        target: { files: [new File(["a"], "a.txt", { type: "text/plain" })] },
      });

      expect(await screen.findByTestId("file-field-error")).toHaveTextContent(
        "アップロードできません",
      );
      expect(onChange).not.toHaveBeenCalled();
    });

    it("上限を超えるファイルは送らずに知らせる", async () => {
      const onChange = vi.fn();
      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="edit"
          file={null}
          handwriting={false}
          maxUploadSize={2}
          onChange={onChange}
        />,
      );
      fireEvent.change(screen.getByTestId("file-field-input"), {
        target: {
          files: [new File(["abcdef"], "big.txt", { type: "text/plain" })],
        },
      });

      expect(await screen.findByTestId("file-field-error")).toBeInTheDocument();
      expect(mockFetch).not.toHaveBeenCalled();
      expect(onChange).not.toHaveBeenCalled();
    });

    it("削除すると値を空にする", async () => {
      mockFetch.mockImplementation(() =>
        jsonResponse({ file: pdfFile, thumbnail: null }),
      );
      const onChange = vi.fn();
      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="edit"
          file={pdfFile}
          handwriting={false}
          maxUploadSize={1024}
          onChange={onChange}
        />,
      );

      fireEvent.click(screen.getByTestId("file-field-remove"));

      expect(onChange).toHaveBeenCalledWith("", null);
      expect(screen.getByTestId("file-field-select")).toBeInTheDocument();
    });

    it("読み取り専用なら変更・削除を出さない", () => {
      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_1"
          mode="edit"
          file={pdfFile}
          handwriting={false}
          maxUploadSize={1024}
          disabled
        />,
      );
      expect(screen.getByTestId("file-field-filename")).toBeInTheDocument();
      expect(screen.queryByTestId("file-field-change")).not.toBeInTheDocument();
      expect(screen.queryByTestId("file-field-remove")).not.toBeInTheDocument();
    });
  });

  describe("編集（手書き）", () => {
    beforeEach(() => {
      const context = {
        save: vi.fn(),
        restore: vi.fn(),
        setTransform: vi.fn(),
        fillRect: vi.fn(),
        scale: vi.fn(),
        beginPath: vi.fn(),
        arc: vi.fn(),
        fill: vi.fn(),
        moveTo: vi.fn(),
        lineTo: vi.fn(),
        stroke: vi.fn(),
        fillStyle: "",
        strokeStyle: "",
        lineWidth: 0,
        lineCap: "",
        lineJoin: "",
      };
      vi.spyOn(HTMLCanvasElement.prototype, "getContext").mockReturnValue(
        context as unknown as CanvasRenderingContext2D,
      );
      vi.spyOn(HTMLCanvasElement.prototype, "toBlob").mockImplementation(
        function (callback: BlobCallback) {
          callback(new Blob(["png"], { type: "image/png" }));
        },
      );
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    /** 書く欄に 1 画書く */
    function drawStroke(pointerType = "pen") {
      const canvas = screen.getByTestId("handwriting-canvas");
      fireEvent.pointerDown(canvas, {
        clientX: 10,
        clientY: 10,
        pointerId: 1,
        pointerType,
      });
      fireEvent.pointerMove(canvas, {
        clientX: 30,
        clientY: 20,
        pointerId: 1,
        pointerType,
      });
      fireEvent.pointerUp(canvas, { pointerId: 1, pointerType });
      return canvas;
    }

    /** 自動で登録するまでの待ち時間を進める */
    function waitAutoSave() {
      act(() => {
        vi.advanceTimersByTime(HANDWRITING_AUTO_SAVE_DELAY_MS);
      });
    }

    function uploadCount() {
      return mockFetch.mock.calls.filter(
        ([input, init]) => modeOf(input, init) === "upload",
      ).length;
    }

    it("手書き項目は書き終わって少し待つと PNG で登録し、書く欄は出したままにする", async () => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
      mockFetch.mockImplementation(() => jsonResponse(draftResponse(DRAFT_A)));
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          fieldLabel="サイン"
          mode="edit"
          file={null}
          handwriting
          maxUploadSize={1024 * 1024}
          onChange={onChange}
        />,
      );

      expect(screen.queryByTestId("file-field-input")).not.toBeInTheDocument();
      expect(screen.queryByTestId("handwriting-save")).not.toBeInTheDocument();

      drawStroke();
      expect(uploadCount()).toBe(0);

      waitAutoSave();

      await waitFor(() =>
        expect(onChange).toHaveBeenCalledWith(
          DRAFT_A,
          expect.objectContaining({ draft: DRAFT_A }),
        ),
      );
      const body = mockFetch.mock.calls[0][1].body as FormData;
      expect(body.get("field")).toBe("cf_2");
      const uploaded = body.get("file") as File;
      expect(uploaded.type).toBe("image/png");
      expect(uploaded.name).toMatch(/^サイン_\d{8}-\d{6}\.png$/);
      // 続けて書けるよう書く欄は出したまま
      expect(screen.getByTestId("handwriting-canvas")).toBeInTheDocument();
      expect(
        await screen.findByTestId("handwriting-saved"),
      ).toBeInTheDocument();
    });

    it("書き続けている間は登録しない", () => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
      mockFetch.mockImplementation(() => jsonResponse(draftResponse(DRAFT_A)));

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={null}
          handwriting
          maxUploadSize={1024 * 1024}
        />,
      );

      drawStroke();
      act(() => {
        vi.advanceTimersByTime(HANDWRITING_AUTO_SAVE_DELAY_MS - 100);
      });
      // 待ち時間内に次の画を書き始めたら、登録は書き終わるまで待つ
      fireEvent.pointerDown(screen.getByTestId("handwriting-canvas"), {
        clientX: 40,
        clientY: 40,
        pointerId: 1,
        pointerType: "pen",
      });
      act(() => {
        vi.advanceTimersByTime(HANDWRITING_AUTO_SAVE_DELAY_MS * 2);
      });

      expect(uploadCount()).toBe(0);
    });

    it("マウスが書く欄から外れたら待たずに登録する（保存ボタンへ移る前に値を入れる）", async () => {
      mockFetch.mockImplementation(() => jsonResponse(draftResponse(DRAFT_A)));
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={null}
          handwriting
          maxUploadSize={1024 * 1024}
          onChange={onChange}
        />,
      );

      const canvas = drawStroke("mouse");
      fireEvent.pointerLeave(canvas, { pointerId: 1, pointerType: "mouse" });

      await waitFor(() =>
        expect(onChange).toHaveBeenCalledWith(DRAFT_A, expect.anything()),
      );
    });

    it("書き直すと値を書く前（空）に戻す", async () => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
      mockFetch.mockImplementation(() => jsonResponse(draftResponse(DRAFT_A)));
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={null}
          handwriting
          maxUploadSize={1024 * 1024}
          onChange={onChange}
        />,
      );
      drawStroke();
      waitAutoSave();
      await waitFor(() =>
        expect(onChange).toHaveBeenCalledWith(DRAFT_A, expect.anything()),
      );

      fireEvent.click(screen.getByTestId("handwriting-clear"));

      expect(onChange).toHaveBeenLastCalledWith("", null);
      expect(screen.getByTestId("handwriting-clear")).toBeDisabled();
    });

    it("登録済みの手書きを書き直してやめると、元のファイルに戻す", async () => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
      mockFetch.mockImplementation(
        (input: RequestInfo | URL, init?: RequestInit) =>
          modeOf(input, init) === "upload"
            ? jsonResponse(draftResponse(DRAFT_A))
            : jsonResponse({ file: imageFile, thumbnail }),
      );
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={imageFile}
          handwriting
          maxUploadSize={1024 * 1024}
          onChange={onChange}
        />,
      );
      fireEvent.click(screen.getByTestId("file-field-change"));
      drawStroke();
      waitAutoSave();
      await waitFor(() =>
        expect(onChange).toHaveBeenCalledWith(DRAFT_A, expect.anything()),
      );

      fireEvent.click(screen.getByTestId("handwriting-cancel"));

      expect(onChange).toHaveBeenLastCalledWith("45", imageFile);
      expect(
        screen.queryByTestId("handwriting-canvas"),
      ).not.toBeInTheDocument();
      expect(screen.getByTestId("file-field-value")).toHaveAttribute(
        "data-document-id",
        "45",
      ); // 戻したファイルのサムネイルを取り直す
      expect(
        await screen.findByTestId("file-field-thumbnail"),
      ).toBeInTheDocument();
    });

    it("書き足すと同じ一時ファイルを上書きし、送信中に書き足した分は送信が終わってから送る", async () => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
      const pending: Array<() => void> = [];
      mockFetch.mockImplementation(
        () =>
          new Promise<Response>((resolve) => {
            pending.push(() =>
              resolve({
                ok: true,
                status: 200,
                json: () => Promise.resolve(draftResponse(DRAFT_A)),
              } as Response),
            );
          }),
      );
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={null}
          handwriting
          maxUploadSize={1024 * 1024}
          onChange={onChange}
        />,
      );
      drawStroke();
      waitAutoSave();
      drawStroke();
      waitAutoSave();
      // 1 回目の送信が終わるまで 2 回目は送らない（古い内容で上書きされないように）
      expect(pending).toHaveLength(1);
      expect(
        (mockFetch.mock.calls[0][1].body as FormData).get("draft"),
      ).toBeNull();

      pending[0]();
      await waitFor(() => expect(pending).toHaveLength(2));
      expect((mockFetch.mock.calls[1][1].body as FormData).get("draft")).toBe(
        DRAFT_A,
      );
      pending[1]();
      await waitFor(() => expect(onChange).toHaveBeenCalledTimes(2));
      expect(onChange).toHaveBeenLastCalledWith(DRAFT_A, expect.anything());
    });

    it("書き直してから書いても、同じ一時ファイルを上書きする", async () => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
      mockFetch.mockImplementation(() => jsonResponse(draftResponse(DRAFT_A)));
      const onChange = vi.fn();

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={null}
          handwriting
          maxUploadSize={1024 * 1024}
          onChange={onChange}
        />,
      );
      drawStroke();
      waitAutoSave();
      await waitFor(() =>
        expect(onChange).toHaveBeenCalledWith(DRAFT_A, expect.anything()),
      );
      fireEvent.click(screen.getByTestId("handwriting-clear"));

      drawStroke();
      waitAutoSave();

      await waitFor(() => expect(uploadCount()).toBe(2));
      expect((mockFetch.mock.calls[1][1].body as FormData).get("draft")).toBe(
        DRAFT_A,
      );
    });

    it("手書きかどうか渡されなければ API から取得して出し分ける", async () => {
      mockFetch.mockImplementation(
        (input: RequestInfo | URL, init?: RequestInit) =>
          modeOf(input, init) === "config"
            ? jsonResponse({
                field_name: "cf_2",
                handwriting: true,
                max_upload_size: 1024,
                max_upload_size_label: "1 KB",
              })
            : jsonResponse({}),
      );

      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={null}
        />,
      );

      expect(
        await screen.findByTestId("handwriting-canvas"),
      ).toBeInTheDocument();
      expect(screen.queryByTestId("file-field-input")).not.toBeInTheDocument();
    });
  });
});
