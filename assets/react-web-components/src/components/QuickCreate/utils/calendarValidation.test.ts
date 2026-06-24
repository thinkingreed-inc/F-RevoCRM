import { describe, it, expect } from 'vitest';
import { isFutureEventHeldInvalid } from './calendarValidation';

describe('isFutureEventHeldInvalid', () => {
  const now = new Date('2026-06-24T12:00:00');

  it('未来日付 + Held で NG (true)', () => {
    expect(isFutureEventHeldInvalid('Held', '2026-07-15T10:00', now)).toBe(true);
  });

  it('未来日付 + Held (date のみ、時刻なし) で NG (true)', () => {
    expect(isFutureEventHeldInvalid('Held', '2026-07-15', now)).toBe(true);
  });

  it('過去日付 + Held は OK (false)', () => {
    expect(isFutureEventHeldInvalid('Held', '2026-06-01T10:00', now)).toBe(false);
  });

  it('現在以前 + Held は OK (false)', () => {
    expect(isFutureEventHeldInvalid('Held', '2026-06-24T11:00:00', now)).toBe(false);
  });

  it('未来日付 + Planned は OK (false)', () => {
    expect(isFutureEventHeldInvalid('Planned', '2026-07-15T10:00', now)).toBe(false);
  });

  it('未来日付 + Not Held は OK (false)', () => {
    expect(isFutureEventHeldInvalid('Not Held', '2026-07-15T10:00', now)).toBe(false);
  });

  it('eventstatus が undefined は OK (false)', () => {
    expect(isFutureEventHeldInvalid(undefined, '2026-07-15T10:00', now)).toBe(false);
  });

  it('date_start が undefined は OK (false)', () => {
    expect(isFutureEventHeldInvalid('Held', undefined, now)).toBe(false);
  });

  it('date_start が空文字は OK (false)', () => {
    expect(isFutureEventHeldInvalid('Held', '', now)).toBe(false);
  });

  it('date_start が不正な文字列は OK (false) — 必須チェック側で弾く', () => {
    expect(isFutureEventHeldInvalid('Held', 'not-a-date', now)).toBe(false);
  });

  describe('ローカル時刻境界 (YYYY-MM-DD のみ入力時の UTC 誤解釈防止)', () => {
    it('当日同一日付 (時刻なし) + 現在 12:00 は OK (false) — 今日0:00は過去', () => {
      // 旧実装では new Date("2026-06-24") = UTC 0:00 = JST 9:00 となり、
      // JST 8:00 時点では「未来」と誤判定された。ローカル時刻として解釈する必要がある。
      const noonNow = new Date('2026-06-24T12:00:00');
      expect(isFutureEventHeldInvalid('Held', '2026-06-24', noonNow)).toBe(false);
    });

    it('当日同一日付 (時刻なし) + 現在 0:00 ちょうど は OK (false) — 同時刻は未来ではない', () => {
      const midnight = new Date('2026-06-24T00:00:00');
      expect(isFutureEventHeldInvalid('Held', '2026-06-24', midnight)).toBe(false);
    });

    it('翌日 (時刻なし) + 現在 23:59 は NG (true)', () => {
      const lateToday = new Date('2026-06-24T23:59:59');
      expect(isFutureEventHeldInvalid('Held', '2026-06-25', lateToday)).toBe(true);
    });
  });

  it('eventstatus が小文字 held はマッチしない (false)', () => {
    expect(isFutureEventHeldInvalid('held', '2026-07-15T10:00', now)).toBe(false);
  });

  it('eventstatus が null は OK (false)', () => {
    expect(isFutureEventHeldInvalid(null, '2026-07-15T10:00', now)).toBe(false);
  });
});
