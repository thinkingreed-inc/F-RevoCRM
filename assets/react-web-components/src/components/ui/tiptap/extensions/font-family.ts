import { Extension } from "@tiptap/core";

const MAX_FONT_FAMILY_LENGTH = 200;

/**
 * フォントファミリ値をindent方式で正規化する。
 * カンマ区切りのフォント名リストを分解→各フォント名を抽出→安全な形式で再構築。
 * CSS injectionに使われる文字（;, \, (), <>, {}等）はフォント名の一部ではないため、
 * フォント名抽出の過程で自然に除外される。
 */
function normalizeFontFamily(raw: string): string | null {
  if (!raw || raw.length > MAX_FONT_FAMILY_LENGTH) return null;

  const fonts = raw
    .split(",")
    .map((f) => {
      // 前後の空白とクォートを除去してフォント名を抽出
      const name = f.trim().replace(/^['"]|['"]$/g, "").trim();
      if (!name) return null;
      // CSS制御文字がフォント名内に残存している場合はそのフォント名を除外
      // 正当なフォント名にはこれらの文字は含まれない
      if (/[;:{}<>()\\/"']/.test(name)) return null;
      // スペースを含むフォント名はダブルクォートで囲んで再構築
      return name.includes(" ") ? `"${name}"` : name;
    })
    .filter((f): f is string => f !== null);

  return fonts.length > 0 ? fonts.join(", ") : null;
}

export const FontFamilyExtension = Extension.create({
  name: "fontFamily",
  addGlobalAttributes() {
    return [
      {
        types: ["textStyle"],
        attributes: {
          fontFamily: {
            default: null,
            parseHTML: (element) => {
              const raw = element.style.fontFamily;
              if (!raw) return null;
              return normalizeFontFamily(raw);
            },
            renderHTML: (attributes) => {
              if (!attributes.fontFamily) return {};
              return { style: `font-family: ${attributes.fontFamily}` };
            },
          },
        },
      },
    ];
  },
});
