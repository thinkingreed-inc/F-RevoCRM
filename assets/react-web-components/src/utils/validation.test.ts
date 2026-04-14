/**
 * バリデーションユーティリティのテスト
 */

import { describe, it, expect } from 'vitest';
import {
  validateUrl,
  validateEmail,
  validateInteger,
  validateDouble,
  validatePositive,
  validatePercentage,
} from './validation';

describe('validateUrl', () => {
  it('空文字は有効', () => {
    expect(validateUrl('')).toEqual({ valid: true });
    expect(validateUrl('   ')).toEqual({ valid: true });
  });

  it('有効なURLを許可', () => {
    expect(validateUrl('https://example.com')).toEqual({ valid: true });
    expect(validateUrl('http://example.com')).toEqual({ valid: true });
    expect(validateUrl('example.com')).toEqual({ valid: true });
    expect(validateUrl('www.example.com')).toEqual({ valid: true });
    expect(validateUrl('sub.example.co.jp')).toEqual({ valid: true });
    expect(validateUrl('https://example.com:8080/path')).toEqual({ valid: true });
    expect(validateUrl('https://example.com/path?query=1')).toEqual({ valid: true });
  });

  it('無効なURLを拒否', () => {
    const result1 = validateUrl('invalidcom');
    expect(result1.valid).toBe(false);
    expect(result1.message).toBe('JS_INVALID_URL');

    const result2 = validateUrl('just-text');
    expect(result2.valid).toBe(false);

    const result3 = validateUrl('notadomain');
    expect(result3.valid).toBe(false);
  });
});

describe('validateEmail', () => {
  it('空文字は有効', () => {
    expect(validateEmail('')).toEqual({ valid: true });
    expect(validateEmail('   ')).toEqual({ valid: true });
  });

  it('有効なメールアドレスを許可', () => {
    expect(validateEmail('test@example.com')).toEqual({ valid: true });
    expect(validateEmail('user.name@example.co.jp')).toEqual({ valid: true });
    expect(validateEmail('user+tag@example.com')).toEqual({ valid: true });
  });

  it('無効なメールアドレスを拒否', () => {
    const result1 = validateEmail('invalid');
    expect(result1.valid).toBe(false);
    expect(result1.message).toBe('JS_PLEASE_ENTER_VALID_EMAIL_ADDRESS');

    const result2 = validateEmail('missing@domain');
    expect(result2.valid).toBe(false);

    const result3 = validateEmail('@nodomain.com');
    expect(result3.valid).toBe(false);
  });
});

describe('validateInteger', () => {
  it('空文字は有効', () => {
    expect(validateInteger('')).toEqual({ valid: true });
  });

  it('有効な整数を許可', () => {
    expect(validateInteger('123')).toEqual({ valid: true });
    expect(validateInteger('-456')).toEqual({ valid: true });
    expect(validateInteger('+789')).toEqual({ valid: true });
    expect(validateInteger('0')).toEqual({ valid: true });
  });

  it('小数も許容（整数部分として扱う）', () => {
    expect(validateInteger('123.45')).toEqual({ valid: true });
  });

  it('無効な値を拒否', () => {
    const result1 = validateInteger('abc');
    expect(result1.valid).toBe(false);
    expect(result1.message).toBe('JS_PLEASE_ENTER_INTEGER_VALUE');

    const result2 = validateInteger('12abc');
    expect(result2.valid).toBe(false);
  });
});

describe('validateDouble', () => {
  it('空文字は有効', () => {
    expect(validateDouble('')).toEqual({ valid: true });
  });

  it('有効な数値を許可', () => {
    expect(validateDouble('123')).toEqual({ valid: true });
    expect(validateDouble('123.45')).toEqual({ valid: true });
    expect(validateDouble('-123.45')).toEqual({ valid: true });
    expect(validateDouble('1,234.56')).toEqual({ valid: true });
  });

  it('無効な値を拒否', () => {
    const result1 = validateDouble('abc');
    expect(result1.valid).toBe(false);
    expect(result1.message).toBe('JS_PLEASE_ENTER_VALID_VALUE');

    const result2 = validateDouble('12abc34');
    expect(result2.valid).toBe(false);
  });

  it('カスタムセパレータを処理', () => {
    // ヨーロッパ形式（桁区切り: . / 小数点: ,）
    expect(validateDouble('1.234,56', '.', ',')).toEqual({ valid: true });
  });
});

describe('validatePositive', () => {
  it('空文字は有効', () => {
    expect(validatePositive('')).toEqual({ valid: true });
  });

  it('正の数を許可', () => {
    expect(validatePositive('123')).toEqual({ valid: true });
    expect(validatePositive('0')).toEqual({ valid: true });
    expect(validatePositive('0.5')).toEqual({ valid: true });
  });

  it('負の数を拒否', () => {
    const result = validatePositive('-123');
    expect(result.valid).toBe(false);
    expect(result.message).toBe('JS_ACCEPT_POSITIVE_NUMBER');
  });
});

describe('validatePercentage', () => {
  it('空文字は有効', () => {
    expect(validatePercentage('')).toEqual({ valid: true });
  });

  it('有効なパーセンテージを許可', () => {
    expect(validatePercentage('50')).toEqual({ valid: true });
    expect(validatePercentage('100')).toEqual({ valid: true });
    expect(validatePercentage('33.33')).toEqual({ valid: true });
  });

  it('無効な値を拒否', () => {
    const result = validatePercentage('abc');
    expect(result.valid).toBe(false);
    expect(result.message).toBe('JS_PLEASE_ENTER_VALID_VALUE');
  });
});
