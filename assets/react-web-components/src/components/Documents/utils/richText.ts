/**
 * メモ（notecontent）の表示用ユーティリティ
 *
 * メモはリッチテキストエディタ（Jodit）が保存した HTML で、本文が空でも
 * `<p><br /></p>` のような値が入る。一覧・プレビュー・詳細ではテキストとして
 * 表示するため、タグを落としてから使う。
 */

/** 実体参照のうち、メモでよく出るものだけ戻す */
const ENTITIES: Record<string, string> = {
  "&nbsp;": " ",
  "&amp;": "&",
  "&lt;": "<",
  "&gt;": ">",
  "&quot;": '"',
  "&#39;": "'",
  "&apos;": "'",
};

/**
 * HTML を表示用のプレーンテキストにする
 *
 * 改行は残す（段落・改行タグを改行に置き換える）。中身が無ければ空文字を返す。
 */
export function htmlToPlainText(html: string | null | undefined): string {
  if (!html) return "";

  const text = html
    // スクリプト・スタイルは中身ごと落とす
    .replace(/<(script|style)[^>]*>[\s\S]*?<\/\1>/gi, "")
    // 改行になるタグを改行に置き換える
    .replace(/<br\s*\/?>/gi, "\n")
    .replace(/<\/(p|div|li|tr|h[1-6])\s*>/gi, "\n")
    // 残りのタグを落とす
    .replace(/<[^>]*>/g, "")
    .replace(/&[a-z]+;|&#\d+;/gi, (entity) => {
      const lower = entity.toLowerCase();
      return Object.prototype.hasOwnProperty.call(ENTITIES, lower)
        ? ENTITIES[lower]
        : entity;
    })
    // 空段落が続いても行が増えないようにまとめる
    .replace(/[ \t]+\n/g, "\n")
    .replace(/\n{3,}/g, "\n\n");

  return text.trim();
}

/**
 * HTML を1行のプレーンテキストにする（一覧の要約用）
 */
export function htmlToSummaryText(html: string | null | undefined): string {
  return htmlToPlainText(html).replace(/\s+/g, " ").trim();
}
