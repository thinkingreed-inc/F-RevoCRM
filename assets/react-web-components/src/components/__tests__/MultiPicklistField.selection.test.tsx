import React, { useState } from "react";
import { describe, it, expect, vi, beforeAll, afterEach } from "vitest";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { MultiPicklistField } from "../MultiPicklistField";

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
  { value: "test1", label: "test1" },
  { value: "test2", label: "test2" },
  { value: "test3", label: "test3" },
];

// 選択結果を value に反映するため、親の state で包む
function Wrapper({ onChange }: { onChange: (n: string, v: string) => void }) {
  const [value, setValue] = useState("");
  return (
    <MultiPicklistField
      name="cf_991"
      label="test2"
      value={value}
      onChange={(n, v) => {
        setValue(v);
        onChange(n, v);
      }}
      options={OPTIONS}
    />
  );
}

function getOptionLabels(): string[] {
  const dropdown = document.querySelector(
    '[data-rwc-dropdown="multipicklist"]',
  );
  return Array.from(
    dropdown?.querySelectorAll<HTMLElement>(".py-1 > div") ?? [],
  ).map((el) => el.textContent ?? "");
}

describe("MultiPicklistField - マウスでの選択", () => {
  it("候補の押下で入力欄のフォーカスを外さない", () => {
    render(<Wrapper onChange={vi.fn()} />);
    const input = screen.getByRole("textbox");
    act(() => input.focus());

    const option = screen.getByText("test1");
    // preventDefault されていれば、ブラウザは入力欄の blur を行わない
    const notPrevented = fireEvent.mouseDown(option);

    expect(notPrevented).toBe(false);
  });

  it("マウスで選択した後もキー操作で続けて選択できる", () => {
    const onChange = vi.fn();
    render(<Wrapper onChange={onChange} />);
    const input = screen.getByRole("textbox");
    act(() => input.focus());

    const option = screen.getByText("test1");
    fireEvent.mouseDown(option);
    fireEvent.click(option);
    expect(onChange).toHaveBeenLastCalledWith("cf_991", "test1");

    // 一覧は開いたまま、入力欄にフォーカスが残る
    expect(document.activeElement).toBe(input);
    expect(getOptionLabels()).toEqual(["test2", "test3"]);

    fireEvent.keyDown(input, { key: "Enter" });
    expect(onChange).toHaveBeenLastCalledWith("cf_991", "test1 |##| test2");
  });

  it("Tab 等でフォーカスが外れた場合はドロップダウンを閉じる", () => {
    vi.useFakeTimers();
    render(<Wrapper onChange={vi.fn()} />);
    const input = screen.getByRole("textbox");
    act(() => input.focus());
    expect(getOptionLabels()).toHaveLength(3);

    fireEvent.blur(input);
    act(() => {
      vi.advanceTimersByTime(300);
    });

    expect(getOptionLabels()).toHaveLength(0);
  });
});
