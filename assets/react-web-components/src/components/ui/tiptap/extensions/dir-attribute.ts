import { Extension } from "@tiptap/core";

const ALLOWED_DIR = ["ltr", "rtl"];

export const DirAttribute = Extension.create({
  name: "dirAttribute",
  addGlobalAttributes() {
    return [
      {
        types: ["paragraph", "heading", "address", "blockquote"],
        attributes: {
          dir: {
            default: null,
            parseHTML: (element) => {
              const raw = element.getAttribute("dir");
              if (!raw) return null;
              const normalized = raw.toLowerCase().trim();
              return ALLOWED_DIR.includes(normalized) ? normalized : null;
            },
            renderHTML: (attributes) => {
              if (!attributes.dir) return {};
              return { dir: attributes.dir };
            },
          },
        },
      },
    ];
  },
});
