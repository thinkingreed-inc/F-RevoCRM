import { Extension } from "@tiptap/core";

declare module "@tiptap/core" {
  interface Commands<ReturnType> {
    indent: {
      indent: () => ReturnType;
      outdent: () => ReturnType;
    };
  }
}

const INDENT_STEP = 24;
const MAX_INDENT = 10;

export const IndentExtension = Extension.create({
  name: "indent",
  addGlobalAttributes() {
    return [
      {
        types: ["paragraph", "heading", "blockquote"],
        attributes: {
          indent: {
            default: 0,
            parseHTML: (element) => {
              const ml = element.style.marginLeft;
              if (!ml) return 0;
              return Math.round(parseInt(ml, 10) / INDENT_STEP) || 0;
            },
            renderHTML: (attributes) => {
              const level = attributes.indent as number;
              if (!level || level <= 0) return {};
              return { style: `margin-left: ${level * INDENT_STEP}px` };
            },
          },
        },
      },
    ];
  },
  addCommands() {
    return {
      indent:
        () =>
        ({ tr, state, dispatch }) => {
          const { from, to } = state.selection;
          let changed = false;
          state.doc.nodesBetween(from, to, (node, pos) => {
            if (["paragraph", "heading", "blockquote"].includes(node.type.name)) {
              const current = (node.attrs.indent as number) || 0;
              if (current < MAX_INDENT) {
                if (dispatch) {
                  tr.setNodeMarkup(pos, undefined, {
                    ...node.attrs,
                    indent: current + 1,
                  });
                }
                changed = true;
              }
            }
          });
          return changed;
        },
      outdent:
        () =>
        ({ tr, state, dispatch }) => {
          const { from, to } = state.selection;
          let changed = false;
          state.doc.nodesBetween(from, to, (node, pos) => {
            if (["paragraph", "heading", "blockquote"].includes(node.type.name)) {
              const current = (node.attrs.indent as number) || 0;
              if (current > 0) {
                if (dispatch) {
                  tr.setNodeMarkup(pos, undefined, {
                    ...node.attrs,
                    indent: current - 1,
                  });
                }
                changed = true;
              }
            }
          });
          return changed;
        },
    };
  },
});
