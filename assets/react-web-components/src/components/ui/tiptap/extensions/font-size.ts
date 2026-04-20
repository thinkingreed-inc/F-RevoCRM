import { Extension } from "@tiptap/core";
import { normalizeLength } from "./utils/normalize";

declare module "@tiptap/core" {
  interface Commands<ReturnType> {
    fontSize: {
      setFontSize: (size: string) => ReturnType;
      unsetFontSize: () => ReturnType;
    };
  }
}

export const FontSizeExtension = Extension.create({
  name: "fontSize",
  addGlobalAttributes() {
    return [
      {
        types: ["textStyle"],
        attributes: {
          fontSize: {
            default: null,
            parseHTML: (element) => {
              const raw = element.style.fontSize;
              if (!raw) return null;
              return normalizeLength(raw, 200, 1);
            },
            renderHTML: (attributes) => {
              if (!attributes.fontSize) return {};
              return { style: `font-size: ${attributes.fontSize}` };
            },
          },
        },
      },
    ];
  },
  addCommands() {
    return {
      setFontSize:
        (size: string) =>
        ({ chain, state }) => {
          if (state.selection.empty) {
            const { from } = state.selection;
            const c = chain();
            if (
              from > 0 &&
              state.doc.textBetween(from - 1, from) === "\u200B"
            ) {
              c.deleteRange({ from: from - 1, to: from });
            }
            return c
              .setMark("textStyle", { fontSize: size })
              .insertContent("\u200B")
              .run();
          }
          return chain().setMark("textStyle", { fontSize: size }).run();
        },
      unsetFontSize:
        () =>
        ({ chain, state }) => {
          if (state.selection.empty) {
            const { from } = state.selection;
            const c = chain();
            if (
              from > 0 &&
              state.doc.textBetween(from - 1, from) === "\u200B"
            ) {
              c.deleteRange({ from: from - 1, to: from });
            }
            return c
              .setMark("textStyle", { fontSize: null })
              .removeEmptyTextStyle()
              .insertContent("\u200B")
              .run();
          }
          return chain()
            .setMark("textStyle", { fontSize: null })
            .removeEmptyTextStyle()
            .run();
        },
    };
  },
});

export const FONT_SIZES = [
  { label: "10px", value: "10px" },
  { label: "12px", value: "12px" },
  { label: "14px", value: "14px" },
  { label: "16px", value: "16px" },
  { label: "18px", value: "18px" },
  { label: "20px", value: "20px" },
  { label: "24px", value: "24px" },
  { label: "28px", value: "28px" },
  { label: "32px", value: "32px" },
  { label: "36px", value: "36px" },
  { label: "48px", value: "48px" },
];
