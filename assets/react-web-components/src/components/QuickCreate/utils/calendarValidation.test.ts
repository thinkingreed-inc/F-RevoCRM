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
});
