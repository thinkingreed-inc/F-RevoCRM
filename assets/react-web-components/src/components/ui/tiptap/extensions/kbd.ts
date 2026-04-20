import { Mark } from "@tiptap/core";

export const Kbd = Mark.create({
  name: "kbd",
  parseHTML() {
    return [{ tag: "kbd" }];
  },
  renderHTML({ HTMLAttributes }) {
    return ["kbd", HTMLAttributes, 0];
  },
});
