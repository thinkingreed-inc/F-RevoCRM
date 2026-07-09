import { CurlPreset, CurlPresetKey } from "./types";

const teamsBody = JSON.stringify(
  {
    "@type": "MessageCard",
    "@context": "https://schema.org/extensions",
    summary: "$subject",
    themeColor: "0078D4",
    title: "$subject",
    sections: [
      {
        activityTitle: "$assigned_user_id",
        text: "$description",
      },
    ],
  },
  null,
  2,
);

const slackBody = JSON.stringify(
  {
    text: "$subject",
    blocks: [
      {
        type: "section",
        text: { type: "mrkdwn", text: "*$subject*\n$description" },
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
