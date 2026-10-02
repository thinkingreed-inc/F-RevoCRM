import * as fs from "fs";
import * as os from "os";
import * as path from "path";

/**
 * user_privileges_<id>.php の再生成を直列化するプロセス間ロック。
 *
 * F-RevoCRM はロール/プロファイル/ユーザーを更新すると
 * modules/Users/CreateUserPrivilegeFile.php が
 *   fopen('user_privileges_<id>.php', 'w+')  // ここで即 0 バイトに truncate
 *   ... DB 問い合わせ ...
 *   fputs($handle, $newbuf)                  // ここでようやく書き戻し
 * という順で動く。truncate から書き戻しまでの間にそのファイルを
 * require した別リクエストは $user_info を得られず、
 * Users_Record_Model の valueMap が TrackableObject のまま
 * Vtiger_Base_Model::has() に渡って Fatal error になり画面が丸ごと落ちる。
 *
 * ログイン(modules/Users/Authenticate.php)と、ロール/プロファイル/ユーザーの
 * 更新がいずれも admin(id=1) のファイルを再生成するため、
 * それらが重ならないようにここで排他する。
 *
 * ディレクトリ作成はファイルシステム上アトミックなので、
 * 追加依存なしでプロセス間ロックとして使える。
 */
const LOCK_DIR = path.join(os.tmpdir(), "frevo-e2e-user-privileges.lock");

/** 異常終了で取り残されたロックを回収するまでの猶予 */
const STALE_MS = 120_000;

/** ロック取得を諦めるまでの上限 */
const ACQUIRE_TIMEOUT_MS = 300_000;

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

async function acquire(): Promise<void> {
  const startedAt = Date.now();
  for (;;) {
    try {
      fs.mkdirSync(LOCK_DIR);
      return;
    } catch {
      // 取り残されたロックは一定時間経過で回収する
      try {
        const age = Date.now() - fs.statSync(LOCK_DIR).mtimeMs;
        if (age > STALE_MS) {
          fs.rmSync(LOCK_DIR, { recursive: true, force: true });
          continue;
        }
      } catch {
        // 待っている間に解放された場合は、そのまま取り直す
      }
      if (Date.now() - startedAt > ACQUIRE_TIMEOUT_MS) {
        throw new Error(
          `user_privileges のロックを取得できませんでした: ${LOCK_DIR}`
        );
      }
      await sleep(200);
    }
  }
}

function release(): void {
  fs.rmSync(LOCK_DIR, { recursive: true, force: true });
}

/** 権限ファイルを再生成しうる処理をロックで包んで実行する */
export async function withUserPrivilegesLock<T>(
  fn: () => Promise<T>
): Promise<T> {
  await acquire();
  try {
    return await fn();
  } finally {
    release();
  }
}
