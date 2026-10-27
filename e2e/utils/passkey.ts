import type { BrowserContext, CDPSession, Page } from "@playwright/test";

/**
 * Chromium の仮想認証器(CDP WebAuthn ドメイン)で Passkey を扱うヘルパ。
 *
 * 認証器は CDP セッション(=ページ)ごとに作られ、その中に credential が入る。
 * 同じページで登録とログインをすれば credential を引き継ぐ必要は無い。
 * 別のページで使うときは getCredentials / addCredential で移し替える。
 */
export interface VirtualAuthenticator {
  cdp: CDPSession;
  authenticatorId: string;
}

/**
 * ページにプラットフォーム認証器(端末内蔵の生体認証に相当)を付ける。
 *
 * Passkey 登録は authenticatorAttachment: "platform" / requireResidentKey: true /
 * userVerification: "required" を求めるため、internal + resident key +
 * user verification 済みの認証器にする。automaticPresenceSimulation で
 * ブラウザのダイアログ操作なしに応答させる。
 */
export async function addVirtualAuthenticator(
  context: BrowserContext,
  page: Page
): Promise<VirtualAuthenticator> {
  const cdp = await context.newCDPSession(page);
  await cdp.send("WebAuthn.enable");
  const { authenticatorId } = await cdp.send(
    "WebAuthn.addVirtualAuthenticator",
    {
      options: {
        protocol: "ctap2",
        transport: "internal",
        hasResidentKey: true,
        hasUserVerification: true,
        isUserVerified: true,
        automaticPresenceSimulation: true,
      },
    }
  );
  return { cdp, authenticatorId };
}

/** 認証器に入っている credential の数を返す(登録できたかの確認用) */
export async function countCredentials(
  authenticator: VirtualAuthenticator
): Promise<number> {
  const { credentials } = await authenticator.cdp.send(
    "WebAuthn.getCredentials",
    { authenticatorId: authenticator.authenticatorId }
  );
  return credentials.length;
}
