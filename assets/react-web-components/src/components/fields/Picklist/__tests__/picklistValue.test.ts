import { describe, it, expect } from "vitest";
import {
  serializeSelected,
  parseSelected,
  normalizeHexColor,
  readableTextColor,
  MULTIPICKLIST_SEPARATOR,
} from "../picklistValue";

describe("serializeSelected", () => {
  it("複数選択は ' |##| ' で連結する", () => {
    expect(serializeSelected(["a", "b", "c"], true)).toBe("a |##| b |##| c");
  });

  it("複数選択で空配列は空文字", () => {
    expect(serializeSelected([], true)).toBe("");
  });

  it("複数選択で1件は区切り無し", () => {
    expect(serializeSelected(["only"], true)).toBe("only");
  });

  it("単一選択は先頭値のみ", () => {
    expect(serializeSelected(["x", "y"], false)).toBe("x");
  });

  it("単一選択で空配列は空文字", () => {
    expect(serializeSelected([], false)).toBe("");
  });

  it("区切り定数が仕様どおり", () => {
    expect(MULTIPICKLIST_SEPARATOR).toBe(" |##| ");
  });

  it("ラベルに空白を含む値も壊れず連結できる", () => {
    expect(serializeSelected(["対応 中", "未対応"], true)).toBe(
      "対応 中 |##| 未対応",
    );
  });
});

describe("parseSelected", () => {
  it("複数選択は ' |##| ' で分割する", () => {
    expect(parseSelected("a |##| b |##| c", true)).toEqual(["a", "b", "c"]);
  });

  it("複数選択で空文字は空配列", () => {
    expect(parseSelected("", true)).toEqual([]);
    expect(parseSelected(null, true)).toEqual([]);
    expect(parseSelected(undefined, true)).toEqual([]);
  });

  it("複数選択で1件は要素1", () => {
    expect(parseSelected("only", true)).toEqual(["only"]);
  });

  it("分割後の前後空白除去・空要素除去", () => {
    // 区切り以外の余分な空白が混じっても壊れない
    expect(parseSelected("a |##|  |##| b", true)).toEqual(["a", "b"]);
  });

  it("単一選択は値があれば要素1、無ければ空配列", () => {
    expect(parseSelected("x", false)).toEqual(["x"]);
    expect(parseSelected("", false)).toEqual([]);
  });

  it("ラベルに空白を含む値も保持される", () => {
    expect(parseSelected("対応 中 |##| 未対応", true)).toEqual([
      "対応 中",
      "未対応",
    ]);
  });

  it("serializeSelected と往復しても一致する（複数）", () => {
    const sel = ["赤", "青", "緑"];
    expect(parseSelected(serializeSelected(sel, true), true)).toEqual(sel);
  });

  it("serializeSelected と往復しても一致する（単一）", () => {
    expect(parseSelected(serializeSelected(["Cold"], false), false)).toEqual([
      "Cold",
    ]);
  });
});

describe("normalizeHexColor", () => {
  it("#rrggbb をそのまま小文字化", () => {
    expect(normalizeHexColor("#AABBCC")).toBe("#aabbcc");
  });
  it("# 省略も受理", () => {
    expect(normalizeHexColor("aabbcc")).toBe("#aabbcc");
  });
  it("#rgb 短縮を展開", () => {
    expect(normalizeHexColor("#abc")).toBe("#aabbcc");
  });
  it("前後空白を除去", () => {
    expect(normalizeHexColor("  #FF0000 ")).toBe("#ff0000");
  });
  it("空・null・無効は null", () => {
    expect(normalizeHexColor("")).toBeNull();
    expect(normalizeHexColor(null)).toBeNull();
    expect(normalizeHexColor(undefined)).toBeNull();
    expect(normalizeHexColor("red")).toBeNull();
    expect(normalizeHexColor("#12")).toBeNull();
    expect(normalizeHexColor("#1234567")).toBeNull();
  });
});

describe("readableTextColor", () => {
  it("明るい背景には黒文字", () => {
    expect(readableTextColor("#ffffff")).toBe("#000000");
    expect(readableTextColor("#ffff00")).toBe("#000000");
  });
  it("暗い背景には白文字", () => {
    expect(readableTextColor("#000000")).toBe("#ffffff");
    expect(readableTextColor("#0000ff")).toBe("#ffffff");
  });
  it("無効色は黒（既定）", () => {
    expect(readableTextColor(null)).toBe("#000000");
    expect(readableTextColor("nope")).toBe("#000000");
  });
});
