import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import { confirmYes } from "../../utils/settings";

/**
 * メールテンプレート / PDFテンプレート — 機能一覧 36 / 37
 *
 * どちらもヘッダーメニュー(TOOLS アプリ)から遷移する通常モジュール。
 * 一覧が正しく開く(タイトル・権限エラー無し・追加ボタン)ことのスモークテスト。
 *
 * 一括削除は **モジュール固有実装** (TEST_COVERAGE P0-B / B3):
 *   modules/EmailTemplates/actions/MassDelete.php (80 行)
 *   modules/PDFTemplates/actions/MassDelete.php   (80 行)
 * どちらも `Vtiger_Mass_Action` を直接継承し、独自の `getRecordsListFromRequest()` と
 * **システムテンプレート(systemtemplate=1)は削除させない** 分岐を持つ。
 * 共通機能側の一括削除テスト(3-26_ゴミ箱)は Accounts 固定なので、
 * この固有実装は誰も通していなかった。
 */
test.describe("テンプレート系モジュール", () => {
  test("メールテンプレート一覧が表示される", async ({ page }) => {
    await page.goto(url("index.php?module=EmailTemplates&view=List&app=TOOLS"));
    await page.waitForLoadState("networkidle");
    await expect(page).toHaveTitle(/メールテンプレート/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
  });

  test("PDFテンプレート一覧が表示される", async ({ page }) => {
    await page.goto(url("index.php?module=PDFTemplates&view=List&app=TOOLS"));
    await page.waitForLoadState("networkidle");
    await expect(page).toHaveTitle(/PDFテンプレート/);
    await expect(page.locator("text=権限がありません")).toHaveCount(0);
  });

  /** テンプレートを 1 件作成して templatename を返す。 */
  async function createTemplate(
    page: import("@playwright/test").Page,
    module: "EmailTemplates" | "PDFTemplates"
  ): Promise<string> {
    const name = `E2Etpl${generateRandomString(6)}`;
    await page.goto(url(`index.php?module=${module}&view=Edit&app=TOOLS`));
    await page.waitForLoadState("networkidle");
    await page.locator('input[name="templatename"]').fill(name);
    // 対象モジュール(select2 だが native select はそのまま操作できる)
    await page
      .locator('select[name="modulename"]')
      .selectOption({ index: 1 });
    // 件名は EmailTemplates のみ(PDFTemplates はテンプレート側でコメントアウト済み)
    if (module === "EmailTemplates") {
      await page.locator('input[name="subject"]').fill(name);
    }
    await page.locator("button.saveButton").first().click();
    await page.waitForLoadState("networkidle");
    return name;
  }

  /** 一覧(list 表示)を開く。grid 表示だと一括削除ボタンが hide されるため明示する。 */
  async function gotoTemplateList(
    page: import("@playwright/test").Page,
    module: "EmailTemplates" | "PDFTemplates"
  ) {
    await page.goto(
      url(`index.php?module=${module}&view=List&app=TOOLS&viewType=list`)
    );
    await page.waitForLoadState("networkidle");
  }

  for (const module of ["EmailTemplates", "PDFTemplates"] as const) {
    test(`${module}: ユーザー作成テンプレートを一括削除できる`, async ({
      page,
    }) => {
      test.setTimeout(120000);
      const name = await createTemplate(page, module);

      await gotoTemplateList(page, module);
      const row = page
        .locator("tr.listViewEntries")
        .filter({ hasText: name })
        .first();
      await expect(row, "作成したテンプレートが一覧に出ること").toBeVisible({
        timeout: 20000,
      });
      await row.locator("input.listViewEntriesCheckBox").check();

      const del = page.locator(`#${module}_listView_massAction_LBL_DELETE`);
      await expect(del).toBeEnabled();
      await del.click();
      await confirmYes(page);

      // 一括削除は Ajax(完了後に一覧が再読込される)。ここで goto してしまうと
      // POST を中断して「削除されないまま次の一覧を見る」ことになり CI で flaky に
      // なるため、まずその場で行が消えるのを待つ。
      await expect(
        page.locator("tr.listViewEntries").filter({ hasText: name })
      ).toHaveCount(0, { timeout: 30000 });

      // 一覧を開き直しても消えている(MassDelete の固有実装が通った)
      await gotoTemplateList(page, module);
      await expect(
        page.locator("tr.listViewEntries").filter({ hasText: name })
      ).toHaveCount(0, { timeout: 15000 });
    });
  }

  test("EmailTemplates: システムテンプレートは一括削除できない", async ({
    page,
  }) => {
    test.setTimeout(120000);
    // dump に焼き込まれたシステムテンプレート(systemtemplate=1)を対象にする。
    // 一覧では systemtemplate を判別できないため固定レコードを使い、
    // dump が差し替わった場合に気付けるよう名前とフラグの両方を assert する
    // (一覧の全行の Edit 画面を順に開いて探す実装は CI で 120 秒を超えたためやめた)。
    const target = { id: "16", name: "Invite Users" };

    await page.goto(
      url(
        `index.php?module=EmailTemplates&view=Edit&record=${target.id}&app=TOOLS`
      )
    );
    await page.waitForLoadState("networkidle");
    expect(
      await page.locator('input[name="templatename"]').inputValue(),
      "対象がシステムテンプレートであること(dump 差し替え検知)"
    ).toBe(target.name);
    expect(
      await page
        .locator("input.isSystemTemplate")
        .first()
        .getAttribute("value"),
      "systemtemplate=1 のテンプレートであること"
    ).toBe("1");

    // 一覧でそのシステムテンプレートを選んで一括削除を試みる
    await gotoTemplateList(page, "EmailTemplates");
    const rows = page.locator("tr.listViewEntries");
    const row = rows.filter({ hasText: target.name }).first();
    await expect(row).toBeVisible({ timeout: 20000 });
    await row.locator("input.listViewEntriesCheckBox").check();
    const del = page.locator("#EmailTemplates_listView_massAction_LBL_DELETE");
    await expect(del).toBeEnabled();
    await del.click();
    await confirmYes(page);
    await page.waitForLoadState("networkidle");

    // 削除されていない(システムテンプレートは保護される)
    await gotoTemplateList(page, "EmailTemplates");
    await expect(
      rows.filter({ hasText: target.name }),
      "システムテンプレートは削除されず一覧に残ること"
    ).toHaveCount(1, { timeout: 15000 });
  });
});
