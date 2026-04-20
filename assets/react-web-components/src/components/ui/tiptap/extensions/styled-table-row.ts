// assets/react-web-components/src/components/ui/tiptap/extensions/styled-table-row.ts
import { TableRow } from "@tiptap/extension-table-row";
import {
  normalizeColor,
  normalizeLength,
  normalizeBorderShorthand,
  normalizeTextAlign,
  normalizeVerticalAlign,
} from "./utils/normalize";

export const StyledTableRow = TableRow.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      backgroundColor: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeColor(el.style.backgroundColor || ""),
      },
      color: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeColor(el.style.color || ""),
      },
      height: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeLength(el.style.height || ""),
      },
      borderBottom: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeBorderShorthand(el.style.borderBottom || ""),
      },
      textAlign: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeTextAlign(el.style.textAlign || ""),
      },
      verticalAlign: {
        default: null,
        parseHTML: (el: HTMLElement) =>
          normalizeVerticalAlign(el.style.verticalAlign || ""),
      },
    };
  },

  renderHTML({ HTMLAttributes }) {
    const parts: string[] = [];
    const {
      backgroundColor,
      color,
      height,
      borderBottom,
      textAlign,
      verticalAlign,
      style: _,
      ...rest
    } = HTMLAttributes;

    if (backgroundColor) parts.push(`background-color: ${backgroundColor}`);
    if (color) parts.push(`color: ${color}`);
    if (height) parts.push(`height: ${height}`);
    if (borderBottom) parts.push(`border-bottom: ${borderBottom}`);
    if (textAlign) parts.push(`text-align: ${textAlign}`);
    if (verticalAlign) parts.push(`vertical-align: ${verticalAlign}`);

    const attrs =
      parts.length > 0 ? { ...rest, style: parts.join("; ") } : rest;
    return ["tr", attrs, 0];
  },
});
