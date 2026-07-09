export function formatJson(text: string) {
  if (text.trim() === "") return { ok: true as const, text: "" };
  try {
    return { ok: true as const, text: JSON.stringify(JSON.parse(text), null, 2) };
  } catch (e) {
    return { ok: false as const, error: (e as Error).message };
  }
}

export function validateJson(text: string) {
  if (text.trim() === "") return { valid: true };
  try {
    JSON.parse(text);
    return { valid: true };
  } catch (e) {
    return { valid: false, error: (e as Error).message };
  }
}

export function insertAtCursor(
  value: string,
  selStart: number,
  selEnd: number,
  insert: string,
) {
  const text = value.slice(0, selStart) + insert + value.slice(selEnd);
  return { text, caret: selStart + insert.length };
}
