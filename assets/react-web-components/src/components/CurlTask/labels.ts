import { CurlPresetKey } from "./types";

/**
 * vt-curl-task で表示する全ラベル。
 * 値は tpl 側の vtranslate() で翻訳済みの文字列が labels-json 属性経由で渡る。
 * 属性が渡らない場合（テスト等）は下記の日本語デフォルトにフォールバックする。
 */
export interface CurlLabels {
  url: string;
  method: string;
  headers: string;
  body: string;
  timeout: string;
  timeoutHelp: string;
  preset: string;
  presetTeams: string;
  presetSlack: string;
  presetGeneric: string;
  presetOverwriteConfirm: string;
  format: string;
  insertField: string;
  testSend: string;
  testSending: string;
  testSendNote: string;
  jsonValid: string;
  jsonInvalid: string;
  ok: string;
  cancel: string;
  adaptiveCardDesigner: string;
}

/** Teams向け Adaptive Card を編集・プレビューできる公式デザイナー */
export const ADAPTIVE_CARD_DESIGNER_URL = "https://adaptivecards.io/designer/";

export const DEFAULT_CURL_LABELS: CurlLabels = {
  url: "リクエストURL",
  method: "HTTPメソッド",
  headers: "リクエストヘッダー",
  body: "リクエストボディ",
  timeout: "タイムアウト（秒）",
  timeoutHelp: "1〜60秒の範囲で指定してください（デフォルト: 30秒）",
  preset: "プリセット",
  presetTeams: "Teams",
  presetSlack: "Slack",
  presetGeneric: "汎用",
  presetOverwriteConfirm: "既存の内容を上書きします。よろしいですか？",
  format: "整形",
  insertField: "フィールド挿入",
  testSend: "テスト送信",
  testSending: "送信中...",
  testSendNote:
    "テスト送信では $項目名 などのフィールド値は置換されず、そのまま送信されます。実際の値の埋め込みはワークフロー実行時に行われます。",
  jsonValid: "JSON OK",
  jsonInvalid: "不正なJSON",
  ok: "OK",
  cancel: "キャンセル",
  adaptiveCardDesigner: "Adaptive Card デザイナーで編集",
};

/**
 * 渡されたラベル（部分）をデフォルトにマージする。
 * 空文字・null・undefined はデフォルトで補完する。
 */
export function mergeLabels(partial?: Partial<CurlLabels> | null): CurlLabels {
  if (!partial) return { ...DEFAULT_CURL_LABELS };
  const merged: CurlLabels = { ...DEFAULT_CURL_LABELS };
  (Object.keys(DEFAULT_CURL_LABELS) as (keyof CurlLabels)[]).forEach((key) => {
    const v = partial[key];
    if (typeof v === "string" && v !== "") {
      merged[key] = v;
    }
  });
  return merged;
}

export function presetLabel(labels: CurlLabels, key: CurlPresetKey): string {
  switch (key) {
    case "teams":
      return labels.presetTeams;
    case "slack":
      return labels.presetSlack;
    case "generic":
      return labels.presetGeneric;
    default:
      return key;
  }
}
