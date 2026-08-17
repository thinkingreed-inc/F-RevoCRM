import { describe, it, expect } from "vitest";
import {
  isDateRangeInvalid,
  isFutureEventHeldInvalid,
} from "./calendarValidation";

describe("isFutureEventHeldInvalid", () => {
  const now = new Date("2026-06-24T12:00:00");

  it("未来日付 + Held で NG (true)", () => {
    expect(isFutureEventHeldInvalid("Held", "2026-07-15T10:00", now)).toBe(
      true,
    );
  });

  it("未来日付 + Held (date のみ、時刻なし) で NG (true)", () => {
    expect(isFutureEventHeldInvalid("Held", "2026-07-15", now)).toBe(true);
  });

  it("過去日付 + Held は OK (false)", () => {
    expect(isFutureEventHeldInvalid("Held", "2026-06-01T10:00", now)).toBe(
      false,
    );
  });

  it("現在以前 + Held は OK (false)", () => {
    expect(isFutureEventHeldInvalid("Held", "2026-06-24T11:00:00", now)).toBe(
      false,
    );
  });

  it("未来日付 + Planned は OK (false)", () => {
    expect(isFutureEventHeldInvalid("Planned", "2026-07-15T10:00", now)).toBe(
      false,
    );
  });

  it("未来日付 + Not Held は OK (false)", () => {
    expect(isFutureEventHeldInvalid("Not Held", "2026-07-15T10:00", now)).toBe(
      false,
    );
  });

  it("eventstatus が undefined は OK (false)", () => {
    expect(isFutureEventHeldInvalid(undefined, "2026-07-15T10:00", now)).toBe(
      false,
    );
  });

  it("date_start が undefined は OK (false)", () => {
    expect(isFutureEventHeldInvalid("Held", undefined, now)).toBe(false);
  });

  it("date_start が空文字は OK (false)", () => {
    expect(isFutureEventHeldInvalid("Held", "", now)).toBe(false);
  });

  it("date_start が不正な文字列は OK (false) — 必須チェック側で弾く", () => {
    expect(isFutureEventHeldInvalid("Held", "not-a-date", now)).toBe(false);
  });

  describe("ローカル時刻境界 (YYYY-MM-DD のみ入力時の UTC 誤解釈防止)", () => {
    it("当日同一日付 (時刻なし) + 現在 12:00 は OK (false) — 今日0:00は過去", () => {
      // 旧実装では new Date("2026-06-24") = UTC 0:00 = JST 9:00 となり、
      // JST 8:00 時点では「未来」と誤判定された。ローカル時刻として解釈する必要がある。
      const noonNow = new Date("2026-06-24T12:00:00");
      expect(isFutureEventHeldInvalid("Held", "2026-06-24", noonNow)).toBe(
        false,
      );
    });

    it("当日同一日付 (時刻なし) + 現在 0:00 ちょうど は OK (false) — 同時刻は未来ではない", () => {
      const midnight = new Date("2026-06-24T00:00:00");
      expect(isFutureEventHeldInvalid("Held", "2026-06-24", midnight)).toBe(
        false,
      );
    });

    it("翌日 (時刻なし) + 現在 23:59 は NG (true)", () => {
      const lateToday = new Date("2026-06-24T23:59:59");
      expect(isFutureEventHeldInvalid("Held", "2026-06-25", lateToday)).toBe(
        true,
      );
    });
  });

  it("eventstatus が小文字 held はマッチしない (false)", () => {
    expect(isFutureEventHeldInvalid("held", "2026-07-15T10:00", now)).toBe(
      false,
    );
  });

  it("eventstatus が null は OK (false)", () => {
    expect(isFutureEventHeldInvalid(null, "2026-07-15T10:00", now)).toBe(false);
  });
});

describe("isDateRangeInvalid", () => {
  describe("ToDo (due_date が日付のみ)", () => {
    it("同一日 + 開始 14:30 は OK (false)", () => {
      // 旧実装では new Date("2026-08-17") が UTC 0:00 (= JST 9:00) と解釈され、
      // 開始時刻が 9:00 以降のとき誤って NG 判定されていた
      expect(isDateRangeInvalid("2026-08-17T14:30", "2026-08-17")).toBe(false);
    });

    it("同一日 + 開始 08:30 は OK (false)", () => {
      expect(isDateRangeInvalid("2026-08-17T08:30", "2026-08-17")).toBe(false);
    });

    it("同一日 + 開始 23:59 は OK (false) — 終了日はその日の終わりまで", () => {
      expect(isDateRangeInvalid("2026-08-17T23:59", "2026-08-17")).toBe(false);
    });

    it("開始日が終了日の翌日は NG (true)", () => {
      expect(isDateRangeInvalid("2026-08-18T09:00", "2026-08-17")).toBe(true);
    });

    it("開始日が終了日より前は OK (false)", () => {
      expect(isDateRangeInvalid("2026-08-16T09:00", "2026-08-17")).toBe(false);
    });
  });

  describe("Events (開始・終了とも日時)", () => {
    it("終了が開始より後は OK (false)", () => {
      expect(isDateRangeInvalid("2026-08-17T14:30", "2026-08-17T15:00")).toBe(
        false,
      );
    });

    it("終了が開始より前は NG (true)", () => {
      expect(isDateRangeInvalid("2026-08-17T15:00", "2026-08-17T14:30")).toBe(
        true,
      );
    });

    it("開始と終了が同時刻は OK (false)", () => {
      expect(isDateRangeInvalid("2026-08-17T14:30", "2026-08-17T14:30")).toBe(
        false,
      );
    });

    it("日跨ぎ (翌日終了) は OK (false)", () => {
      expect(isDateRangeInvalid("2026-08-17T23:30", "2026-08-18T00:30")).toBe(
        false,
      );
    });
  });

  describe("終日 (開始・終了とも日付のみ)", () => {
    it("同一日は OK (false)", () => {
      expect(isDateRangeInvalid("2026-08-17", "2026-08-17")).toBe(false);
    });

    it("開始日が終了日より後は NG (true)", () => {
      expect(isDateRangeInvalid("2026-08-18", "2026-08-17")).toBe(true);
    });
  });

  describe("値が不足・不正な場合はチェックしない (false)", () => {
    it("date_start が空文字", () => {
      expect(isDateRangeInvalid("", "2026-08-17")).toBe(false);
    });

    it("due_date が空文字", () => {
      expect(isDateRangeInvalid("2026-08-17T14:30", "")).toBe(false);
    });

    it("date_start が undefined", () => {
      expect(isDateRangeInvalid(undefined, "2026-08-17")).toBe(false);
    });

    it("due_date が undefined", () => {
      expect(isDateRangeInvalid("2026-08-17T14:30", undefined)).toBe(false);
    });

    it("文字列以外 (数値)", () => {
      expect(isDateRangeInvalid(20260817, "2026-08-17")).toBe(false);
    });

    it("不正な日付文字列 — 必須チェック側で弾く", () => {
      expect(isDateRangeInvalid("not-a-date", "2026-08-17")).toBe(false);
      expect(isDateRangeInvalid("2026-08-17T14:30", "not-a-date")).toBe(false);
    });
  });
});
