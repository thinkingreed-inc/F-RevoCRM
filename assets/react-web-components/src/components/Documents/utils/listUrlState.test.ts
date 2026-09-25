import { describe, expect, it } from "vitest";
import {
  buildListUrl,
  isSameListUrlState,
  parseListUrlState,
  type DocumentsListUrlState,
} from "./listUrlState";

const fallback: DocumentsListUrlState = {
  folderId: "all",
  page: 1,
  filterType: "all",
  searchKeyword: "",
};

describe("parseListUrlState", () => {
  it("指定が無ければ既定値を返す", () => {
    expect(parseListUrlState("?module=Documents&view=List", fallback)).toEqual(
      fallback,
    );
  });

  it("URLの表示状態を読み取る", () => {
    expect(
      parseListUrlState(
        "?module=Documents&view=List&doc_folder=12&doc_page=3&doc_filter=starred&doc_q=%E5%A5%91%E7%B4%84",
        fallback,
      ),
    ).toEqual({
      folderId: 12,
      page: 3,
      filterType: "starred",
      searchKeyword: "契約",
    });
  });

  it("すべてのドキュメントは all として扱う", () => {
    expect(parseListUrlState("?doc_folder=all", fallback).folderId).toBe("all");
  });

  it("不正な値は既定値にする", () => {
    const parsed = parseListUrlState(
      "?doc_folder=abc&doc_page=0&doc_filter=unknown",
      fallback,
    );
    expect(parsed.folderId).toBe("all");
    expect(parsed.page).toBe(1);
    expect(parsed.filterType).toBe("all");
  });

  it("フォルダの指定が無ければ渡された既定のフォルダを使う", () => {
    expect(
      parseListUrlState("?module=Documents", { ...fallback, folderId: 5 })
        .folderId,
    ).toBe(5);
  });
});

describe("buildListUrl", () => {
  it("既存のパラメータを保ったままURLを組み立てる", () => {
    expect(
      buildListUrl(
        "?module=Documents&view=List",
        { folderId: 12, page: 2, filterType: "starred", searchKeyword: "請求" },
        "/index.php",
      ),
    ).toBe(
      "/index.php?module=Documents&view=List&doc_folder=12&doc_page=2&doc_filter=starred&doc_q=%E8%AB%8B%E6%B1%82",
    );
  });

  it("既定値の項目はURLに載せない", () => {
    expect(
      buildListUrl("?module=Documents&view=List", fallback, "/index.php"),
    ).toBe("/index.php?module=Documents&view=List");
  });

  it("既定値に戻したときは以前のパラメータを消す", () => {
    expect(
      buildListUrl(
        "?module=Documents&doc_folder=12&doc_page=4&doc_filter=starred&doc_q=x",
        fallback,
        "/index.php",
      ),
    ).toBe("/index.php?module=Documents");
  });

  it("読み取り→組み立てで同じURLになる", () => {
    const search =
      "?module=Documents&view=List&doc_folder=7&doc_page=3&doc_filter=recent";
    const parsed = parseListUrlState(search, fallback);
    expect(buildListUrl(search, parsed, "/index.php")).toBe(
      `/index.php${search}`,
    );
  });
});

describe("isSameListUrlState", () => {
  it("同じ内容なら true", () => {
    expect(isSameListUrlState(fallback, { ...fallback })).toBe(true);
  });

  it("ページが違えば false", () => {
    expect(isSameListUrlState(fallback, { ...fallback, page: 2 })).toBe(false);
  });

  it("フォルダが違えば false", () => {
    expect(isSameListUrlState(fallback, { ...fallback, folderId: 3 })).toBe(
      false,
    );
  });
});
