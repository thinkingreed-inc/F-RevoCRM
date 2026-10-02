import { test as base, expect } from "./isolated";
import { withUserPrivilegesLock } from "./userPrivilegesLock";

/**
 * user_privileges_<id>.php を再生成する管理設定テスト用のフィクスチャ。
 *
 * ロール/プロファイル/グループ/ユーザーの更新は、所属ユーザー全員分の
 * 権限ファイルを作り直す(modules/Settings/Roles/models/Record.php,
 * modules/Settings/Profiles/models/Record.php ほか)。
 * 作り直しの最中はファイルが一時的に空になり、それを読んだ別リクエストが
 * Fatal error で白画面になるため、テスト 1 件ずつロックを取って直列化する。
 * 一般テストとの時間分離は playwright.config.ts の 'chrome-privileges'
 * プロジェクト(chrome の完了後に実行)が担う。
 *
 * 背景の詳細は fixtures/userPrivilegesLock.ts を参照。
 */
export const test = base.extend<{ userPrivilegesLock: void }>({
  userPrivilegesLock: [
    // workerStorageState を先に解決させる。ワーカーのログインも同じロックを取るため、
    // 先にロックを握ってしまうと自分のログインを待ち続けて止まる。
    async ({ workerStorageState }, use) => {
      void workerStorageState;
      await withUserPrivilegesLock(async () => {
        await use();
      });
    },
    { auto: true },
  ],
});

export { expect };
