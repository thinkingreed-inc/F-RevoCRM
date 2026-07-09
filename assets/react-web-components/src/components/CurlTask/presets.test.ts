import { describe, it, expect } from "vitest";
import { CURL_PRESETS, applyPreset } from "./presets";

describe("CURL_PRESETS", () => {
  it("teams preset body is valid JSON", () => {
    expect(() => JSON.parse(CURL_PRESETS.teams.body)).not.toThrow();
  });
  it("slack preset body is valid JSON", () => {
    expect(() => JSON.parse(CURL_PRESETS.slack.body)).not.toThrow();
  });
  it("teams headers contain application/json content-type", () => {
    expect(CURL_PRESETS.teams.headers.toLowerCase()).toContain(
      "content-type: application/json",
    );
  });
  it("presets do not hard-code field variables ($xxx)", () => {
    // 決め打ち変数は対象モジュールに無いとそのまま送信されるため含めない
    expect(CURL_PRESETS.teams.body).not.toContain("$");
    expect(CURL_PRESETS.slack.body).not.toContain("$");
    expect(CURL_PRESETS.generic.body).not.toContain("$");
  });

  it("applyPreset returns method/headers/body", () => {
    const r = applyPreset("slack");
    expect(r.method).toBe("POST");
    expect(r.headers).toBe(CURL_PRESETS.slack.headers);
    expect(r.body).toBe(CURL_PRESETS.slack.body);
  });
});
