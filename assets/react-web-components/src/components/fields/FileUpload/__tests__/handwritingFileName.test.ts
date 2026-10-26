import { describe, it, expect } from "vitest";
import { buildHandwritingFileName } from "../handwritingFileName";

describe("buildHandwritingFileName", () => {
  const now = new Date(2026, 9, 5, 8, 3, 9);

  it("項目名と日時からファイル名を作る", () => {
    expect(buildHandwritingFileName("サイン", now)).toBe(
      "サイン_20261005-080309.png",
    );
  });

  it("ファイル名に使えない文字と空白は _ にする", () => {
    expect(buildHandwritingFileName('受領 印/確認:"A"', now)).toBe(
      "受領_印_確認_A__20261005-080309.png",
    );
  });

  it("項目名が無ければ handwriting にする", () => {
    expect(buildHandwritingFileName("", now)).toBe(
      "handwriting_20261005-080309.png",
    );
  });
});
