import { test, expect } from "../../fixtures/isolated";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, settingsUrl, confirmYes } from "../../utils/settings";
import { createAccount, deleteViaDetail } from "../../utils/listview";
import { apiSession } from "../../utils/api";
import { frQuery } from "../../model/fetcher";

/**
 * E-03 ワークフロー (自動化 > ワークフロー)
 *
 * 一覧＋作成/編集＋トグルON/OFF の代表。作成→名称変更→無効化(トグルOFF)を直列で行い、
 * それぞれが一覧に反映されることを検証する。作成したワークフロー名そのものを追跡する。
 */
const listParamsShared = { module: "Workflows", view: "List" };

/**
 * 一覧の行アクションからワークフローを削除する(後始末)。
 *
 * 無効化(トグル OFF)だけでは行が残り続け、一覧の 1 ページ目(20 件)が
 * テストのゴミで埋まって「作成した行が見つからない」という別テストの失敗を招く。
 * 削除アイコンは opacity:0 でホバー時に見える作りなので hover してから押す。
 */
async function deleteWorkflowByName(
  page: import("@playwright/test").Page,
  name: string
): Promise<void> {
  try {
    await gotoSettings(page, listParamsShared);
    const target = page
      .locator("tr.listViewEntries")
      .filter({ hasText: name })
      .first();
    if ((await target.count()) === 0) return;
    await target.hover();
    await target.locator("a.deleteRecordButton").click({ force: true });
    await confirmYes(page);
    await expect(
      page.locator("tr.listViewEntries").filter({ hasText: name })
    ).toHaveCount(0, { timeout: 15000 });
  } catch {
    /* 後始末のため失敗は無視 */
  }
}

test.describe.serial("管理: ワークフロー (Workflows)", () => {
  const listParams = { module: "Workflows", view: "List" };
  // 追加名と編集名は互いに部分文字列にならないようにする(hasText の部分一致で
  // 「旧名が消えたこと」を検証するため)。
  const token = generateRandomString(5);
  const name = `E2Eワークフローadd${token}`;
  const editedName = `E2Eワークフローedit${token}`;

  const row = (page: import("@playwright/test").Page, text: string) =>
    page.locator("tr.listViewEntries").filter({ hasText: text });

  test("ワークフローの追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    await page.getByText("新しいワークフロー").first().click();
    await page.locator('input[name="workflowname"]').fill(name);
    await page.locator("button.saveButton").click();
    // 保存の完了を待たずに一覧へ遷移すると反映前の一覧を見てしまう(CI で flaky)
    await page.waitForLoadState("networkidle");

    // 一覧に作成したワークフローが現れること
    await gotoSettings(page, listParams);
    await expect(row(page, name)).toBeVisible({ timeout: 15000 });
  });

  test("ワークフローの編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    await row(page, name).first().click();
    const nameInput = page.locator('input[name="workflowname"]');
    await expect(nameInput).toBeVisible();
    await nameInput.fill(editedName);
    await page.locator("button.saveButton").click();
    await page.waitForLoadState("networkidle");

    // 一覧に変更後の名称が現れ、元の名称は消えていること
    await gotoSettings(page, listParams);
    await expect(row(page, editedName)).toBeVisible({ timeout: 15000 });
    await expect(row(page, name)).toHaveCount(0);
  });

  test("ワークフローの無効化(トグルOFF)", async ({ page }) => {
    await gotoSettings(page, listParams);

    const target = row(page, editedName).first();
    const toggle = target.locator(".bootstrap-switch");
    // 初期状態は有効(ON)
    await expect(toggle).toHaveClass(/bootstrap-switch-on/);

    await toggle.click();
    await expect(toggle).toHaveClass(/bootstrap-switch-off/);

    // リロード後も無効(OFF)が保持されていること
    await gotoSettings(page, listParams);
    await expect(
      row(page, editedName).first().locator(".bootstrap-switch")
    ).toHaveClass(/bootstrap-switch-off/);
  });

  test("後始末: 作成したワークフローを削除する", async ({ page }) => {
    // 共有 CRM に残すと一覧の 1 ページ目を埋めて後続実行の妨げになる。
    await deleteWorkflowByName(page, editedName);
    await deleteWorkflowByName(page, name);
    await gotoSettings(page, listParams);
    await expect(row(page, editedName)).toHaveCount(0);
  });
});

/**
 * ワークフローの「実発火」 — TEST_COVERAGE P0-4
 *
 * 上の describe は設定 CRUD だけを見ており、**アクションが実際に動くか**は
 * これまで誰も検証していなかった。`VTWorkflowManager` の
 * ON_FIRST_SAVE(1) / ON_EVERY_SAVE(3) / ON_MODIFY(4) は保存時に即時実行される
 * (cron を要する ON_SCHEDULE(6) とは別) ため、E2E で完結して検証できる。
 *
 * 流れ: 「顧客企業の更新(作成時含む)」で website を固定値に上書きするワークフローを
 * 作り、顧客企業を 1 件作成 → API で website が上書きされていることを確認する。
 *
 * 画面構造(実機/テンプレートで確認):
 *  - ワークフロー作成: view=Edit&mode=V7Edit の form#workflow_edit
 *      input[name="workflowname"] / select[name="module_name"](select2) /
 *      input[name="workflow_trigger"](1=作成時, 3=更新時(作成含む), 6=定期)
 *  - タスク追加:       view=EditTask&type=VTUpdateFieldsTask&for_workflow=<id> の form#saveTask
 *      input[name="summary"](タスク名) / #addFieldBtn で行追加 /
 *      .conditionRow select[name="fieldname"](option の data-field-name が項目名) /
 *      .conditionRow input[name="fieldValue"] / button[type=submit].btn-success
 *  - 保存時に Edit.js の preSaveVTUpdateFieldsTask が行から
 *    field_value_mapping(JSON)を組み立て直すため、hidden の直接設定では通らない。
 *    行の UI を実際に埋める必要がある。
 */
test.describe.serial("管理: ワークフローの実発火 (フィールド更新)", () => {
  const token = generateRandomString(5);
  const wfName = `E2E発火${token}`;
  const expectedWebsite = `https://e2e-wf-${token}.example.com`;
  let workflowId = "";

  /** select2 で隠れている select に値を設定して change を発火させる。 */
  const setSelect = async (
    page: import("@playwright/test").Page,
    selector: string,
    value: string
  ) => {
    await page.evaluate(
      ({ selector, value }) => {
        const sel = document.querySelector(selector) as HTMLSelectElement | null;
        if (!sel) throw new Error(`select が見つかりません: ${selector}`);
        sel.value = value;
        sel.dispatchEvent(new Event("change", { bubbles: true }));
        // select2 は jQuery のイベントを購読するため両方投げる
        const jq = (window as unknown as { jQuery?: (e: Element) => { trigger: (n: string) => void } }).jQuery;
        if (jq) jq(sel).trigger("change");
      },
      { selector, value }
    );
  };

  test("保存時トリガ + フィールド更新タスクを設定できる", async ({ page }) => {
    await page.goto(settingsUrl({ module: "Workflows", view: "Edit", mode: "V7Edit" }));
    await page.waitForLoadState("domcontentloaded");

    await page.locator('input[name="workflowname"]').fill(wfName);
    // 対象モジュールを顧客企業へ(既定は案件)
    await setSelect(page, 'select[name="module_name"]', "Accounts");
    // トリガ: 更新時(作成時を含む) = ON_EVERY_SAVE(3)。既定で選択されているが明示する。
    await page
      .locator('input[type="radio"][name="workflow_trigger"][value="3"]')
      .check();

    await page.locator("button.saveButton").click();
    await page.waitForLoadState("networkidle");
    // 保存後は詳細ではなく一覧へ戻る(record= は付かない)。作成した行を開いて ID を得る。
    await page.waitForURL(/view=List/, { timeout: 20000 });
    const created = page
      .locator("tr.listViewEntries")
      .filter({ hasText: wfName })
      .first();
    await expect(created, "作成したワークフローが一覧に出ること").toBeVisible();
    await created.click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 20000 });
    workflowId = page.url().match(/record=(\d+)/)?.[1] ?? "";
    expect(workflowId, "ワークフロー ID が取得できること").not.toBe("");

    // --- フィールド更新タスクを追加 ---
    // タスク編集は必ず「アクションの追加」からモーダル(loadPageContentOverlay)で開く。
    // 直接 view=EditV7Task へ goto すると Edit.js の
    // registerVTUpdateFieldsTaskEvents が走らず #addFieldBtn が無反応になる。
    await page
      .locator("button.dropdown-toggle", { hasText: "アクションの追加" })
      .first()
      .click();
    await page
      .locator('a[data-url*="type=VTUpdateFieldsTask"]')
      .first()
      .click();

    const modal = page.locator(".modal-content:visible").first();
    await expect(modal).toBeVisible({ timeout: 15000 });
    await modal.locator('input[name="summary"]').fill(`項目更新${token}`);

    // 更新対象の項目行を 1 行追加する(新規タスクでは行が無い)。
    // モーダル読み込み直後はハンドラ登録が間に合わないことがあるため、
    // 行が出るまでクリックを再試行する(3-05_リスト条件 と同じ既知パターン)。
    const rows = modal.locator("#save_fieldvaluemapping .conditionRow");
    await expect
      .poll(
        async () => {
          if ((await rows.count()) === 0) {
            await modal.locator("#addFieldBtn").click().catch(() => {});
          }
          return rows.count();
        },
        { timeout: 20000, intervals: [500, 1000, 1000, 2000] }
      )
      .toBeGreaterThan(0);
    const row = rows.first();

    // option の data-field-name から website を選ぶ(option value は
    // workflow_columnname という内部表現なので直接書かない)
    const fieldSelect =
      ".modal-content #save_fieldvaluemapping .conditionRow select[name='fieldname']";
    const fieldValue = await page.evaluate((sel) => {
      const el = document.querySelector(sel) as HTMLSelectElement | null;
      if (!el) return null;
      const opt = Array.from(el.options).find(
        (o) => o.dataset.fieldName === "website"
      );
      return opt ? opt.value : null;
    }, fieldSelect);
    expect(fieldValue, "website の選択肢があること").toBeTruthy();
    await setSelect(page, fieldSelect, fieldValue!);

    // 項目選択で値 UI が差し替わるため、入力できるようになるまで待ってから埋める。
    // 保存時に Edit.js の getValues が読むのは [data-value="value"] の要素
    // (テンプレート初期状態の name="fieldValue" は差し替えで失われる)。
    const valueInput = row.locator('[data-value="value"]').first();
    await expect(valueInput).toBeVisible();
    await valueInput.fill(expectedWebsite);
    await expect(valueInput).toHaveValue(expectedWebsite);

    // 保存ボタンは form#saveTask 配下(モーダル下部の固定バー)にあるため
    // .modal-content スコープでは掴めない。form でスコープする。
    // モーダルは縦スクロール領域を持ち、ボタンがビューポート外にいることがあるので
    // スクロールして可視にしてから押す。
    // モーダル内の保存ボタン(背後の画面の button.saveButton とは別物で name 属性が無い)。
    // モーダルは showVerticalScroll による独自スクロール領域を持ち、Playwright の
    // クリック前判定(安定待ち/スクロール)が収束しないため force で押す。
    // 非表示のテンプレート側フォームにも同じ submit ボタンがあるため :visible で絞る。
    const saveTask = page
      .locator('#saveTask button[type="submit"]:visible')
      .first();
    await expect(saveTask).toBeVisible({ timeout: 10000 });
    await saveTask.click({ force: true });

    // 保存後、アクション一覧に追加したタスクが並ぶ
    await expect(page.getByText(`項目更新${token}`).first()).toBeVisible({
      timeout: 20000,
    });
  });

  test("顧客企業を保存するとワークフローが発火して項目が更新される", async ({
    page,
  }) => {
    const accountName = `[E2E-WF] ${token}`;
    const recordId = await createAccount(page, accountName);

    try {
      // 保存時にワークフローが走り website が上書きされている。
      // Webservice ID の prefix は環境依存なので、名前で query して値を取る。
      const session = await apiSession();
      await expect
        .poll(
          async () => {
            const rows = await frQuery(
              session,
              `SELECT website FROM Accounts WHERE accountname = '${accountName}';`
            );
            return rows?.[0]?.website ?? "";
          },
          { timeout: 15000, intervals: [500, 1000, 2000] }
        )
        .toBe(expectedWebsite);
    } finally {
      // 後始末は「ワークフローを消す → レコードを消す」の順。
      // 有効なまま残すと以降の全テストの Accounts 保存で発火し続けるため、
      // 無効化ではなく削除する。
      await deleteWorkflowByName(page, wfName);
      await deleteViaDetail(page, "Accounts", recordId);
    }
  });

});
