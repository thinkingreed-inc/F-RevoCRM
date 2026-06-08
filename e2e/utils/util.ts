import { Page } from "@playwright/test";

/**
 * 指定したms秒待機する
 */
export async function waitSeconds(page: Page, ms: number) {
  await page.waitForTimeout(ms);
}

/**
 * url生成
 */
export function url(path: string) {
  return `http://localhost/${path}`;
}

/**
 * ハッシュ生成
 */
export function generateRandomString(length: number) {
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
      const randomIndex = Math.floor(Math.random() * characters.length);
      result += characters[randomIndex];
  }
  return result;
}

/**
 * 大文字・小文字英数をIntに変換する
 */
export function base62ToInt(input: string): number {
  const base62Chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  const base = base62Chars.length;
  const intMin = -2147483648;
  const intMax = 2147483647;
  let result = 0;
  const isNegative = input[0] === '-';
  const chars = isNegative ? input.slice(1) : input;

  for (let i = 0; i < chars.length; i++) {
    const char = chars[i];
    const value = base62Chars.indexOf(char);
    if (value === -1) {
      throw new Error(`Invalid character in input: ${char}`);
    }
    result = result * base + value;
  }

  // 正負の符号を適用
  result = isNegative ? -result : result;

  // Intの範囲に無理やり収める
  if (result < intMin || result > intMax) {
    result = ((result % (intMax - intMin + 1)) + (intMax - intMin + 1)) % (intMax - intMin + 1) + intMin;
  }

  return result;
}
