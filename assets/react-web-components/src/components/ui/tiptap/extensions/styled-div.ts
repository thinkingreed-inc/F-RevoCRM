// assets/react-web-components/src/components/ui/tiptap/extensions/styled-div.ts
import { Node } from "@tiptap/core";
import {
  normalizeColor,
  normalizeLength,
  normalizeBorderStyle,
  normalizeSpacing,
  normalizeTextAlign,
} from "./utils/normalize";

const MAX_DIMENSION_PX = 2000;

export const StyledDiv = Node.create({
  name: "styledDiv",
  group: "block",
  content: "(block | paragraph)+",
  defining: true,

  addAttributes() {
    return {
      borderWidth: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeLength(el.style.borderWidth || ""),
      },
      borderStyle: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeBorderStyle(el.style.borderStyle || ""),
      },
      borderColor: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeColor(el.style.borderColor || ""),
      },
      padding: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeSpacing(el.style.padding || ""),
      },
      backgroundColor: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeColor(el.style.backgroundColor || ""),
      },
      margin: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeSpacing(el.style.margin || ""),
      },
      textAlign: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeTextAlign(el.style.textAlign || ""),
      },
      width: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeLength(el.style.width || "", MAX_DIMENSION_PX),
      },
      height: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeLength(el.style.height || "", MAX_DIMENSION_PX),
      },
      borderRadius: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeLength(el.style.borderRadius || ""),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: "div",
        getAttrs: (el) => {
          const s = (el as HTMLElement).style;
          // style属性を持つdivのみマッチ
          if (
            !s.border &&
            !s.borderWidth &&
            !s.padding &&
            !s.backgroundColor &&
            !s.margin &&
            !s.textAlign &&
            !s.width &&
            !s.height &&
            !s.borderRadius
          ) {
            return false;
          }
          return {};
        },
      },
    ];
  },

  renderHTML({ node }) {
    const parts: string[] = [];
    const a = node.attrs;

    if (a.borderWidth || a.borderStyle || a.borderColor) {
      parts.push(
        `border: ${a.borderWidth || "1px"} ${a.borderStyle || "solid"} ${a.borderColor || "#000000"}`
      );
    }
    if (a.padding) parts.push(`padding: ${a.padding}`);
    if (a.backgroundColor)
      parts.push(`background-color: ${a.backgroundColor}`);
    if (a.margin) parts.push(`margin: ${a.margin}`);
    if (a.textAlign) parts.push(`text-align: ${a.textAlign}`);
    if (a.width) parts.push(`width: ${a.width}`);
    if (a.height) parts.push(`height: ${a.height}`);
    if (a.borderRadius) parts.push(`border-radius: ${a.borderRadius}`);

    return ["div", { style: parts.join("; ") }, 0];
  },
});
