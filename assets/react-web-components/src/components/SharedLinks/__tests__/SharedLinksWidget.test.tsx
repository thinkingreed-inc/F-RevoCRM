import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, within } from "@testing-library/react";
import type { SharedLinkWidgetItem } from "@/types/sharedLinks";
import { SharedLinksWidget } from "../SharedLinksWidget";
import type { OpenEnvironment } from "../utils/openSharedLink";

/**
 * ダッシュボードの共有リンクのウィジェット（<shared-links-widget>）のテスト
 *
 * どう開くか（window.open・フォーム）は openSharedLink.test.ts で確かめる。
 * ここでは表示と、クリックをどう受け渡すかを確かめる。
 * テスト仕様書: docs/tests/SharedLinks/TS-04_リンクの開き方.md / TS-07_ウィジェットとマイグレーション.md
 */

const LABELS = {
  empty: "共有リンクが登録されていません",
  settings: "共有リンクを設定する",
  openInNewTab: "新しいタブで開く",
  openInNewWindow: "新しいウィンドウで開く",
  popupBlocked: "POPUP",
  postBlocked: "BLOCKED",
};

const link = (
  id: number,
  overrides: Partial<SharedLinkWidgetItem> = {},
): SharedLinkWidgetItem => ({
  id,
  title: `リンク${id}`,
  description: "",
  method: "GET",
  url: `https://example.com/${id}?a=1&b=2`,
  params: [],
  openMode: "tab",
  width: null,
  height: null,
  noreferrer: false,
  blocked: false,
  initial: "リ",
  color: id % 8,
  ...overrides,
});

function fakeEnvironment() {
  const opens: unknown[][] = [];
  const notices: string[] = [];
  const environment: OpenEnvironment = {
    window: {
      screen: { availWidth: 1920, availHeight: 1080 },
      outerWidth: 1600,
      outerHeight: 900,
      screenX: 0,
      screenY: 0,
      open: (...args: unknown[]) => {
        opens.push(args);
        return null;
      },
    } as unknown as Window,
    document,
    notify: (message) => notices.push(message),
  };
  return { environment, opens, notices };
}

const links = () => screen.getAllByRole("link");

describe("SharedLinksWidget", () => {
  it("TC-WDG-010 TC-OPN-031 リンクを渡された順に、タイトル・説明・開き方の印で並べる", () => {
    render(
      <SharedLinksWidget
        links={[
          link(3, {
            title: "勤怠",
            initial: "勤",
            description: "1行目\n2行目",
            color: 3,
          }),
          link(1, {
            title: "Box",
            initial: "B",
            openMode: "window",
            width: 800,
            height: 600,
          }),
        ]}
        labels={LABELS}
      />,
    );

    expect(links()).toHaveLength(2);
    expect(links()[0]).toHaveTextContent("勤怠");
    expect(links()[1]).toHaveTextContent("Box");
    // 印は右端の開き方の印 1 つだけ（左に頭文字の色付きバッジやリンクの印は出さない）
    expect(links()[0].lastElementChild).toHaveClass("sharedLinkOpen");
    expect(links()[0].querySelectorAll(".fa")).toHaveLength(1);
    expect(links()[0].querySelector(".sharedLinkBadge")).toBeNull();
    // 説明は改行を保つ
    const description = links()[0].querySelector(
      ".sharedLinkDescription",
    ) as HTMLElement;
    expect(description.textContent).toBe("1行目\n2行目");
    expect(description.style.whiteSpace).toBe("pre-wrap");
    expect(links()[1].querySelector(".sharedLinkDescription")).toBeNull();
    // 開き方の印（読み上げ用の文言つき）
    expect(
      within(links()[0]).getByTitle("新しいタブで開く"),
    ).toBeInTheDocument();
    expect(links()[0].querySelector(".fa-external-link")).not.toBeNull();
    expect(
      within(links()[1]).getByTitle("新しいウィンドウで開く"),
    ).toBeInTheDocument();
    expect(links()[1].querySelector(".fa-clone")).not.toBeNull();
  });

  it("TC-WDG-015 TC-OPN-033 vtiger の共通 CSS とぶつかるクラス名（.group）を使わない", () => {
    render(<SharedLinksWidget links={[link(1)]} labels={LABELS} />);

    expect(links()[0].classList.contains("group")).toBe(false);
  });

  it("TC-OPN-010 TC-OPN-011 GET は最終的な URL を href に入れ、別タブで開く。リファラーの設定で rel が変わる", () => {
    render(
      <SharedLinksWidget
        links={[link(1), link(2, { noreferrer: true })]}
        labels={LABELS}
      />,
    );

    expect(links()[0]).toHaveAttribute("href", "https://example.com/1?a=1&b=2");
    expect(links()[0]).toHaveAttribute("target", "_blank");
    expect(links()[0]).toHaveAttribute("rel", "noopener");
    expect(links()[1]).toHaveAttribute("rel", "noopener noreferrer");
  });

  it("TC-OPN-009 POST は href・target を持たず、ボタンとして扱う（<form> を書き出さない）", () => {
    const { container } = render(
      <SharedLinksWidget
        links={[
          link(1, { method: "POST", params: [{ name: "a", value: "1" }] }),
        ]}
        labels={LABELS}
      />,
    );

    const post = screen.getByRole("button", { name: /リンク1/ });
    expect(post).toHaveAttribute("href", "#");
    expect(post).not.toHaveAttribute("target");
    expect(container.querySelector("form")).toBeNull();
  });

  it("TC-OPN-001 GET・別タブのクリックは止めない（ブラウザに任せる）", () => {
    const { environment, opens } = fakeEnvironment();
    render(
      <SharedLinksWidget
        links={[link(1)]}
        labels={LABELS}
        environment={environment}
      />,
    );

    const notPrevented = fireEvent.click(links()[0]);

    expect(notPrevented).toBe(true);
    expect(opens).toEqual([]);
  });

  it("TC-OPN-002 TC-OPN-003 GET・別ウィンドウは通常のクリックだけ止めて開き、修飾キー付きはブラウザに任せる", () => {
    const { environment, opens } = fakeEnvironment();
    render(
      <SharedLinksWidget
        links={[link(1, { openMode: "window", width: 800, height: 600 })]}
        labels={LABELS}
        environment={environment}
      />,
    );

    expect(fireEvent.click(links()[0])).toBe(false);
    expect(opens).toHaveLength(1);
    for (const init of [
      { ctrlKey: true },
      { metaKey: true },
      { shiftKey: true },
      { altKey: true },
      { button: 1 },
    ]) {
      expect(fireEvent.click(links()[0], init)).toBe(true);
    }
    expect(opens).toHaveLength(1);
  });

  it("TC-OPN-044 送り先が許可されていない POST は通知の文言を渡す", () => {
    const { environment, notices } = fakeEnvironment();
    render(
      <SharedLinksWidget
        links={[link(1, { method: "POST", blocked: true })]}
        labels={LABELS}
        environment={environment}
      />,
    );

    expect(
      fireEvent.click(screen.getByRole("button", { name: /リンク1/ })),
    ).toBe(false);
    expect(notices).toEqual(["BLOCKED"]);
  });

  it("TC-OPN-006 ポップアップがブロックされたら通知の文言を渡す", () => {
    const { environment, notices } = fakeEnvironment();
    render(
      <SharedLinksWidget
        links={[
          link(1, {
            method: "POST",
            openMode: "window",
            width: 800,
            height: 600,
          }),
        ]}
        labels={LABELS}
        environment={environment}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: /リンク1/ }));
    expect(notices).toEqual(["POPUP"]);
  });

  it("TC-WDG-011 0 件で設定画面の URL があれば、文言と設定画面へのボタンを出す", () => {
    render(
      <SharedLinksWidget
        links={[]}
        labels={LABELS}
        settingsUrl="index.php?module=SharedLinks&parent=Settings&view=List"
      />,
    );

    expect(
      screen.getByText("共有リンクが登録されていません"),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "共有リンクを設定する" }),
    ).toHaveAttribute(
      "href",
      "index.php?module=SharedLinks&parent=Settings&view=List",
    );
  });

  it("TC-WDG-012 0 件で設定画面の URL がなければ、文言だけ", () => {
    render(<SharedLinksWidget links={[]} labels={LABELS} />);

    expect(
      screen.getByText("共有リンクが登録されていません"),
    ).toBeInTheDocument();
    expect(screen.queryByRole("link")).toBeNull();
  });

  it("TC-OPN-032 links が配列でなくても落ちない（0 件として扱う）", () => {
    render(
      <SharedLinksWidget
        links={"broken" as unknown as SharedLinkWidgetItem[]}
        labels={LABELS}
      />,
    );
    expect(
      screen.getByText("共有リンクが登録されていません"),
    ).toBeInTheDocument();
  });

  it("TC-OPN-030 タイトル・説明の記号はそのまま文字として出す（HTML として解釈しない）", () => {
    const { container } = render(
      <SharedLinksWidget
        links={[
          link(1, {
            title: "<script>alert(1)</script>",
            description: "<img src=x onerror=alert(2)>",
          }),
        ]}
        labels={LABELS}
      />,
    );

    expect(links()[0]).toHaveTextContent("<script>alert(1)</script>");
    expect(container.querySelector("script")).toBeNull();
    expect(container.querySelector("img")).toBeNull();
  });

  it("通知の仕組みが無い画面では alert で知らせる", () => {
    const alert = vi.spyOn(window, "alert").mockImplementation(() => {});
    render(
      <SharedLinksWidget
        links={[link(1, { method: "POST", blocked: true })]}
        labels={LABELS}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: /リンク1/ }));

    expect(alert).toHaveBeenCalledWith("BLOCKED");
    alert.mockRestore();
  });
});
