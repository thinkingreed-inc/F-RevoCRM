import { Extension } from "@tiptap/core";

declare module "@tiptap/core" {
  interface Commands<ReturnType> {
    cellStyle: {
      setCellBackground: (color: string | null) => ReturnType;
    };
  }
}

/**
 * CellStyle拡張: セルの背景色・罫線色コマンド
 */
export const CellStyleExtension = Extension.create({
  name: "cellStyle",
  addCommands() {
    return {
      setCellBackground:
        (color: string | null) =>
        ({ tr, state, dispatch }) => {
          if (!dispatch) return true;
          const { selection } = state;
          if ("$anchorCell" in selection) {
            const sel = selection as unknown as {
              forEachCell: (
                fn: (node: unknown, pos: number) => void
              ) => void;
            };
            sel.forEachCell((_node, pos) => {
              tr.setNodeMarkup(pos, undefined, {
                ...(state.doc.nodeAt(pos)?.attrs || {}),
                backgroundColor: color,
              });
            });
          } else {
            const $pos = state.selection.$from;
            for (let d = $pos.depth; d > 0; d--) {
              const node = $pos.node(d);
              if (
                node.type.name === "tableCell" ||
                node.type.name === "tableHeader"
              ) {
                const pos = $pos.before(d);
                tr.setNodeMarkup(pos, undefined, {
                  ...node.attrs,
                  backgroundColor: color,
                });
                break;
              }
            }
          }
          dispatch(tr);
          return true;
        },
    };
  },
});
