import { Extension } from "@tiptap/core";

/**
 * SelectAll拡張
 * Ctrl+A（Windows/Linux）または Cmd+A（macOS）で全選択する。
 * ProseMirror は Mod-a を selectAll に明示的にマッピングしていないため、
 * この拡張で明示的にマッピングする。
 */
export const SelectAllExtension = Extension.create({
  name: "selectAll",
  addKeyboardShortcuts() {
    return {
      "Mod-a": () => this.editor.commands.selectAll(),
    };
  },
});
