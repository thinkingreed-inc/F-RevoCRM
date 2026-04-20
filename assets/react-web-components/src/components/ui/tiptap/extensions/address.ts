import { Node } from "@tiptap/core";

export const Address = Node.create({
  name: "address",
  group: "block",
  content: "inline*",
  parseHTML() {
    return [{ tag: "address" }];
  },
  renderHTML({ HTMLAttributes }) {
    return ["address", HTMLAttributes, 0];
  },
});
