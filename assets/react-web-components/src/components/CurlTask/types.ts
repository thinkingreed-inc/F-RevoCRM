export type CurlPresetKey = "teams" | "slack" | "generic";

export interface CurlPreset {
  key: CurlPresetKey;
  labelKey: string; // 翻訳キー
  method: string;
  headers: string;
  body: string;
}

export interface FieldOption {
  name: string; // マージ変数名（$name で参照）
  label: string; // 表示名
}
