import { login } from "./fetcher";
import { sessionNameFile, userIdName } from "../utils/util";
import { readFileSync, writeFileSync } from "fs";

/**
 * Webservice API セッション取得の共通化。
 *
 * getchallenge→login はユーザ単位でチャレンジトークンが 1 つしか無いため、
 * 複数の setup(auth/seed)が並行して login すると互いのトークンを打ち消し合い
 * 失敗する(ローカルの parallel 実行で発生。CI は workers=1 で偶然回避していた)。
 *
 * そこで「最初に一度だけ」acquire し、以降は getStored* で使い回す設計にする。
 * playwright.config の project 依存関係で auth(=acquire)を必ず先頭に実行させ、
 * seed / 各 spec はファイルから読むだけにする。これで workers=1 に頼らず済む。
 */

/**
 * API セッションを新規取得し sessionName / userId をファイルへ保存する。
 * 依存関係の起点(auth.setup)からのみ呼ぶこと。
 */
export async function acquireApiSession(): Promise<{
  sessionName: string;
  userId: string;
}> {
  const response = await login(
    process.env.E2E_USER_NAME || "",
    process.env.E2E_USER_ACCESSKEY || ""
  );
  if (!response) {
    throw new Error("API login failed (acquireApiSession)");
  }
  // sessionNameFile は従来どおり sessionName のみのプレーンテキスト(既存 spec 互換)。
  writeFileSync(sessionNameFile, response.sessionName);
  writeFileSync(userIdName, response.userId);
  return { sessionName: response.sessionName, userId: response.userId };
}

/** acquire 済みの sessionName を読み出す(未取得ならエラー)。 */
export function getStoredSessionName(): string {
  const name = safeRead(sessionNameFile);
  if (!name) {
    throw new Error(
      "sessionName が未取得です。auth.setup(session 取得)を先に実行してください。"
    );
  }
  return name;
}

/** acquire 済みの userId(担当のワークスペースID)を読み出す。 */
export function getStoredUserId(): string {
  const id = safeRead(userIdName);
  if (!id) {
    throw new Error(
      "userId が未取得です。auth.setup(session 取得)を先に実行してください。"
    );
  }
  return id;
}

function safeRead(path: string): string {
  try {
    return readFileSync(path, "utf-8").trim();
  } catch {
    return "";
  }
}
