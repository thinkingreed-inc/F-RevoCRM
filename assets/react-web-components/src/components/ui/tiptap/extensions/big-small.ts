import { Mark } from "@tiptap/core";

export const Big = Mark.create({
  name: "big",
  parseHTML() {
    return [{ tag: "big" }];
  },
  renderHTML({ HTMLAttributes }) {
    return ["big", HTMLAttributes, 0];
  },
});

export const Small = Mark.create({
  name: "small",
  parseHTML() {
    return [{ tag: "small" }];
  },
  renderHTML({ HTMLAttributes }) {
    return ["small", HTMLAttributes, 0];
  },
});
