import { describe, it, expect, vi, beforeAll } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
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

/**
 * 数値だけのピックリスト（TS-10 画面）
 *
 * サーバーは選択肢の値を数値で返すことがある。PHP の配列キーは数字だと
 * 自動的に整数になるため、スキャン解像度（200/300/400/600）などが該当する。
 * 文字列前提で toLowerCase() を呼ぶと落ち、画面が真っ白になっていた。
 */

// サーバーが実際に返す形（value が数値）
const NUMERIC_OPTIONS = [
  { value: 200, label: "200" },
  { value: 300, label: "300" },
  { value: 400, label: "400" },
  { value: 600, label: "600" },
] as unknown as Array<{ value: string; label: string }>;

function renderField(value = "", options = NUMERIC_OPTIONS) {
  const onChange = vi.fn();
  const result = render(
    <PicklistField
      name="scan_resolution_dpi"
      label="スキャン解像度"
      value={value}
      onChange={onChange}
      options={options}
    />,
  );
  return { ...result, onChange };
}

describe("PicklistField - 値が数値の選択肢", () => {
  it("入力しても落ちない（絞り込みで文字列メソッドを呼ばない）", () => {
    renderField();
    const input = screen.getByRole("textbox") as HTMLInputElement;
    // 入力した瞬間に絞り込みが走る。ここで落ちていた
    expect(() =>
      fireEvent.change(input, { target: { value: "199" } }),
    ).not.toThrow();
    expect(input.value).toBe("199");
  });

  it("数字で絞り込める", () => {
    renderField();
    const input = screen.getByRole("textbox") as HTMLInputElement;
    fireEvent.focus(input);
    fireEvent.change(input, { target: { value: "20" } });
    expect(screen.getByText("200")).toBeInTheDocument();
    expect(screen.queryByText("600")).toBeNull();
  });

  it("選択肢に無い入力では候補が出ない", () => {
    renderField();
    fireEvent.focus(screen.getByRole("textbox"));
    fireEvent.change(screen.getByRole("textbox"), { target: { value: "199" } });
    expect(screen.queryByText("200")).toBeNull();
  });

  it("数値の値でも現在の選択がラベルに出る", () => {
    renderField("300");
    expect((screen.getByRole("textbox") as HTMLInputElement).value).toBe("300");
  });

  it("選択した値は文字列で渡す", () => {
    const { onChange } = renderField();
    fireEvent.focus(screen.getByRole("textbox"));
    fireEvent.change(screen.getByRole("textbox"), { target: { value: "40" } });
    fireEvent.click(screen.getByText("400"));
    expect(onChange).toHaveBeenLastCalledWith("scan_resolution_dpi", "400");
  });

  it("文字列の選択肢はこれまでどおり動く", () => {
    const { onChange } = renderField("", [
      { value: "invoice", label: "請求書" },
      { value: "receipt", label: "領収書" },
    ]);
    fireEvent.focus(screen.getByRole("textbox"));
    fireEvent.change(screen.getByRole("textbox"), {
      target: { value: "請求" },
    });
    fireEvent.click(screen.getByText("請求書"));
    expect(onChange).toHaveBeenLastCalledWith("scan_resolution_dpi", "invoice");
  });
});
