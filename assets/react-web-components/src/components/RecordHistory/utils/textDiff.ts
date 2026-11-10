export type DiffOp = "equal" | "insert" | "delete";

export interface DiffSegment {
  op: DiffOp;
  text: string;
}

/** これを超える長さ同士の比較は計算量が大きいため、差分を取らず全置換として扱う */
export const MAX_DIFF_CELLS = 4_000_000;

/**
 * 文字単位の差分（LCS）。日本語は単語区切りが無いため文字単位で比較する。
 * サロゲートペアを壊さないようにコードポイント単位で扱う。
 */
export function diffText(before: string, after: string): DiffSegment[] {
  if (before === after) return before ? [{ op: "equal", text: before }] : [];

  const a = Array.from(before);
  const b = Array.from(after);

  // 共通の先頭・末尾を除いてから LCS を計算する（よくある「一部だけ書き換え」を軽くする）
  let start = 0;
  while (start < a.length && start < b.length && a[start] === b[start]) start++;
  let endA = a.length;
  let endB = b.length;
  while (endA > start && endB > start && a[endA - 1] === b[endB - 1]) {
    endA--;
    endB--;
  }

  const segments: DiffSegment[] = [];
  const push = (op: DiffOp, text: string) => {
    if (!text) return;
    const last = segments[segments.length - 1];
    if (last && last.op === op) last.text += text;
    else segments.push({ op, text });
  };

  push("equal", a.slice(0, start).join(""));

  const midA = a.slice(start, endA);
  const midB = b.slice(start, endB);
  const n = midA.length;
  const m = midB.length;

  if ((n + 1) * (m + 1) > MAX_DIFF_CELLS) {
    push("delete", midA.join(""));
    push("insert", midB.join(""));
  } else {
    // dp[i][j] = midA[i..] と midB[j..] の LCS 長
    const width = m + 1;
    const dp = new Uint32Array((n + 1) * width);
    for (let i = n - 1; i >= 0; i--) {
      for (let j = m - 1; j >= 0; j--) {
        dp[i * width + j] =
          midA[i] === midB[j]
            ? dp[(i + 1) * width + j + 1] + 1
            : Math.max(dp[(i + 1) * width + j], dp[i * width + j + 1]);
      }
    }
    let i = 0;
    let j = 0;
    while (i < n && j < m) {
      if (midA[i] === midB[j]) {
        push("equal", midA[i]);
        i++;
        j++;
      } else if (dp[(i + 1) * width + j] >= dp[i * width + j + 1]) {
        push("delete", midA[i]);
        i++;
      } else {
        push("insert", midB[j]);
        j++;
      }
    }
    push("delete", midA.slice(i).join(""));
    push("insert", midB.slice(j).join(""));
  }

  push("equal", a.slice(endA).join(""));
  return segments;
}
