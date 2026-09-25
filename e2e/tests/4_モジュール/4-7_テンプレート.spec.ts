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

  /**
   * テンプレートを 1 件作成し、名前 / 説明 / 作成された record id を返す。
   * 保存後は詳細画面へ遷移するので URL から record を取る。
   */
  async function createTemplate(
    page: import("@playwright/test").Page,
    module: "EmailTemplates" | "PDFTemplates"
  ): Promise<{ name: string; description: string; recordId: string }> {
    const token = generateRandomString(6);
    const name = `E2Etpl${token}`;
    const description = `E2E説明${token}`;
    await page.goto(url(`index.php?module=${module}&view=Edit&app=TOOLS`));
    await page.waitForLoadState("networkidle");
    await page.locator('input[name="templatename"]').fill(name);
    await page.locator('textarea[name="description"]').fill(description);
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
    const recordId = page.url().match(/record=(\d+)/)?.[1] ?? "";
    return { name, description, recordId };
  }

  /**
   * 一覧の行アクション(「…」)を開き、開いたメニューを返す。
   *
   * vtiger は dropdown を開くとメニュー要素を **body 直下へ移動** し、
   * 元の行は `data('original-menu')` から辿れるようにする
   * (`app.helper.getDropDownmenuParent`)。削除ハンドラはこの仕組みで
   * 対象 record id を得るため、**メニューを実際に開かないと削除が実行されない**。
   * また移動後のメニューは行の中には無いのでページ全体から探す。
   * トグル自体は opacity で隠れており通常クリックが届かないので dispatchEvent で叩く
   * (force クリックは座標指定のため隣の行のトグルを押してしまう)。
   */
  async function openRowMenu(
    page: import("@playwright/test").Page,
    row: import("@playwright/test").Locator
  ): Promise<import("@playwright/test").Locator> {
    await row.hover();
    await row.locator(".dropdown-toggle").first().dispatchEvent("click");
    const menu = page.locator("ul.dropdown-menu:visible").first();
    await expect(menu).toBeVisible({ timeout: 20000 });
    return menu;
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
      const { name } = await createTemplate(page, module);

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

  for (const module of ["EmailTemplates", "PDFTemplates"] as const) {
    test(`${module}: 作成 → 詳細表示 → 編集 → 単体削除`, async ({ page }) => {
      test.setTimeout(120000);
      const created = await createTemplate(page, module);
      expect(created.recordId, "保存後に record id が取れること").not.toBe("");

      // === 詳細画面: 入力した内容が表示され、本文プレビュー(iframe)が出る ===
      await page.goto(
        url(
          `index.php?module=${module}&view=Detail&record=${created.recordId}&app=TOOLS`
        )
      );
      await page.waitForLoadState("networkidle");
      const detail = page.locator("td.fieldValue");
      await expect(detail.filter({ hasText: created.name }).first()).toBeVisible(
        { timeout: 20000 }
      );
      await expect(
        detail.filter({ hasText: created.description }).first(),
        "説明が詳細に表示されること"
      ).toBeVisible({ timeout: 20000 });
      // 本文は iframe(#TemplateIFrame)に流し込まれる。既定の本文が入るので
      // iframe 自体が描画されていることを本文プレビューの成立とみなす。
      await expect(
        page.locator("#TemplateIFrame"),
        "本文プレビューの iframe が描画されること"
      ).toBeVisible({ timeout: 20000 });

      // === 編集: 一覧の行メニュー「編集」から名前を変える ===
      const renamed = `${created.name}R`;
      await gotoTemplateList(page, module);
      const row = page
        .locator("tr.listViewEntries")
        .filter({ hasText: created.name })
        .first();
      await expect(row).toBeVisible({ timeout: 20000 });
      // 「編集」は data-url に遷移先を持つ(ハンドラは行メニュー前提で
      // dispatchEvent では遷移しないことがあるため、URL を読んで遷移する)。
      const editUrl = await row
        .locator('a[name="editlink"]')
        .first()
        .getAttribute("data-url");
      expect(editUrl, "行メニューの編集リンクが遷移先を持つこと").toContain(
        "view=Edit"
      );
      await page.goto(url(editUrl!));
      await page.waitForLoadState("networkidle");
      const nameInput = page.locator('input[name="templatename"]');
      await expect(nameInput).toBeVisible({ timeout: 20000 });
      await nameInput.fill(renamed);
      await page.locator("button.saveButton").first().click();
      await page.waitForLoadState("networkidle");

      // 詳細に変更が反映されていること
      await page.goto(
        url(
          `index.php?module=${module}&view=Detail&record=${created.recordId}&app=TOOLS`
        )
      );
      await page.waitForLoadState("networkidle");
      await expect(
        page.locator("td.fieldValue").filter({ hasText: renamed }).first(),
        "編集した名前が詳細に反映されること"
      ).toBeVisible({ timeout: 20000 });

      // === 単体削除: 行メニュー「削除」 ===
      await gotoTemplateList(page, module);
      const target = page
        .locator("tr.listViewEntries")
        .filter({ hasText: renamed })
        .first();
      await expect(target).toBeVisible({ timeout: 20000 });
      const menu = await openRowMenu(page, target);
      // メニューは開いているが項目自体は可視判定を満たさないため dispatchEvent で叩く。
      // (メニューが開いていれば data('original-menu') が張られており、
      //  ハンドラは対象行の record id を正しく解決できる)
      await menu.locator("a.deleteRecordButton").first().dispatchEvent("click");
      await confirmYes(page);

      // 削除は Ajax。goto で中断しないよう、まずその場で消えるのを待つ
      await expect(
        page.locator("tr.listViewEntries").filter({ hasText: renamed })
      ).toHaveCount(0, { timeout: 30000 });
      await gotoTemplateList(page, module);
      await expect(
        page.locator("tr.listViewEntries").filter({ hasText: renamed })
      ).toHaveCount(0, { timeout: 15000 });
    });
  }
});
