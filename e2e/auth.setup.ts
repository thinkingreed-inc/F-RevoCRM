import { test as setup } from '@playwright/test';
import { acquireApiSession } from './model/session';
import { BASE_URL, authFile } from './utils/util';

/**
 * 依存関係の起点。ここで「一度だけ」ブラウザ認証(storageState)と
 * Webservice API セッション取得を行う。以降の seed / 各 spec は保存済みの
 * sessionName / userId を使い回すため、getchallenge の競合が起きない。
 */
setup('authenticate & acquire api session', async ({ page }) => {
  // Perform authentication steps. Replace these actions with your own.
  // ログイン画面は外部 CDN 画像(f-revocrm.jp)を参照するため、既定の 'load' 待ちだと
  // 外部到達不可(オフライン)な環境で load が発火せずタイムアウトする。DOM 構築完了で
  // 十分なので domcontentloaded を待つ(フォーム要素はサーバ描画済み)。
  await page.goto(BASE_URL, { waitUntil: "domcontentloaded" });
  // await page.getByRole('button', { name: 'Login with SimpleSAMLPHP' }).click();

  await page.fill('id=username', process.env.E2E_USER_NAME || 'admin');
  await page.fill('id=password', process.env.E2E_USER_PASSWORD || 'Admin1234/');
  await page.getByRole('button', { name: 'ログイン' }).click();

  await page.waitForURL(`${BASE_URL}index.php**`);

  await page.context().storageState({ path: authFile });

  // API セッションはここで一度だけ取得して sessionName / userId を保存する。
  await acquireApiSession();
});
