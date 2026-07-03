import { login } from "../model/fetcher";

/**
 * 検証・後始末用に Webservice API セッションを「その場で」取得する。
 * 保存済み sessionName は実行/並行ワーカーの状況で失効していることがあるため、
 * 使う直前に取り直すのが確実。
 */
export async function apiSession(): Promise<string> {
  const res = await login(
    process.env.E2E_USER_NAME || "",
    process.env.E2E_USER_ACCESSKEY || ""
  );
  if (!res) throw new Error("API login failed");
  return res.sessionName;
}
