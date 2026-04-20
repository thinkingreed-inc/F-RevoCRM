import { Table } from "@tiptap/extension-table";
import { normalizeColor } from "./utils/normalize";
import { TableCell } from "@tiptap/extension-table-cell";
import { TableHeader } from "@tiptap/extension-table-header";

const DEFAULT_BORDER_COLOR = "#e2e2e2";
const CELL_PADDING = "6px 10px";

export const StyledTable = Table.extend({
  renderHTML({ HTMLAttributes }) {
    return [
      "table",
      {
        ...HTMLAttributes,
        style: [
          "border-collapse: collapse",
          "width: 100%",
          HTMLAttributes.style,
        ]
          .filter(Boolean)
          .join("; "),
      },
      ["tbody", 0],
    ];
  },
});

const cellAttributes = {
  backgroundColor: {
    default: null,
    parseHTML: (el: HTMLElement) => normalizeColor(el.style.backgroundColor || ""),
    renderHTML: () => ({}),
  },
};

function buildCellStyles(
  attrs: Record<string, unknown>,
  isHeader: boolean
): string {
  const parts = [
    `border: 1px solid ${DEFAULT_BORDER_COLOR}`,
    `padding: ${CELL_PADDING}`,
    "vertical-align: top",
  ];
  if (isHeader) {
    parts.push("font-weight: 600");
    parts.push("text-align: left");
  }
  if (attrs.backgroundColor) {
    parts.push(`background-color: ${attrs.backgroundColor}`);
  }
  return parts.join("; ");
}

export const StyledTableCell = TableCell.extend({
  addAttributes() {
    return { ...this.parent?.(), ...cellAttributes };
  },
  renderHTML({ node, HTMLAttributes }) {
    const { style: _, ...rest } = HTMLAttributes;
    return [
      "td",
      { ...rest, style: buildCellStyles(node.attrs, false) },
      0,
    ];
  },
});

export const StyledTableHeader = TableHeader.extend({
  addAttributes() {
    return { ...this.parent?.(), ...cellAttributes };
  },
  renderHTML({ node, HTMLAttributes }) {
    const { style: _, ...rest } = HTMLAttributes;
    return [
      "th",
      { ...rest, style: buildCellStyles(node.attrs, true) },
      0,
    ];
  },
});
