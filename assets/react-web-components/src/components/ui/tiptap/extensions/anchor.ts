import { Node } from "@tiptap/core";

const ANCHOR_NAME_RE = /^[a-zA-Z0-9][a-zA-Z0-9\-_]*$/;

// DOMクロバリング対策: これらのid/name値はブラウザのDOM APIを乗っ取る可能性がある
const DANGEROUS_ANCHOR_NAMES = new Set([
  "forms", "elements", "location", "document", "window", "content",
  "body", "head", "top", "self", "parent", "frames", "opener",
  "navigator", "history", "screen", "cookie", "domain", "referrer",
  "title", "images", "links", "anchors", "plugins", "embeds",
  "scripts", "all", "children",
  "tostring", "valueof", "constructor", "__proto__",
  "nodetype", "nodename", "baseuri", "defaultview", "documentelement",
]);

function normalizeAnchorName(raw: string): string | null {
  if (!raw) return null;
  const trimmed = raw.trim();
  if (!ANCHOR_NAME_RE.test(trimmed)) return null;
  if (DANGEROUS_ANCHOR_NAMES.has(trimmed.toLowerCase())) return null;
  return trimmed;
}

export const Anchor = Node.create({
  name: "anchor",
  inline: true,
  group: "inline",
  atom: true,
  selectable: false,

  addAttributes() {
    return {
      anchor: {
        default: null,
        parseHTML: (element) => {
          const value =
            element.getAttribute("id") || element.getAttribute("name");
          return normalizeAnchorName(value || "");
        },
        renderHTML: (attributes) => {
          if (!attributes.anchor) return {};
          return { id: attributes.anchor, name: attributes.anchor };
        },
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: "a",
        getAttrs: (el) => {
          const element = el as HTMLElement;
          if (element.getAttribute("href")) return false;
          const id = element.getAttribute("id");
          const name = element.getAttribute("name");
          if (!id && !name) return false;
          if (!normalizeAnchorName(id || name || "")) return false;
          return {};
        },
      },
    ];
  },

  renderHTML({ node }) {
    const anchorName = node.attrs.anchor;
    if (!anchorName) return ["a", {}];
    return ["a", { id: anchorName, name: anchorName }];
  },
});
