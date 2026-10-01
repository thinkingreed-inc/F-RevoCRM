/**
 * vtiger が画面側で定義しているグローバル値の型定義
 */

declare global {
  interface Window {
    /**
     * CSRF トークンのパラメータ名（既定は `__vtrftk`）
     * csrf-magic.js が定義する
     */
    csrfMagicName?: string;
    /**
     * CSRF トークンの値
     * csrf-magic.js は XMLHttpRequest だけを書き換えてトークンを自動付与するため、
     * fetch で POST する場合はこの値を自分で付ける必要がある
     */
    csrfMagicToken?: string;
  }
}

export {};
