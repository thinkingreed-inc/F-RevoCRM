import { describe, it, expect } from 'vitest';
import {
  normalizeColor,
  normalizeLength,
  normalizeSpacing,
  normalizeBorderStyle,
  normalizeTextAlign,
  normalizeVerticalAlign,
  normalizeBorderShorthand,
} from '../../extensions/utils/normalize';

describe('normalizeColor セキュリティテスト', () => {
  it('正常な#RRGGBB値を保持する', () => {
    expect(normalizeColor('#ff0000')).toBe('#ff0000');
  });

  it('正常なrgb()値を受け入れる', () => {
    // normalizeColorはrgb()形式をそのまま保持する
    expect(normalizeColor('rgb(255, 0, 0)')).toBe('rgb(255, 0, 0)');
  });

  it('正常なCSS名前付き色を正規化する', () => {
    expect(normalizeColor('red')).toBe('#ff0000');
  });

  it('expression()を含む値を拒否する', () => {
    expect(normalizeColor('expression(alert(1))')).toBeNull();
  });

  it('javascript:を含む値を拒否する', () => {
    expect(normalizeColor('javascript:alert(1)')).toBeNull();
  });

  it('url()を含む値を拒否する', () => {
    expect(normalizeColor('url(https://evil.com)')).toBeNull();
  });

  it('空文字を拒否する', () => {
    expect(normalizeColor('')).toBeNull();
  });

  it('制御文字を含む値を拒否する', () => {
    expect(normalizeColor('\x00red')).toBeNull();
  });
});

describe('normalizeLength セキュリティテスト', () => {
  it('正常なpx値を保持する', () => {
    expect(normalizeLength('16px')).toBe('16px');
  });

  it('maxPx上限を超える値をクランプする', () => {
    const result = normalizeLength('3000px', 2000);
    if (result !== null) {
      const num = parseInt(result, 10);
      expect(num).toBeLessThanOrEqual(2000);
    }
  });

  it('expression()を含む値を拒否する', () => {
    expect(normalizeLength('expression(alert(1))')).toBeNull();
  });

  it('空文字を拒否する', () => {
    expect(normalizeLength('')).toBeNull();
  });
});

describe('normalizeBorderStyle セキュリティテスト', () => {
  it('有効なborder-styleを受け入れる', () => {
    expect(normalizeBorderStyle('solid')).toBe('solid');
    expect(normalizeBorderStyle('dashed')).toBe('dashed');
  });

  it('無効な値を拒否する', () => {
    expect(normalizeBorderStyle('expression(alert(1))')).toBeNull();
    expect(normalizeBorderStyle('<script>')).toBeNull();
  });
});

describe('normalizeTextAlign セキュリティテスト', () => {
  it('有効なtext-alignを受け入れる', () => {
    expect(normalizeTextAlign('left')).toBe('left');
    expect(normalizeTextAlign('center')).toBe('center');
  });

  it('無効な値を拒否する', () => {
    expect(normalizeTextAlign('expression(alert(1))')).toBeNull();
    expect(normalizeTextAlign('; background:url(evil)')).toBeNull();
  });
});

describe('normalizeVerticalAlign セキュリティテスト', () => {
  it('有効なvertical-alignを受け入れる', () => {
    expect(normalizeVerticalAlign('top')).toBe('top');
    expect(normalizeVerticalAlign('middle')).toBe('middle');
  });

  it('無効な値を拒否する', () => {
    expect(normalizeVerticalAlign('javascript:alert(1)')).toBeNull();
  });
});

describe('normalizeSpacing セキュリティテスト', () => {
  it('有効なspacing値を受け入れる', () => {
    const result = normalizeSpacing('10px');
    expect(result).not.toBeNull();
  });

  it('不正なCSSインジェクションを拒否する', () => {
    expect(normalizeSpacing('10px; background:url(evil)')).toBeNull();
  });
});

describe('normalizeBorderShorthand セキュリティテスト', () => {
  it('有効なborder shorthandを受け入れる', () => {
    const result = normalizeBorderShorthand('1px solid #000000');
    expect(result).not.toBeNull();
  });

  it('不正な値を拒否する', () => {
    expect(normalizeBorderShorthand('expression(alert(1))')).toBeNull();
  });
});
