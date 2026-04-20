// CSS Level 4 Named Colors（148色）→ #RRGGBB マッピング
const CSS_NAMED_COLORS: Record<string, string> = {
  aliceblue: "#f0f8ff",
  antiquewhite: "#faebd7",
  aqua: "#00ffff",
  aquamarine: "#7fffd4",
  azure: "#f0ffff",
  beige: "#f5f5dc",
  bisque: "#ffe4c4",
  black: "#000000",
  blanchedalmond: "#ffebcd",
  blue: "#0000ff",
  blueviolet: "#8a2be2",
  brown: "#a52a2a",
  burlywood: "#deb887",
  cadetblue: "#5f9ea0",
  chartreuse: "#7fff00",
  chocolate: "#d2691e",
  coral: "#ff7f50",
  cornflowerblue: "#6495ed",
  cornsilk: "#fff8dc",
  crimson: "#dc143c",
  cyan: "#00ffff",
  darkblue: "#00008b",
  darkcyan: "#008b8b",
  darkgoldenrod: "#b8860b",
  darkgray: "#a9a9a9",
  darkgreen: "#006400",
  darkgrey: "#a9a9a9",
  darkkhaki: "#bdb76b",
  darkmagenta: "#8b008b",
  darkolivegreen: "#556b2f",
  darkorange: "#ff8c00",
  darkorchid: "#9932cc",
  darkred: "#8b0000",
  darksalmon: "#e9967a",
  darkseagreen: "#8fbc8f",
  darkslateblue: "#483d8b",
  darkslategray: "#2f4f4f",
  darkslategrey: "#2f4f4f",
  darkturquoise: "#00ced1",
  darkviolet: "#9400d3",
  deeppink: "#ff1493",
  deepskyblue: "#00bfff",
  dimgray: "#696969",
  dimgrey: "#696969",
  dodgerblue: "#1e90ff",
  firebrick: "#b22222",
  floralwhite: "#fffaf0",
  forestgreen: "#228b22",
  fuchsia: "#ff00ff",
  gainsboro: "#dcdcdc",
  ghostwhite: "#f8f8ff",
  gold: "#ffd700",
  goldenrod: "#daa520",
  gray: "#808080",
  green: "#008000",
  greenyellow: "#adff2f",
  grey: "#808080",
  honeydew: "#f0fff0",
  hotpink: "#ff69b4",
  indianred: "#cd5c5c",
  indigo: "#4b0082",
  ivory: "#fffff0",
  khaki: "#f0e68c",
  lavender: "#e6e6fa",
  lavenderblush: "#fff0f5",
  lawngreen: "#7cfc00",
  lemonchiffon: "#fffacd",
  lightblue: "#add8e6",
  lightcoral: "#f08080",
  lightcyan: "#e0ffff",
  lightgoldenrodyellow: "#fafad2",
  lightgray: "#d3d3d3",
  lightgreen: "#90ee90",
  lightgrey: "#d3d3d3",
  lightpink: "#ffb6c1",
  lightsalmon: "#ffa07a",
  lightseagreen: "#20b2aa",
  lightskyblue: "#87cefa",
  lightslategray: "#778899",
  lightslategrey: "#778899",
  lightsteelblue: "#b0c4de",
  lightyellow: "#ffffe0",
  lime: "#00ff00",
  limegreen: "#32cd32",
  linen: "#faf0e6",
  magenta: "#ff00ff",
  maroon: "#800000",
  mediumaquamarine: "#66cdaa",
  mediumblue: "#0000cd",
  mediumorchid: "#ba55d3",
  mediumpurple: "#9370db",
  mediumseagreen: "#3cb371",
  mediumslateblue: "#7b68ee",
  mediumspringgreen: "#00fa9a",
  mediumturquoise: "#48d1cc",
  mediumvioletred: "#c71585",
  midnightblue: "#191970",
  mintcream: "#f5fffa",
  mistyrose: "#ffe4e1",
  moccasin: "#ffe4b5",
  navajowhite: "#ffdead",
  navy: "#000080",
  oldlace: "#fdf5e6",
  olive: "#808000",
  olivedrab: "#6b8e23",
  orange: "#ffa500",
  orangered: "#ff4500",
  orchid: "#da70d6",
  palegoldenrod: "#eee8aa",
  palegreen: "#98fb98",
  paleturquoise: "#afeeee",
  palevioletred: "#db7093",
  papayawhip: "#ffefd5",
  peachpuff: "#ffdab9",
  peru: "#cd853f",
  pink: "#ffc0cb",
  plum: "#dda0dd",
  powderblue: "#b0e0e6",
  purple: "#800080",
  rebeccapurple: "#663399",
  red: "#ff0000",
  rosybrown: "#bc8f8f",
  royalblue: "#4169e1",
  saddlebrown: "#8b4513",
  salmon: "#fa8072",
  sandybrown: "#f4a460",
  seagreen: "#2e8b57",
  seashell: "#fff5ee",
  sienna: "#a0522d",
  silver: "#c0c0c0",
  skyblue: "#87ceeb",
  slateblue: "#6a5acd",
  slategray: "#708090",
  slategrey: "#708090",
  snow: "#fffafa",
  springgreen: "#00ff7f",
  steelblue: "#4682b4",
  tan: "#d2b48c",
  teal: "#008080",
  thistle: "#d8bfd8",
  tomato: "#ff6347",
  turquoise: "#40e0d0",
  violet: "#ee82ee",
  wheat: "#f5deb3",
  white: "#ffffff",
  whitesmoke: "#f5f5f5",
  yellow: "#ffff00",
  yellowgreen: "#9acd32",
};

/**
 * CSS Color値を正規化する。
 * 対応形式: #RRGGBB, #RGB, rgb(), rgba(), CSS名前付きカラー
 * 不正値はnullを返す。
 */
export function normalizeColor(raw: string): string | null {
  if (!raw) return null;
  const trimmed = raw.trim().toLowerCase();
  if (!trimmed) return null;

  // #RRGGBB
  if (/^#[0-9a-f]{6}$/.test(trimmed)) return trimmed;

  // #RGB → #RRGGBB
  const m3 = trimmed.match(/^#([0-9a-f])([0-9a-f])([0-9a-f])$/);
  if (m3) return `#${m3[1]}${m3[1]}${m3[2]}${m3[2]}${m3[3]}${m3[3]}`;

  // rgb(r, g, b)
  const mRgb = trimmed.match(
    /^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/
  );
  if (mRgb) {
    const [r, g, b] = [mRgb[1], mRgb[2], mRgb[3]].map(Number);
    if (r <= 255 && g <= 255 && b <= 255) return trimmed;
    return null;
  }

  // rgba(r, g, b, a)
  const mRgba = trimmed.match(
    /^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([\d.]+)\s*\)$/
  );
  if (mRgba) {
    const [r, g, b] = [mRgba[1], mRgba[2], mRgba[3]].map(Number);
    const a = parseFloat(mRgba[4]);
    if (r <= 255 && g <= 255 && b <= 255 && a >= 0 && a <= 1) return trimmed;
    return null;
  }

  // CSS名前付きカラー
  const named = CSS_NAMED_COLORS[trimmed];
  if (named) return named;

  return null;
}

/**
 * CSS Length値を正規化する。
 * 対応形式: 数値+単位(px/em/rem/%/pt)、および単位なし "0"
 * minPx/maxPxが指定された場合、px単位の値に範囲制限を設ける。
 * 不正値はnullを返す。
 */
export function normalizeLength(
  raw: string,
  maxPx?: number,
  minPx?: number
): string | null {
  if (!raw) return null;
  const trimmed = raw.trim();

  // 単位なし "0" は有効（margin: 0 等）
  if (trimmed === "0") return "0";

  const match = trimmed.match(/^(\d+(?:\.\d+)?)(px|em|rem|%|pt)$/);
  if (!match) return null;
  const num = parseFloat(match[1]);
  const unit = match[2];
  if (maxPx != null && unit === "px" && num > maxPx) return null;
  if (minPx != null && unit === "px" && num < minPx) return null;
  return `${match[1]}${unit}`;
}

/**
 * CSSのpadding/margin shorthandを正規化する。
 * 各辺の値を個別にnormalizeLengthして再結合。
 * 1辺でも不正値があれば全体をnullとする。
 */
export function normalizeSpacing(raw: string): string | null {
  if (!raw) return null;
  const parts = raw.trim().split(/\s+/);
  if (parts.length < 1 || parts.length > 4) return null;
  const normalized = parts.map((p) => normalizeLength(p));
  if (normalized.some((p) => p === null)) return null;
  return normalized.join(" ");
}

const BORDER_STYLES = [
  "none", "solid", "dashed", "dotted", "double",
  "groove", "ridge", "inset", "outset",
];

/**
 * Border Style値をEnum正規化する。
 */
export function normalizeBorderStyle(raw: string): string | null {
  if (!raw) return null;
  const normalized = raw.trim().toLowerCase();
  return BORDER_STYLES.includes(normalized) ? normalized : null;
}

const TEXT_ALIGNS = ["left", "center", "right", "justify"];

/**
 * Text Align値をEnum正規化する。
 */
export function normalizeTextAlign(raw: string): string | null {
  if (!raw) return null;
  const normalized = raw.trim().toLowerCase();
  return TEXT_ALIGNS.includes(normalized) ? normalized : null;
}

const VERTICAL_ALIGNS = ["top", "middle", "bottom", "baseline"];

/**
 * Vertical Align値をEnum正規化する。
 */
export function normalizeVerticalAlign(raw: string): string | null {
  if (!raw) return null;
  const normalized = raw.trim().toLowerCase();
  return VERTICAL_ALIGNS.includes(normalized) ? normalized : null;
}

/**
 * Border Shorthand（例: "1px solid #000"）を正規化する。
 * width/style/colorの3要素に分解し各々を正規化して再結合。
 * いずれかが不正の場合はnullを返す。
 */
export function normalizeBorderShorthand(raw: string): string | null {
  if (!raw) return null;
  const parts = raw.trim().split(/\s+/);
  if (parts.length !== 3) return null;
  const width = normalizeLength(parts[0]);
  const style = normalizeBorderStyle(parts[1]);
  const color = normalizeColor(parts[2]);
  if (!width || !style || !color) return null;
  return `${width} ${style} ${color}`;
}
