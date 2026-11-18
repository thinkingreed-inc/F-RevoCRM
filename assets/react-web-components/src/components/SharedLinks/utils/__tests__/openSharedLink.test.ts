import { beforeEach, describe, expect, it, vi } from "vitest";
import type { SharedLinkWidgetItem } from "@/types/sharedLinks";
import { openSharedLink, windowFeatures, windowName } from "../openSharedLink";
import type { OpenEnvironment } from "../openSharedLink";

/**
 * ダッシュボードの共有リンクを開く処理のテスト
 *
 * window.open・フォームの送信・通知を差し替えて、どう開くかを確かめる。
 * テスト仕様書: docs/tests/SharedLinks/TS-04_リンクの開き方.md
 */

interface Submitted {
  doc: Document;
  method: string;
  action: string;
  target: string;
  rel: string | null;
  inputs: [string, string][];
}

const GET_TAB: SharedLinkWidgetItem = {
  id: 1,
  title: "勤怠",
  description: "",
  method: "GET",
  url: "https://kintai.example.com/login?lang=ja&uid=7",
  params: [],
  openMode: "tab",
  width: null,
  height: null,
  noreferrer: false,
  blocked: false,
};

const POST_WINDOW: SharedLinkWidgetItem = {
  ...GET_TAB,
  id: 2,
  method: "POST",
  url: "https://sso.example.com/post",
  params: [
    { name: "mail", value: "taro@example.com" },
    { name: "k", value: `a'b"<c>` },
  ],
  openMode: "window",
  width: 800,
  height: 600,
};

const MESSAGES = { popupBlocked: "POPUP", postBlocked: "BLOCKED" };

let opens: unknown[][];
let openReturn: unknown;
let submitted: Submitted[];
let notices: string[];

/** 今のウィンドウ: 左上 (100,50)、外寸 1600×900、画面 1920×1080 */
function fakeWindow(): Window {
  return {
    screen: { availWidth: 1920, availHeight: 1080 },
    outerWidth: 1600,
    outerHeight: 900,
    screenX: 100,
    screenY: 50,
    open: (...args: unknown[]) => {
      opens.push(args);
      return openReturn;
    },
  } as unknown as Window;
}

function env(): OpenEnvironment {
  return {
    window: fakeWindow(),
    document,
    notify: (message) => notices.push(message),
    csrfName: "__vtrftk",
  };
}

const open = (link: SharedLinkWidgetItem, plainClick = true) =>
  openSharedLink(link, plainClick, env(), MESSAGES);

/** window.open が返すポップアップ（CRM と同じオリジンの about:blank） */
const popup = () => ({
  opener: window as unknown,
  document: document.implementation.createHTMLDocument(""),
});

beforeEach(() => {
  opens = [];
  openReturn = {};
  submitted = [];
  notices = [];
  document.body.innerHTML = "";
  vi.spyOn(HTMLFormElement.prototype, "submit").mockImplementation(function (
    this: HTMLFormElement,
  ) {
    submitted.push({
      doc: this.ownerDocument,
      method: this.method,
      action: this.action,
      target: this.target,
      rel: this.getAttribute("rel"),
      inputs: Array.from(this.elements).map((e) => [
        (e as HTMLInputElement).name,
        (e as HTMLInputElement).value,
      ]),
    });
  });
});

describe("DT-1 クリックしたときの動き", () => {
  it("TC-OPN-001 GET・別タブはブラウザに任せる", () => {
    expect(open(GET_TAB)).toBe(false);
    expect(opens).toEqual([]);
    expect(submitted).toEqual([]);
  });

  it("TC-OPN-002 GET・別ウィンドウは window.open で開く（今のウィンドウの中央）", () => {
    const link = {
      ...GET_TAB,
      openMode: "window" as const,
      width: 800,
      height: 600,
    };
    expect(open(link)).toBe(true);
    expect(opens).toEqual([
      [
        GET_TAB.url,
        "frSharedLink_1",
        "width=800,height=600,left=500,top=200,resizable=yes,scrollbars=yes,noopener",
      ],
    ]);
  });

  it("TC-OPN-003 GET・別ウィンドウでも中クリック・修飾キー付きはブラウザに任せる", () => {
    const link = {
      ...GET_TAB,
      openMode: "window" as const,
      width: 800,
      height: 600,
    };
    expect(open(link, false)).toBe(false);
    expect(opens).toEqual([]);
  });

  it("TC-OPN-004 POST・別タブは target=_blank でフォームを送り、window.open しない", () => {
    expect(open({ ...POST_WINDOW, openMode: "tab" })).toBe(true);
    expect(opens).toEqual([]);
    expect(submitted).toHaveLength(1);
    expect(submitted[0]).toMatchObject({
      doc: document,
      method: "post",
      action: POST_WINDOW.url,
      target: "_blank",
      rel: "noopener",
    });
  });

  it("TC-OPN-005 POST・別ウィンドウは空のウィンドウを開き、その中でフォームを送り、opener を外す", () => {
    const opened = popup();
    openReturn = opened;
    open(POST_WINDOW);

    expect(opens).toEqual([
      [
        "about:blank",
        "frSharedLink_2",
        // フォームの送り先にするため noopener を付けない
        "width=800,height=600,left=500,top=200,resizable=yes,scrollbars=yes",
      ],
    ]);
    expect(submitted).toHaveLength(1);
    // rel="noopener" を付けると名前付きのウィンドウを探さず新しいタブで送ってしまうため、
    // 開いたウィンドウの文書の中で _self へ送る
    expect(submitted[0]).toMatchObject({
      doc: opened.document,
      target: "_self",
      rel: null,
    });
    expect(opened.opener).toBeNull();
    expect(opened.document.querySelector('meta[name="referrer"]')).toBeNull();
  });

  it("TC-OPN-005 ポップアップの中を読めないときは、名前を指定して CRM の画面から rel なしで送る", () => {
    const opened = { opener: window as unknown };
    Object.defineProperty(opened, "document", {
      get() {
        throw new DOMException("Blocked a frame", "SecurityError");
      },
    });
    openReturn = opened;
    open({ ...POST_WINDOW, noreferrer: true });

    expect(submitted).toHaveLength(1);
    expect(submitted[0]).toMatchObject({
      doc: document,
      target: "frSharedLink_2",
      rel: null,
    });
  });

  it("TC-OPN-006 POST・別ウィンドウでポップアップがブロックされたら通知して送らない", () => {
    openReturn = null;
    expect(open(POST_WINDOW)).toBe(true);
    expect(notices).toEqual(["POPUP"]);
    expect(submitted).toEqual([]);
  });

  it.each(["tab", "window"] as const)(
    "TC-OPN-044 送り先が CSP で許可されていない POST（%s）は、開かずに通知する",
    (openMode) => {
      expect(open({ ...POST_WINDOW, openMode, blocked: true })).toBe(true);
      expect(notices).toEqual(["BLOCKED"]);
      expect(opens).toEqual([]);
      expect(submitted).toEqual([]);
    },
  );

  it("TC-OPN-007 POST は登録したパラメータだけを登録順に送り、CSRF トークンと同じ名前の値は送らない", () => {
    open({
      ...POST_WINDOW,
      openMode: "tab",
      params: [...POST_WINDOW.params, { name: "__vtrftk", value: "x" }],
    });
    expect(submitted[0].inputs).toEqual([
      ["mail", "taro@example.com"],
      ["k", `a'b"<c>`],
    ]);
  });

  it("TC-OPN-008 送信後にフォームを残さない", () => {
    open({ ...POST_WINDOW, openMode: "tab" });
    expect(submitted).toHaveLength(1);
    expect(document.querySelectorAll("form")).toHaveLength(0);
  });
});

describe("DT-2 リファラー", () => {
  const getWindow = {
    ...GET_TAB,
    openMode: "window" as const,
    width: 800,
    height: 600,
  };

  it("TC-OPN-012 GET・別ウィンドウで noreferrer オフなら noopener だけ", () => {
    open(getWindow);
    expect(String(opens[0][2])).toMatch(/,noopener$/);
  });

  it("TC-OPN-013 GET・別ウィンドウで noreferrer オンなら noopener,noreferrer", () => {
    open({ ...getWindow, noreferrer: true });
    expect(String(opens[0][2])).toMatch(/,noopener,noreferrer$/);
  });

  it("TC-OPN-014 POST・別タブで noreferrer オフならフォームの rel は noopener", () => {
    open({ ...POST_WINDOW, openMode: "tab" });
    expect(submitted[0].rel).toBe("noopener");
  });

  it("TC-OPN-015 POST・別タブで noreferrer オンならフォームの rel は noopener noreferrer", () => {
    open({ ...POST_WINDOW, openMode: "tab", noreferrer: true });
    expect(submitted[0].rel).toBe("noopener noreferrer");
  });

  it("TC-OPN-015 POST・別ウィンドウで noreferrer オンなら、ポップアップに referrer=no-referrer の meta を入れてから送る", () => {
    const opened = popup();
    openReturn = opened;
    open({ ...POST_WINDOW, noreferrer: true });

    expect(submitted[0].doc).toBe(opened.document);
    expect(
      opened.document
        .querySelector('meta[name="referrer"]')
        ?.getAttribute("content"),
    ).toBe("no-referrer");
  });
});

describe("DT-3 別ウィンドウのサイズ", () => {
  it("TC-OPN-020 画面に収まるサイズは指定どおりで、今のウィンドウの中央に開く", () => {
    expect(
      windowFeatures({ ...GET_TAB, width: 1000, height: 500 }, fakeWindow()),
    ).toMatch(/^width=1000,height=500,left=400,top=250,/);
  });

  it("TC-OPN-021 画面より大きいサイズは画面に収め、はみ出す分は左上に寄せる", () => {
    expect(
      windowFeatures({ ...GET_TAB, width: 5000, height: 4000 }, fakeWindow()),
    ).toMatch(/^width=1920,height=1080,left=100,top=50,/);
  });

  it("ウィンドウの名前はリンクごとに固定", () => {
    expect(windowName({ ...GET_TAB, id: 42 })).toBe("frSharedLink_42");
  });
});
