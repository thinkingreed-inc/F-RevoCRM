import { describe, expect, it } from "vitest";
import {
  EMPTY_CRITERIA,
  countFieldUpdates,
  filterHistory,
  hasActiveFilter,
} from "../utils/filterHistory";
import { buildFieldTrack } from "../utils/fieldTrack";
import { diffText } from "../utils/textDiff";
import {
  formatFullDateTime,
  formatMonthDay,
  formatRelative,
  formatTime,
  groupByMonth,
  heldDays,
  sortByLayoutOrder,
} from "../utils/historyDate";
import { formatDelta } from "../utils/formatChange";
import { RECORD_HISTORY_DEFAULTS } from "../translations";
import { historyFixture as data } from "./fixtures";

const run = (overrides: Partial<typeof EMPTY_CRITERIA>) =>
  filterHistory(data.events, data.fields, data.users, {
    ...EMPTY_CRITERIA,
    ...overrides,
  });

const t = (key: string, ...args: (string | number)[]) => {
  let i = 0;
  return (RECORD_HISTORY_DEFAULTS[key] ?? key).replace(/%s/g, () =>
    String(args[i++]),
  );
};

describe("filterHistory", () => {
  it("既定ではシステム項目を除き、システム項目だけの変更も残さない", () => {
    const result = run({});
    expect(result).toHaveLength(data.events.length);
    const latest = result.find((e) => e.id === 8)!;
    expect(latest.visibleChanges.map((c) => c.field)).toEqual(["phone"]);
  });

  it("システム項目トグルで最終更新者も表示する", () => {
    const latest = run({ showSystemFields: true }).find((e) => e.id === 8)!;
    expect(latest.visibleChanges.map((c) => c.field)).toEqual([
      "phone",
      "modifiedby",
    ]);
  });

  it("項目で絞ると、その項目を含むイベントだけになり関連付けは出ない", () => {
    const result = run({ fields: ["phone"] });
    expect(result.map((e) => e.id)).toEqual([8, 6, 2]);
    expect(
      result.every((e) => e.visibleChanges.every((c) => c.field === "phone")),
    ).toBe(true);
  });

  it("システム項目を明示的に選んだときはトグル OFF でも表示する", () => {
    expect(run({ fields: ["modifiedby"] }).map((e) => e.id)).toEqual([8, 2]);
  });

  it("変更者・種別で絞り込める", () => {
    expect(run({ userIds: [6] }).map((e) => e.id)).toEqual([7, 4]);
    expect(run({ types: ["link", "unlink"] }).map((e) => e.id)).toEqual([101]);
  });

  it("キーワードは項目名・表示値・関連先名に部分一致する（大文字小文字を区別しない）", () => {
    expect(run({ keyword: "3333" }).map((e) => e.id)).toEqual([8]);
    expect(run({ keyword: "格付け" }).map((e) => e.id)).toEqual([7, 2]);
    expect(run({ keyword: "保守契約" }).map((e) => e.id)).toEqual([101]);
    expect(run({ keyword: "MIRAI" }).map((e) => e.id)).toEqual([7]);
  });

  it("hasActiveFilter はシステム項目トグルを条件に含めない", () => {
    expect(hasActiveFilter({ ...EMPTY_CRITERIA, showSystemFields: true })).toBe(
      false,
    );
    expect(hasActiveFilter({ ...EMPTY_CRITERIA, keyword: " a " })).toBe(true);
  });
});

describe("countFieldUpdates", () => {
  it("更新の回数だけを数え、作成時の値は数えない", () => {
    const counts = countFieldUpdates(data.events);
    expect(counts.phone).toBe(2);
    expect(counts.rating).toBe(1);
  });
});

describe("buildFieldTrack", () => {
  it("新しい順に値の変遷を返し、until に次の変更日時を入れる", () => {
    const track = buildFieldTrack(data.events, "phone");
    expect(track.map((e) => e.value?.raw)).toEqual([
      "03-3333-4444",
      "03-1111-2222",
      "03-9876-5432",
    ]);
    expect(track[0].until).toBeNull();
    expect(track[1].until).toBe("2026-11-01T09:30:00");
    expect(track[2].isInitial).toBe(true);
  });

  it("変更者で絞っても until は全体の変遷から求める", () => {
    const track = buildFieldTrack(data.events, "phone", [1]);
    expect(track).toHaveLength(1);
    expect(track[0].until).toBe("2026-10-15T11:48:00");
  });
});

describe("diffText", () => {
  it("文字単位で差分を取り、共通部分はそのまま残す", () => {
    expect(diffText("担当窓口は総務部。", "担当窓口は購買部。")).toEqual([
      { op: "equal", text: "担当窓口は" },
      { op: "delete", text: "総務" },
      { op: "insert", text: "購買" },
      { op: "equal", text: "部。" },
    ]);
  });

  it("追記だけなら insert のみになる", () => {
    expect(diffText("abc", "abc\ndef")).toEqual([
      { op: "equal", text: "abc" },
      { op: "insert", text: "\ndef" },
    ]);
  });

  it("サロゲートペアを壊さない", () => {
    const segments = diffText("𠮷野家", "𠮷田家");
    expect(segments.map((s) => s.text).join("")).toContain("𠮷");
    expect(segments[0]).toEqual({ op: "equal", text: "𠮷" });
  });

  it("同じ文字列は equal 1 つ、空同士は空配列", () => {
    expect(diffText("x", "x")).toEqual([{ op: "equal", text: "x" }]);
    expect(diffText("", "")).toEqual([]);
  });
});

describe("historyDate", () => {
  const now = "2026-11-02T16:00:00";
  it("相対時間を返す", () => {
    expect(formatRelative("2026-11-02T15:59:40", now, t)).toBe("たった今");
    expect(formatRelative("2026-11-02T15:10:00", now, t)).toBe("50分前");
    expect(formatRelative("2026-11-02T09:00:00", now, t)).toBe("7時間前");
    expect(formatRelative("2026-11-01T23:00:00", now, t)).toBe("昨日");
    expect(formatRelative("2026-10-15T11:48:00", now, t)).toBe("18日前");
    expect(formatRelative("2026-09-01T10:12:00", now, t)).toBe("2か月前");
    expect(formatRelative("2025-06-01T10:12:00", now, t)).toBe("1年前");
  });

  it("保持日数を求める", () => {
    expect(heldDays("2026-10-15T11:48:00", "2026-11-01T09:30:00", now)).toBe(
      17,
    );
    expect(heldDays("2026-11-01T09:30:00", null, now)).toBe(1);
  });

  it("並びを保ったまま月ごとにまとめる", () => {
    const groups = groupByMonth(data.events);
    expect(groups.map((g) => [g.key, g.items.length])).toEqual([
      ["2026-11", 1],
      ["2026-10", 3],
      ["2026-09", 2],
    ]);
  });
});

describe("formatDelta", () => {
  it("API が整形した差分に符号と増減率を付ける", () => {
    expect(
      formatDelta({ sign: "+", display: "220.000.000 ¥", percent: 44 }, t),
    ).toBe("+220.000.000 ¥（+44%）");
    expect(formatDelta({ sign: "-", display: "30", percent: -20 }, t)).toBe(
      "−30（−20%）",
    );
    expect(formatDelta({ sign: "+", display: "5", percent: null }, t)).toBe(
      "+5",
    );
  });
});

describe("ユーザー設定の日付・時刻形式", () => {
  const value = "2026-11-01T15:05:00";
  it.each([
    ["yyyy-mm-dd", "11/01", "2026-11-01"],
    ["dd-mm-yyyy", "01/11", "01-11-2026"],
    ["mm-dd-yyyy", "11/01", "11-01-2026"],
    ["dd.mm.yyyy", "01/11", "01.11.2026"],
    ["dd/mm/yyyy", "01/11", "01/11/2026"],
  ])("%s", (dateFormat, monthDay, date) => {
    const fmt = { dateFormat, hourFormat: "24" as const };
    expect(formatMonthDay(value, fmt)).toBe(monthDay);
    expect(formatFullDateTime(value, t, fmt)).toBe(`${date}（日）15:05`);
  });

  it("12 時間制は vtiger と同じく h:mm AM/PM", () => {
    const fmt = { dateFormat: "yyyy-mm-dd", hourFormat: "12" as const };
    expect(formatTime(value, fmt)).toBe("3:05 PM");
    expect(formatTime("2026-11-01T00:30:00", fmt)).toBe("12:30 AM");
  });
});

describe("sortByLayoutOrder", () => {
  it("詳細画面の並び順に並べる", () => {
    const sorted = sortByLayoutOrder(Object.values(data.fields));
    expect(sorted.map((f) => f.name)).toEqual([
      "website",
      "phone",
      "rating",
      "annual_revenue",
      "modifiedby",
      "description",
    ]);
  });
});
