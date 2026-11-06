function pad2(value: number): string {
  return String(value).padStart(2, "0");
}

/** 手書きの画像に付けるファイル名（項目名_年月日-時分秒.png） */
export function buildHandwritingFileName(
  label: string,
  now: Date = new Date(),
): string {
  const base = (label || "handwriting").replace(/[\\/:*?"<>|\s]+/g, "_");
  const stamp =
    `${now.getFullYear()}${pad2(now.getMonth() + 1)}${pad2(now.getDate())}` +
    `-${pad2(now.getHours())}${pad2(now.getMinutes())}${pad2(now.getSeconds())}`;
  return `${base}_${stamp}.png`;
}
