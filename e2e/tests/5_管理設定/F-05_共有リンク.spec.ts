import {
  test,
  expect,
  Browser,
  BrowserContext,
  Page,
  Request,
} from "@playwright/test";
import { url, BASE_URL } from "../../utils/util";
import { settingsUrl, loginInIsolatedContext, confirmYes } from "../../utils/settings";
import { passwordFor } from "../../fixtures/seedSpec";

/**
 * 管理設定: 共有リンク（設定 > システム構成 > 共有リンク）とダッシュボードのウィジェット（#1963）
 *
 * テスト仕様書: docs/tests/SharedLinks/（テスト名にケース ID を入れている）
 *
 * リンク先は実在しないドメイン（TARGET）にし、context.route で横取りして
 * 届いたメソッド・本文・Referer を記録する。外部へは接続しない。
 * 公開範囲は拡充ベースライン（seed-spec.json）のロール階層・グループ・ユーザーで確かめる。
 *
 * 共有リンクは全ユーザー共通のデータのため、このファイルのテストは直列に実行し、
 * 作ったリンク（タイトルの接頭辞 PREFIX）は前後で消す。
 */

const PREFIX = "[E2E-SL]";
const TARGET = "https://e2e-shared-link.test";
const ORIGIN = new URL(BASE_URL).origin;

interface ApiResult<T> {
  success?: boolean;
  result?: T;
  error?: { message?: string };
}

interface SavedLink {
  id: number;
  title: string;
  url: string;
}

interface Info {
  roles: { id: string; name: string }[];
  groups: { id: string; name: string }[];
  users: { id: string; name: string }[];
}

/** 管理画面の API を、ページの CSRF トークン付きで呼ぶ（ブラウザの fetch と同じ経路） */
async function api<T>(
  page: Page,
  mode: string,
  params: Record<string, string> = {},
): Promise<ApiResult<T>> {
  return page.evaluate(
    async ({ mode, params }) => {
      const w = window as unknown as {
        csrfMagicName?: string;
        csrfMagicToken?: string;
      };
      const body = new URLSearchParams();
      if (w.csrfMagicName && w.csrfMagicToken) {
        body.append(w.csrfMagicName, w.csrfMagicToken);
      }
      body.append("module", "SharedLinks");
      body.append("parent", "Settings");
      body.append("api", "SharedLinkAPI");
      body.append("mode", mode);
      for (const [k, v] of Object.entries(params)) body.append(k, v);
      const res = await fetch("index.php", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body.toString(),
      });
      const text = await res.text();
      let data: Record<string, unknown>;
      try {
        data = JSON.parse(text);
      } catch {
        return { success: false, error: { message: text.slice(0, 200) } };
      }
      // 成功時は結果そのもの（EMIT_PURE_JSON）、エラー時は {success:false, error} で返る
      if (data && typeof data === "object" && "success" in data) {
        return data;
      }
      return { success: true, result: data };
    },
    { mode, params },
  );
}

async function listLinks(page: Page): Promise<SavedLink[]> {
  const res = await api<{ links: SavedLink[] }>(page, "list");
  expect(res.success, JSON.stringify(res.error)).toBe(true);
  return res.result?.links ?? [];
}

async function createLink(
  page: Page,
  link: Record<string, unknown>,
): Promise<number> {
  const res = await api<{ saved: boolean; id: number; errors?: object }>(
    page,
    "save",
    {
      link: JSON.stringify({
        description: "",
        method: "GET",
        open_mode: "tab",
        window_width: null,
        window_height: null,
        noreferrer: false,
        active: true,
        visibility: "all",
        params: [],
        targets: [],
        ...link,
      }),
    },
  );
  expect(res.result?.saved, JSON.stringify(res)).toBe(true);
  return res.result!.id;
}

async function deleteTestLinks(page: Page): Promise<void> {
  for (const link of await listLinks(page)) {
    if (link.title.startsWith(PREFIX)) {
      await api(page, "delete", { id: String(link.id) });
    }
  }
}

/**
 * POST の送り先（TARGET）が CRM の CSP（form-action）で許可されているか。
 * 許可されていなければブラウザが送信を止めるため、POST で送るケースは飛ばす
 * （config.customize.php の $csp_allowed_domains['form-action'] に TARGET を登録すると実行する）。
 */
async function skipUnlessPostAllowed(page: Page, linkId: number): Promise<void> {
  const link = (await listLinks(page)).find((l) => l.id === linkId) as
    | (SavedLink & { post_blocked?: boolean })
    | undefined;
  test.skip(
    link?.post_blocked !== false,
    `${TARGET} が $csp_allowed_domains['form-action'] に登録されていないため飛ばします`,
  );
}

/** リンク先への要求を横取りして記録する */
function interceptTarget(context: BrowserContext): Request[] {
  const requests: Request[] = [];
  void context.route(`${TARGET}/**`, async (route) => {
    requests.push(route.request());
    await route.fulfill({
      status: 200,
      contentType: "text/html; charset=utf-8",
      body: "<!doctype html><title>target</title><p>shared link target</p>",
    });
  });
  return requests;
}

/** 管理者のダッシュボードに共有リンクのウィジェットが 1 個ある状態にして、その要素を返す */
async function openDashboardWithWidget(page: Page) {
  await page.goto(url("index.php?module=Home&view=DashBoard"));
  const widget = page.locator('.dashboardWidget[data-name="SharedLinks"]');
  if ((await widget.count()) === 0) {
    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    await page
      .locator('.widgetsList a[data-name="SharedLinks"]')
      .click();
  }
  await expect(widget.locator(".sharedLinksWidget")).toBeVisible();
  return widget;
}

/**
 * ウィジェットの中身（ShowWidget の content=data）を、そのユーザーのセッションで取得する
 *
 * ShowWidget はウィジェットを置く処理を兼ねるため POST と CSRF トークンが必須。
 * ダッシュボードの JS と同じく、ページの中から送る。
 */
async function widgetHtml(page: Page, linkId: string): Promise<string> {
  await page.goto(url("index.php?module=Home&view=DashBoard"));
  return page.evaluate(async (linkId) => {
    const w = window as unknown as { csrfMagicName: string; csrfMagicToken: string };
    const body = new URLSearchParams({
      [w.csrfMagicName]: w.csrfMagicToken,
      module: "Home",
      view: "ShowWidget",
      name: "SharedLinks",
      linkid: linkId,
      content: "data",
    });
    const res = await fetch("index.php", { method: "POST", credentials: "same-origin", body });
    return res.text();
  }, linkId);
}

async function loginAs(
  browser: Browser,
  userName: string,
): Promise<{ context: BrowserContext; page: Page }> {
  const session = await loginInIsolatedContext(
    browser,
    userName,
    passwordFor(userName),
  );
  await session.page.waitForURL(/index\.php/);
  return session;
}

test.describe.configure({ mode: "serial" });

test.describe("管理設定: 共有リンク", () => {
  let widgetLinkId = "";

  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext({
      storageState: ".auth/user.json",
    });
    const page = await context.newPage();
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await deleteTestLinks(page);
    await context.close();
  });

  test.afterAll(async ({ browser }) => {
    const context = await browser.newContext({
      storageState: ".auth/user.json",
    });
    const page = await context.newPage();
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await deleteTestLinks(page);
    await context.close();
  });

  test("TC-UI-001 TC-MIG-001 設定メニューのシステム構成に共有リンクがあり管理画面を開ける", async ({
    page,
  }) => {
    await page.goto(settingsUrl({ module: "Vtiger", view: "Index" }));
    // 左のメニューは折りたたまれているため、「システム構成」を開いてから押す
    await page.getByText("システム構成", { exact: true }).first().click();
    await page.getByRole("link", { name: "共有リンク", exact: true }).first().click();
    await expect(page).toHaveURL(/module=SharedLinks/);
    await expect(
      page.getByRole("button", { name: /リンクを追加/ }),
    ).toBeVisible();
  });

  test("TC-UI-057 TC-UI-047 TC-API-005 管理画面で追加・編集・並び替え・削除でき、再読み込み後も反映されている", async ({
    page,
  }) => {
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    await page.getByRole("button", { name: /リンクを追加/ }).click();
    const dialog = page.getByRole("dialog");
    await dialog.getByLabel(/タイトル/).fill(`${PREFIX}画面から追加`);
    await dialog.getByLabel(/^URL/).fill(`${TARGET}/ui`);
    await dialog.getByLabel("POST").check();
    await dialog.getByLabel("別ウィンドウ").check();
    await expect(dialog.getByLabel(/幅（px）/)).toHaveValue("1024");
    await dialog.getByLabel(/幅（px）/).fill("800");
    await dialog.getByLabel(/高さ（px）/).fill("600");
    await dialog.getByRole("button", { name: /パラメータを追加/ }).click();
    const row = dialog.getByTestId("shared-link-param-row").first();
    await row.getByLabel("名前").fill("mail");
    await row.getByLabel("ユーザー情報").check();
    await row.getByRole("combobox", { name: "値" }).selectOption("email1");
    await dialog.getByLabel("指定した対象のみ").check();
    await dialog.getByLabel("種類", { exact: true }).selectOption("Roles");
    await dialog
      .getByRole("combobox", { name: "対象", exact: true })
      .selectOption({ label: "E2E営業1課長" });
    await dialog.getByLabel("下位の役割も含む").check();
    await dialog.getByRole("button", { name: "追加", exact: true }).click();
    await expect(dialog.getByTestId("shared-link-targets")).toContainText(
      "役割と下位: E2E営業1課長",
    );
    await dialog.getByLabel("リファラーを送らない").check();
    await dialog.getByRole("button", { name: "保存" }).click();
    await expect(page.getByRole("dialog")).toHaveCount(0);
    await expect(page.getByRole("status")).toContainText("保存しました");

    // 再読み込みしても残っている
    await page.reload();
    const saved = page
      .getByTestId("shared-link-row")
      .filter({ hasText: `${PREFIX}画面から追加` });
    await expect(saved).toContainText("POST");
    await expect(saved).toContainText("別ウィンドウ（800×600）");
    await expect(saved).toContainText("役割と下位: E2E営業1課長");

    // 編集: 内容が入っていて、変更が保存される
    await saved.getByRole("button", { name: "編集" }).click();
    await expect(dialog.getByLabel(/タイトル/)).toHaveValue(
      `${PREFIX}画面から追加`,
    );
    await expect(
      dialog
        .getByTestId("shared-link-param-row")
        .first()
        .getByRole("combobox", { name: "値" }),
    ).toHaveValue("email1");
    await dialog.getByLabel(/タイトル/).fill(`${PREFIX}画面から編集`);
    await dialog.getByRole("button", { name: "保存" }).click();
    await expect(page.getByRole("dialog")).toHaveCount(0);
    await page.reload();
    await expect(
      page.getByTestId("shared-link-row").filter({ hasText: `${PREFIX}画面から編集` }),
    ).toHaveCount(1);

    // 並び替え（TC-UI-016）: もう 1 件作り、上へ動かす
    await createLink(page, { title: `${PREFIX}並び替え`, url: `${TARGET}/order` });
    await page.reload();
    await expect(
      page.getByTestId("shared-link-row").filter({ hasText: `${PREFIX}並び替え` }),
    ).toHaveCount(1);
    const titles = async () =>
      (await page.getByTestId("shared-link-row").allTextContents()).filter((t) =>
        t.includes(PREFIX),
      );
    expect((await titles()).findIndex((t) => t.includes("並び替え"))).toBeGreaterThan(
      (await titles()).findIndex((t) => t.includes("画面から編集")),
    );
    await page
      .getByRole("button", { name: `上へ ${PREFIX}並び替え` })
      .click();
    await expect
      .poll(async () => {
        const list = await titles();
        return (
          list.findIndex((t) => t.includes("並び替え")) <
          list.findIndex((t) => t.includes("画面から編集"))
        );
      })
      .toBe(true);

    // 削除（TC-UI-017）: キャンセルでは消えず、OK で消える
    const target = page
      .getByTestId("shared-link-row")
      .filter({ hasText: `${PREFIX}並び替え` });
    page.once("dialog", (d) => void d.dismiss());
    await target.getByRole("button", { name: "削除" }).click();
    await expect(target).toHaveCount(1);
    page.once("dialog", (d) => void d.accept());
    await target.getByRole("button", { name: "削除" }).click();
    await expect(target).toHaveCount(0);
    await page.reload();
    await expect(
      page.getByTestId("shared-link-row").filter({ hasText: `${PREFIX}並び替え` }),
    ).toHaveCount(0);
  });

  test("TC-UI-050 画面の検証: javascript: の URL と未選択のユーザー情報は保存しない", async ({
    page,
  }) => {
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    const before = (await listLinks(page)).length;
    await page.getByRole("button", { name: /リンクを追加/ }).click();
    const dialog = page.getByRole("dialog");
    await dialog.getByLabel(/タイトル/).fill(`${PREFIX}不正`);
    await dialog.getByLabel(/^URL/).fill("javascript:alert(1)");
    await dialog.getByRole("button", { name: /パラメータを追加/ }).click();
    const row = dialog.getByTestId("shared-link-param-row").first();
    await row.getByLabel("名前").fill("x");
    await row.getByLabel("ユーザー情報").check();
    await dialog.getByRole("button", { name: "保存" }).click();

    await expect(dialog).toContainText("http:// または https:// で始まる URL");
    await expect(row).toContainText("項目を選択してください");
    expect((await listLinks(page)).length).toBe(before);
  });

  test("TC-WDG-001 TC-WDG-002 TC-WDG-003 TC-WDG-004 ウィジェットの追加から置け、見出しは共有リンクで、追加済みなら出ない", async ({
    page,
  }) => {
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await createLink(page, { title: `${PREFIX}ウィジェット確認`, url: `${TARGET}/w` });
    await page.reload();
    // 既に置いてあれば外し、メニューから置き直す
    const widget = page.locator('.dashboardWidget[data-name="SharedLinks"]');
    if ((await widget.count()) > 0) {
      await widget.locator('a[name="dclose"]').click({ force: true });
      await confirmYes(page);
      await expect(widget).toHaveCount(0);
      await page.reload();
    }
    // TC-WDG-004: ウィジェットの JS を読み込んでいる
    await expect(
      page.locator('script[src*="modules/Home/resources/SharedLinksWidget.js"]'),
    ).toHaveCount(1);

    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    const menuItem = page.locator('.widgetsList a[data-name="SharedLinks"]');
    await expect(menuItem).toHaveText("共有リンク");
    widgetLinkId = (await menuItem.getAttribute("data-linkid")) ?? "";
    await menuItem.click();

    await expect(widget.locator(".dashboardTitle")).toHaveText("共有リンク");
    await expect(widget.locator(".sharedLinksWidget")).toContainText(
      `${PREFIX}ウィジェット確認`,
    );

    // TC-WDG-002: 再読み込み後、追加済みのタブでは選べない
    await page.reload();
    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    await expect(
      page.locator('.widgetsList a[data-name="SharedLinks"]'),
    ).toHaveCount(0);
    expect(widgetLinkId).not.toBe("");
  });

  test("TC-WDG-013 管理画面で追加したリンクがウィジェットの更新で出る", async ({
    page,
  }) => {
    const widget = await openDashboardWithWidget(page);
    const title = `${PREFIX}更新で出る`;
    await expect(widget).not.toContainText(title);
    await createLink(page, { title, url: `${TARGET}/refresh` });

    await widget.hover();
    await widget.locator('a[name="drefresh"]').click({ force: true });
    await expect(widget.locator(".sharedLinksWidget")).toContainText(title);
  });

  test("TC-OPN-001 TC-OPN-016 TC-RES-042 GET・別タブ: パラメータ付きで新しいタブに開き、Referer はオリジンだけ", async ({
    page,
    context,
  }) => {
    const requests = interceptTarget(context);
    const title = `${PREFIX}GET別タブ`;
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await createLink(page, {
      title,
      url: `${TARGET}/get?lang=ja`,
      params: [
        { name: "login", value_type: "user", value: "user_name" },
        { name: "from", value_type: "fixed", value: "山田 太郎&x" },
      ],
    });
    const widget = await openDashboardWithWidget(page);
    await widget.locator('a[name="drefresh"]').click({ force: true });
    const before = page.url();

    const [popup] = await Promise.all([
      context.waitForEvent("page"),
      widget.getByRole("link", { name: title }).click(),
    ]);
    await popup.waitForLoadState();

    expect(popup.url()).toBe(
      `${TARGET}/get?lang=ja&login=admin&from=%E5%B1%B1%E7%94%B0%20%E5%A4%AA%E9%83%8E%26x`,
    );
    const request = requests.find((r) => r.url().startsWith(`${TARGET}/get`));
    expect(request?.method()).toBe("GET");
    // 既定のポリシー（strict-origin-when-cross-origin）でオリジンだけが送られる
    expect(await request?.headerValue("referer")).toBe(`${ORIGIN}/`);
    // CRM の画面は遷移しない
    expect(page.url()).toBe(before);
    await popup.close();
  });

  test("TC-OPN-005 TC-OPN-007 TC-OPN-017 TC-OPN-018 TC-OPN-022 TC-RES-043 POST・別ウィンドウ: 指定サイズのポップアップへ送り、CSRF トークンと Referer を送らない", async ({
    page,
    context,
  }) => {
    const requests = interceptTarget(context);
    const title = `${PREFIX}POST別ウィンドウ`;
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    const postWindowId = await createLink(page, {
      title,
      url: `${TARGET}/post`,
      method: "POST",
      open_mode: "window",
      window_width: 800,
      window_height: 600,
      noreferrer: true,
      params: [
        { name: "mail", value_type: "user", value: "email1" },
        { name: "uid", value_type: "user", value: "id" },
        { name: "k", value_type: "fixed", value: `a'b"<c>` },
      ],
    });
    await skipUnlessPostAllowed(page, postWindowId);
    const widget = await openDashboardWithWidget(page);
    await widget.locator('a[name="drefresh"]').click({ force: true });
    const before = page.url();

    const [popup] = await Promise.all([
      page.waitForEvent("popup"),
      widget.getByRole("link", { name: title }).click(),
    ]);
    await popup.waitForURL(`${TARGET}/post`);
    await popup.waitForLoadState();

    const request = requests.find((r) => r.url() === `${TARGET}/post`);
    expect(request?.method()).toBe("POST");
    const body = new URLSearchParams(request?.postData() ?? "");
    expect([...body.keys()]).toEqual(["mail", "uid", "k"]);
    expect(body.get("uid")).toBe("1");
    expect(body.get("mail")).toContain("@");
    expect(body.get("k")).toBe(`a'b"<c>`);
    expect(request?.postData()).not.toContain("__vtrftk");
    expect(await request?.headerValue("referer")).toBeNull();
    // 送信後に opener を外している
    expect(await popup.evaluate(() => window.opener === null)).toBe(true);
    // 指定したサイズのウィンドウ（ブラウザの枠の分の差を許容）
    const [w, h] = await popup.evaluate(() => [
      window.outerWidth,
      window.outerHeight,
    ]);
    expect(Math.abs(w - 800)).toBeLessThanOrEqual(40);
    expect(Math.abs(h - 600)).toBeLessThanOrEqual(120);
    expect(page.url()).toBe(before);
    // 空のウィンドウが別に残っていない（CRM の画面とポップアップの 2 つだけ）
    expect(context.pages()).toHaveLength(2);
    await popup.close();
  });

  test("TC-OPN-004 TC-OPN-016 POST・別タブ: 新しいタブへ送り、Referer はオリジンだけ", async ({
    page,
    context,
  }) => {
    const requests = interceptTarget(context);
    const title = `${PREFIX}POST別タブ`;
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    const postTabId = await createLink(page, {
      title,
      url: `${TARGET}/post-tab`,
      method: "POST",
      params: [{ name: "a", value_type: "fixed", value: "1" }],
    });
    await skipUnlessPostAllowed(page, postTabId);
    const widget = await openDashboardWithWidget(page);
    await widget.locator('a[name="drefresh"]').click({ force: true });

    const [popup] = await Promise.all([
      context.waitForEvent("page"),
      widget.getByRole("link", { name: title }).click(),
    ]);
    await popup.waitForURL(`${TARGET}/post-tab`);

    const request = requests.find((r) => r.url() === `${TARGET}/post-tab`);
    expect(request?.method()).toBe("POST");
    expect(request?.postData()).toBe("a=1");
    expect(await request?.headerValue("referer")).toBe(`${ORIGIN}/`);
    await popup.close();
  });

  test("TC-OPN-044 TC-UI-018 送り先が CSP で許可されていない POST は、管理画面で警告し、ウィジェットでは開かずに通知する", async ({
    page,
    context,
  }) => {
    const requests = interceptTarget(context);
    const blockedTarget = "https://e2e-shared-link-blocked.test";
    await context.route(`${blockedTarget}/**`, async (route) => {
      requests.push(route.request());
      await route.fulfill({ status: 200, body: "blocked target" });
    });
    const title = `${PREFIX}POST許可なし`;
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await createLink(page, {
      title,
      url: `${blockedTarget}/post`,
      method: "POST",
      open_mode: "window",
      window_width: 800,
      window_height: 600,
    });

    // 管理画面: 一覧に印が出る
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    const row = page.getByTestId("shared-link-row").filter({ hasText: title });
    await expect(row.getByRole("img", { name: /POST の送り先が許可されていない/ })).toBeVisible();

    // ウィジェット: 押しても開かず、通知が出る
    const widget = await openDashboardWithWidget(page);
    await widget.locator('a[name="drefresh"]').click({ force: true });
    const pagesBefore = context.pages().length;
    await widget.getByRole("link", { name: title }).click();
    await expect(page.getByText("このリンクの送り先は許可されていないため開けません")).toBeVisible();
    expect(context.pages()).toHaveLength(pagesBefore);
    expect(requests.filter((r) => r.url().startsWith(blockedTarget))).toHaveLength(0);
  });

  test("TC-OPN-017 GET・別ウィンドウ・リファラーを送らない: Referer なしでポップアップに開く", async ({
    page,
    context,
  }) => {
    const requests = interceptTarget(context);
    const title = `${PREFIX}GET別ウィンドウ`;
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await createLink(page, {
      title,
      url: `${TARGET}/get-window`,
      open_mode: "window",
      window_width: 640,
      window_height: 480,
      noreferrer: true,
    });
    const widget = await openDashboardWithWidget(page);
    await widget.locator('a[name="drefresh"]').click({ force: true });

    const [popup] = await Promise.all([
      context.waitForEvent("page"),
      widget.getByRole("link", { name: title }).click(),
    ]);
    await popup.waitForURL(`${TARGET}/get-window`);

    const request = requests.find((r) => r.url() === `${TARGET}/get-window`);
    expect(await request?.headerValue("referer")).toBeNull();
    expect(await popup.evaluate(() => window.opener === null)).toBe(true);
    await popup.close();
  });

  test("TC-VIS-030〜035 TC-RES-008 公開範囲: ログインしたユーザーごとに見えるリンクが変わる", async ({
    page,
    browser,
  }) => {
    test.setTimeout(120000);
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    const info = (await api<Info>(page, "info")).result!;
    const roleId = (name: string) => info.roles.find((r) => r.name === name)!.id;
    const groupId = info.groups.find((g) => g.name === "E2Eクロスグループ")!.id;
    const repB = info.users.find((u) => u.name.startsWith("2課員"))!.id;
    // ダッシュボードに置いたウィジェットの data-url（…&linkid=NN…）から、ウィジェットの linkid を取る
    await openDashboardWithWidget(page);
    await page.reload();
    const dataUrl =
      (await page
        .locator('.dashboardWidget[data-name="SharedLinks"]')
        .getAttribute("data-url")) ?? "";
    widgetLinkId = /[?&]linkid=(\d+)/.exec(dataUrl)?.[1] ?? "";
    expect(widgetLinkId, dataUrl).not.toBe("");

    const links = {
      all: `${PREFIX}VIS全員`,
      role: `${PREFIX}VIS役割1課長`,
      roleSub: `${PREFIX}VIS役割と下位1課長`,
      group: `${PREFIX}VISグループ`,
      user: `${PREFIX}VISユーザー2課員`,
      inactive: `${PREFIX}VIS無効`,
    };
    const targets = (type: string, id: string) => ({
      visibility: "targets",
      targets: [{ type, id }],
    });
    await createLink(page, {
      title: links.all,
      url: `${TARGET}/vis`,
      params: [{ name: "login", value_type: "user", value: "user_name" }],
    });
    await createLink(page, { title: links.role, url: `${TARGET}/vis`, ...targets("Roles", roleId("E2E営業1課長")) });
    await createLink(page, {
      title: links.roleSub,
      url: `${TARGET}/vis`,
      ...targets("RoleAndSubordinates", roleId("E2E営業1課長")),
    });
    await createLink(page, { title: links.group, url: `${TARGET}/vis`, ...targets("Groups", groupId) });
    await createLink(page, { title: links.user, url: `${TARGET}/vis`, ...targets("Users", repB) });
    await createLink(page, { title: links.inactive, url: `${TARGET}/vis`, active: false });

    const expected: Record<string, (keyof typeof links)[]> = {
      admin: ["all"],
      e2e_director: ["all"],
      e2e_mgr_a: ["all", "role", "roleSub"],
      e2e_rep_a: ["all", "roleSub", "group"],
      e2e_rep_b: ["all", "group", "user"],
    };

    for (const [userName, visible] of Object.entries(expected)) {
      let html: string;
      let context: BrowserContext | null = null;
      if (userName === "admin") {
        html = await widgetHtml(page, widgetLinkId);
      } else {
        const session = await loginAs(browser, userName);
        context = session.context;
        html = await widgetHtml(session.page, widgetLinkId);
      }
      try {
        for (const key of Object.keys(links) as (keyof typeof links)[]) {
          const shouldShow = visible.includes(key);
          expect(html.includes(links[key]), `${userName} に ${key} が${shouldShow ? "出る" : "出ない"}: ${html.slice(0, 600)}`).toBe(shouldShow);
        }
        // TC-RES-008: ユーザー情報は見ている本人の値
        expect(html).toContain(`login=${userName}`);
        // TC-VIS-029: 公開範囲外のリンクの URL も出さない（全リンク共通の URL なので出る数で確かめる）
        const count = html.split(`${TARGET}/vis`).length - 1;
        expect(count, `${userName} のリンクの数`).toBeGreaterThanOrEqual(visible.length);
      } finally {
        await context?.close();
      }
    }
  });

  test("TC-UI-002 TC-API-001 管理者以外は管理画面も API も使えない", async ({
    browser,
  }) => {
    const { context, page } = await loginAs(browser, "e2e_rep_a");
    try {
      await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
      await expect(page.locator("shared-links-page")).toHaveCount(0);

      await page.goto(url("index.php?module=Home&view=DashBoard"));
      const res = await api(page, "list");
      expect(res.success).toBe(false);
      const saved = await api(page, "save", {
        link: JSON.stringify({ title: `${PREFIX}一般ユーザー`, url: `${TARGET}/x` }),
      });
      expect(saved.success).toBe(false);
    } finally {
      await context.close();
    }
  });

  test("TC-API-004 CSRF トークンの無い保存は受け付けない", async ({ page }) => {
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    const title = `${PREFIX}CSRFなし`;
    const res = await page.request.post(url("index.php"), {
      form: {
        module: "SharedLinks",
        parent: "Settings",
        api: "SharedLinkAPI",
        mode: "save",
        link: JSON.stringify({ title, url: `${TARGET}/csrf` }),
      },
    });
    const text = await res.text();
    expect(text).not.toContain('"saved":true');
    expect((await listLinks(page)).some((l) => l.title === title)).toBe(false);
  });

  test("TC-API-006 未ログインでは一覧を返さない", async ({ browser, page }) => {
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await createLink(page, { title: `${PREFIX}未ログイン確認`, url: `${TARGET}/anon` });
    const context = await browser.newContext({
      storageState: { cookies: [], origins: [] },
    });
    try {
      const res = await context.request.get(
        url("index.php?module=SharedLinks&parent=Settings&api=SharedLinkAPI&mode=list"),
      );
      expect(await res.text()).not.toContain(`${PREFIX}未ログイン確認`);
    } finally {
      await context.close();
    }
  });
});
