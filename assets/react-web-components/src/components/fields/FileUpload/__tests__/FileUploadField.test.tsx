import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { FileUploadField } from "../FileUploadField";
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

const thumbnail = {
  data_uri: "data:image/png;base64,AAAA",
  width: 320,
  height: 160,
  mime: "image/png",
};

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
      expect(screen.getByTestId("file-field-filename")).toHaveAttribute(
        "href",
        imageFile.download_url,
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
    it("ファイルを選ぶとドキュメントとして登録し、ドキュメントIDを返す", async () => {
      mockFetch.mockImplementation(() =>
        jsonResponse({ ...imageFile, id: 99, thumbnail }),
      );
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
          "99",
          expect.objectContaining({ id: 99 }),
        ),
      );
      expect(screen.getByTestId("file-field-thumbnail")).toHaveAttribute(
        "src",
        thumbnail.data_uri,
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

    it("手書き項目はファイル選択の代わりに書く欄を出し、書いた画像を PNG で登録する", async () => {
      mockFetch.mockImplementation(() =>
        jsonResponse({ ...imageFile, id: 77, thumbnail }),
      );
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
      const save = screen.getByTestId("handwriting-save");
      expect(save).toBeDisabled();

      const canvas = screen.getByTestId("handwriting-canvas");
      fireEvent.pointerDown(canvas, { clientX: 10, clientY: 10, pointerId: 1 });
      fireEvent.pointerMove(canvas, { clientX: 30, clientY: 20, pointerId: 1 });
      fireEvent.pointerUp(canvas, { pointerId: 1 });
      expect(save).toBeEnabled();

      fireEvent.click(save);

      await waitFor(() =>
        expect(onChange).toHaveBeenCalledWith(
          "77",
          expect.objectContaining({ id: 77 }),
        ),
      );
      const body = mockFetch.mock.calls[0][1].body as FormData;
      expect(body.get("field")).toBe("cf_2");
      const uploaded = body.get("file") as File;
      expect(uploaded.type).toBe("image/png");
      expect(uploaded.name).toMatch(/^サイン_\d{8}-\d{6}\.png$/);
    });

    it("書き直すと保存できない状態に戻る", () => {
      render(
        <FileUploadField
          module="Accounts"
          fieldName="cf_2"
          mode="edit"
          file={null}
          handwriting
          maxUploadSize={1024}
        />,
      );
      const canvas = screen.getByTestId("handwriting-canvas");
      fireEvent.pointerDown(canvas, { clientX: 10, clientY: 10, pointerId: 1 });
      fireEvent.pointerUp(canvas, { pointerId: 1 });
      expect(screen.getByTestId("handwriting-save")).toBeEnabled();

      fireEvent.click(screen.getByTestId("handwriting-clear"));

      expect(screen.getByTestId("handwriting-save")).toBeDisabled();
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
