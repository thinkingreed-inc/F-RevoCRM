import { login } from "../model/fetcher";
import { getStoredSessionName } from "../model/session";

/**
 * 検証・後始末用の Webservice API セッションを返す。
 *
 * **毎回 login してはいけない**: getchallenge → login はユーザ単位でチャレンジ
 * トークンが 1 つしか無いため、並行ワーカーが同時に login すると互いのトークンを
 * 打ち消し合って失敗する(`model/session.ts` の設計コメント参照)。workers=4 で
 * API を使う spec が増えるほど確率的に落ちる。
 *
 * そこで `auth.setup`(project 依存の起点)が一度だけ取得したセッションを使い回す。
 * 保存が無い場合(spec 単体を setup 抜きで走らせた場合など)だけ login し、
 * トークン競合に備えて間隔を空けて再試行する。
 */

/** login で取得したセッションのプロセス内キャッシュ(保存ファイルが無い場合用)。 */
let fallbackSession: string | null = null;

/** 保存済み sessionName を読む(未取得なら null)。 */
function readStored(): string | null {
  try {
    return getStoredSessionName();
  } catch {
    return null;
  }
}

/** チャレンジトークン競合に備えて間隔を空けて login を再試行する。 */
async function loginWithRetry(attempts = 3): Promise<string> {
  let lastError: unknown = null;
  for (let i = 0; i < attempts; i++) {
    try {
      const res = await login(
        process.env.E2E_USER_NAME || "",
        process.env.E2E_USER_ACCESSKEY || ""
      );
      if (res) return res.sessionName;
      lastError = new Error("API login returned empty response");
    } catch (e) {
      lastError = e;
    }
    // 競合相手とタイミングをずらす(固定待ちだと再衝突しやすい)
    await new Promise((r) => setTimeout(r, 300 + Math.floor(Math.random() * 400)));
  }
  throw new Error(`API login failed: ${String(lastError)}`);
}

/**
 * @param opts.force セッションが失効した疑いがあるとき、保存値を無視して取り直す。
 */
export async function apiSession(opts?: { force?: boolean }): Promise<string> {
  if (!opts?.force) {
    const stored = readStored();
    if (stored) return stored;
    if (fallbackSession) return fallbackSession;
  }
  fallbackSession = await loginWithRetry();
  return fallbackSession;
}
