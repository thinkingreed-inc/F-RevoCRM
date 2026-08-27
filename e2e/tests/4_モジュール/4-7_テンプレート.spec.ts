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
      await page.waitForLoadState("networkidle");

      // 一覧から消えている(MassDelete の固有実装が通った)
      await gotoTemplateList(page, module);
      await expect(
        page.locator("tr.listViewEntries").filter({ hasText: name })
      ).toHaveCount(0);
    });
  }

  test("EmailTemplates: システムテンプレートは一括削除できない", async ({
    page,
  }) => {
    test.setTimeout(120000);
    // dump には systemtemplate=1 のテンプレートが焼き込まれている。
    // 一覧では「システム」列/バッジで判別できないので、
    // 詳細を開かずに済むよう API 不要の方法として ListView の行に付く
    // data-id を使い、削除後に件数が減っていないことで確認する。
    await gotoTemplateList(page, "EmailTemplates");
    const rows = page.locator("tr.listViewEntries");
    const before = await rows.count();
    expect(before, "テンプレートが 1 件以上あること").toBeGreaterThan(0);

    // システムテンプレートを特定する: 詳細ページの削除導線が無いものがそれ。
    // 行のリンク先(data-recordurl)から record id を取り、
    // Edit 画面の .isSystemTemplate(hidden input)で判定する。
    let systemRow = -1;
    for (let i = 0; i < before; i++) {
      const rec = await rows
        .nth(i)
        .getAttribute("data-recordurl")
        .then((u) => u?.match(/record=(\d+)/)?.[1]);
      if (!rec) continue;
      await page.goto(
        url(
          `index.php?module=EmailTemplates&view=Edit&record=${rec}&app=TOOLS`
        )
      );
      const flag = await page
        .locator("input.isSystemTemplate")
        .first()
        .getAttribute("value");
      if (flag === "1" || flag === "true") {
        systemRow = i;
        break;
      }
      await gotoTemplateList(page, "EmailTemplates");
    }
    expect(
      systemRow,
      "dump にシステムテンプレートが含まれること"
    ).toBeGreaterThanOrEqual(0);

    await gotoTemplateList(page, "EmailTemplates");
    await rows.nth(systemRow).locator("input.listViewEntriesCheckBox").check();
    const del = page.locator("#EmailTemplates_listView_massAction_LBL_DELETE");
    await expect(del).toBeEnabled();
    await del.click();
    await confirmYes(page);
    await page.waitForLoadState("networkidle");

    // 削除されていない(件数が変わらない)
    await gotoTemplateList(page, "EmailTemplates");
    await expect(page.locator("tr.listViewEntries")).toHaveCount(before);
  });
});
