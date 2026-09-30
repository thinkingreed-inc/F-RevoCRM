/**
 * vtiger のグローバル `app` オブジェクトの型定義
 *
 * layouts/v7 の JS（app.js）が window 直下に定義しているもので、
 * WebComponent からサーバーAPIを呼ぶ際に利用する。
 */

/**
 * app.request.post が第1引数で返すエラー
 * 成功時は null が渡される
 */
export interface VtigerAppError {
  /** エラーメッセージ */
  message?: string;
  /** エラーコード */
  code?: string | number;
}

/**
 * app.request.post のコールバック
 * vtiger の実装は (error, data) の順で渡す
 */
export type VtigerAppPostCallback<T> = (
  error: VtigerAppError | null,
  data: T,
) => void;

/**
 * app.request.post が返す Deferred 相当のオブジェクト
 */
export interface VtigerAppPostResult<T> {
  then(callback: VtigerAppPostCallback<T>): void;
}

/**
 * vtiger のグローバル `app` オブジェクト
 * 必要になったメンバーのみ順次追加する
 */
export interface VtigerApp {
  request: {
    post<T>(options: { data: Record<string, string> }): VtigerAppPostResult<T>;
  };
}

declare global {
  /** vtiger が提供するグローバルオブジェクト */
  const app: VtigerApp;
}
