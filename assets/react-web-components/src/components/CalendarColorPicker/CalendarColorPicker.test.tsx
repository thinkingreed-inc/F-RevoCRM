import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { CalendarColorPicker } from "./CalendarColorPicker";
import { CALENDAR_COLOR_PALETTE, isPresetColor, normalizeHex } from "./colors";

/**
 * CalendarColorPicker を <form> 内にレンダリングし、
 * 周辺処理が参照する hidden input(.selectedColor) を併設するヘルパー。
 */
function renderInForm(props: {
  value?: string;
  onChange?: (hex: string) => void;
}) {
  const utils = render(
    <form>
      <input type="hidden" className="selectedColor" defaultValue="" />
      <CalendarColorPicker {...props} />
    </form>,
  );
  const hiddenInput = utils.container.querySelector(
    "input.selectedColor",
  ) as HTMLInputElement;
  return { ...utils, hiddenInput };
}

describe("normalizeHex", () => {
  it("#付き6桁をそのまま小文字化する", () => {
    expect(normalizeHex("#EF4444")).toBe("#ef4444");
  });

  it("#無しでも補完する", () => {
    expect(normalizeHex("ef4444")).toBe("#ef4444");
  });

  it("3桁形式を6桁に展開する", () => {
    expect(normalizeHex("#f00")).toBe("#ff0000");
  });

  it("不正値は null を返す", () => {
    expect(normalizeHex("")).toBeNull();
    expect(normalizeHex("red")).toBeNull();
    expect(normalizeHex("#12345")).toBeNull();
    expect(normalizeHex(undefined)).toBeNull();
  });
});

describe("isPresetColor", () => {
  it("パレット内の色を判定する", () => {
    expect(isPresetColor(CALENDAR_COLOR_PALETTE[0].hex)).toBe(true);
    expect(isPresetColor("#EF4444")).toBe(true); // 大文字でも一致
  });

  it("パレット外の色は false", () => {
    expect(isPresetColor("#123456")).toBe(false);
  });
});

describe("CalendarColorPicker", () => {
  it("全プリセット色のスウォッチが描画される", () => {
    renderInForm({});
    CALENDAR_COLOR_PALETTE.forEach((swatch) => {
      expect(screen.getByLabelText(swatch.name)).toBeInTheDocument();
    });
  });

  it("プリセット選択で hidden input に #rrggbb が書き込まれ、onChange が発火する", () => {
    const onChange = vi.fn();
    const { hiddenInput } = renderInForm({ onChange });

    const target = CALENDAR_COLOR_PALETTE[3];
    fireEvent.click(screen.getByLabelText(target.name));

    expect(hiddenInput.value).toBe(target.hex);
    expect(onChange).toHaveBeenCalledWith(target.hex);
  });

  it("value 属性の初期値が hidden input に反映される", () => {
    const { hiddenInput } = renderInForm({ value: "#ABCDEF" });
    expect(hiddenInput.value).toBe("#abcdef");
  });

  it("カスタムHEX入力で任意のRGB色を指定できる", () => {
    const onChange = vi.fn();
    const { hiddenInput } = renderInForm({ onChange });

    const hexInput = screen.getByLabelText(
      "カラーコード（16進数）",
    ) as HTMLInputElement;
    fireEvent.change(hexInput, { target: { value: "#123abc" } });

    expect(hiddenInput.value).toBe("#123abc");
    expect(onChange).toHaveBeenCalledWith("#123abc");
  });

  it("不正なHEX入力ではコミットされない", () => {
    const onChange = vi.fn();
    const { hiddenInput } = renderInForm({ onChange });

    const hexInput = screen.getByLabelText(
      "カラーコード（16進数）",
    ) as HTMLInputElement;
    fireEvent.change(hexInput, { target: { value: "#12" } });

    expect(hiddenInput.value).toBe("");
    expect(onChange).not.toHaveBeenCalled();
  });
});
