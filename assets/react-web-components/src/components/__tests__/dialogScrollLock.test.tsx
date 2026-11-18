import { describe, it, expect, beforeAll, afterAll } from "vitest";
import { render } from "@testing-library/react";
import indexCss from "../../index.css?raw";
import { Dialog, DialogContent, DialogTitle } from "../ui/dialog";

/**
 * vtiger のスキンは html に overflow-y: auto を指定している。
 * このため Radix のダイアログが body に付ける overflow: hidden では画面全体のスクロールが止まらず、
 * ダイアログの上でマウスホイールを回すと背面の画面がスクロールしてしまう（#1987）。
 */

const styles: HTMLStyleElement[] = [];
const addStyle = (css: string) => {
  const el = document.createElement("style");
  el.textContent = css;
  document.head.appendChild(el);
  styles.push(el);
};

beforeAll(() => {
  addStyle("html { overflow-y: auto; }");
  addStyle(indexCss);
});

afterAll(() => {
  styles.forEach((el) => el.remove());
});

const htmlOverflowY = () =>
  getComputedStyle(document.documentElement).overflowY;

describe("ダイアログを開いている間の背面のスクロール", () => {
  it("ダイアログを開いている間は html のスクロールを止め、閉じると戻す", () => {
    expect(htmlOverflowY()).toBe("auto");

    const { unmount } = render(
      <Dialog open>
        <DialogContent>
          <DialogTitle>テスト</DialogTitle>
        </DialogContent>
      </Dialog>,
    );
    expect(document.body.hasAttribute("data-scroll-locked")).toBe(true);
    expect(htmlOverflowY()).toBe("hidden");

    unmount();
    expect(htmlOverflowY()).toBe("auto");
  });
});
