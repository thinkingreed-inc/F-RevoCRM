import {
  test,
  expect,
  Browser,
  BrowserContext,
  Locator,
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
 * - テスト用のウィジェット（見出し SET_TITLE）を作り、リンクはそこに作る。
 *   ダッシュボードには共有リンクのウィジェットが複数並びうるため、見出しで特定する。
 * - リンク先は実在しないドメイン（TARGET）にし、context.route で横取りして
 *   届いたメソッド・本文・Referer を記録する。外部へは接続しない。
 * - 公開範囲は拡充ベースライン（seed-spec.json）のロール階層・グループ・ユーザーで確かめる。
 *
 * 共有リンクは全ユーザー共通のデータのため、このファイルのテストは直列に実行し、
 * 作ったウィジェット・リンク（見出し・タイトルの接頭辞 PREFIX）は前後で消す。
 */

const PREFIX = "[E2E-SL]";
const SET_TITLE = `${PREFIX}リンク`;
const TARGET = "https://e2e-shared-link.test";
const ORIGIN = new URL(BASE_URL).origin;

interface ApiResult<T> {
  success?: boolean;
  result?: T;
  error?: { message?: string };
}

interface SavedLink {
  id: number;
  set_id: number;
  title: string;
  url: string;
  post_blocked?: boolean;
}

interface SavedSet {
  id: number;
  title: string;
  linkid: number;
}

interface Info {
  roles: { id: string; name: string }[];
  groups: { id: string; name: string }[];
  users: { id: string; name: string }[];
}

/** 正規表現の中で文字どおりに使う（見出しの [ ] など） */
function literal(text: string): string {
  return text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
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

async function listAll(page: Page): Promise<{ sets: SavedSet[]; links: SavedLink[] }> {
  const res = await api<{ sets: SavedSet[]; links: SavedLink[] }>(page, "list");
  expect(res.success, JSON.stringify(res.error)).toBe(true);
  return { sets: res.result?.sets ?? [], links: res.result?.links ?? [] };
}

async function testSet(page: Page, title = SET_TITLE): Promise<SavedSet> {
  const set = (await listAll(page)).sets.find((s) => s.title === title);
  expect(set, `ウィジェット ${title} がある`).toBeTruthy();
  return set!;
}

async function createSet(page: Page, title: string): Promise<SavedSet> {
  const res = await api<{ saved: boolean; id: number }>(page, "save_set", { title });
  expect(res.result?.saved, JSON.stringify(res)).toBe(true);
  return testSet(page, title);
}

async function createLink(
  page: Page,
  link: Record<string, unknown>,
  setTitle = SET_TITLE,
): Promise<number> {
  const set = await testSet(page, setTitle);
  const res = await api<{ saved: boolean; id: number }>(page, "save", {
    set_id: String(set.id),
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
  });
  expect(res.result?.saved, JSON.stringify(res)).toBe(true);
  return res.result!.id;
}

/** テストで作ったウィジェット（中のリンクも消える）とリンクを消す */
async function cleanUp(page: Page): Promise<void> {
  const { sets, links } = await listAll(page);
  for (const set of sets) {
    if (set.title.startsWith(PREFIX)) {
      await api(page, "delete_set", { id: String(set.id) });
    }
  }
  for (const link of links) {
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
  const link = (await listAll(page)).links.find((l) => l.id === linkId);
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

/** 管理画面で、見出しのタブを選ぶ */
async function selectSetTab(page: Page, title: string): Promise<void> {
  await page
    .locator("shared-links-page")
    .getByRole("tab", { name: new RegExp(`^${literal(title)}\\d*$`) })
    .click();
}

/** 見出しで、ダッシュボードに置いた共有リンクのウィジェットを特定する */
function widgetOf(page: Page, title = SET_TITLE): Locator {
  return page
    .locator('.dashboardWidget[data-name="SharedLinks"]')
    .filter({ has: page.locator(".dashboardTitle", { hasText: title }) });
}

/**
 * ダッシュボードを開き、置いてある共有リンクのウィジェットの中身が読み込まれるのを待つ
 *
 * ウィジェットは枠が先に出て、中身（見出しを含む）は後から読み込まれる。
 * 見出しで探す前に待たないと、置いてあるのに見つからないことになる。
 */
async function gotoDashboard(page: Page): Promise<void> {
  await page.goto(url("index.php?module=Home&view=DashBoard"));
  await page.waitForLoadState("networkidle").catch(() => {});
  await expect
    .poll(() =>
      page.evaluate(() =>
        Array.from(document.querySelectorAll('.dashboardWidget[data-name="SharedLinks"]')).every(
          (w) => w.querySelector(".dashboardTitle") !== null,
        ),
      ),
    )
    .toBe(true);
}

/** 管理者のダッシュボードに、指定したウィジェットが 1 個ある状態にして、その要素を返す */
async function openDashboardWithWidget(page: Page, title = SET_TITLE): Promise<Locator> {
  await gotoDashboard(page);
  const widget = widgetOf(page, title);
  if ((await widget.count()) === 0) {
    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    await page
      .locator('.widgetsList a[data-name="SharedLinks"]')
      .filter({ hasText: title })
      .click();
  }
  await expect(widget.locator(".sharedLinksWidget")).toBeVisible();
  return widget;
}

/** ウィジェットの中身を読み直す（フッターの更新ボタン） */
async function refreshWidget(widget: Locator): Promise<void> {
  await widget.locator('a[name="drefresh"]').click({ force: true });
}

/** ウィジェットのリンク（GET は link、POST は button の役割になるため a.sharedLink で探す） */
function linkIn(widget: Locator, title: string): Locator {
  return widget.locator("a.sharedLink", { hasText: title });
}

/**
 * ウィジェットの中身（ShowWidget の content=data）を、そのユーザーのセッションで取得する
 *
 * ShowWidget はウィジェットを置く処理を兼ねるため POST と CSRF トークンが必須。
 * ダッシュボードの JS と同じく、ページの中から送る。
 */
async function widgetHtml(page: Page, linkId: number): Promise<string> {
  await page.goto(url("index.php?module=Home&view=DashBoard"));
  return page.evaluate(async (linkId) => {
    const w = window as unknown as { csrfMagicName: string; csrfMagicToken: string };
    const body = new URLSearchParams({
      [w.csrfMagicName]: w.csrfMagicToken,
      module: "Home",
      view: "ShowWidget",
      name: "SharedLinks",
      linkid: String(linkId),
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
  const session = await loginInIsolatedContext(browser, userName, passwordFor(userName));
  await session.page.waitForURL(/index\.php/);
  return session;
}

test.describe.configure({ mode: "serial" });

test.describe("管理設定: 共有リンク", () => {
  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext({ storageState: ".auth/user.json" });
    const page = await context.newPage();
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await cleanUp(page);
    await createSet(page, SET_TITLE);
    await context.close();
  });

  test.afterAll(async ({ browser }) => {
    const context = await browser.newContext({ storageState: ".auth/user.json" });
    const page = await context.newPage();
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await cleanUp(page);
    await context.close();
  });

  test("TC-UI-001 TC-MIG-001 設定メニューのシステム構成に共有リンクがあり管理画面を開ける。最初のウィジェット「共有リンク」がある", async ({
    page,
  }) => {
    await page.goto(settingsUrl({ module: "Vtiger", view: "Index" }));
    // 左のメニューは折りたたまれているため、「システム構成」を開いてから押す
    await page.getByText("システム構成", { exact: true }).first().click();
    await page.getByRole("link", { name: "共有リンク", exact: true }).first().click();
    await expect(page).toHaveURL(/module=SharedLinks/);
    await expect(page.getByRole("button", { name: /リンクを追加/ })).toBeVisible();
    await expect(
      page.locator("shared-links-page").getByRole("tab", { name: /^共有リンク\d*$/ }),
    ).toBeVisible();
  });

  test("TC-UI-057 TC-UI-047 TC-API-005 TC-SET-036 管理画面で追加・編集・並び替え・削除でき、再読み込み後も反映されている", async ({
    page,
  }) => {
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    await selectSetTab(page, SET_TITLE);
    await page.getByRole("button", { name: /リンクを追加/ }).click();
    const dialog = page.getByRole("dialog");
    // 開いているウィジェットが選ばれている
    await expect(dialog.getByLabel(/^ウィジェット/)).toHaveValue(String((await testSet(page)).id));
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
    await selectSetTab(page, SET_TITLE);
    const saved = page.getByTestId("shared-link-row").filter({ hasText: `${PREFIX}画面から追加` });
    await expect(saved).toContainText("POST");
    await expect(saved).toContainText("別ウィンドウ（800×600）");
    await expect(saved).toContainText("役割と下位: E2E営業1課長");

    // 編集: 内容が入っていて、変更が保存される
    await saved.getByRole("button", { name: "編集" }).click();
    await expect(dialog.getByLabel(/タイトル/)).toHaveValue(`${PREFIX}画面から追加`);
    await expect(
      dialog.getByTestId("shared-link-param-row").first().getByRole("combobox", { name: "値" }),
    ).toHaveValue("email1");
    await dialog.getByLabel(/タイトル/).fill(`${PREFIX}画面から編集`);
    await dialog.getByRole("button", { name: "保存" }).click();
    await expect(page.getByRole("dialog")).toHaveCount(0);
    await expect(
      page.getByTestId("shared-link-row").filter({ hasText: `${PREFIX}画面から編集` }),
    ).toHaveCount(1);

    // 並び替え（TC-UI-016）: もう 1 件作り、上へ動かす
    await createLink(page, { title: `${PREFIX}並び替え`, url: `${TARGET}/order` });
    await page.reload();
    await selectSetTab(page, SET_TITLE);
    await expect(
      page.getByTestId("shared-link-row").filter({ hasText: `${PREFIX}並び替え` }),
    ).toHaveCount(1);
    const titles = async () => page.getByTestId("shared-link-row").allTextContents();
    expect((await titles()).findIndex((t) => t.includes("並び替え"))).toBeGreaterThan(
      (await titles()).findIndex((t) => t.includes("画面から編集")),
    );
    await page.getByRole("button", { name: `上へ ${PREFIX}並び替え` }).click();
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
    const target = page.getByTestId("shared-link-row").filter({ hasText: `${PREFIX}並び替え` });
    page.once("dialog", (d) => void d.dismiss());
    await target.getByRole("button", { name: "削除" }).click();
    await expect(target).toHaveCount(1);
    page.once("dialog", (d) => void d.accept());
    await target.getByRole("button", { name: "削除" }).click();
    await expect(target).toHaveCount(0);
  });

  test("TC-UI-050 画面の検証: javascript: の URL と未選択のユーザー情報は保存しない", async ({
    page,
  }) => {
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    const before = (await listAll(page)).links.length;
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
    expect((await listAll(page)).links.length).toBe(before);
  });

  test("TC-SET-040 ウィジェットを複数作り、それぞれダッシュボードに置ける。見出しを変える・削除すると反映される", async ({
    page,
  }) => {
    const secondTitle = `${PREFIX}申請`;
    const renamedTitle = `${PREFIX}申請・手続き`;
    const settings = page.locator("shared-links-page");

    // 管理画面でウィジェットを作る
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    await settings.getByRole("button", { name: /ウィジェットを作る/ }).click();
    await settings.getByLabel("ウィジェットの見出し").first().fill(secondTitle);
    await settings.getByRole("button", { name: "作る", exact: true }).click();
    await expect(settings.getByRole("tab", { selected: true })).toContainText(secondTitle);
    await createLink(page, { title: `${PREFIX}経費精算`, url: `${TARGET}/keihi` }, secondTitle);
    await createLink(page, { title: `${PREFIX}勤怠`, url: `${TARGET}/kintai` }, SET_TITLE);

    // 2 つともダッシュボードに置け、それぞれのリンクだけが出る。
    // 1 つ置いても、ほかの共有リンクのウィジェットはメニューから消えない（再読み込みしなくても）
    await gotoDashboard(page);
    for (const title of [SET_TITLE, secondTitle]) {
      if ((await widgetOf(page, title).count()) > 0) {
        await widgetOf(page, title).locator('a[name="dclose"]').click({ force: true });
        await confirmYes(page);
        await expect(widgetOf(page, title)).toHaveCount(0);
      }
    }
    await page.reload();
    const menu = page.locator('.widgetsList a[data-name="SharedLinks"]');
    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    await menu.filter({ hasText: SET_TITLE }).click();
    await expect(widgetOf(page, SET_TITLE).locator(".sharedLinksWidget")).toBeVisible();
    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    await expect(menu.filter({ hasText: SET_TITLE })).toBeHidden();
    await expect(menu.filter({ hasText: secondTitle })).toBeVisible();
    await menu.filter({ hasText: secondTitle }).click();
    await expect(widgetOf(page, secondTitle).locator(".sharedLinksWidget")).toBeVisible();
    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    await expect(menu.filter({ hasText: SET_TITLE })).toBeHidden();
    await expect(menu.filter({ hasText: secondTitle })).toBeHidden();
    await page.keyboard.press("Escape");
    await gotoDashboard(page);
    const first = widgetOf(page, SET_TITLE);
    const second = widgetOf(page, secondTitle);
    await expect(first.locator(".dashboardTitle")).toHaveText(SET_TITLE);
    await expect(second.locator(".dashboardTitle")).toHaveText(secondTitle);
    await expect(linkIn(first, `${PREFIX}勤怠`)).toBeVisible();
    await expect(linkIn(first, `${PREFIX}経費精算`)).toHaveCount(0);
    await expect(linkIn(second, `${PREFIX}経費精算`)).toBeVisible();
    await expect(linkIn(second, `${PREFIX}勤怠`)).toHaveCount(0);

    // 見出しを変えると、ダッシュボードの見出しも変わる（置いたウィジェットはそのまま）
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    await selectSetTab(page, secondTitle);
    const header = settings.getByTestId("shared-link-set-header");
    await header.getByLabel("ウィジェットの見出し").fill(renamedTitle);
    await header.getByRole("button", { name: "保存" }).click();
    await expect(page.getByRole("status")).toContainText("保存しました");
    await gotoDashboard(page);
    await expect(widgetOf(page, renamedTitle).locator(".dashboardTitle")).toHaveText(renamedTitle);

    // 削除すると、ダッシュボードからも消える
    await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
    await selectSetTab(page, renamedTitle);
    page.once("dialog", (d) => void d.accept());
    await settings.getByRole("button", { name: "このウィジェットを削除" }).click();
    await expect(
      settings.getByRole("tab", { name: new RegExp(`^${literal(renamedTitle)}`) }),
    ).toHaveCount(0);
    await gotoDashboard(page);
    await expect(widgetOf(page, SET_TITLE)).toHaveCount(1);
    await expect(widgetOf(page, renamedTitle)).toHaveCount(0);
  });

  test("TC-WDG-001 TC-WDG-002 TC-WDG-003 TC-WDG-004 ウィジェットの追加から置け、見出しはウィジェットの見出しで、追加済みなら出ない", async ({
    page,
  }) => {
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await createLink(page, { title: `${PREFIX}ウィジェット確認`, url: `${TARGET}/w` });
    await gotoDashboard(page);
    // 既に置いてあれば外し、メニューから置き直す
    const widget = widgetOf(page);
    if ((await widget.count()) > 0) {
      await widget.locator('a[name="dclose"]').click({ force: true });
      await confirmYes(page);
      await expect(widget).toHaveCount(0);
      await gotoDashboard(page);
    }
    // TC-WDG-004: 枠を調整する JS と CSS を読み込んでいる
    await expect(
      page.locator('script[src*="modules/Home/resources/SharedLinksWidget.js"]'),
    ).toHaveCount(1);
    await expect(
      page.locator('link[href*="modules/Home/resources/SharedLinksWidget.css"]'),
    ).toHaveCount(1);

    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    const menuItem = page
      .locator('.widgetsList a[data-name="SharedLinks"]')
      .filter({ hasText: SET_TITLE });
    await expect(menuItem).toHaveText(SET_TITLE);
    await menuItem.click();

    await expect(widget.locator(".dashboardTitle")).toHaveText(SET_TITLE);
    await expect(linkIn(widget, `${PREFIX}ウィジェット確認`)).toBeVisible();

    // TC-WDG-002: 再読み込み後、追加済みのタブでは選べない
    await page.reload();
    await page.getByRole("button", { name: "ウィジェットの追加" }).click();
    await expect(
      page.locator('.widgetsList a[data-name="SharedLinks"]').filter({ hasText: SET_TITLE }),
    ).toHaveCount(0);
  });

  test("TC-WDG-013 TC-WDG-015 管理画面で追加したリンクがウィジェットの更新で出る。行の余白は崩れない", async ({
    page,
  }) => {
    const widget = await openDashboardWithWidget(page);
    const title = `${PREFIX}更新で出る`;
    await expect(widget).not.toContainText(title);
    await createLink(page, { title, url: `${TARGET}/refresh` });

    await refreshWidget(widget);
    const row = linkIn(widget, title);
    await expect(row).toBeVisible();
    // vtiger の共通 CSS（.group）の margin が付かない
    expect(await row.evaluate((a) => getComputedStyle(a).margin)).toBe("0px");
  });

  test("TC-WDG-016 ウィジェットを大きくすると中身も広がり、スクロールバーが出ない", async ({
    page,
  }) => {
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    for (let i = 0; i < 5; i++) {
      await createLink(page, { title: `${PREFIX}行${i}`, url: `${TARGET}/row${i}`, description: "説明" });
    }
    const widget = await openDashboardWithWidget(page);
    await refreshWidget(widget);
    await expect(linkIn(widget, `${PREFIX}行4`)).toBeAttached();
    const content = widget.locator(".dashboardWidgetContent");

    await widget.hover();
    const box = await widget.locator(".gs-resize-handle").boundingBox();
    expect(box).not.toBeNull();
    await page.mouse.move(box!.x + 5, box!.y + 5);
    await page.mouse.down();
    await page.mouse.move(box!.x + 300, box!.y + 600, { steps: 15 });
    await page.mouse.up();

    await expect
      .poll(async () => content.evaluate((e) => e.scrollHeight <= e.clientHeight))
      .toBe(true);
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
    await refreshWidget(widget);
    const before = page.url();

    const [popup] = await Promise.all([
      context.waitForEvent("page"),
      linkIn(widget, title).click(),
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
    const linkId = await createLink(page, {
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
    await skipUnlessPostAllowed(page, linkId);
    const widget = await openDashboardWithWidget(page);
    await refreshWidget(widget);
    const before = page.url();

    const [popup] = await Promise.all([
      page.waitForEvent("popup"),
      linkIn(widget, title).click(),
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
    const [w, h] = await popup.evaluate(() => [window.outerWidth, window.outerHeight]);
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
    const linkId = await createLink(page, {
      title,
      url: `${TARGET}/post-tab`,
      method: "POST",
      params: [{ name: "a", value_type: "fixed", value: "1" }],
    });
    await skipUnlessPostAllowed(page, linkId);
    const widget = await openDashboardWithWidget(page);
    await refreshWidget(widget);

    const [popup] = await Promise.all([
      context.waitForEvent("page"),
      linkIn(widget, title).click(),
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
    const blockedTarget = "https://e2e-shared-link-blocked.test";
    const requests: Request[] = [];
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
    await selectSetTab(page, SET_TITLE);
    const row = page.getByTestId("shared-link-row").filter({ hasText: title });
    await expect(row.getByRole("img", { name: /POST の送り先が許可されていない/ })).toBeVisible();

    // ウィジェット: 押しても開かず、通知が出る
    const widget = await openDashboardWithWidget(page);
    await refreshWidget(widget);
    const pagesBefore = context.pages().length;
    await linkIn(widget, title).click();
    await expect(page.getByText("このリンクの送り先は許可されていないため開けません")).toBeVisible();
    expect(context.pages()).toHaveLength(pagesBefore);
    expect(requests).toHaveLength(0);
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
    await refreshWidget(widget);

    const [popup] = await Promise.all([
      context.waitForEvent("page"),
      linkIn(widget, title).click(),
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
    const { linkid } = await testSet(page);

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
    await createLink(page, {
      title: links.role,
      url: `${TARGET}/vis`,
      ...targets("Roles", roleId("E2E営業1課長")),
    });
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
        html = await widgetHtml(page, linkid);
      } else {
        const session = await loginAs(browser, userName);
        context = session.context;
        html = await widgetHtml(session.page, linkid);
      }
      try {
        expect(html, `${userName} の応答に <shared-links-widget> がある`).toContain(
          "<shared-links-widget",
        );
        for (const key of Object.keys(links) as (keyof typeof links)[]) {
          const shouldShow = visible.includes(key);
          expect(
            html.includes(links[key]),
            `${userName} に ${key} が${shouldShow ? "出る" : "出ない"}`,
          ).toBe(shouldShow);
        }
        // TC-RES-008: ユーザー情報は見ている本人の値
        expect(html).toContain(`login=${userName}`);
      } finally {
        await context?.close();
      }
    }
  });

  test("TC-UI-002 TC-API-001 管理者以外は管理画面も API も使えない", async ({ browser }) => {
    const { context, page } = await loginAs(browser, "e2e_rep_a");
    try {
      await page.goto(settingsUrl({ module: "SharedLinks", view: "List" }));
      await expect(page.locator("shared-links-page")).toHaveCount(0);

      await page.goto(url("index.php?module=Home&view=DashBoard"));
      expect((await api(page, "list")).success).toBe(false);
      expect((await api(page, "save_set", { title: `${PREFIX}一般ユーザー` })).success).toBe(false);
      expect(
        (
          await api(page, "save", {
            set_id: "1",
            link: JSON.stringify({ title: `${PREFIX}一般ユーザー`, url: `${TARGET}/x` }),
          })
        ).success,
      ).toBe(false);
    } finally {
      await context.close();
    }
  });

  test("TC-API-004 CSRF トークンの無い保存は受け付けない", async ({ page }) => {
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    const title = `${PREFIX}CSRFなし`;
    const set = await testSet(page);
    for (const form of [
      { mode: "save", set_id: String(set.id), link: JSON.stringify({ title, url: `${TARGET}/csrf` }) },
      { mode: "save_set", title },
    ]) {
      const res = await page.request.post(url("index.php"), {
        form: { module: "SharedLinks", parent: "Settings", api: "SharedLinkAPI", ...form },
      });
      expect(await res.text()).not.toContain('"saved":true');
    }
    const { sets, links } = await listAll(page);
    expect(links.some((l) => l.title === title)).toBe(false);
    expect(sets.some((s) => s.title === title)).toBe(false);
  });

  test("TC-API-006 未ログインでは一覧を返さない", async ({ browser, page }) => {
    await page.goto(url("index.php?module=Home&view=DashBoard"));
    await createLink(page, { title: `${PREFIX}未ログイン確認`, url: `${TARGET}/anon` });
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
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
