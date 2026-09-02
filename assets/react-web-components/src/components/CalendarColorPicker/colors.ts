/**
 * カレンダー色選択用のプリセットパレット
 *
 * 「1列 = 1色相の濃淡」「1行 = 同じ明るさ」のグリッドとして定義する。
 * 全色相を1行に並べると横幅に収まらないため、色相を上下2グループに分け、
 * それぞれを 3行 × 7列 のブロックとして描画する。
 * どちらのグループも色相環の順（暖色 → 寒色 → ピンク／グレー）に並ぶので、
 * 画面上に軸ラベルを出さなくても並びの基準が伝わる。
 *
 * 色値は Tailwind CSS のカラーパレットから、カレンダー上で
 * 互いに判別しやすい色相を抜き出したもの。
 * ここに無い色はカスタム指定（HEX／RGB）で自由に選択できる。
 */

/** 明るさの段階。Tailwind の階調番号をそのまま識別子に使う（画面には出さない） */
export type CalendarColorTier = 400 | 500 | 600;

/** 明るさの段階を明るい順に並べたもの。ブロック内の行順になる */
export const CALENDAR_COLOR_TIERS: readonly CalendarColorTier[] = [
  400, 500, 600,
];

/** 明るさの段階に対応する表示名 */
const TIER_LABELS: Record<CalendarColorTier, string> = {
  400: "明るめ",
  500: "標準",
  600: "濃いめ",
};

/** 1色相分の定義 */
export interface CalendarColorHue {
  /** 内部識別子（例: "red"） */
  key: string;
  /** 表示名（例: "赤"）。Tailwind の内部名は画面に出さない */
  label: string;
  /** 明るさの段階ごとの #rrggbb */
  shades: Record<CalendarColorTier, string>;
}

/**
 * 色相の定義。1つの配列が1ブロック（= 3行 × 7列）の列構成になる。
 * 上下どちらのブロックも色相環を一周するよう、色相環の順に交互に振り分けている。
 */
export const CALENDAR_COLOR_HUE_GROUPS: readonly (readonly CalendarColorHue[])[] =
  [
    [
      {
        key: "red",
        label: "赤",
        shades: { 400: "#f87171", 500: "#ef4444", 600: "#dc2626" },
      },
      {
        key: "amber",
        label: "アンバー",
        shades: { 400: "#fbbf24", 500: "#f59e0b", 600: "#d97706" },
      },
      {
        key: "lime",
        label: "ライム",
        shades: { 400: "#a3e635", 500: "#84cc16", 600: "#65a30d" },
      },
      {
        key: "emerald",
        label: "エメラルド",
        shades: { 400: "#34d399", 500: "#10b981", 600: "#059669" },
      },
      {
        key: "cyan",
        label: "シアン",
        shades: { 400: "#22d3ee", 500: "#06b6d4", 600: "#0891b2" },
      },
      {
        key: "indigo",
        label: "インディゴ",
        shades: { 400: "#818cf8", 500: "#6366f1", 600: "#4f46e5" },
      },
      {
        key: "pink",
        label: "ピンク",
        shades: { 400: "#f472b6", 500: "#ec4899", 600: "#db2777" },
      },
    ],
    [
      {
        key: "orange",
        label: "オレンジ",
        shades: { 400: "#fb923c", 500: "#f97316", 600: "#ea580c" },
      },
      {
        key: "yellow",
        label: "黄",
        shades: { 400: "#facc15", 500: "#eab308", 600: "#ca8a04" },
      },
      {
        key: "green",
        label: "緑",
        shades: { 400: "#4ade80", 500: "#22c55e", 600: "#16a34a" },
      },
      {
        key: "teal",
        label: "ティール",
        shades: { 400: "#2dd4bf", 500: "#14b8a6", 600: "#0d9488" },
      },
      {
        key: "blue",
        label: "青",
        shades: { 400: "#60a5fa", 500: "#3b82f6", 600: "#2563eb" },
      },
      {
        key: "violet",
        label: "バイオレット",
        shades: { 400: "#a78bfa", 500: "#8b5cf6", 600: "#7c3aed" },
      },
      {
        key: "gray",
        label: "グレー",
        shades: { 400: "#9ca3af", 500: "#6b7280", 600: "#4b5563" },
      },
    ],
  ];

/**
 * グリッドの列数。画面側の grid-cols-* と一致させる必要がある。
 */
export const CALENDAR_COLOR_COLUMNS = CALENDAR_COLOR_HUE_GROUPS[0].length;

/** プリセット1色分の定義 */
export interface CalendarColorSwatch {
  /** 内部識別子（例: "red-500"）。React の key に使用 */
  name: string;
  /** 表示名（例: "赤（標準）"）。tooltip／aria-label に使用 */
  label: string;
  /** 色相の識別子（例: "red"） */
  hue: string;
  /** 明るさの段階 */
  tier: CalendarColorTier;
  /** #rrggbb 形式の16進数カラーコード */
  hex: string;
}

/**
 * 上下2つのブロック。grid は DOM 順に左上から埋まるため、
 * 行（明るさ）を外側、列（色相）を内側にして並べる。
 */
export const CALENDAR_COLOR_BLOCKS: readonly (readonly CalendarColorSwatch[])[] =
  CALENDAR_COLOR_HUE_GROUPS.map((hues) =>
    CALENDAR_COLOR_TIERS.flatMap((tier) =>
      hues.map((hue) => ({
        name: `${hue.key}-${tier}`,
        label: `${hue.label}（${TIER_LABELS[tier]}）`,
        hue: hue.key,
        tier,
        hex: hue.shades[tier],
      })),
    ),
  );

/** パレット本体。ブロックを上から順に連結した並び */
export const CALENDAR_COLOR_PALETTE: readonly CalendarColorSwatch[] =
  CALENDAR_COLOR_BLOCKS.flat();

/**
 * プリセット全色の #rrggbb を並び順どおりに返す。
 *
 * カレンダー側の JS（Calendar.js の getRandomColor）が色をランダムに選ぶ際に
 * 参照する。色の定義をこのファイルに一元化し、JS 側に同じ色表を持たせない。
 */
export function getCalendarColorHexes(): string[] {
  return CALENDAR_COLOR_PALETTE.map((swatch) => swatch.hex);
}

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
