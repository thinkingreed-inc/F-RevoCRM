import { Extension } from "@tiptap/core";
import { normalizeColor } from "./utils/normalize";

export const BlockColor = Extension.create({
  name: "blockColor",
  addGlobalAttributes() {
    return [
      {
        types: ["heading", "paragraph"],
        attributes: {
          color: {
            default: null,
            parseHTML: (element) => {
              const raw = element.style.color;
              if (!raw) return null;
              return normalizeColor(raw);
            },
            renderHTML: (attributes) => {
              if (!attributes.color) return {};
              return { style: `color: ${attributes.color}` };
            },
          },
        },
      },
    ];
  },
});
