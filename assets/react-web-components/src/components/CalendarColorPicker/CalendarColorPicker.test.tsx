import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { CalendarColorPicker } from "./CalendarColorPicker";
import {
  CALENDAR_COLOR_BLOCKS,
  CALENDAR_COLOR_COLUMNS,
  CALENDAR_COLOR_HUE_GROUPS,
  CALENDAR_COLOR_PALETTE,
  CALENDAR_COLOR_TIERS,
  getCalendarColorHexes,
  isPresetColor,
  normalizeHex,
} from "./colors";

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

describe("CALENDAR_COLOR_PALETTE の並び順", () => {
  it("色相 × 階調のマトリクスとして色数が決まる", () => {
    const hueCount = CALENDAR_COLOR_HUE_GROUPS.flat().length;
    expect(CALENDAR_COLOR_PALETTE).toHaveLength(
      hueCount * CALENDAR_COLOR_TIERS.length,
    );
  });

  it("すべての色相グループが列数と同じ色相数を持つ", () => {
    CALENDAR_COLOR_HUE_GROUPS.forEach((group) => {
      expect(group).toHaveLength(CALENDAR_COLOR_COLUMNS);
    });
  });

  it("各ブロックが対応する色相グループの並び順になる", () => {
    expect(CALENDAR_COLOR_BLOCKS).toHaveLength(
      CALENDAR_COLOR_HUE_GROUPS.length,
    );

    CALENDAR_COLOR_HUE_GROUPS.forEach((group, index) => {
      expect([
        ...new Set(CALENDAR_COLOR_BLOCKS[index].map((swatch) => swatch.hue)),
      ]).toEqual(group.map((hue) => hue.key));
    });
  });

  it("各ブロックは1行=1階調で並ぶ", () => {
    CALENDAR_COLOR_BLOCKS.forEach((block) => {
      expect(block).toHaveLength(
        CALENDAR_COLOR_COLUMNS * CALENDAR_COLOR_TIERS.length,
      );

      CALENDAR_COLOR_TIERS.forEach((tier, rowIndex) => {
        const row = block.slice(
          rowIndex * CALENDAR_COLOR_COLUMNS,
          (rowIndex + 1) * CALENDAR_COLOR_COLUMNS,
        );
        expect(row.map((swatch) => swatch.tier)).toEqual(row.map(() => tier));
      });
    });
  });

  it("各ブロックは1列=1色相の濃淡になる", () => {
    CALENDAR_COLOR_BLOCKS.forEach((block) => {
      for (let column = 0; column < CALENDAR_COLOR_COLUMNS; column++) {
        const hues = CALENDAR_COLOR_TIERS.map(
          (_, rowIndex) =>
            block[rowIndex * CALENDAR_COLOR_COLUMNS + column].hue,
        );
        expect(new Set(hues).size).toBe(1);
      }
    });
  });

  it("パレットはブロックを順に連結した並びになる", () => {
    expect(CALENDAR_COLOR_PALETTE).toEqual(CALENDAR_COLOR_BLOCKS.flat());
  });

  it("同じ色が重複しない", () => {
    const hexes = CALENDAR_COLOR_PALETTE.map((swatch) => swatch.hex);
    expect(new Set(hexes).size).toBe(hexes.length);
  });

  it("表示ラベルに Tailwind の内部名（red-500 等）を使わない", () => {
    CALENDAR_COLOR_PALETTE.forEach((swatch) => {
      expect(swatch.label).not.toMatch(/[a-z]+-\d{3}/);
    });
  });
});

describe("getCalendarColorHexes", () => {
  it("プリセット全色のHEXを並び順どおりに返す", () => {
    expect(getCalendarColorHexes()).toEqual(
      CALENDAR_COLOR_PALETTE.map((swatch) => swatch.hex),
    );
  });

  it("返す色はすべてプリセットとして判定できる", () => {
    getCalendarColorHexes().forEach((hex) => {
      expect(isPresetColor(hex)).toBe(true);
    });
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
      expect(screen.getByLabelText(swatch.label)).toBeInTheDocument();
    });
  });

  it("プリセット選択で hidden input に #rrggbb が書き込まれ、onChange が発火する", () => {
    const onChange = vi.fn();
    const { hiddenInput } = renderInForm({ onChange });

    const target = CALENDAR_COLOR_PALETTE[3];
    fireEvent.click(screen.getByLabelText(target.label));

    expect(hiddenInput.value).toBe(target.hex);
    expect(onChange).toHaveBeenCalledWith(target.hex);
  });

  it("プリセット選択でHEX入力欄に選択色が反映される", () => {
    renderInForm({});

    const target = CALENDAR_COLOR_PALETTE[5];
    fireEvent.click(screen.getByLabelText(target.label));

    expect(screen.getByLabelText("カラーコード（16進数）")).toHaveValue(
      target.hex,
    );
  });

  it("カスタム色の指定UIが「色を選ぶ」と表示され、カラー入力に紐づく", () => {
    renderInForm({});

    expect(screen.getByText("色を選ぶ")).toBeInTheDocument();
    expect(screen.getByLabelText("色を選ぶ")).toHaveAttribute("type", "color");
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
