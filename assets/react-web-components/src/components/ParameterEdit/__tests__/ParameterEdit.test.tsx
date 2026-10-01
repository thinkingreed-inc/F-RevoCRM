import React from "react";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ParameterEdit } from "../ParameterEdit";
import type { ParameterRecord, ParameterSaveApiResponse } from "../types";

// 翻訳はキーをそのまま返す。翻訳の取得自体は TranslationContext のテストで担保する
vi.mock("@/hooks/useTranslation", () => ({
  useTranslation: () => ({ t: (key: string) => key }),
}));
vi.mock("@/contexts/TranslationContext", () => ({
  TranslationProvider: ({ children }: { children: React.ReactNode }) => (
    <>{children}</>
  ),
}));

const mockFetch = vi.fn();
const mockPost = vi.fn();

/** GetRecord のレスポンスを差し込む */
const givenRecord = (record: Partial<ParameterRecord>) => {
  mockFetch.mockResolvedValue({
    ok: true,
    json: async () => ({
      result: {
        id: 2,
        key: "USER_LOCK_TIME",
        value: "30",
        type: "integer",
        secret: 0,
        description: "ロック時間",
        ...record,
      },
    }),
  });
};

/** Save API へ実際に送信されたパラメータ */
const sentParams = (): Record<string, string> =>
  mockPost.mock.calls[0][0].data as Record<string, string>;

const save = () => screen.getByRole("button", { name: "LBL_SAVE" });

describe("ParameterEdit", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    (globalThis as unknown as { app: unknown }).app = {
      request: {
        post: () => ({
          then: (
            callback: (err: unknown, data: ParameterSaveApiResponse) => void,
          ) => callback(null, { success: true }),
        }),
      },
    };
    // 送信内容を検証するため post 自体もスパイしておく
    (globalThis as unknown as { app: { request: { post: unknown } } }).app = {
      request: {
        post: mockPost.mockImplementation(() => ({
          then: (
            callback: (err: unknown, data: ParameterSaveApiResponse) => void,
          ) => callback(null, { success: true }),
        })),
      },
    };
    Object.defineProperty(window, "location", {
      value: { origin: "http://localhost", pathname: "/frevocrm/index.php" },
      writable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("isOpen が false の場合は何も描画しない", () => {
    const { container } = render(<ParameterEdit recordId="2" isOpen={false} />);

    expect(container).toBeEmptyDOMElement();
    expect(mockFetch).not.toHaveBeenCalled();
  });

  it("integer 型は数値入力で描画される", async () => {
    givenRecord({ type: "integer", value: "30" });

    render(<ParameterEdit recordId="2" isOpen />);

    expect(
      await screen.findByRole("heading", { name: "USER_LOCK_TIME" }),
    ).toBeInTheDocument();
    expect(screen.getByRole("spinbutton")).toHaveValue(30);
  });

  it("boolean 型はトグルで描画される", async () => {
    givenRecord({
      key: "SHOW_SCHEDULE_CONFIRM_FLAG",
      type: "boolean",
      value: "true",
    });

    render(<ParameterEdit recordId="4" isOpen />);

    await screen.findByRole("heading", { name: "SHOW_SCHEDULE_CONFIRM_FLAG" });
    // boolean はシークレットを設定できないため、トグルは値の 1 つだけ
    const switches = screen.getAllByRole("switch");
    expect(switches).toHaveLength(1);
    expect(switches[0]).toBeChecked();
  });

  it("boolean 型にはシークレットの設定欄を出さない", async () => {
    // 値が true / false の 2 択しかなく、マスクしても値を推測できる
    givenRecord({
      key: "SHOW_SCHEDULE_CONFIRM_FLAG",
      type: "boolean",
      value: "true",
    });

    render(<ParameterEdit recordId="4" isOpen />);

    await screen.findByRole("heading", { name: "SHOW_SCHEDULE_CONFIRM_FLAG" });
    expect(screen.queryByText("LBL_SECRET")).not.toBeInTheDocument();
    expect(
      screen.queryByRole("switch", { name: "LBL_SECRET_OFF" }),
    ).not.toBeInTheDocument();
  });

  it("integer 型にはシークレットの設定欄を出す", async () => {
    givenRecord({ type: "integer", value: "30" });

    render(<ParameterEdit recordId="2" isOpen />);

    await screen.findByRole("heading", { name: "USER_LOCK_TIME" });
    expect(screen.getByText("LBL_SECRET")).toBeInTheDocument();
    expect(
      screen.getByRole("switch", { name: "LBL_SECRET_OFF" }),
    ).toBeInTheDocument();
  });

  describe("シークレット変数の値の扱い", () => {
    it("値欄を編集しなければ value を送らない（既存値が保持される）", async () => {
      // secret=1 のとき GetRecord は value を空で返す
      givenRecord({ secret: 1, value: "" });

      render(<ParameterEdit recordId="2" isOpen />);
      await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

      await userEvent.click(save());

      await waitFor(() => expect(mockPost).toHaveBeenCalled());
      expect(sentParams()).not.toHaveProperty("value");
      expect(sentParams().description).toBe("ロック時間");
    });

    it("現在の値が表示されない旨の注記を出す", async () => {
      givenRecord({ secret: 1, value: "" });

      render(<ParameterEdit recordId="2" isOpen />);

      expect(
        await screen.findByText("LBL_SECRET_VALUE_HIDDEN"),
      ).toBeInTheDocument();
    });

    it("値欄を編集した場合はその値を送る", async () => {
      givenRecord({ secret: 1, value: "" });

      render(<ParameterEdit recordId="2" isOpen />);
      await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

      await userEvent.type(screen.getByRole("spinbutton"), "45");
      await userEvent.click(save());

      await waitFor(() => expect(mockPost).toHaveBeenCalled());
      expect(sentParams().value).toBe("45");
    });

    it("シークレットを解除するには値の再入力が必要", async () => {
      givenRecord({ secret: 1, value: "", type: "integer" });

      render(<ParameterEdit recordId="2" isOpen />);
      await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

      // 値を入力せずにシークレットを解除しようとする
      await userEvent.click(
        screen.getByRole("switch", { name: "LBL_SECRET_ON" }),
      );
      await userEvent.click(save());

      expect(await screen.findByText("LBL_VALUE_REQUIRED")).toBeInTheDocument();
      expect(mockPost).not.toHaveBeenCalled();
    });

    it("解除を選ぶと値の再入力を促す案内に切り替わる", async () => {
      givenRecord({ secret: 1, value: "", type: "integer" });

      render(<ParameterEdit recordId="2" isOpen />);
      await screen.findByRole("heading", { name: "USER_LOCK_TIME" });
      expect(screen.getByText("LBL_SECRET_VALUE_HIDDEN")).toBeInTheDocument();

      await userEvent.click(
        screen.getByRole("switch", { name: "LBL_SECRET_ON" }),
      );

      expect(
        await screen.findByText("LBL_SECRET_RELEASE_REQUIRES_VALUE"),
      ).toBeInTheDocument();
      expect(
        screen.queryByText("LBL_SECRET_VALUE_HIDDEN"),
      ).not.toBeInTheDocument();
    });

    it("値を入力すればシークレットを解除できる", async () => {
      givenRecord({ secret: 1, value: "", type: "integer" });

      render(<ParameterEdit recordId="2" isOpen />);
      await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

      await userEvent.click(
        screen.getByRole("switch", { name: "LBL_SECRET_ON" }),
      );
      await userEvent.type(screen.getByRole("spinbutton"), "45");
      await userEvent.click(save());

      await waitFor(() => expect(mockPost).toHaveBeenCalled());
      expect(sentParams().value).toBe("45");
      expect(sentParams().secret).toBe("0");
    });

    it("boolean 型では secret を送らない（不整合データを保存で解消できる）", async () => {
      // boolean に secret=1 を送るとサーバーが拒否するため、画面から修正できなくなる
      givenRecord({
        key: "FORCE_MULTI_FACTOR_AUTH",
        type: "boolean",
        secret: 1,
        value: "",
      });

      render(<ParameterEdit recordId="1" isOpen />);
      await screen.findByRole("heading", { name: "FORCE_MULTI_FACTOR_AUTH" });

      await userEvent.click(save());

      await waitFor(() => expect(mockPost).toHaveBeenCalled());
      expect(sentParams()).not.toHaveProperty("secret");
    });

    it("boolean 型に不整合なシークレットが残っていても値を送らない", async () => {
      // boolean はシークレットを設定できないが、過去のデータが残っていても
      // トグルを操作しない限り値を上書きしない
      givenRecord({
        key: "FORCE_MULTI_FACTOR_AUTH",
        type: "boolean",
        secret: 1,
        value: "",
      });

      render(<ParameterEdit recordId="1" isOpen />);
      await screen.findByRole("heading", { name: "FORCE_MULTI_FACTOR_AUTH" });

      await userEvent.click(save());

      await waitFor(() => expect(mockPost).toHaveBeenCalled());
      expect(sentParams()).not.toHaveProperty("value");
    });
  });

  it("boolean 以外は secret を送る", async () => {
    givenRecord({ type: "integer", secret: 0, value: "30" });

    render(<ParameterEdit recordId="2" isOpen />);
    await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

    await userEvent.click(save());

    await waitFor(() => expect(mockPost).toHaveBeenCalled());
    expect(sentParams().secret).toBe("0");
  });

  it("シークレットでない変数は値欄を編集しなくても value を送る", async () => {
    givenRecord({ secret: 0, value: "30" });

    render(<ParameterEdit recordId="2" isOpen />);
    await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

    await userEvent.click(save());

    await waitFor(() => expect(mockPost).toHaveBeenCalled());
    expect(sentParams().value).toBe("30");
  });

  it("integer 型で値を空にすると空文字を送る（サーバー側で 0 に正規化される）", async () => {
    givenRecord({ type: "integer", value: "30", secret: 0 });

    render(<ParameterEdit recordId="2" isOpen />);
    await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

    await userEvent.clear(screen.getByRole("spinbutton"));
    await userEvent.click(save());

    await waitFor(() => expect(mockPost).toHaveBeenCalled());
    expect(sentParams()).toHaveProperty("value", "");
  });

  it("キャンセルすると onCancel と onOpenChange(false) を呼ぶ", async () => {
    givenRecord({});
    const onCancel = vi.fn();
    const onOpenChange = vi.fn();

    render(
      <ParameterEdit
        recordId="2"
        isOpen
        onCancel={onCancel}
        onOpenChange={onOpenChange}
      />,
    );
    await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

    await userEvent.click(screen.getByRole("button", { name: "LBL_CANCEL" }));

    expect(onCancel).toHaveBeenCalled();
    expect(onOpenChange).toHaveBeenCalledWith(false);
  });

  it("保存に成功すると onSave を呼ぶ", async () => {
    givenRecord({ secret: 0, value: "30" });
    const onSave = vi.fn();

    render(<ParameterEdit recordId="2" isOpen onSave={onSave} />);
    await screen.findByRole("heading", { name: "USER_LOCK_TIME" });

    await userEvent.click(save());

    await waitFor(() =>
      expect(onSave).toHaveBeenCalledWith(
        expect.objectContaining({ id: 2, key: "USER_LOCK_TIME" }),
      ),
    );
  });
});
