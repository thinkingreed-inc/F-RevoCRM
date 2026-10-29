import { describe, it, expect } from "vitest";
import {
  currentYearMonth,
  formatYearMonth,
  parseYearMonth,
  toDbValue,
} from "../yearMonth";

describe("parseYearMonth", () => {
  it.each([
    ["2026-10-01", { year: 2026, month: 10 }],
    ["2026-10-28", { year: 2026, month: 10 }],
    ["2026-10", { year: 2026, month: 10 }],
    ["2026-3", { year: 2026, month: 3 }],
    [" 2026-10-01 ", { year: 2026, month: 10 }],
  ])("%s を解釈する", (input, expected) => {
    expect(parseYearMonth(input)).toEqual(expected);
  });

  it.each([
    "",
    null,
    undefined,
    "abc",
    "2026-13-01",
    "2026-00-01",
    "999-10-01",
  ])("%s は null", (input) => {
    expect(parseYearMonth(input as string | null | undefined)).toBeNull();
  });
});

describe("toDbValue", () => {
  it("月初日にする", () => {
    expect(toDbValue({ year: 2026, month: 3 })).toBe("2026-03-01");
    expect(toDbValue({ year: 2026, month: 12 })).toBe("2026-12-01");
  });
});

describe("formatYearMonth", () => {
  it("書式に当てはめる", () => {
    const value = { year: 2026, month: 3 };
    expect(formatYearMonth(value, "{Y}年{M}月")).toBe("2026年3月");
    expect(formatYearMonth(value, "{Y}-{MM}")).toBe("2026-03");
    expect(formatYearMonth(null, "{Y}年{M}月")).toBe("");
  });
});

describe("currentYearMonth", () => {
  it("日付から年月を取る", () => {
    expect(currentYearMonth(new Date(2026, 9, 28))).toEqual({
      year: 2026,
      month: 10,
    });
  });
});
