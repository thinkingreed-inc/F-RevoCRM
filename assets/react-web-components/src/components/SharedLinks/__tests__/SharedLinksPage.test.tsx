import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import type { SharedLink, SharedLinkInfo } from "@/types/sharedLinks";
import { SharedLinksPage } from "../SharedLinksPage";

/**
 * 共有リンクの管理画面のテスト
 *
 * SharedLinkAPI（fetch）を mode ごとに差し替える。
 * 仕様: docs/specs/shared_links_widget.md 3.1 / 8.1
 */

const INFO: SharedLinkInfo = {
  user_fields: [{ name: "email1", label: "メールアドレス" }],
  roles: [{ id: "H2", name: "CEO", depth: 1 }],
  groups: [{ id: "10", name: "営業チーム" }],
  users: [{ id: "1", name: "管理者" }],
  limits: {
    title_max: 100,
    url_max: 2000,
    description_max: 500,
    params_max: 20,
    param_name_max: 100,
    param_value_max: 1000,
    window_min: 200,
    window_max: 4000,
    default_window_width: 1024,
    default_window_height: 768,
  },
  labels: { LBL_MORE_TARGETS: "ほか %s 件" },
};

const link = (id: number, overrides: Partial<SharedLink> = {}): SharedLink => ({
  id,
  sequence: id,
  title: `リンク${id}`,
  url: `https://example.com/${id}`,
  description: "",
  method: "GET",
  open_mode: "tab",
  window_width: null,
  window_height: null,
  noreferrer: false,
  active: true,
  visibility: "all",
  params: [],
  targets: [],
  has_warning: false,
  post_blocked: false,
  ...overrides,
});

type Handler = (params: URLSearchParams) => unknown;

const mockFetch = vi.fn();

/** mode ごとの応答を差し込む。値が Error ならエラー応答を返す */
const givenApi = (handlers: Record<string, Handler>) => {
  mockFetch.mockImplementation(async (_url: string, init?: RequestInit) => {
    const params = new URLSearchParams(String(init?.body ?? ""));
    const mode = params.get("mode") ?? "";
    const handler = handlers[mode];
    const result = handler ? handler(params) : undefined;
    const body =
      result instanceof Error
        ? { success: false, error: { message: result.message } }
        : { success: true, result };
    return { ok: true, status: 200, text: async () => JSON.stringify(body) };
  });
};

/** 送信した mode と内容 */
const sent = (mode: string): URLSearchParams[] =>
  mockFetch.mock.calls
    .map(([, init]) => new URLSearchParams(String(init?.body ?? "")))
    .filter((params) => params.get("mode") === mode);

const rows = () => screen.queryAllByTestId("shared-link-row");

describe("SharedLinksPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    window.csrfMagicName = "__vtrftk";
    window.csrfMagicToken = "test-token";
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("TC-UI-010 読み込み中を表示し、読み込んだらリンクを並び順で表示する", async () => {
    let resolveInfo: (value: unknown) => void = () => {};
    mockFetch.mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          resolveInfo = resolve;
        }),
    );
    render(<SharedLinksPage />);
    expect(screen.getByText("LBL_LOADING")).toBeInTheDocument();

    givenApi({ list: () => ({ links: [link(2), link(1)] }) });
    resolveInfo({
      ok: true,
      status: 200,
      text: async () => JSON.stringify({ success: true, result: INFO }),
    });

    await waitFor(() => expect(rows()).toHaveLength(2));
    expect(within(rows()[0]).getByText("リンク2")).toBeInTheDocument();
    expect(within(rows()[1]).getByText("リンク1")).toBeInTheDocument();
  });

  it("TC-UI-015 API には CSRF トークンを付け、module・parent・api を指定する", async () => {
    givenApi({ info: () => INFO, list: () => ({ links: [] }) });
    render(<SharedLinksPage />);
    await screen.findByText("LBL_NO_LINKS");

    const [info] = sent("info");
    expect(info.get("__vtrftk")).toBe("test-token");
    expect(info.get("module")).toBe("SharedLinks");
    expect(info.get("parent")).toBe("Settings");
    expect(info.get("api")).toBe("SharedLinkAPI");
  });

  it("TC-UI-013 0 件なら登録されていない旨を表示する", async () => {
    givenApi({ info: () => INFO, list: () => ({ links: [] }) });
    render(<SharedLinksPage />);

    expect(await screen.findByText("LBL_NO_LINKS")).toBeInTheDocument();
  });

  it("TC-UI-011 初期表示で権限エラーなどが返ったらメッセージを出す", async () => {
    givenApi({ info: () => new Error("Permission denied") });
    render(<SharedLinksPage />);

    expect(await screen.findByText("Permission denied")).toBeInTheDocument();
  });

  it("TC-UI-012 不正な応答（JSON でない）ならエラーを出す", async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      status: 200,
      text: async () => "<html>Fatal error</html>",
    });
    render(<SharedLinksPage />);

    expect(await screen.findByText("Invalid response")).toBeInTheDocument();
  });

  it("TC-UI-014 開き方・公開範囲・状態・警告を一覧に出す", async () => {
    givenApi({
      info: () => INFO,
      list: () => ({
        links: [
          link(1, {
            open_mode: "window",
            window_width: 800,
            window_height: 600,
            active: false,
          }),
          link(2, {
            visibility: "targets",
            has_warning: true,
            targets: [
              { type: "Roles", id: "H2", name: "CEO", deleted: false },
              {
                type: "RoleAndSubordinates",
                id: "H3",
                name: "営業部長",
                deleted: false,
              },
              { type: "Groups", id: "99", name: null, deleted: true },
              { type: "Users", id: "1", name: "管理者", deleted: false },
              { type: "Users", id: "5", name: "山田", deleted: false },
            ],
          }),
        ],
      }),
    });
    render(<SharedLinksPage />);
    await waitFor(() => expect(rows()).toHaveLength(2));

    expect(rows()[0]).toHaveTextContent("LBL_OPEN_MODE_WINDOW（800×600）");
    expect(rows()[0]).toHaveTextContent("LBL_VISIBILITY_ALL");
    expect(rows()[0]).toHaveTextContent("LBL_INACTIVE");
    expect(
      within(rows()[0]).queryByRole("img", { name: "LBL_HAS_WARNING" }),
    ).toBeNull();

    expect(rows()[1]).toHaveTextContent(
      "LBL_TARGET_ROLES: CEO、LBL_TARGET_ROLE_AND_SUBORDINATES: 営業部長、LBL_TARGET_GROUPS: LBL_DELETED_TARGET ほか 2 件",
    );
    expect(rows()[1]).toHaveTextContent("LBL_ACTIVE");
    expect(
      within(rows()[1]).getByRole("img", { name: "LBL_HAS_WARNING" }),
    ).toBeInTheDocument();
  });

  it("TC-UI-018 POST の送り先が許可されていないリンクに印を出す", async () => {
    givenApi({
      info: () => INFO,
      list: () => ({
        links: [
          link(1, { method: "POST", post_blocked: true }),
          link(2, { method: "POST" }),
        ],
      }),
    });
    render(<SharedLinksPage />);
    await waitFor(() => expect(rows()).toHaveLength(2));

    expect(
      within(rows()[0]).getByRole("img", { name: "LBL_POST_BLOCKED" }),
    ).toBeInTheDocument();
    expect(
      within(rows()[1]).queryByRole("img", { name: "LBL_POST_BLOCKED" }),
    ).toBeNull();
  });

  it("TC-UI-016 並び替えると、入れ替えた順の ID を送って一覧を読み直す", async () => {
    let links = [link(1), link(2), link(3)];
    givenApi({
      info: () => INFO,
      list: () => ({ links }),
      reorder: (params) => {
        const ids = (params.get("ids") ?? "").split(",").map(Number);
        links = ids.map((id) => link(id));
        return { ids };
      },
    });
    const user = userEvent.setup();
    render(<SharedLinksPage />);
    await waitFor(() => expect(rows()).toHaveLength(3));

    // 先頭の「上へ」と末尾の「下へ」は押せない
    expect(
      screen.getByRole("button", { name: "LBL_MOVE_UP リンク1" }),
    ).toBeDisabled();
    expect(
      screen.getByRole("button", { name: "LBL_MOVE_DOWN リンク3" }),
    ).toBeDisabled();

    await user.click(
      screen.getByRole("button", { name: "LBL_MOVE_DOWN リンク1" }),
    );

    await waitFor(() =>
      expect(within(rows()[0]).getByText("リンク2")).toBeInTheDocument(),
    );
    expect(sent("reorder")[0].get("ids")).toBe("2,1,3");
  });

  it("TC-UI-017 削除は確認してから行い、キャンセルなら送らない", async () => {
    let links = [link(1), link(2)];
    givenApi({
      info: () => INFO,
      list: () => ({ links }),
      delete: (params) => {
        links = links.filter((l) => String(l.id) !== params.get("id"));
        return { id: Number(params.get("id")), deleted: true };
      },
    });
    const confirm = vi.spyOn(window, "confirm");
    const user = userEvent.setup();
    render(<SharedLinksPage />);
    await waitFor(() => expect(rows()).toHaveLength(2));

    confirm.mockReturnValueOnce(false);
    await user.click(
      within(rows()[0]).getByRole("button", { name: "LBL_DELETE" }),
    );
    expect(sent("delete")).toHaveLength(0);

    confirm.mockReturnValueOnce(true);
    await user.click(
      within(rows()[0]).getByRole("button", { name: "LBL_DELETE" }),
    );
    await waitFor(() => expect(rows()).toHaveLength(1));
    expect(sent("delete")[0].get("id")).toBe("1");
    expect(confirm).toHaveBeenLastCalledWith("LBL_CONFIRM_DELETE\nリンク1");
    expect(screen.getByRole("status")).toHaveTextContent("LBL_DELETED");
  });

  it("TC-UI-051 追加して保存すると JSON で送り、モーダルを閉じて一覧を読み直す", async () => {
    let links: SharedLink[] = [];
    givenApi({
      info: () => INFO,
      list: () => ({ links }),
      save: (params) => {
        const input = JSON.parse(params.get("link") ?? "{}");
        links = [link(9, input)];
        return { saved: true, id: 9 };
      },
    });
    const user = userEvent.setup();
    render(<SharedLinksPage />);
    await screen.findByText("LBL_NO_LINKS");

    await user.click(screen.getByRole("button", { name: /LBL_ADD_LINK/ }));
    const dialog = screen.getByRole("dialog");
    await user.type(within(dialog).getByLabelText(/LBL_TITLE/), "勤怠");
    await user.type(
      within(dialog).getByLabelText(/LBL_URL/),
      "https://a.example.com/?x=1&y=2",
    );
    await user.click(within(dialog).getByRole("button", { name: "LBL_SAVE" }));

    await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
    expect(rows()).toHaveLength(1);
    const [saveCall] = sent("save");
    expect(saveCall.has("id")).toBe(false);
    expect(JSON.parse(saveCall.get("link") ?? "")).toMatchObject({
      title: "勤怠",
      url: "https://a.example.com/?x=1&y=2",
    });
    expect(screen.getByRole("status")).toHaveTextContent("LBL_SAVED");
  });

  it("TC-UI-052 TC-UI-055 編集して保存すると ID を付けて送る。入力エラーならモーダルを閉じない", async () => {
    let attempts = 0;
    givenApi({
      info: () => INFO,
      list: () => ({ links: [link(4)] }),
      save: () => {
        attempts++;
        return attempts === 1
          ? { saved: false, errors: { title: "重複しています" } }
          : { saved: true, id: 4 };
      },
    });
    const user = userEvent.setup();
    render(<SharedLinksPage />);
    await waitFor(() => expect(rows()).toHaveLength(1));

    await user.click(
      within(rows()[0]).getByRole("button", { name: "LBL_EDIT" }),
    );
    const dialog = screen.getByRole("dialog");
    expect(within(dialog).getByLabelText(/LBL_TITLE/)).toHaveValue("リンク4");

    await user.click(within(dialog).getByRole("button", { name: "LBL_SAVE" }));
    expect(
      await within(dialog).findByText("重複しています"),
    ).toBeInTheDocument();
    expect(screen.getByRole("dialog")).toBeInTheDocument();
    expect(sent("save")[0].get("id")).toBe("4");

    await user.click(within(dialog).getByRole("button", { name: "LBL_SAVE" }));
    await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
  });
});
