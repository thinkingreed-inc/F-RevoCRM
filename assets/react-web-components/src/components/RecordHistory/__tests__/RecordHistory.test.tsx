import React from "react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  fireEvent,
  render,
  screen,
  waitFor,
  within,
} from "@testing-library/react";
import { RecordHistory } from "../RecordHistory";
import { historyFixture } from "./fixtures";

function mockFetch(historyOk = true) {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = String(input);
    if (url.includes("api=GetHistory")) {
      return historyOk
        ? new Response(JSON.stringify(historyFixture), { status: 200 })
        : new Response("", { status: 500 });
    }
    // 翻訳 API は失敗させ、既定文言で表示されることを確認する
    return new Response("", { status: 404 });
  });
}

const eventIds = () =>
  screen
    .queryAllByTestId("history-event")
    .map((el) => Number(el.getAttribute("data-event-id")));

describe("RecordHistory (full)", () => {
  let fetchMock: ReturnType<typeof mockFetch>;
  beforeEach(() => {
    fetchMock = mockFetch();
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(console, "error").mockImplementation(() => {});
    vi.spyOn(console, "warn").mockImplementation(() => {});
  });
  afterEach(() => vi.restoreAllMocks());

  it("履歴 API を 1 回だけ呼び、月見出しつきでイベントを表示する", async () => {
    render(<RecordHistory module="Accounts" recordId="3" />);
    await waitFor(() => expect(eventIds()).toHaveLength(6));

    const historyCalls = fetchMock.mock.calls.filter(([u]) =>
      String(u).includes("api=GetHistory"),
    );
    expect(historyCalls).toHaveLength(1);
    expect(String(historyCalls[0][0])).toContain("parent_module=Accounts");
    expect(String(historyCalls[0][0])).toContain("parent_id=3");
    expect(screen.getByText("2026年10月")).toBeInTheDocument();
    // 最終更新者は既定で出ない
    expect(screen.queryByText("最終更新者")).not.toBeInTheDocument();
  });

  it("変更者で絞り込むと、追加の API 呼び出しなしで表示が変わる", async () => {
    render(<RecordHistory module="Accounts" recordId="3" />);
    await waitFor(() => expect(eventIds()).toHaveLength(6));
    const before = fetchMock.mock.calls.length;

    fireEvent.click(screen.getByRole("button", { name: /佐藤 花子/ }));
    await waitFor(() => expect(eventIds()).toEqual([7, 4]));
    expect(fetchMock.mock.calls.length).toBe(before);
    expect(screen.getByText("2 / 6 件")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "すべて解除" }));
    await waitFor(() => expect(eventIds()).toHaveLength(6));
  });

  it("キーワード検索とシステム項目トグル", async () => {
    render(<RecordHistory module="Accounts" recordId="3" />);
    await waitFor(() => expect(eventIds()).toHaveLength(6));

    fireEvent.change(screen.getByLabelText("項目名・値で検索"), {
      target: { value: "3333" },
    });
    await waitFor(() => expect(eventIds()).toEqual([8]));

    // 「3333」は最終更新者の値に一致しないので、トグルを ON にしても出ない
    fireEvent.click(screen.getByLabelText("システム項目を表示"));
    expect(screen.queryByText("最終更新者")).not.toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("項目名・値で検索"), {
      target: { value: "" },
    });
    await waitFor(() =>
      expect(screen.getAllByText("最終更新者").length).toBeGreaterThan(0),
    );
  });

  it("項目名をクリックすると、その項目の変遷を表示する", async () => {
    render(<RecordHistory module="Accounts" recordId="3" />);
    await waitFor(() => expect(eventIds()).toHaveLength(6));

    const latest = screen.getAllByTestId("history-event")[0];
    fireEvent.click(
      within(latest).getByRole("button", { name: "代表電話番号" }),
    );

    expect(await screen.findByText("代表電話番号の変遷")).toBeInTheDocument();
    const entries = screen.getAllByTestId("field-track-entry");
    expect(entries).toHaveLength(3);
    expect(within(entries[0]).getByText("現在")).toBeInTheDocument();
    expect(
      within(entries[2]).getByText("システム管理者が作成時に設定"),
    ).toBeInTheDocument();
  });

  it("項目ごとの一覧は詳細画面の並び順になる", async () => {
    render(<RecordHistory module="Accounts" recordId="3" />);
    await waitFor(() => expect(eventIds()).toHaveLength(6));
    fireEvent.click(screen.getByRole("button", { name: "項目ごと" }));
    const nav = await screen.findByRole("navigation", { name: "項目" });
    const labels = within(nav)
      .getAllByRole("button")
      .map((b) => b.textContent?.replace(/\d+$/, ""));
    // 変更回数（代表電話番号 2 回）ではなくレイアウト順
    expect(labels).toEqual([
      "Webサイト",
      "代表電話番号",
      "格付け",
      "年間売上高",
      "詳細内容",
    ]);
  });

  it("増減は API が整形した値で表示する", async () => {
    render(<RecordHistory module="Accounts" recordId="3" />);
    expect(
      await screen.findByText("+220,000,000.00（+44%）"),
    ).toBeInTheDocument();
  });

  it("変更日時はユーザー設定の日付・時刻形式で表示する", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) =>
        String(input).includes("api=GetHistory")
          ? new Response(
              JSON.stringify({
                ...historyFixture,
                format: { dateFormat: "dd-mm-yyyy", hourFormat: "12" },
              }),
            )
          : new Response("", { status: 404 }),
      ),
    );
    render(<RecordHistory module="Accounts" recordId="3" />);
    const first = (await screen.findAllByTestId("history-event"))[0];
    expect(within(first).getByText("01/11")).toBeInTheDocument();
    expect(within(first).getByText("9:30 AM")).toBeInTheDocument();
    expect(
      within(first).getByTitle("01-11-2026（日）9:30 AM"),
    ).toBeInTheDocument();
  });

  it("作成時の値は折りたたまれていて、開ける", async () => {
    render(<RecordHistory module="Accounts" recordId="3" />);
    await waitFor(() => expect(eventIds()).toHaveLength(6));
    const toggle = screen.getByRole("button", {
      name: "作成時の値を表示（2項目）",
    });
    fireEvent.click(toggle);
    expect(
      screen.getByRole("button", { name: "作成時の値を閉じる" }),
    ).toBeInTheDocument();
  });

  it("API が失敗したら再読み込みボタンを出す", async () => {
    vi.stubGlobal("fetch", mockFetch(false));
    render(<RecordHistory module="Accounts" recordId="3" />);
    expect(
      await screen.findByText("更新履歴を取得できませんでした"),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "再読み込み" }),
    ).toBeInTheDocument();
  });
});

describe("RecordHistory (compact)", () => {
  beforeEach(() => {
    vi.stubGlobal("fetch", mockFetch());
    vi.spyOn(console, "error").mockImplementation(() => {});
    vi.spyOn(console, "warn").mockImplementation(() => {});
  });
  afterEach(() => vi.restoreAllMocks());

  it("最新 page-size 件を 1 行ずつ要約して表示する", async () => {
    render(
      <RecordHistory
        module="Accounts"
        recordId="3"
        variant="compact"
        pageSize={2}
        fullHistoryUrl="index.php?x=1"
      />,
    );
    const rows = await screen.findAllByTestId("compact-history-row");
    expect(rows).toHaveLength(2);
    expect(
      within(rows[0]).getByText("代表電話番号：03-1111-2222 → 03-3333-4444"),
    ).toBeInTheDocument();
    expect(
      within(rows[1]).getByText(
        /^Webサイト：https:\/\/mirai\.example\.jp → （空）\s+ほか1項目$/,
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "すべて表示" })).toHaveAttribute(
      "href",
      "index.php?x=1",
    );
  });
});
