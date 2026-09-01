import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";
import { url } from "../../utils/util";

/**
 * D-05 選択肢の連動設定 (モジュール管理 > 選択肢の連動設定)
 *
 * 一覧CRUD型。既定モジュールの選択肢フィールドから「連動元」「連動先」を選び、
 * 連動マッピングを新規作成して一覧に反映されることを検証する。連動設定は永続的
 * (DBの vtiger_picklist_dependency に保存)なため、テスト末尾で作成した設定を
 * 必ず削除して原状復帰する。
 *
 * 実装メモ:
 * - 連動元/連動先フィールドは select2 でラップされた <select> のため、UI ではなく
 *   ネイティブ <select> に selectOption して change を発火させる(select2 は
 *   ネイティブの change を購読しており、連動グラフ描画もこれで走る)。
 * - 追加画面(triggerAdd)では sourceModule は先頭モジュールが既定選択される。
 *   本テストは sourceModule を触らず既定のままとし、保存後に一覧の該当行から
 *   モジュール表示ラベルを読み取って、後始末の行特定に使う。
 * - 連動元/連動先の両方が選択されると checkCyclicDependency → getDependencyGraph が
 *   非同期で走り #dependencyGraph に連動表(td.picklistValueMapping)が描画される。
 *   既定では全セルが選択状態(selectedCell)となる。
 * - 保存(submit)は AJAX(SaveAjax)で、完了後は一覧へ pjax 再読込される。
 * - 行アクション(編集/削除)は .table-actions 内の span.fa-pencil / span.fa-trash-o。
 *   (ListViewRecordActions.tpl は <span> で描画する。<i> ではない点に注意)
 * - 削除は triggerDelete → showConfirmationBox(confirmYes)を経て DeleteAjax を POST し、
 *   成功後は当該 <tr> を fadeOut().remove() でクライアント側から取り除く(画面遷移なし)。
 *   このため削除確認後は「行が DOM から消える」ことを直接検証し、加えて再読込後の
 *   一覧にも当該行が存在しないことを確認する。
 *
 * さらに「連動設定が実際に効くか」を検証する。連動は保存後、レコードの編集画面で
 * 連動元 select の change をきっかけに連動先 select の option を差し替える実装
 * (`layouts/v7/modules/Vtiger/resources/Edit.js` の picklistDependency 処理)なので、
 * 設定画面だけでは動作を担保できない。案件の フェーズ(sales_stage) → タイプ
 * (opportunity_type) で連動を作り、作成画面で絞り込みが起きることを確認する。
 *
 * 環境メモ: 蓄積された e2e テストデータが混在しうるため、行の特定は
 * 「モジュール表示ラベル + 連動元ラベル + 連動先ラベル」の3点で行い、
 * DB 上一意(sourceModule+sourceField+targetField)な本テスト作成分だけに絞る。
 */
test.describe.serial("管理: 選択肢の連動設定 (PickListDependency)", () => {
  const listParams = { module: "PickListDependency", view: "List" };

  // このテストで確定した連動元/連動先/モジュールの表示ラベル(一覧行の特定・後始末に使う)
  let sourceLabel = "";
  let targetLabel = "";
  let moduleLabel = "";

  // 連動グラフ(連動表)を包むコンテナ
  const dependencyGraph = (page: Page) => page.locator("#dependencyGraph");

  // 一覧のデータ行(アクションセルを持つ tr)をテキストで特定する。
  // hasText はセルテキストの部分一致になるため、渡すラベルすべてを含む tr に絞る。
  const row = (page: Page, ...texts: string[]) => {
    let locator = page
      .locator("tr")
      .filter({ has: page.locator(".table-actions") });
    for (const t of texts) locator = locator.filter({ hasText: t });
    return locator;
  };

  test("連動設定の追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 「選択肢の連動設定の追加」= getCreateRecordUrl の triggerAdd。編集画面を開く。
    await page.getByText("選択肢の連動設定の追加").first().click();

    // 連動元フィールドの select2 化を待つ(編集画面が pjax で描画された合図)
    const sourceField = page.locator('select[name="sourceField"]');
    const targetField = page.locator('select[name="targetField"]');
    await expect(sourceField).toBeAttached();
    await expect(targetField).toBeAttached();

    // 既定モジュールの選択肢フィールドから、非空で相異なる2値(連動元/連動先)を取得する。
    // 連動元と連動先が同じだとエラーになるため必ず別のものを選ぶ。
    const options = await sourceField
      .locator("option")
      .evaluateAll((els) =>
        (els as HTMLOptionElement[])
          .filter((o) => o.value !== "")
          .map((o) => ({ value: o.value, label: (o.textContent || "").trim() }))
      );
    expect(options.length).toBeGreaterThanOrEqual(2);
    const src = options[0];
    const tgt = options[1];
    sourceLabel = src.label;
    targetLabel = tgt.label;

    // ネイティブ <select> に値を設定(select2 が購読する change を発火させる)。
    // 連動先を先に、連動元を後に設定して、最後の change で連動グラフ描画をトリガーする。
    await targetField.selectOption(tgt.value);
    await sourceField.selectOption(src.value);

    // 連動表(td.picklistValueMapping)が描画されるのを待つ。既定で全セルが選択状態。
    await expect(
      dependencyGraph(page).locator("td.picklistValueMapping").first()
    ).toBeVisible({ timeout: 15000 });

    // 保存(submit)。AJAX 保存のため saveAndSettle で POST 完了を待つ。
    await saveAndSettle(
      page,
      page.locator("#pickListDependencyForm button.saveButton"),
      { force: true }
    );

    // 一覧に、選んだ連動元/連動先ラベルを持つ行が現れること。
    await gotoSettings(page, listParams);
    const created = row(page, sourceLabel, targetLabel).first();
    await expect(created).toBeVisible();

    // 後始末で使うモジュール表示ラベルを、作成された行のモジュールセルから読み取る。
    // 列順は 操作 / モジュール / 連動元 / 連動先(ListViewHeader.tpl)。
    // アクションセル(.table-actions を含む cell)を除いた 2 番目の td がモジュール。
    moduleLabel = (
      (await created.locator("td").nth(1).textContent()) || ""
    ).trim();
    expect(moduleLabel.length).toBeGreaterThan(0);
  });

  test("連動設定の削除(後始末)", async ({ page }) => {
    // ネイティブ dialog に備えて自動承認する
    page.on("dialog", (d) => d.accept().catch(() => {}));

    await gotoSettings(page, listParams);

    // モジュール+連動元+連動先の3点で本テスト作成分の行を一意に特定する。
    const target = row(page, moduleLabel, sourceLabel, targetLabel).first();
    await expect(target).toBeVisible();

    // 行アクションの削除アイコンは span.fa-trash-o(<i> ではない)。
    // triggerDelete → showConfirmationBox(confirmYes)→ DeleteAjax(画面遷移なし)。
    await target.locator(".table-actions span.fa-trash-o").click();
    await confirmYes(page);

    // DeleteAjax 成功後、当該 <tr> はクライアント側で fadeOut().remove() される。
    // まず DOM から消えることを直接検証する。
    await expect(target).toHaveCount(0, { timeout: 15000 });

    // 再読込後の一覧にも当該行が存在しないこと(原状復帰の確定確認)。
    await gotoSettings(page, listParams);
    await expect(
      row(page, moduleLabel, sourceLabel, targetLabel)
    ).toHaveCount(0);
  });

  test("連動設定を作ると、レコード作成画面で連動先の選択肢が絞られる", async ({
    page,
  }) => {
    test.setTimeout(180000);
    page.on("dialog", (d) => d.accept().catch(() => {}));

    // 案件の フェーズ(連動元) → タイプ(連動先)。値が既知で選択肢数が少なく検証しやすい。
    const SOURCE_MODULE = "Potentials";
    const SOURCE_FIELD = "sales_stage";
    const TARGET_FIELD = "opportunity_type";
    let created = false;
    let srcLabel = "";
    let tgtLabel = "";
    let modLabel = "";

    try {
      // === 1. 連動設定を追加する ===
      await gotoSettings(page, listParams);
      await page.getByText("選択肢の連動設定の追加").first().click();

      const sourceModule = page.locator('select[name="sourceModule"]');
      const sourceField = page.locator('select[name="sourceField"]');
      const targetField = page.locator('select[name="targetField"]');
      await expect(sourceModule).toBeAttached({ timeout: 20000 });

      // モジュールを切り替えると連動元/連動先の選択肢が AJAX で差し替わる
      await sourceModule.selectOption(SOURCE_MODULE);
      await expect(
        sourceField.locator(`option[value="${SOURCE_FIELD}"]`)
      ).toBeAttached({ timeout: 20000 });
      await targetField.selectOption(TARGET_FIELD);
      await sourceField.selectOption(SOURCE_FIELD);

      // 一覧行の特定に使う表示ラベルは、翻訳に依存しないよう option から読み取る
      srcLabel = (
        (await sourceField
          .locator(`option[value="${SOURCE_FIELD}"]`)
          .textContent()) || ""
      ).trim();
      tgtLabel = (
        (await targetField
          .locator(`option[value="${TARGET_FIELD}"]`)
          .textContent()) || ""
      ).trim();
      expect(srcLabel.length, "連動元のラベルが取れること").toBeGreaterThan(0);
      expect(tgtLabel.length, "連動先のラベルが取れること").toBeGreaterThan(0);

      // 連動表が描画される(既定は全セル選択状態)
      const cells = dependencyGraph(page).locator("td.picklistValueMapping");
      await expect(cells.first()).toBeVisible({ timeout: 20000 });

      // 連動元の 1 値を対象に、連動先を 1 つだけ残して他を解除する
      const pickedSource = await cells
        .first()
        .getAttribute("data-source-value");
      expect(pickedSource, "連動元の値が取れること").toBeTruthy();
      const sameSource = dependencyGraph(page).locator(
        `td.picklistValueMapping[data-source-value="${pickedSource}"]`
      );
      const sameSourceCount = await sameSource.count();
      expect(
        sameSourceCount,
        "連動先の選択肢が 2 つ以上あること(絞り込みを検証するため)"
      ).toBeGreaterThan(1);

      const keepValue = await sameSource.nth(0).getAttribute("data-target-value");
      for (let i = 1; i < sameSourceCount; i++) {
        const cell = sameSource.nth(i);
        const selected = await cell.evaluate((e) =>
          e.classList.contains("selectedCell")
        );
        if (selected) await cell.click();
      }
      // 残した 1 セルは選択状態のままであること
      await expect(sameSource.nth(0)).toHaveClass(/selectedCell/);

      await saveAndSettle(
        page,
        page.locator("#pickListDependencyForm button.saveButton"),
        { force: true }
      );
      created = true;

      // 一覧に出たことを確認し、後始末用のラベルを押さえる
      await gotoSettings(page, listParams);
      const createdRow = row(page, srcLabel, tgtLabel).first();
      await expect(createdRow, "連動設定が一覧に出ること").toBeVisible({
        timeout: 20000,
      });
      modLabel = ((await createdRow.locator("td").nth(1).textContent()) || "").trim();

      // === 2. レコード作成画面で絞り込みが効くこと ===
      await page.goto(url(`index.php?module=${SOURCE_MODULE}&view=Edit`));
      await page.waitForLoadState("networkidle");
      const src = page.locator(`select[name="${SOURCE_FIELD}"]`);
      const tgt = page.locator(`select[name="${TARGET_FIELD}"]`);
      await expect(src).toBeAttached({ timeout: 20000 });
      await expect(tgt).toBeAttached({ timeout: 20000 });

      // 連動前は連動先に複数の選択肢がある
      const before = await tgt
        .locator("option")
        .evaluateAll((els) =>
          (els as HTMLOptionElement[]).map((o) => o.value).filter((v) => v !== "")
        );
      expect(before.length).toBeGreaterThan(1);

      // 連動元を対象値にすると、連動先が残した 1 値だけになる
      await src.selectOption(pickedSource!);
      await expect
        .poll(
          async () =>
            await tgt
              .locator("option")
              .evaluateAll((els) =>
                (els as HTMLOptionElement[])
                  .map((o) => o.value)
                  .filter((v) => v !== "")
              ),
          { timeout: 20000 }
        )
        .toEqual([keepValue]);
    } finally {
      // === 後始末: 作成した連動設定を削除する ===
      if (created) {
        await gotoSettings(page, listParams).catch(() => {});
        const target = row(page, modLabel, srcLabel, tgtLabel).first();
        if ((await target.count()) > 0) {
          await target.locator(".table-actions span.fa-trash-o").click();
          await confirmYes(page);
          await expect(target).toHaveCount(0, { timeout: 15000 });
        }
      }
    }
  });
});
