import Highlight from "@tiptap/extension-highlight";
import { normalizeColor } from "./utils/normalize";
import { ALLOWED_MARKER_CLASSES } from "./marker";

/**
 * Highlight拡張をカスタマイズ: <mark> → <span> に変更
 * HTMLPurifierが<mark>タグを除去するため
 */
export const SpanHighlight = Highlight.extend({
  renderHTML({ HTMLAttributes }) {
    return ["span", HTMLAttributes, 0];
  },
  parseHTML() {
    return [
      { tag: "mark" },
      {
        tag: "span",
        getAttrs: (element) => {
          const el = element as HTMLElement;
          // markerクラスを持つspanはMarker拡張が処理するため除外
          const classList = el.className?.split(/\s+/) || [];
          if (classList.some((cls) => ALLOWED_MARKER_CLASSES.has(cls))) return false;
          const bg = el.style.backgroundColor;
          if (!bg) return false;
          return { color: bg };
        },
      },
    ];
  },
  addAttributes() {
    return {
      color: {
        default: null,
        parseHTML: (element) => {
          const raw =
            element.getAttribute("data-color") ||
            element.style.backgroundColor;
          if (!raw) return null;
          return normalizeColor(raw);
        },
        renderHTML: (attributes) => {
          if (!attributes.color) return {};
          const safe = normalizeColor(attributes.color);
          if (!safe) return {};
          return {
            "data-color": safe,
            style: `background-color: ${safe}`,
          };
        },
      },
    };
  },
});
