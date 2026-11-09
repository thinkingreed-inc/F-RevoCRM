import React from "react";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { act, render, screen, waitFor } from "@testing-library/react";
import { ListFavorites } from "../ListFavorites";
import { FAVORITES_CHANGED_EVENT, FAVORITES_RENDERED_EVENT } from "../events";
import type { FavoriteListItem } from "@/types/listFavorite";

const mockFetch = vi.fn();

const LABELS = {
  LBL_FAVORITE_LIST: "お気に入りリスト",
  LBL_REMOVE_FROM_FAVORITES: "お気に入りから外す",
};

const item = (id: number, name: string): FavoriteListItem => ({
  id,
  name,
  url: `index.php?module=Accounts&view=List&viewname=${id}`,
});

const renderFavorites = (
  props: Partial<React.ComponentProps<typeof ListFavorites>> = {},
) =>
  render(
    <ListFavorites
      module="Accounts"
      favorites={[item(10, "リスト1"), item(20, "リスト2")]}
      app="MARKETING"
      labels={LABELS}
      {...props}
    />,
  );

const jsonResponse = (body: unknown, status = 200) =>
  Promise.resolve({
    ok: status >= 200 && status < 300,
    status,
    json: async () => body,
  });

const dispatch = (name: string, detail: unknown) => {
  act(() => {
    document.dispatchEvent(new CustomEvent(name, { detail }));
  });
};

const listNames = () =>
  screen.queryAllByRole("link").map((link) => link.textContent);

const rowOf = (name: string) =>
  screen.getByRole("link", { name }).closest("li") as HTMLElement;

describe("ListFavorites", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("お気に入りが無い場合は欄ごと表示しない", () => {
    const { container } = renderFavorites({ favorites: [] });
    expect(container).toBeEmptyDOMElement();
  });

  it("お気に入りを既存のリストと同じマークアップで表示する", () => {
    renderFavorites();

    expect(screen.getAllByText("お気に入りリスト")[0]).toHaveClass(
      "lists-header",
    );
    const link = screen.getByRole("link", { name: "リスト1" });
    expect(link).toHaveClass("filterName");
    expect(link).toHaveAttribute("data-filter-id", "10");
    expect(link).toHaveAttribute(
      "href",
      "index.php?module=Accounts&view=List&viewname=10&app=MARKETING",
    );
    expect(mockFetch).not.toHaveBeenCalled();
  });

  it("行の class は listViewFilter だけにし、選択中（active）などは ListSidebar.js に任せる", () => {
    renderFavorites();
    const row = rowOf("リスト1");
    row.classList.add("active", "filter-search-hide");

    dispatch(FAVORITES_CHANGED_EVENT, {
      favorites: [item(10, "リスト1"), item(30, "リスト3")],
    });

    expect(rowOf("リスト1")).toHaveClass(
      "listViewFilter",
      "active",
      "filter-search-hide",
    );
    expect(rowOf("リスト3").className).toBe("listViewFilter");
  });

  it("名前の左に塗りつぶしの星、右に ListSidebar.js がメニューを付ける下矢印を出す", () => {
    renderFavorites();
    const row = rowOf("リスト1");

    const star = row.firstElementChild;
    expect(star).toHaveClass("favoriteListStar", "fa-star", "active");
    expect(star).not.toHaveClass("fa-star-o");
    expect(star).toHaveAttribute("data-filter-id", "10");
    expect(star).toHaveAttribute("data-source-module", "Accounts");
    expect(star).toHaveAttribute("title", "お気に入りから外す");

    const arrow = row.querySelector(".js-popover-container > .fa-angle-down");
    expect(arrow).toHaveAttribute("rel", "popover");
    expect(arrow).toHaveAttribute("data-toggle", "popover");
    expect(arrow).toHaveAttribute("data-filter-id", "10");
  });

  it("描画のたびに ListSidebar.js へ知らせる", () => {
    const rendered = vi.fn();
    document.addEventListener(FAVORITES_RENDERED_EVENT, rendered);

    renderFavorites();
    expect(rendered).toHaveBeenCalledTimes(1);

    dispatch(FAVORITES_CHANGED_EVENT, {
      favorites: [item(20, "リスト2"), item(30, "リスト3")],
    });
    expect(rendered).toHaveBeenCalledTimes(2);

    document.removeEventListener(FAVORITES_RENDERED_EVENT, rendered);
  });

  it("app が空の場合は URL に付けない", () => {
    renderFavorites({ app: "" });
    expect(screen.getByRole("link", { name: "リスト1" })).toHaveAttribute(
      "href",
      "index.php?module=Accounts&view=List&viewname=10",
    );
  });

  it("変更イベントで渡された一覧に置き換える", () => {
    renderFavorites();
    dispatch(FAVORITES_CHANGED_EVENT, {
      favorites: [item(20, "リスト2"), item(30, "リスト3")],
    });
    expect(listNames()).toEqual(["リスト2", "リスト3"]);
  });

  it("変更イベントに一覧が無い場合は API から取り直す", async () => {
    mockFetch.mockReturnValue(
      jsonResponse({ favorites: [item(20, "リスト2")] }),
    );
    renderFavorites();

    dispatch(FAVORITES_CHANGED_EVENT, {});

    await waitFor(() => expect(listNames()).toEqual(["リスト2"]));
    const [url, init] = mockFetch.mock.calls[0];
    const params = new URLSearchParams(String(url).split("?")[1]);
    expect(params.get("module")).toBe("CustomView");
    expect(params.get("api")).toBe("GetFavorites");
    expect(params.get("sourceModule")).toBe("Accounts");
    expect(init.method).toBe("GET");
  });

  it("取り直しが HTTP 200 のエラーで返った場合は一覧を変えない", async () => {
    mockFetch.mockReturnValue(
      jsonResponse({
        success: false,
        error: { code: "権限がありません", message: "権限がありません" },
      }),
    );
    const consoleError = vi
      .spyOn(console, "error")
      .mockImplementation(() => {});
    renderFavorites();

    dispatch(FAVORITES_CHANGED_EVENT, {});

    await waitFor(() => expect(consoleError).toHaveBeenCalled());
    expect(listNames()).toEqual(["リスト1", "リスト2"]);
  });
});
