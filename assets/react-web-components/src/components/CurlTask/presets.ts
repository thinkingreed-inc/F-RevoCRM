import { CurlPreset, CurlPresetKey } from "./types";

// プリセットは特定フィールド($subject等)を決め打ちしない。
// 該当フィールドが無いモジュールでは $var がそのまま送られてしまうため。
// 動的な値は「フィールド挿入」で対象モジュールの実項目を入れてもらう。
//
// Teamsは Power Automate / Workflows の Webhook(「Post card in a chat or channel」)向けに
// Adaptive Card を message/attachments で包んだ形式にする。
// ※ レガシーの Incoming Webhook(Office365コネクタ/MessageCard)はMicrosoftが廃止進行中。
// ※ $schema はテンプレート変数($xxx)と衝突するため意図的に含めない。
const teamsBody = JSON.stringify(
  {
    type: "message",
    attachments: [
      {
        contentType: "application/vnd.microsoft.card.adaptive",
        content: {
          type: "AdaptiveCard",
          version: "1.4",
          body: [
            {
              type: "TextBlock",
              size: "Medium",
              weight: "Bolder",
              text: "F-RevoCRM 通知",
            },
            {
              type: "TextBlock",
              text: "メッセージ本文",
              wrap: true,
            },
          ],
        },
      },
    ],
  },
  null,
  2,
);

const slackBody = JSON.stringify(
  {
    text: "メッセージ本文",
    blocks: [
      {
        type: "section",
        text: { type: "mrkdwn", text: "メッセージ本文" },
      },
    ],
  },
  null,
  2,
);

export const CURL_PRESETS: Record<CurlPresetKey, CurlPreset> = {
  teams: {
    key: "teams",
    labelKey: "LBL_CURL_PRESET_TEAMS",
    method: "POST",
    headers: "Content-Type: application/json",
    body: teamsBody,
  },
  slack: {
    key: "slack",
    labelKey: "LBL_CURL_PRESET_SLACK",
    method: "POST",
    headers: "Content-Type: application/json",
    body: slackBody,
  },
  generic: {
    key: "generic",
    labelKey: "LBL_CURL_PRESET_GENERIC",
    method: "POST",
    headers: "Content-Type: application/json",
    body: "{}",
  },
};

export function applyPreset(key: CurlPresetKey) {
  const p = CURL_PRESETS[key];
  return { method: p.method, headers: p.headers, body: p.body };
}
