import { Node } from "@tiptap/core";

const PAGE_BREAK_VALUES = ["always", "avoid", "auto", "left", "right"];

function normalizePageBreak(raw: string): string | null {
  if (!raw) return null;
  const normalized = raw.trim().toLowerCase();
  return PAGE_BREAK_VALUES.includes(normalized) ? normalized : null;
}

/**
 * style属性の文字列からpage-break-after/beforeの値を抽出する。
 * CSSOMを経由せず直接文字列パースすることで、ブラウザのCSS正規化
 * （page-break-after → break-after への内部変換等）の影響を回避する。
 *
 * 3段階フォールバック:
 * 1. style文字列から旧CSS仕様(page-break-after/before)を正規表現で抽出
 * 2. style文字列から現代CSS仕様(break-after/before)を正規表現で抽出
 * 3. CSSOM(el.style.pageBreakAfter/Before) — 将来のブラウザ改善時のセーフティネット
 */
function extractPageBreak(
  el: HTMLElement,
  prop: "page-break-after" | "page-break-before"
): string | null {
  const styleAttr = el.getAttribute("style") || "";

  // フォールバック1: 旧CSS仕様
  const legacyRegex = new RegExp(`${prop}\\s*:\\s*(\\w+)`, "i");
  const legacyMatch = styleAttr.match(legacyRegex);
  if (legacyMatch) return normalizePageBreak(legacyMatch[1]);

  // フォールバック2: 現代CSS仕様 (break-after / break-before)
  const modernProp = prop.replace("page-break-", "break-");
  const modernRegex = new RegExp(`${modernProp}\\s*:\\s*(\\w+)`, "i");
  const modernMatch = styleAttr.match(modernRegex);
  if (modernMatch) return normalizePageBreak(modernMatch[1]);

  // フォールバック3: CSSOM（将来のブラウザ改善時のセーフティネット）
  const cssomValue =
    prop === "page-break-after"
      ? el.style.pageBreakAfter
      : el.style.pageBreakBefore;
  if (cssomValue) return normalizePageBreak(cssomValue);

  return null;
}

function hasPageBreak(el: HTMLElement): boolean {
  return (
    extractPageBreak(el, "page-break-after") !== null ||
    extractPageBreak(el, "page-break-before") !== null
  );
}

export const PageBreak = Node.create({
  name: "pageBreak",
  group: "block",
  atom: true,

  addAttributes() {
    return {
      pageBreakAfter: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          extractPageBreak(el, "page-break-after"),
      },
      pageBreakBefore: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          extractPageBreak(el, "page-break-before"),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: "div",
        priority: 60,
        getAttrs: (el) => {
          if (!hasPageBreak(el as HTMLElement)) return false;
          return {};
        },
      },
      {
        tag: "p",
        priority: 60,
        getAttrs: (el) => {
          if (!hasPageBreak(el as HTMLElement)) return false;
          return {};
        },
      },
    ];
  },

  renderHTML({ node }) {
    const parts: string[] = [];
    // renderHTML時にもEnum再検証（JSONインポート等でparseHTMLバイパス時の防御）
    const after = normalizePageBreak(node.attrs.pageBreakAfter);
    if (after) parts.push(`page-break-after: ${after}`);
    const before = normalizePageBreak(node.attrs.pageBreakBefore);
    if (before) parts.push(`page-break-before: ${before}`);
    return ["div", { style: parts.join("; ") }];
  },
});
