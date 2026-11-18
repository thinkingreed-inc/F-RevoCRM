import { describe, expect, it } from "vitest";
import type {
  SharedLink,
  SharedLinkInput,
  SharedLinkLimits,
} from "@/types/sharedLinks";
import {
  createEmptyLink,
  hasTarget,
  isValidHttpUrl,
  toInput,
  toPayload,
  toTargetType,
  validateSharedLink,
  validateWidgetTitle,
} from "../sharedLinkForm";

/**
 * 共有リンクの編集フォームの補助のテスト
 *
 * 入力の検証はサーバー側（tests/Unit/Settings/SharedLinks/RulesTest.php）と同じ規則。
 * 仕様: docs/specs/shared_links_widget.md 3.1 / 3.3 / 3.4 / 4
 */

const LIMITS: SharedLinkLimits = {
  title_max: 100,
  url_max: 2000,
  description_max: 500,
  params_max: 20,
  param_name_max: 100,
  param_value_max: 1000,
  window_min: 200,
  window_max: 4000,
  default_window_width: 1024,
  default_window_height: 768,
  widget_title_max: 30,
};

const USER_FIELDS = ["id", "full_name", "email1", "user_name"];

const valid = (overrides: Partial<SharedLinkInput> = {}): SharedLinkInput => ({
  ...createEmptyLink(),
  title: "勤怠システム",
  url: "https://kintai.example.com/",
  ...overrides,
});

const validate = (overrides: Partial<SharedLinkInput> = {}) =>
  validateSharedLink(valid(overrides), LIMITS, USER_FIELDS);

describe("createEmptyLink", () => {
  it("TC-UI-054 既定は GET・別タブ・全員・有効・リファラーを送る", () => {
    expect(createEmptyLink()).toEqual({
      title: "",
      url: "",
      description: "",
      method: "GET",
      open_mode: "tab",
      window_width: null,
      window_height: null,
      noreferrer: false,
      active: true,
      visibility: "all",
      params: [],
      targets: [],
    });
  });
});

describe("toInput", () => {
  it("一覧の表示用の情報（ラベル・削除済みなど）を落とす", () => {
    const link: SharedLink = {
      ...valid({ visibility: "targets" }),
      id: 3,
      sequence: 1,
      has_warning: true,
      params: [
        {
          name: "m",
          value_type: "user",
          value: "email1",
          label: "メール",
          missing: false,
        },
      ],
      targets: [{ type: "Groups", id: "5", name: null, deleted: true }],
    };

    const input = toInput(link);

    expect(input.params).toEqual([
      { name: "m", value_type: "user", value: "email1" },
    ]);
    expect(input.targets).toEqual([{ type: "Groups", id: "5" }]);
    expect(input).not.toHaveProperty("id");
    expect(input).not.toHaveProperty("has_warning");
  });
});

describe("toPayload", () => {
  it("TC-VAL-060 TC-VAL-020 TC-VAL-040 前後の空白を落とし、別タブならサイズを、全員なら対象を捨てる", () => {
    const payload = toPayload(
      valid({
        title: "  勤怠 ",
        url: " https://a.example.com ",
        window_width: 800,
        window_height: 600,
        open_mode: "tab",
        visibility: "all",
        targets: [{ type: "Users", id: "1" }],
        params: [{ name: " id ", value_type: "fixed", value: " 値 " }],
      }),
    );

    expect(payload.title).toBe("勤怠");
    expect(payload.url).toBe("https://a.example.com");
    expect(payload.window_width).toBeNull();
    expect(payload.window_height).toBeNull();
    expect(payload.targets).toEqual([]);
    expect(payload.params[0]).toEqual({
      name: "id",
      value_type: "fixed",
      value: " 値 ",
    });
  });

  it("別ウィンドウ・指定した対象のみなら残す", () => {
    const payload = toPayload(
      valid({
        open_mode: "window",
        window_width: 800,
        window_height: 600,
        visibility: "targets",
        targets: [{ type: "Users", id: "1" }],
      }),
    );

    expect(payload.window_width).toBe(800);
    expect(payload.targets).toEqual([{ type: "Users", id: "1" }]);
  });
});

describe("toTargetType", () => {
  it("下位を含むのは役割のときだけ", () => {
    expect(toTargetType("Roles", true)).toBe("RoleAndSubordinates");
    expect(toTargetType("Roles", false)).toBe("Roles");
    expect(toTargetType("Groups", true)).toBe("Groups");
    expect(toTargetType("Users", true)).toBe("Users");
  });
});

describe("hasTarget", () => {
  it("TC-UI-044 種類と ID の両方が同じものだけを重複とみなす", () => {
    const targets = [{ type: "Roles" as const, id: "H2" }];

    expect(hasTarget(targets, { type: "Roles", id: "H2" })).toBe(true);
    expect(hasTarget(targets, { type: "RoleAndSubordinates", id: "H2" })).toBe(
      false,
    );
    expect(hasTarget(targets, { type: "Roles", id: "H20" })).toBe(false);
  });
});

describe("isValidHttpUrl", () => {
  it.each([
    ["https://a.example.com/x?y=1#z", true],
    ["HTTP://a.example.com", true],
    ["http://192.168.0.1:8080/", true],
    ["javascript:alert(1)", false],
    ["data:text/html,<b>x</b>", false],
    ["ftp://a.example.com/", false],
    ["a.example.com", false],
    ["/index.php", false],
    ["", false],
  ])("TC-VAL-013〜015 %s → %s", (url, expected) => {
    expect(isValidHttpUrl(url)).toBe(expected);
  });
});

describe("validateSharedLink", () => {
  it("妥当な入力はエラーなし", () => {
    expect(validate()).toEqual({});
  });

  it("TC-VAL-001 TC-VAL-002 TC-VAL-004 タイトルは必須で 100 文字まで（空白だけは未入力）", () => {
    expect(validate({ title: "  " }).title).toBe("LBL_ERROR_TITLE_REQUIRED");
    expect(validate({ title: "あ".repeat(100) }).title).toBeUndefined();
    expect(validate({ title: "あ".repeat(101) }).title).toBe(
      "LBL_ERROR_TITLE_TOO_LONG",
    );
  });

  it("TC-VAL-003 文字数はサロゲートペアも 1 文字として数える（サーバーの mb_strlen と同じ）", () => {
    expect(validate({ title: "𠮷".repeat(100) }).title).toBeUndefined();
  });

  it("TC-VAL-010〜014 URL は必須・2000 文字まで・http/https のみ", () => {
    expect(validate({ url: "" }).url).toBe("LBL_ERROR_URL_REQUIRED");
    const base = "https://a.example.com/";
    const max = base + "a".repeat(2000 - base.length);
    expect(validate({ url: max }).url).toBeUndefined();
    expect(validate({ url: max + "a" }).url).toBe("LBL_ERROR_URL_TOO_LONG");
    expect(validate({ url: "javascript:alert(1)" }).url).toBe(
      "LBL_ERROR_URL_INVALID",
    );
  });

  it("TC-VAL-016 説明は 500 文字まで", () => {
    expect(validate({ description: "あ".repeat(500) })).toEqual({});
    expect(validate({ description: "あ".repeat(501) }).description).toBe(
      "LBL_ERROR_DESCRIPTION_TOO_LONG",
    );
  });

  it.each([
    [199, false],
    [200, true],
    [4000, true],
    [4001, false],
    [null, false],
    [300.5, false],
    [Number.NaN, false],
  ])("TC-VAL-021〜024 別ウィンドウの幅・高さ %s → 妥当: %s", (size, ok) => {
    const errors = validate({
      open_mode: "window",
      window_width: size,
      window_height: size,
    });
    if (ok) {
      expect(errors).toEqual({});
    } else {
      expect(errors.window_width).toBe("LBL_ERROR_WINDOW_SIZE");
      expect(errors.window_height).toBe("LBL_ERROR_WINDOW_SIZE");
    }
  });

  it("TC-VAL-020 別タブならサイズは見ない", () => {
    expect(
      validate({ open_mode: "tab", window_width: 1, window_height: null }),
    ).toEqual({});
  });

  it("TC-VAL-039 パラメータは 20 件まで", () => {
    const param = { name: "a", value_type: "fixed" as const, value: "" };
    expect(validate({ params: Array(20).fill(param) })).toEqual({});
    expect(validate({ params: Array(21).fill(param) }).params).toBe(
      "LBL_ERROR_TOO_MANY_PARAMS",
    );
  });

  it("TC-VAL-030〜037 パラメータの名前・固定値・ユーザー情報の項目を行ごとに検証する", () => {
    const errors = validate({
      params: [
        { name: " ", value_type: "fixed", value: "" },
        {
          name: "a".repeat(101),
          value_type: "fixed",
          value: "あ".repeat(1001),
        },
        { name: "c", value_type: "user", value: "" },
        { name: "d", value_type: "user", value: "user_password" },
        { name: "e", value_type: "user", value: "email1" },
        { name: "f", value_type: "fixed", value: "あ".repeat(1000) },
      ],
    });

    expect(errors).toEqual({
      "params.0.name": "LBL_ERROR_PARAM_NAME_REQUIRED",
      "params.1.name": "LBL_ERROR_PARAM_NAME_TOO_LONG",
      "params.1.value": "LBL_ERROR_PARAM_VALUE_TOO_LONG",
      "params.2.value": "LBL_ERROR_USER_FIELD_REQUIRED",
      "params.3.value": "LBL_ERROR_USER_FIELD_INVALID",
    });
  });

  it("TC-VAL-040 TC-VAL-041 指定した対象のみなら対象が 1 件以上必要。全員なら不要", () => {
    expect(validate({ visibility: "targets", targets: [] }).targets).toBe(
      "LBL_ERROR_TARGETS_REQUIRED",
    );
    expect(validate({ visibility: "all", targets: [] })).toEqual({});
    expect(
      validate({
        visibility: "targets",
        targets: [{ type: "Users", id: "1" }],
      }),
    ).toEqual({});
  });
});

describe("validateWidgetTitle", () => {
  it.each([
    ["TC-TTL-001 空", "", "LBL_ERROR_WIDGET_TITLE_REQUIRED"],
    ["TC-TTL-001 空白だけ", "   ", "LBL_ERROR_WIDGET_TITLE_REQUIRED"],
    ["TC-TTL-002 30 文字", "あ".repeat(30), null],
    ["TC-TTL-003 前後の空白は数えない", ` ${"あ".repeat(30)} `, null],
    ["TC-TTL-004 31 文字", "あ".repeat(31), "LBL_ERROR_WIDGET_TITLE_TOO_LONG"],
    ["TC-TTL-005 <", "社内<システム", "LBL_ERROR_WIDGET_TITLE_CHARS"],
    ["TC-TTL-005 &", "R&D", "LBL_ERROR_WIDGET_TITLE_CHARS"],
    ['TC-TTL-005 "', 'a"b', "LBL_ERROR_WIDGET_TITLE_CHARS"],
    ["TC-TTL-005 '", "a'b", "LBL_ERROR_WIDGET_TITLE_CHARS"],
    ["TC-TTL-005 >", "a>b", "LBL_ERROR_WIDGET_TITLE_CHARS"],
    ["TC-TTL-006 記号でも使えるもの", "社内（システム）・リンク集!", null],
  ])("%s", (_, title, expected) => {
    expect(validateWidgetTitle(title, LIMITS)).toBe(expected);
  });
});
