import { Mark } from "@tiptap/core";
import { normalizeColor } from "./utils/normalize";

// CKEditorのマーカークラス名 → 背景色マッピング
const MARKER_CLASS_COLORS: Record<string, string> = {
  "marker": "#ffff00",
  "marker-yellow": "#ffff00",
  "marker-green": "#00ff00",
  "marker-pink": "#ff00ff",
  "marker-blue": "#0000ff",
};

// 許可されるクラス名のセット（セキュリティ: 許可リスト外は除去）
export const ALLOWED_MARKER_CLASSES = new Set(Object.keys(MARKER_CLASS_COLORS));

export const Marker = Mark.create({
  name: "marker",

  addAttributes() {
    return {
      backgroundColor: {
        default: null,
        parseHTML: (element) => {
          const classList = element.className?.split(/\s+/) || [];
          for (const cls of classList) {
            const color = MARKER_CLASS_COLORS[cls];
            if (color) return color;
          }
          const bg = element.style.backgroundColor;
          if (bg) return normalizeColor(bg);
          return null;
        },
        renderHTML: (attributes) => {
          if (!attributes.backgroundColor) return {};
          const safe = normalizeColor(attributes.backgroundColor);
          if (!safe) return {};
          return { style: `background-color: ${safe}` };
        },
      },
      markerClass: {
        default: null,
        parseHTML: (element) => {
          const classList = element.className?.split(/\s+/) || [];
          for (const cls of classList) {
            if (ALLOWED_MARKER_CLASSES.has(cls)) return cls;
          }
          return null;
        },
        renderHTML: (attributes) => {
          if (!attributes.markerClass || !ALLOWED_MARKER_CLASSES.has(attributes.markerClass)) return {};
          return { class: attributes.markerClass };
        },
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: "span",
        priority: 60,
        getAttrs: (el) => {
          const element = el as HTMLElement;
          const classList = element.className?.split(/\s+/) || [];
          const hasMarkerClass = classList.some((cls) =>
            ALLOWED_MARKER_CLASSES.has(cls)
          );
          if (!hasMarkerClass) return false;
          return {};
        },
      },
    ];
  },

  renderHTML({ HTMLAttributes }) {
    return ["span", HTMLAttributes, 0];
  },
});
