import { describe, it, expect } from "vitest";
import { formatJson, validateJson, insertAtCursor } from "./jsonEditorUtils";

describe("formatJson", () => {
  it("pretty-prints valid JSON", () => {
    const r = formatJson('{"a":1}');
    expect(r.ok).toBe(true);
    if (r.ok) expect(r.text).toBe('{\n  "a": 1\n}');
  });
  it("returns error for invalid JSON", () => {
    const r = formatJson("{bad}");
    expect(r.ok).toBe(false);
  });
});

describe("validateJson", () => {
  it("valid for empty string", () => {
    expect(validateJson("").valid).toBe(true);
  });
  it("invalid reports error", () => {
    expect(validateJson("{").valid).toBe(false);
  });
});

describe("insertAtCursor", () => {
  it("inserts at caret and returns new caret position", () => {
    const r = insertAtCursor("ab", 1, 1, "$x");
    expect(r.text).toBe("a$xb");
    expect(r.caret).toBe(3);
  });
  it("replaces selection", () => {
    const r = insertAtCursor("abc", 1, 2, "$x");
    expect(r.text).toBe("a$xc");
    expect(r.caret).toBe(3);
  });
});
