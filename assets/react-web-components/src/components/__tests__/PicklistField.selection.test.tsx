import { describe, it, expect, vi, beforeAll, afterEach } from "vitest";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { PicklistField } from "../PicklistField";

// jsdom は要素の大きさを持たない。ドロップダウンは入力欄の位置から
// 描画位置を決めるため、実寸を返すようにしておく
beforeAll(() => {
  Element.prototype.getBoundingClientRect = function () {
    return {
      top: 0,
      left: 0,
      bottom: 30,
      right: 200,
      width: 200,
      height: 30,
      x: 0,
      y: 0,
      toJSON: () => ({}),
    } as DOMRect;
  };
});

afterEach(() => {
  vi.useRealTimers();
});

const OPTIONS = [
  { value: "Low", label: "低" },
  { value: "Normal", label: "通常" },
  { value: "High", label: "高" },
];

function renderField(value = "") {
  const onChange = vi.fn();
  const result = render(
    <PicklistField
      name="ticketpriorities"
      label="優先度"
      value={value}
      onChange={onChange}
      options={OPTIONS}
    />,
  );
  return { ...result, onChange };
}

function getOptionElements(): HTMLElement[] {
  const dropdown = document.querySelector('[data-rwc-dropdown="picklist"]');
  return Array.from(
    dropdown?.querySelectorAll<HTMLElement>(".py-1 > div") ?? [],
  );
}

describe("PicklistField - マウスでの選択", () => {
  it("押してから離すまで時間がかかっても選択できる", () => {
    vi.useFakeTimers();
    const { onChange } = renderField();
    const input = screen.getByRole("textbox");

    fireEvent.focus(input);
    const option = screen.getByText("通常");

    // 実ブラウザの順序: 押下 → 入力欄の blur → (押したまま) → 離す → click
    fireEvent.mouseDown(option);
    fireEvent.blur(input);
    act(() => {
      vi.advanceTimersByTime(300);
    });

    // 押している間にドロップダウンが消えると click が発生せず選択できない
    expect(document.contains(option)).toBe(true);
    fireEvent.click(option);

    expect(onChange).toHaveBeenCalledWith("ticketpriorities", "Normal");
  });

  it("Tab 等でフォーカスが外れた場合はドロップダウンを閉じる", () => {
    vi.useFakeTimers();
    renderField();
    const input = screen.getByRole("textbox");

    fireEvent.focus(input);
    expect(getOptionElements()).toHaveLength(3);

    fireEvent.blur(input);
    act(() => {
      vi.advanceTimersByTime(300);
    });

    expect(getOptionElements()).toHaveLength(0);
  });
});

describe("PicklistField - 開いた時のハイライト", () => {
  it("2番目以降を選択済みで開くと、選択中の項目だけがハイライトされる", () => {
    renderField("Normal");
    fireEvent.focus(screen.getByRole("textbox"));

    const highlighted = getOptionElements()
      .filter((el) => el.className.includes("bg-blue-100"))
      .map((el) => el.textContent);

    expect(highlighted).toEqual(["通常"]);
  });

  it("未選択で開くと先頭がハイライトされる", () => {
    renderField();
    fireEvent.focus(screen.getByRole("textbox"));

    const highlighted = getOptionElements()
      .filter((el) => el.className.includes("bg-blue-100"))
      .map((el) => el.textContent);

    expect(highlighted).toEqual(["低"]);
  });

  it("選択済みで開いてすぐ Enter を押すと選択中の値のまま", () => {
    const { onChange } = renderField("High");
    const input = screen.getByRole("textbox");
    fireEvent.focus(input);
    fireEvent.keyDown(input, { key: "Enter" });

    expect(onChange).toHaveBeenCalledWith("ticketpriorities", "High");
  });
});
