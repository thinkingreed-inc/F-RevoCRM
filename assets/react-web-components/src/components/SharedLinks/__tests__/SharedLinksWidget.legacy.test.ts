import { readFileSync } from "fs";
import { resolve } from "path";
import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from "vitest";

/**
 * ダッシュボードのウィジェット「共有リンク」の JS（React ではない画面側の JS）のテスト
 *
 * 対象: public/layouts/v7/modules/Home/resources/SharedLinksWidget.js
 * 実際の jQuery（public/layouts/v7/lib/jquery/jquery.min.js）を読み込み、
 * ウィジェットの HTML と同じ形の <a> をクリックして動きを確かめる。
 * テスト仕様書: docs/tests/SharedLinks/TS-04_リンクの開き方.md
 */

const ROOT = resolve(__dirname, "../../../../../..");

interface LinkOptions {
  id: number;
  method: "GET" | "POST";
  url: string;
  params: { name: string; value: string }[];
  openMode: "tab" | "window";
  width: number | null;
  height: number | null;
  noreferrer: boolean;
  /** POST の送り先が CSP で許可されていない */
  blocked?: boolean;
}

interface Submitted {
  /** フォームを置いた文書（CRM の画面か、ポップアップか） */
  doc: Document;
  method: string;
  action: string;
  target: string;
  rel: string | null;
  inputs: [string, string][];
}

type WidgetWindow = Window &
  typeof globalThis & {
    jQuery: (el: Element) => { removeData: (key: string) => void };
    app: unknown;
    csrfMagicName?: string;
    csrfMagicToken?: string;
  };

const w = window as WidgetWindow;
let opens: unknown[][] = [];
let openReturn: unknown = {};
let submitted: Submitted[] = [];
const notices: string[] = [];

const GET_TAB: LinkOptions = {
  id: 1,
  method: "GET",
  url: "https://kintai.example.com/login?lang=ja&uid=7",
  params: [],
  openMode: "tab",
  width: null,
  height: null,
  noreferrer: false,
};

const POST_WINDOW: LinkOptions = {
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
  noreferrer: false,
};

/** ウィジェットのテンプレートと同じ形のリンクを置く */
function renderLink(link: LinkOptions): HTMLAnchorElement {
  const a = document.createElement("a");
  a.className = "sharedLink";
  a.setAttribute("data-shared-link", JSON.stringify(link));
  if (link.method === "GET") {
    a.href = link.url;
    a.target = "_blank";
    a.rel = link.noreferrer ? "noopener noreferrer" : "noopener";
  } else {
    a.setAttribute("href", "javascript:void(0);");
  }
  a.textContent = `link${link.id}`;
  document.querySelector(".sharedLinksWidget")?.appendChild(a);
  return a;
}

function click(el: Element, init: MouseEventInit = {}): { prevented: boolean } {
  const event = new MouseEvent("click", {
    bubbles: true,
    cancelable: true,
    button: 0,
    ...init,
  });
  // jsdom は javascript: の遷移を実装していないため、既定の動作だけ止めておく
  const guard = (e: Event) => e.preventDefault();
  let prevented = false;
  const observe = (e: Event) => {
    prevented = e.defaultPrevented;
  };
  document.addEventListener("click", observe);
  window.addEventListener("click", guard);
  el.dispatchEvent(event);
  document.removeEventListener("click", observe);
  window.removeEventListener("click", guard);
  return { prevented };
}

beforeAll(async () => {
  const jquery = readFileSync(
    resolve(ROOT, "public/layouts/v7/lib/jquery/jquery.min.js"),
    "utf8",
  );
  const widget = readFileSync(
    resolve(
      ROOT,
      "public/layouts/v7/modules/Home/resources/SharedLinksWidget.js",
    ),
    "utf8",
  );
  // jQuery とウィジェットの JS はブラウザで読まれるスクリプト。グローバルに評価する
  new Function(jquery).call(window);
  w.app = {
    helper: {
      showErrorNotification: (o: { message: string }) =>
        notices.push(o.message),
    },
    vtranslate: (key: string) => key,
  };
  new Function(widget).call(window);
  // jQuery の $(fn) は読み込み済みの文書では非同期に呼ばれるため、登録を待つ
  await new Promise((done) => setTimeout(done, 0));
});

beforeEach(() => {
  document.body.innerHTML = '<div class="sharedLinksWidget"></div>';
  opens = [];
  submitted = [];
  notices.length = 0;
  openReturn = {};
  w.csrfMagicName = "__vtrftk";
  w.csrfMagicToken = "SECRET";
  vi.spyOn(window, "open").mockImplementation((...args: unknown[]) => {
    opens.push(args);
    return openReturn as Window;
  });
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
  Object.defineProperty(window.screen, "availWidth", {
    value: 1920,
    configurable: true,
  });
  Object.defineProperty(window.screen, "availHeight", {
    value: 1080,
    configurable: true,
  });
  Object.defineProperty(window, "outerWidth", {
    value: 1600,
    configurable: true,
  });
  Object.defineProperty(window, "outerHeight", {
    value: 900,
    configurable: true,
  });
  Object.defineProperty(window, "screenX", { value: 100, configurable: true });
  Object.defineProperty(window, "screenY", { value: 50, configurable: true });
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe("SharedLinksWidget.js", () => {
  describe("DT-1 クリックしたときの動き", () => {
    it("TC-OPN-001 GET・別タブはブラウザに任せる", () => {
      const a = renderLink(GET_TAB);
      const { prevented } = click(a);

      expect(prevented).toBe(false);
      expect(opens).toEqual([]);
      expect(submitted).toEqual([]);
    });

    it("TC-OPN-002 GET・別ウィンドウは通常のクリックを受けて window.open で開く", () => {
      const a = renderLink({
        ...GET_TAB,
        openMode: "window",
        width: 800,
        height: 600,
      });
      const { prevented } = click(a);

      expect(prevented).toBe(true);
      expect(opens).toEqual([
        [
          GET_TAB.url,
          "frSharedLink_1",
          // 今のウィンドウ（左上 100,50・外寸 1600×900）の中央
          "width=800,height=600,left=500,top=200,resizable=yes,scrollbars=yes,noopener",
        ],
      ]);
      expect(submitted).toEqual([]);
    });

    it.each([
      ["中クリック", { button: 1 }],
      ["Ctrl+クリック", { ctrlKey: true }],
      ["⌘+クリック", { metaKey: true }],
      ["Shift+クリック", { shiftKey: true }],
      ["Alt+クリック", { altKey: true }],
    ])("TC-OPN-003 GET・別ウィンドウでも %s はブラウザに任せる", (_, init) => {
      const a = renderLink({
        ...GET_TAB,
        openMode: "window",
        width: 800,
        height: 600,
      });
      const { prevented } = click(a, init);

      expect(prevented).toBe(false);
      expect(opens).toEqual([]);
    });

    it("TC-OPN-004 POST・別タブは target=_blank でフォームを送り、window.open しない", () => {
      const a = renderLink({
        ...POST_WINDOW,
        openMode: "tab",
        width: null,
        height: null,
      });
      const { prevented } = click(a);

      expect(prevented).toBe(true);
      expect(opens).toEqual([]);
      expect(submitted).toHaveLength(1);
      expect(submitted[0]).toMatchObject({
        method: "post",
        action: POST_WINDOW.url,
        target: "_blank",
        rel: "noopener",
      });
    });

    /** window.open が返すポップアップ（CRM と同じオリジンの about:blank） */
    const popup = () => {
      const doc = document.implementation.createHTMLDocument("");
      return { opener: window as unknown, document: doc };
    };

    it("TC-OPN-005 POST・別ウィンドウは空のウィンドウを開き、その中でフォームを送り、opener を外す", () => {
      const opened = popup();
      openReturn = opened;
      const a = renderLink(POST_WINDOW);
      click(a);

      expect(opens).toEqual([
        [
          "about:blank",
          "frSharedLink_2",
          // フォームの送り先にするため noopener を付けない
          "width=800,height=600,left=500,top=200,resizable=yes,scrollbars=yes",
        ],
      ]);
      expect(submitted).toHaveLength(1);
      // CRM の画面ではなく、開いたウィンドウの文書で送る（rel="noopener" を付けると
      // ブラウザが名前付きのウィンドウを探さず新しいタブを開くため、名前で送らない）
      expect(submitted[0].doc).toBe(opened.document);
      expect(submitted[0].target).toBe("_self");
      expect(submitted[0].rel).toBeNull();
      expect(submitted[0].action).toBe(POST_WINDOW.url);
      expect(opened.opener).toBeNull();
      // リファラーを送る設定なら、ポップアップに referrer の meta を入れない
      expect(opened.document.querySelector('meta[name="referrer"]')).toBeNull();
    });

    it("TC-OPN-005 ポップアップの中を読めないときは、名前を指定して CRM の画面から送る", () => {
      const opened = { opener: window as unknown };
      Object.defineProperty(opened, "document", {
        get() {
          throw new DOMException("Blocked a frame", "SecurityError");
        },
      });
      openReturn = opened;
      click(renderLink({ ...POST_WINDOW, noreferrer: true }));

      expect(submitted).toHaveLength(1);
      expect(submitted[0].doc).toBe(document);
      expect(submitted[0].target).toBe("frSharedLink_2");
      // rel を付けると名前で探さなくなるため付けない
      expect(submitted[0].rel).toBeNull();
    });

    it.each(["tab", "window"] as const)(
      "TC-OPN-044 送り先が CSP で許可されていない POST（%s）は、開かずに通知する",
      (openMode) => {
        const a = renderLink({ ...POST_WINDOW, openMode, blocked: true });
        const { prevented } = click(a);

        expect(prevented).toBe(true);
        expect(notices).toEqual(["JS_SHARED_LINK_POST_BLOCKED"]);
        expect(opens).toEqual([]);
        expect(submitted).toEqual([]);
      },
    );

    it("TC-OPN-006 POST・別ウィンドウでポップアップがブロックされたら通知して送らない", () => {
      openReturn = null;
      const a = renderLink(POST_WINDOW);
      const { prevented } = click(a);

      expect(prevented).toBe(true);
      expect(notices).toEqual(["JS_SHARED_LINK_POPUP_BLOCKED"]);
      expect(submitted).toEqual([]);
    });

    it("TC-OPN-007 POST は登録したパラメータだけを登録順に送り、CSRF トークンを含まない", () => {
      const a = renderLink({ ...POST_WINDOW, openMode: "tab" });
      click(a);

      expect(submitted[0].inputs).toEqual([
        ["mail", "taro@example.com"],
        ["k", `a'b"<c>`],
      ]);
      expect(JSON.stringify(submitted[0].inputs)).not.toContain("SECRET");
    });

    it("TC-OPN-007 パラメータ名が CSRF トークンと同じでも送らない", () => {
      const a = renderLink({
        ...POST_WINDOW,
        openMode: "tab",
        params: [
          { name: "__vtrftk", value: "x" },
          { name: "ok", value: "1" },
        ],
      });
      click(a);

      expect(submitted[0].inputs).toEqual([["ok", "1"]]);
    });

    it("TC-OPN-008 送信後にフォームを残さない", () => {
      const a = renderLink({ ...POST_WINDOW, openMode: "tab" });
      click(a);

      expect(submitted).toHaveLength(1);
      expect(document.querySelectorAll("form")).toHaveLength(0);
    });

    it("TC-OPN-032 data 属性が無い・壊れているリンクでは何もしない", () => {
      const broken = document.createElement("a");
      broken.className = "sharedLink";
      broken.setAttribute("data-shared-link", "{broken");
      broken.setAttribute("href", "https://a.example.com/");
      const none = document.createElement("a");
      none.className = "sharedLink";
      none.setAttribute("href", "https://b.example.com/");
      document.querySelector(".sharedLinksWidget")?.append(broken, none);

      expect(click(broken).prevented).toBe(false);
      expect(click(none).prevented).toBe(false);
      expect(opens).toEqual([]);
      expect(submitted).toEqual([]);
    });

    it("ウィジェットの外にある .sharedLink には反応しない", () => {
      const a = document.createElement("a");
      a.className = "sharedLink";
      a.setAttribute("data-shared-link", JSON.stringify(POST_WINDOW));
      document.body.appendChild(a);

      click(a);
      expect(opens).toEqual([]);
      expect(submitted).toEqual([]);
    });
  });

  describe("DT-2 リファラー", () => {
    it("TC-OPN-012 GET・別ウィンドウで noreferrer オフなら noopener だけ", () => {
      click(
        renderLink({ ...GET_TAB, openMode: "window", width: 800, height: 600 }),
      );
      expect(String(opens[0][2])).toMatch(/,noopener$/);
    });

    it("TC-OPN-013 GET・別ウィンドウで noreferrer オンなら noopener,noreferrer", () => {
      click(
        renderLink({
          ...GET_TAB,
          openMode: "window",
          width: 800,
          height: 600,
          noreferrer: true,
        }),
      );
      expect(String(opens[0][2])).toMatch(/,noopener,noreferrer$/);
    });

    it("TC-OPN-014 POST で noreferrer オフならフォームの rel は noopener", () => {
      click(renderLink({ ...POST_WINDOW, openMode: "tab" }));
      expect(submitted[0].rel).toBe("noopener");
    });

    it("TC-OPN-015 POST・別タブで noreferrer オンならフォームの rel は noopener noreferrer", () => {
      click(renderLink({ ...POST_WINDOW, openMode: "tab", noreferrer: true }));
      expect(submitted[0].rel).toBe("noopener noreferrer");
    });

    it("TC-OPN-015 POST・別ウィンドウで noreferrer オンなら、ポップアップに referrer=no-referrer の meta を入れてから送る", () => {
      const doc = document.implementation.createHTMLDocument("");
      openReturn = { opener: window, document: doc };
      click(renderLink({ ...POST_WINDOW, noreferrer: true }));

      expect(submitted).toHaveLength(1);
      expect(submitted[0].doc).toBe(doc);
      expect(
        doc.querySelector('meta[name="referrer"]')?.getAttribute("content"),
      ).toBe("no-referrer");
    });
  });

  describe("DT-3 別ウィンドウのサイズ", () => {
    it("TC-OPN-020 画面に収まるサイズは指定どおりで、今のウィンドウの中央に開く", () => {
      click(
        renderLink({
          ...GET_TAB,
          openMode: "window",
          width: 1000,
          height: 500,
        }),
      );
      expect(String(opens[0][2])).toMatch(
        /^width=1000,height=500,left=400,top=250,/,
      );
    });

    it("TC-OPN-021 画面より大きいサイズは画面に収め、はみ出す分は左上に寄せる", () => {
      click(
        renderLink({
          ...GET_TAB,
          openMode: "window",
          width: 5000,
          height: 4000,
        }),
      );
      expect(String(opens[0][2])).toMatch(
        /^width=1920,height=1080,left=100,top=50,/,
      );
    });
  });
});
