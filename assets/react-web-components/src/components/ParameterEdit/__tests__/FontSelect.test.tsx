import React from "react";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, waitFor, fireEvent } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ParameterEdit } from "../ParameterEdit";
import { MAX_FONT_FILE_SIZE } from "../hooks/useFontData";

/**
 * PDF_DEFAULT_FONT（font 型）の編集（thinkingreed-inc#1901）
 * フォントの一覧から選び、TrueType フォントを追加できる。
 */

// 翻訳はキーと差し込む値をそのまま返す。翻訳の取得自体は TranslationContext のテストで担保する
vi.mock("@/hooks/useTranslation", () => ({
  useTranslation: () => ({
    t: (key: string, ...args: (string | number)[]) =>
      args.length ? `${key}:${args.join(",")}` : key,
  }),
}));
vi.mock("@/contexts/TranslationContext", () => ({
  TranslationProvider: ({ children }: { children: React.ReactNode }) => (
    <>{children}</>
  ),
}));

const mockFetch = vi.fn();

const jsonResponse = (body: unknown, status = 200) =>
  Promise.resolve({
    ok: status >= 200 && status < 300,
    status,
    json: async () => body,
  });

interface Options {
  value?: string;
  fonts?: string[];
  fontsFail?: boolean;
  upload?: { font?: string; error?: string };
}

/** GetRecord / GetFonts / UploadFont / Save のレスポンスを差し込む */
const givenServer = ({
  value = "",
  fonts = ["cid0jp", "dejavusans", "ume-tgo4"],
  fontsFail = false,
  upload = { font: "lineseedjp" },
}: Options = {}) => {
  mockFetch.mockImplementation((url: string, init?: RequestInit) => {
    if (init?.method === "POST") {
      if (init.body instanceof FormData) {
        return upload.error
          ? jsonResponse(
              { success: false, error: { message: upload.error } },
              400,
            )
          : jsonResponse({ font: upload.font });
      }
      return jsonResponse({ saved: true });
    }
    if (url.includes("api=GetFonts")) {
      return fontsFail
        ? jsonResponse({ success: false, error: { message: "error" } }, 500)
        : jsonResponse({ fonts, fallback: "ume-tgo4" });
    }
    return jsonResponse({
      result: {
        id: 7,
        key: "PDF_DEFAULT_FONT",
        value,
        type: "font",
        secret: 0,
        description: "PDF の既定フォント",
      },
    });
  });
};

/** Save API へ送信されたパラメータ */
const savedParams = (): URLSearchParams => {
  const call = mockFetch.mock.calls.find(
    ([, init]) => init?.method === "POST" && !(init.body instanceof FormData),
  );
  return new URLSearchParams(String(call?.[1]?.body ?? ""));
};

/** UploadFont API へ送信されたフォーム */
const uploadedForm = (): FormData | undefined =>
  mockFetch.mock.calls.find(([, init]) => init?.body instanceof FormData)?.[1]
    ?.body as FormData | undefined;

const openDialog = async () => {
  render(<ParameterEdit recordId="7" isOpen />);
  await screen.findByRole("heading", { name: "PDF_DEFAULT_FONT" });
  // フォントの一覧を読み込み終わるまで待つ
  await waitFor(() =>
    expect(screen.getByRole("combobox", { name: "Value" })).toBeEnabled(),
  );
  return screen.getByRole("combobox", { name: "Value" }) as HTMLSelectElement;
};

const optionValues = (select: HTMLSelectElement) =>
  Array.from(select.options).map((o) => o.value);

const selectFile = (file: File) =>
  fireEvent.change(screen.getByTestId("font-file-input"), {
    target: { files: [file] },
  });

const ttf = (name = "LINESeedJP-Regular.ttf", size = 10) =>
  new File([new Uint8Array(size)], name, { type: "font/ttf" });

describe("ParameterEdit: font 型（PDF_DEFAULT_FONT）", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    window.csrfMagicName = "__vtrftk";
    window.csrfMagicToken = "test-token";
    Object.defineProperty(window, "location", {
      value: { origin: "http://localhost", pathname: "/frevocrm/index.php" },
      writable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("フォントの一覧から選べ、先頭は未設定（既定のフォント）", async () => {
    givenServer();

    const select = await openDialog();

    expect(optionValues(select)).toEqual([
      "",
      "cid0jp",
      "dejavusans",
      "ume-tgo4",
    ]);
    expect(select.options[0].text).toBe("LBL_PDF_FONT_DEFAULT:ume-tgo4");
    expect(select).toHaveValue("");
  });

  it("設定済みのフォントが選ばれた状態で開く", async () => {
    givenServer({ value: "dejavusans" });

    const select = await openDialog();

    expect(select).toHaveValue("dejavusans");
  });

  it("設定済みのフォントが見つからなくても、未設定に変えずに選択肢に残す", async () => {
    givenServer({ value: "notoserifjp" });

    const select = await openDialog();

    expect(select).toHaveValue("notoserifjp");
    expect(
      screen.getByRole("option", {
        name: "LBL_PDF_FONT_NOT_FOUND:notoserifjp",
      }),
    ).toBeInTheDocument();
  });

  it("フォントの一覧を取得できない場合はその旨を表示する", async () => {
    givenServer({ value: "dejavusans", fontsFail: true });

    const select = await openDialog();

    expect(screen.getByText("LBL_PDF_FONT_LOAD_FAILED")).toBeInTheDocument();
    // 今の値は選べるように残す
    expect(select).toHaveValue("dejavusans");
  });

  it("シークレットの設定欄を出さず、保存時に secret を送らない", async () => {
    givenServer();

    const select = await openDialog();
    expect(screen.queryByText("LBL_SECRET")).not.toBeInTheDocument();

    await userEvent.selectOptions(select, "cid0jp");
    await userEvent.click(screen.getByRole("button", { name: "LBL_SAVE" }));

    await waitFor(() => expect(savedParams().get("value")).toBe("cid0jp"));
    expect(savedParams().has("secret")).toBe(false);
  });

  it("未設定を選んで保存すると空文字を送る", async () => {
    givenServer({ value: "dejavusans" });

    const select = await openDialog();
    await userEvent.selectOptions(select, "");
    await userEvent.click(screen.getByRole("button", { name: "LBL_SAVE" }));

    await waitFor(() => expect(savedParams().get("value")).toBe(""));
  });

  describe("フォントの追加", () => {
    it("TTF を選ぶとアップロードし、追加したフォントを選んだ状態にする", async () => {
      givenServer({ upload: { font: "lineseedjp" } });

      const select = await openDialog();
      selectFile(ttf());

      expect(
        await screen.findByText("LBL_PDF_FONT_ADDED:lineseedjp"),
      ).toBeInTheDocument();
      expect(select).toHaveValue("lineseedjp");
      expect(optionValues(select)).toContain("lineseedjp");

      const form = uploadedForm();
      expect(form?.get("api")).toBe("UploadFont");
      expect(form?.get("module")).toBe("Parameters");
      expect(form?.get("parent")).toBe("Settings");
      expect(form?.get("__vtrftk")).toBe("test-token");
      expect((form?.get("font") as File).name).toBe("LINESeedJP-Regular.ttf");
    });

    it("追加しただけでは保存せず、保存ボタンで追加したフォントを保存する", async () => {
      givenServer({ upload: { font: "lineseedjp" } });

      await openDialog();
      selectFile(ttf());
      await screen.findByText("LBL_PDF_FONT_ADDED:lineseedjp");
      expect(savedParams().has("value")).toBe(false);

      await userEvent.click(screen.getByRole("button", { name: "LBL_SAVE" }));

      await waitFor(() =>
        expect(savedParams().get("value")).toBe("lineseedjp"),
      );
    });

    it("アップロード中は保存できず、終わると保存できる", async () => {
      givenServer({ upload: { font: "lineseedjp" } });
      // アップロードの応答を止めておく
      let respond: () => void = () => {};
      const base = mockFetch.getMockImplementation()!;
      mockFetch.mockImplementation((url: string, init?: RequestInit) =>
        init?.body instanceof FormData
          ? new Promise((resolve) => {
              respond = () => resolve(base(url, init));
            })
          : base(url, init),
      );

      await openDialog();
      selectFile(ttf());

      const save = screen.getByRole("button", { name: /LBL_SAVING|LBL_SAVE/ });
      await waitFor(() => expect(save).toBeDisabled());

      respond();
      await screen.findByText("LBL_PDF_FONT_ADDED:lineseedjp");
      expect(screen.getByRole("button", { name: "LBL_SAVE" })).toBeEnabled();
    });

    it("サーバーが断った場合はそのメッセージを表示し、選択は変えない", async () => {
      givenServer({
        value: "dejavusans",
        upload: { error: "同じ名前のフォントがすでにあります。" },
      });

      const select = await openDialog();
      selectFile(ttf());

      expect(await screen.findByRole("alert")).toHaveTextContent(
        "同じ名前のフォントがすでにあります。",
      );
      expect(select).toHaveValue("dejavusans");
    });

    it("拡張子が .ttf でなければ送らずに止める", async () => {
      givenServer();

      await openDialog();
      selectFile(ttf("NotoSansJP-Regular.otf"));

      expect(await screen.findByRole("alert")).toHaveTextContent(
        "LBL_FONT_UPLOAD_INVALID_EXTENSION",
      );
      expect(uploadedForm()).toBeUndefined();
    });

    it("30MB を超えるファイルは送らずに止める", async () => {
      givenServer();

      await openDialog();
      const file = ttf();
      Object.defineProperty(file, "size", { value: MAX_FONT_FILE_SIZE + 1 });
      selectFile(file);

      expect(await screen.findByRole("alert")).toHaveTextContent(
        "LBL_FONT_UPLOAD_TOO_LARGE",
      );
      expect(uploadedForm()).toBeUndefined();
    });
  });
});
