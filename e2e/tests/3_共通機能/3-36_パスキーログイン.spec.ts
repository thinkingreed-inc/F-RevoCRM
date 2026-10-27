import { test, expect } from "../../fixtures/isolated";
import type { Browser, BrowserContext, Page } from "@playwright/test";
import { BASE_URL, generateRandomString, url } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";
import { listSearch } from "../../utils/listview";
import { apiSession } from "../../utils/api";
import { frQuery } from "../../model/fetcher";
import { withUserPrivilegesLock } from "../../fixtures/userPrivilegesLock";
import {
  addVirtualAuthenticator,
  countCredentials,
  type VirtualAuthenticator,
} from "../../utils/passkey";

/**
 * 共通機能: パスキーのみのログイン(システム変数 ALLOW_PASSKEY_ONLY_LOGIN)
 *
 * Chromium の仮想認証器で、
 *   1. フラグ OFF ではログイン画面に「パスキーでログイン」が出ない
 *   2. 個人設定から登録した Passkey だけで(ID/PW を入れずに)ログインできる
 *   3. ID/PW のあと 2 段階目の Passkey でもログインできる
 *   4. Passkey だけのログインがログイン履歴に種別 passkey で残る
 * を通しで確認する。
 *
 * - admin に Passkey を登録すると auth.setup が 2 段階目で止まり、ほかの全テストが
 *   落ちる。そのためこのテストで専用ユーザーを作り、afterAll で完全に削除する。
 * - 仮想認証器はページ単位なので、専用ユーザーの操作は 1 つのページで続けて行い、
 *   登録した credential をそのままログインに使う。
 * - フラグは必要なテストの中だけ ON にし、finally / afterAll で必ず false に戻す。
 *   ON の間は他のテストのログイン画面にもボタンが出るため、ログインボタンの
 *   指定は完全一致にしてある(utils/settings.ts ほか)。
 */
test.describe.serial("共通: パスキーのみのログイン", () => {
  test.describe.configure({ timeout: 120_000 });

  const FLAG_KEY = "ALLOW_PASSKEY_ONLY_LOGIN";
  const token = generateRandomString(6).toLowerCase();
  const userName = `e2epk${token}`;
  // ログイン履歴のユーザー名列は表示名(姓)で出るため、token を含めて行を特定する
  const lastName = `Passkey${token}`;
  const password = "Test_1234";
  const deviceName = `e2e-device-${token}`;

  let userId = "";
  let userContext: BrowserContext | undefined;
  let userPage: Page;
  let authenticator: VirtualAuthenticator;

  /** admin の独立ページを開いて処理し、必ず閉じる */
  const asAdmin = async (
    browser: Browser,
    storageState: string,
    fn: (page: Page) => Promise<void>
  ): Promise<void> => {
    const context = await browser.newContext({ storageState });
    try {
      await fn(await context.newPage());
    } finally {
      await context.close();
    }
  };

  /** システム変数一覧の指定キーの行(備考に別のキー名が出るため完全一致で絞る) */
  const parameterRow = (page: Page) =>
    page
      .locator("#listview-table tr.listViewEntries")
      .filter({ has: page.getByText(FLAG_KEY, { exact: true }) });

  /**
   * ALLOW_PASSKEY_ONLY_LOGIN を切り替える(F-07 と同じ編集ダイアログを使う)。
   * 既に望む値なら何もしない。
   */
  const setPasskeyOnlyLogin = async (page: Page, enabled: boolean) => {
    const want = enabled ? "true" : "false";
    const listParams = { module: "Parameters", view: "List" };
    await gotoSettings(page, listParams);
    // 列は キー / 値 / 備考 の順(ID 列は非表示)
    const valueCell = parameterRow(page).locator("td.listViewEntryValue").nth(1);
    await expect(valueCell).toBeVisible();
    if ((await valueCell.innerText()).trim() === want) return;

    await parameterRow(page).locator("a.parameter-edit-btn").click();
    // Radix は閉じた後も非表示要素を残すため :visible で絞る
    const dialog = page.locator('[role="dialog"]:visible');
    await expect(dialog.getByRole("heading", { name: FLAG_KEY })).toBeVisible();
    await dialog.getByRole("switch").click();
    await dialog.getByRole("button", { name: "保存" }).click();
    await expect(dialog).toBeHidden({ timeout: 15000 });
    await page.waitForLoadState("networkidle").catch(() => {});

    await gotoSettings(page, listParams);
    await expect(
      parameterRow(page).locator("td.listViewEntryValue").nth(1)
    ).toHaveText(want);
  };

  /** 専用ユーザーのページを ID/PW のログイン画面から送信する */
  const submitIdPassword = async () => {
    await userPage.goto(BASE_URL, { waitUntil: "domcontentloaded" });
    await userPage.fill("id=username", userName);
    await userPage.fill("id=password", password);
    await userPage.getByRole("button", { name: "ログイン", exact: true }).click();
    await userPage.waitForLoadState("domcontentloaded").catch(() => {});
  };

  /** ログイン後の画面に着いたこと(ログイン画面・2 段階目の画面ではない) */
  const expectLoggedIn = async () => {
    // ヘッダーのサインアウト(ログイン中の画面にだけある)が出るまで待つ。
    // URL の否定だけで判定すると、遷移が始まる前の URL で通ってしまう
    await expect(userPage.locator("#menubar_item_right_LBL_SIGN_OUT")).toBeAttached({
      timeout: 30000,
    });
    await expect(userPage).not.toHaveURL(/view=(Login|MultiFactorAuth)\b|error=login/);
    await expect(userPage.locator("input#password")).toHaveCount(0);
  };

  const logout = async () => {
    await userPage.goto(url("index.php?module=Users&action=Logout"), {
      waitUntil: "domcontentloaded",
    });
    await expect(userPage.locator("#username")).toBeVisible();
  };

  /** 専用ユーザーの record id を API で引く(居なければ空文字) */
  const lookupUserId = async (): Promise<string> => {
    const rows = await frQuery(
      await apiSession(),
      `SELECT id FROM Users WHERE user_name = '${userName}';`
    );
    const wsId: string = rows?.[0]?.id ?? "";
    return wsId.includes("x") ? wsId.split("x")[1] : wsId;
  };

  test.beforeAll(async ({ browser, workerStorageState }) => {
    // ユーザーの作成は作成したユーザーの権限ファイルだけを作るが、
    // ログイン(admin の権限ファイル再生成)と重ならないよう念のため排他する
    await withUserPrivilegesLock(() =>
      asAdmin(browser, workerStorageState, async (page) => {
        await gotoSettings(page, { module: "Users", view: "List" });
        await page.getByText("ユーザーの追加").first().click();
        await page.locator('input[name="user_name"]').fill(userName);
        await page.locator('input[name="last_name"]').fill(lastName);
        await page.locator('input[name="email1"]').fill(`${userName}@example.com`);
        await page.locator('input[name="user_password"]').fill(password);
        await page.locator('input[name="confirm_password"]').fill(password);
        await saveAndSettle(page, page.locator("button.saveButton"));

        await gotoSettings(page, { module: "Users", view: "List" });
        await listSearch(page, "user_name", userName);
        await expect(
          page.locator("tr.listViewEntries").filter({ hasText: token }).first()
        ).toBeVisible({ timeout: 15000 });
      })
    );

    userId = await lookupUserId();
    expect(userId, "作成したユーザーの record id が引けること").not.toBe("");

    // 専用ユーザー用のログアウト状態の context と、そのページの仮想認証器
    userContext = await browser.newContext({
      storageState: { cookies: [], origins: [] },
    });
    userPage = await userContext.newPage();
    authenticator = await addVirtualAuthenticator(userContext, userPage);
  });

  test.afterAll(async ({ browser, workerStorageState }) => {
    await userContext?.close();
    await asAdmin(browser, workerStorageState, async (page) => {
      try {
        // フラグは必ず既定(false)へ戻す
        await setPasskeyOnlyLogin(page, false);
      } finally {
        // beforeAll が途中で落ちても作成済みなら消す
        const id = userId || (await lookupUserId());
        if (id) {
          // 完全削除(登録した Passkey も消える)。レコードは admin(id=1) へ移す
          await gotoSettings(page, { module: "Users", view: "List" });
          await withUserPrivilegesLock(() =>
            page.evaluate(
              (target) =>
                new Promise<boolean>((resolve, reject) => {
                  // vtiger の app.request.post は CSRF トークンを付与して送る
                  // eslint-disable-next-line @typescript-eslint/no-explicit-any
                  (window as any).app.request
                    .post({
                      data: {
                        module: "Users",
                        action: "DeleteAjax",
                        userid: target,
                        transfer_user_id: "1",
                        permanent: "1",
                      },
                    })
                    .then((err: unknown) => (err ? reject(err) : resolve(true)));
                }),
              id
            )
          );
          expect(await lookupUserId(), "テスト用ユーザーが削除されていること").toBe("");
        }
      }
    });
  });

  test("フラグOFFではパスキーボタンが出ない", async ({ page }) => {
    await setPasskeyOnlyLogin(page, false);

    await userPage.goto(BASE_URL, { waitUntil: "domcontentloaded" });
    await expect(userPage.locator("#username")).toBeVisible();
    await expect(userPage.locator("#passkeyOnlyLoginBtn")).toHaveCount(0);
    await expect(userPage.locator("#passkeyOnlyLoginForm")).toHaveCount(0);
  });

  test("登録したパスキーだけでログインできる", async ({ page }) => {
    // 1. ID/PW でログインし、個人設定から Passkey を登録する
    await submitIdPassword();
    await expectLoggedIn();

    await userPage.goto(
      url(`index.php?module=Users&view=PreferenceDetail&parent=Settings&record=${userId}`)
    );
    // 詳細画面の「その他」メニュー → 多要素認証の追加
    await userPage
      .locator(".detailViewButtoncontainer button[data-toggle='dropdown']")
      .click();
    await userPage
      .locator("#Users_detailView_moreAction_LBL_ADD_MULTI_FACTOR_AUTHENTICATION a")
      .click();
    // 1 段目: 方式の選択(Passkey / TOTP)
    await userPage
      .locator(".multi-factor-area")
      .first()
      // ボタンの文言は div > span の中にあり、アクセシブルネームが付かないため文言で絞る
      .locator("button", { hasText: "パスキー認証を追加" })
      .click();
    // 2 段目: 端末名を入れて登録(仮想認証器が応答する)
    const passkeyForm = userPage.locator("#passkeyForm");
    await expect(passkeyForm).toBeVisible();
    await passkeyForm.locator('input[name="device_name"]').fill(deviceName);
    await Promise.all([
      userPage.waitForResponse(
        (r) =>
          r.request().method() === "POST" &&
          (r.request().postData() ?? "").includes("SaveMultiFactorAuthenticationAjax"),
        { timeout: 30000 }
      ),
      userPage.locator("#passkeyAdd").click(),
    ]);
    // 登録後は画面が再読み込みされ、多要素認証の一覧に端末名が出る
    await expect(
      userPage.locator(".multi_factor_credentialList").getByText(deviceName)
    ).toBeVisible({ timeout: 30000 });
    expect(await countCredentials(authenticator)).toBe(1);

    await logout();

    // 2. フラグ ON で、ID 欄を空のまま「パスキーでログイン」
    try {
      await setPasskeyOnlyLogin(page, true);

      await userPage.goto(BASE_URL, { waitUntil: "domcontentloaded" });
      await expect(userPage.locator("#username")).toHaveValue("");
      const passkeyButton = userPage.locator("#passkeyOnlyLoginBtn");
      await expect(passkeyButton).toBeVisible();
      await expect(passkeyButton).toHaveText("パスキーでログイン");
      await expect(passkeyButton).toBeEnabled();
      await passkeyButton.click();

      await expectLoggedIn();
    } finally {
      await setPasskeyOnlyLogin(page, false);
    }

    await logout();
  });

  test("ID/PWのあと2段階目のパスキーでも入れる", async () => {
    await submitIdPassword();
    // Passkey 登録済みのユーザーは 2 段階目の画面へ進む
    await expect(userPage).toHaveURL(/view=MultiFactorAuth\b/, { timeout: 30000 });
    await userPage.locator("#passkeyLoginBtn").click();

    await expectLoggedIn();
    await logout();
  });

  test("ログイン履歴に種別passkeyで記録される", async ({ page }) => {
    await gotoSettings(page, { module: "LoginHistory", view: "List" });

    const rows = page
      .locator("table#listview-table tr.listViewEntries")
      .filter({ hasText: lastName });
    await expect(rows.first()).toBeVisible();
    // 種別の列は最後の列。Passkey だけのログインは passkey、2 段階目は mfa_passkey
    const typeCells = rows.locator("td.listViewEntryValue:last-child");
    await expect(typeCells.filter({ hasText: /^\s*passkey\s*$/ })).toHaveCount(1);
    await expect(typeCells.filter({ hasText: /^\s*mfa_passkey\s*$/ })).toHaveCount(1);
  });
});
