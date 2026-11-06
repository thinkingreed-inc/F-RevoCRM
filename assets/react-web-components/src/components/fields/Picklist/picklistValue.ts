/**
 * ピックリスト WC の値・色まわりの純粋関数（#1677）
 *
 * 送信値の書式は既存の multipicklist と同じ ' |##| ' 連結。
 * これらは副作用なしで、Vitest で境界値を検証する。
 */

/** 複数選択の値の区切り（vtiger の multipicklist 格納書式） */
export const MULTIPICKLIST_SEPARATOR = " |##| ";

/**
 * 選択配列を hidden 入力へ書き戻す文字列にする。
 * 複数は ' |##| ' 連結、単一は先頭値（無ければ空文字）。
 */
export function serializeSelected(
  selected: string[],
  multiple: boolean,
): string {
  if (multiple) {
    return selected.join(MULTIPICKLIST_SEPARATOR);
  }
  return selected.length > 0 ? selected[0] : "";
}

/**
 * hidden 入力の文字列値を選択配列へ復元する（serializeSelected の逆）。
 * 複数は ' |##| ' 分割（前後空白除去・空要素除去）、単一は値があれば要素1。
 */
export function parseSelected(
  value: string | null | undefined,
  multiple: boolean,
): string[] {
  const raw = value == null ? "" : String(value);
  if (!multiple) {
    return raw === "" ? [] : [raw];
  }
  if (raw === "") return [];
  return raw
    .split(MULTIPICKLIST_SEPARATOR)
    .map((v) => v.trim())
    .filter((v) => v !== "");
}

/**
 * HEX 色を #rrggbb に正規化する。無効・空なら null。
 * 受理: #rgb / #rrggbb（# は省略可）。
 */
export function normalizeHexColor(
  color: string | null | undefined,
): string | null {
  if (!color) return null;
  let hex = String(color).trim().replace(/^#/, "");
  if (/^[0-9a-fA-F]{3}$/.test(hex)) {
    hex = hex
      .split("")
      .map((c) => c + c)
      .join("");
  }
  if (!/^[0-9a-fA-F]{6}$/.test(hex)) return null;
  return "#" + hex.toLowerCase();
}

/**
 * 背景色に対して読みやすい文字色（黒 or 白）を返す。
 * WCAG 相対輝度の簡易版（sRGB ガンマ近似）で判定する。
 * 無効色なら既定の黒。
 */
export function readableTextColor(
  color: string | null | undefined,
): "#000000" | "#ffffff" {
  const hex = normalizeHexColor(color);
  if (!hex) return "#000000";
  const r = parseInt(hex.slice(1, 3), 16) / 255;
  const g = parseInt(hex.slice(3, 5), 16) / 255;
  const b = parseInt(hex.slice(5, 7), 16) / 255;
  const toLinear = (c: number) =>
    c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
  const luminance =
    0.2126 * toLinear(r) + 0.7152 * toLinear(g) + 0.0722 * toLinear(b);
  // 輝度がしきい値より高ければ暗い文字、低ければ白文字
  return luminance > 0.5 ? "#000000" : "#ffffff";
}
