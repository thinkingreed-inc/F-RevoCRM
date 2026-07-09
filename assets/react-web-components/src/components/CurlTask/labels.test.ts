import { describe, it, expect } from "vitest";
import {
  DEFAULT_CURL_LABELS,
  mergeLabels,
  presetLabel,
} from "./labels";

describe("mergeLabels", () => {
  it("returns defaults when nothing passed", () => {
    expect(mergeLabels()).toEqual(DEFAULT_CURL_LABELS);
  });
  it("overrides only provided keys", () => {
    const r = mergeLabels({ testSend: "Test Send" });
    expect(r.testSend).toBe("Test Send");
    expect(r.format).toBe(DEFAULT_CURL_LABELS.format);
  });
  it("ignores empty strings and falls back to default", () => {
    const r = mergeLabels({ format: "" });
    expect(r.format).toBe(DEFAULT_CURL_LABELS.format);
  });
});

describe("presetLabel", () => {
  it("maps preset keys to labels", () => {
    const labels = mergeLabels({
      presetTeams: "Teams",
      presetSlack: "Slack",
      presetGeneric: "Generic",
    });
    expect(presetLabel(labels, "teams")).toBe("Teams");
    expect(presetLabel(labels, "slack")).toBe("Slack");
    expect(presetLabel(labels, "generic")).toBe("Generic");
  });
});
