import { describe, it, expect } from "vitest";
import { htmlToPlainText, htmlToSummaryText } from "../richText";

describe("htmlToPlainText", () => {
  it("空のメモ（Jodit が入れる空段落）は空文字になる", () => {
    expect(htmlToPlainText("<p><br /></p>")).toBe("");
    expect(htmlToPlainText("<p>&nbsp;</p>")).toBe("");
  });

  it("null / undefined / 空文字は空文字を返す", () => {
    expect(htmlToPlainText(null)).toBe("");
    expect(htmlToPlainText(undefined)).toBe("");
    expect(htmlToPlainText("")).toBe("");
  });

  it("タグを落として本文だけを残す", () => {
    expect(htmlToPlainText("<p>請求書の<strong>控え</strong></p>")).toBe(
      "請求書の控え",
    );
  });

  it("段落・改行タグは改行として残す", () => {
    expect(htmlToPlainText("<p>1行目</p><p>2行目</p>")).toBe("1行目\n2行目");
    expect(htmlToPlainText("1行目<br>2行目")).toBe("1行目\n2行目");
  });

  it("空段落が続いても行を増やさない", () => {
    expect(htmlToPlainText("<p>前</p><p><br></p><p><br></p><p>後</p>")).toBe(
      "前\n\n後",
    );
  });

  it("実体参照を戻す", () => {
    expect(htmlToPlainText("<p>A &amp; B &lt;注&gt;</p>")).toBe("A & B <注>");
  });

  it("スクリプト・スタイルは中身ごと落とす", () => {
    expect(htmlToPlainText("<p>本文</p><script>alert(1)</script>")).toBe(
      "本文",
    );
  });

  it("タグが無いメモはそのまま返す", () => {
    expect(htmlToPlainText("ただのメモ")).toBe("ただのメモ");
  });
});

describe("htmlToSummaryText", () => {
  it("改行を詰めて1行にする", () => {
    expect(htmlToSummaryText("<p>1行目</p><p>2行目</p>")).toBe("1行目 2行目");
  });

  it("空のメモは空文字になる", () => {
    expect(htmlToSummaryText("<p><br /></p>")).toBe("");
  });
});
