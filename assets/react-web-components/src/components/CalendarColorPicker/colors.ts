/**
 * カレンダー色選択用のプリセットパレット
 *
 * Tailwind CSS のカラー定義から、カレンダー上で視認しやすく区別しやすい
 * 色を厳選している。各色相について標準（500）と濃色（600）の2階調を用意し、
 * 「ちょうどいい色をそれなりの種類」から選べるようにしている。
 * ここに無い色はカスタム（RGB）指定で自由に選択できる。
 */

/** プリセット1色分の定義 */
export interface CalendarColorSwatch {
  /** Tailwind の色名（例: "blue-500"）。ツールチップ／aria-label に使用 */
  name: string;
  /** #rrggbb 形式の16進数カラーコード */
  hex: string;
}

/**
 * プリセットパレット本体。
 * Tailwind の各色相 × 標準/濃色の2階調を色相順に並べている。
 */
export const CALENDAR_COLOR_PALETTE: readonly CalendarColorSwatch[] = [
  { name: "red-500", hex: "#ef4444" },
  { name: "red-600", hex: "#dc2626" },
  { name: "orange-500", hex: "#f97316" },
  { name: "orange-600", hex: "#ea580c" },
  { name: "amber-500", hex: "#f59e0b" },
  { name: "amber-600", hex: "#d97706" },
  { name: "yellow-500", hex: "#eab308" },
  { name: "yellow-600", hex: "#ca8a04" },
  { name: "lime-500", hex: "#84cc16" },
  { name: "lime-600", hex: "#65a30d" },
  { name: "green-500", hex: "#22c55e" },
  { name: "green-600", hex: "#16a34a" },
  { name: "emerald-500", hex: "#10b981" },
  { name: "emerald-600", hex: "#059669" },
  { name: "teal-500", hex: "#14b8a6" },
  { name: "teal-600", hex: "#0d9488" },
  { name: "cyan-500", hex: "#06b6d4" },
  { name: "cyan-600", hex: "#0891b2" },
  { name: "sky-500", hex: "#0ea5e9" },
  { name: "sky-600", hex: "#0284c7" },
  { name: "blue-500", hex: "#3b82f6" },
  { name: "blue-600", hex: "#2563eb" },
  { name: "indigo-500", hex: "#6366f1" },
  { name: "indigo-600", hex: "#4f46e5" },
  { name: "violet-500", hex: "#8b5cf6" },
  { name: "violet-600", hex: "#7c3aed" },
  { name: "purple-500", hex: "#a855f7" },
  { name: "purple-600", hex: "#9333ea" },
  { name: "fuchsia-500", hex: "#d946ef" },
  { name: "fuchsia-600", hex: "#c026d3" },
  { name: "pink-500", hex: "#ec4899" },
  { name: "pink-600", hex: "#db2777" },
  { name: "rose-500", hex: "#f43f5e" },
  { name: "rose-600", hex: "#e11d48" },
  { name: "slate-500", hex: "#64748b" },
  { name: "slate-600", hex: "#475569" },
  { name: "gray-500", hex: "#6b7280" },
  { name: "gray-700", hex: "#374151" },
];

/** 16進数カラーコード（#rrggbb / #rgb）にマッチする正規表現 */
const HEX_COLOR_REGEX = /^#?([0-9a-f]{6}|[0-9a-f]{3})$/i;

/**
 * 入力された色文字列を #rrggbb（小文字）形式に正規化する。
 * 不正な値の場合は null を返す。
 */
export function normalizeHex(value: string | null | undefined): string | null {
  if (!value) {
    return null;
  }
  const trimmed = value.trim();
  if (!HEX_COLOR_REGEX.test(trimmed)) {
    return null;
  }
  let hex = trimmed.startsWith("#") ? trimmed.slice(1) : trimmed;
  // #rgb 形式は #rrggbb に展開する
  if (hex.length === 3) {
    hex = hex
      .split("")
      .map((c) => c + c)
      .join("");
  }
  return "#" + hex.toLowerCase();
}

/**
 * 与えられた色がパレット内のプリセットと一致するか（大文字小文字を無視）を判定する。
 */
export function isPresetColor(value: string | null | undefined): boolean {
  const normalized = normalizeHex(value);
  if (!normalized) {
    return false;
  }
  return CALENDAR_COLOR_PALETTE.some((swatch) => swatch.hex === normalized);
}
