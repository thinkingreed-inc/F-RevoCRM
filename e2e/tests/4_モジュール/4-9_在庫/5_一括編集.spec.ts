import { test, expect } from "../../../fixtures/isolated";
import { seedSpec } from "../../../fixtures/seedSpec";
import { deleteRecordViaApi } from "../../../utils/record";
import {
  gotoList,
  listSearch,
  listRows,
  clearListSearch,
  deleteViaDetail,
} from "../../../utils/listview";
import { generateRandomString } from "../../../utils/util";
import { apiSession } from "../../../utils/api";
import { frQuery } from "../../../model/fetcher";
import * as li from "../../../utils/lineitem";

/**
 * 在庫系の一括編集(Mass Edit) — TEST_COVERAGE P0-B / B1
 *
 * 在庫系は一括編集に**モジュール固有の実装**がある:
 *   modules/Inventory/actions/MassSave.php (35 行)
 *   modules/Invoice/actions/MassSave.php   (75 行, getRecordModelsFromRequest を override)
 *   modules/Products/actions/MassSave.php  (47 行)
 * Invoice の実装にはコメントで「Inventory line items getting wiped out」とあり、
 * 保存時に `$_REQUEST['action'] = 'MassEditSave'` を立てて **明細(LineItems)が
 * 消えるのを防いでいる**。つまりこの個別実装が壊れると
 * 「一括編集したら明細が消える」という重大なデータ損失になる。
 *
 * それにもかかわらず `tests/3_共通機能/3-25_一括編集.spec.ts` は Accounts 固定で、
 * 在庫系の個別実装は誰も通していなかった(2026-08-26 の棚卸しで判明)。
 *
 * 本 spec の主眼は **一括編集の前後で明細と総計が保持されること**。
 * ヘッダ項目(description)を一括編集し、API で総計(hdnGrandTotal)が変わらないことを検証する。
 */

const productA = seedSpec.inventory.products[0];
const QTY = 2;

/** 在庫モジュールは INVENTORY アプリ配下。 */
const APP = "INVENTORY";

/** subject から Webservice ID と総計を引く。 */
async function fetchTotals(
  moduleName: string,
  subject: string
): Promise<{ wsId: string; grandTotal: number; description: string } | null> {
  const session = await apiSession();
  const rows = await frQuery(
    session,
    `SELECT id, hdnGrandTotal, description FROM ${moduleName} WHERE subject = '${subject}' LIMIT 1;`
  );
  const r = rows?.[0];
  if (!r) return null;
  return {
    wsId: r.id,
    grandTotal: Number(r.hdnGrandTotal ?? 0),
    description: String(r.description ?? ""),
  };
}

for (const cfg of li.INVENTORY_MODULES) {
  test.describe(`在庫の一括編集: ${cfg.module}`, () => {
    test("一括編集しても明細と総計が保持される", async ({ page }) => {
      test.setTimeout(120000);
      const suffix = generateRandomString(6);
      let created: li.CreatedInventoryRecord | null = null;

      try {
        created = await li.createInventoryRecord(page, cfg, {
          suffix,
          product: productA,
          qty: QTY,
        });

        // 一括編集前の総計(明細から算出された値)を押さえる
        const before = await fetchTotals(cfg.module, created.subject);
        expect(before, "作成したレコードが API から引けること").not.toBeNull();
        expect(
          before!.grandTotal,
          "明細が載っているので総計は 0 より大きい"
        ).toBeGreaterThan(0);

        // === 一覧で対象を絞って選択し、一括編集モーダルを開く ===
        await gotoList(page, cfg.module, APP);
        await listSearch(page, "subject", created.subject);
        const row = listRows(page).filter({ hasText: created.subject }).first();
        await expect(row).toBeVisible({ timeout: 15000 });
        await row.locator("input.listViewEntriesCheckBox").check();

        const massEdit = page.locator(
          `#${cfg.module}_listView_massAction_LBL_EDIT`
        );
        await expect(massEdit).toBeEnabled();
        await massEdit.click();

        const modal = page.locator(".modal-content:visible").first();
        await expect(modal).toBeVisible({ timeout: 15000 });

        // 説明(description)を編集対象に含めて値を入れる
        const newDescription = `E2E一括編集 ${suffix}`;
        await modal.locator("#include_in_mass_edit_description").check();
        await modal.locator('textarea[name="description"]').fill(newDescription);
        await modal.locator("button.saveButton").first().click();
        await expect(modal).toBeHidden({ timeout: 30000 });
        await page.waitForLoadState("networkidle");

        // 検索条件はセッションに残るため解除しておく(後続の一覧操作に影響する)
        await clearListSearch(page);

        // === 検証: 説明が反映され、かつ総計(= 明細)が保持されている ===
        const after = await fetchTotals(cfg.module, created.subject);
        expect(after, "一括編集後もレコードが引けること").not.toBeNull();
        expect(after!.description).toContain(newDescription);
        expect(
          after!.grandTotal,
          "一括編集で明細が消えていないこと(総計が保持される)"
        ).toBe(before!.grandTotal);
      } finally {
        if (created) {
          await deleteViaDetail(page, cfg.module, created.recordId);
          await deleteRecordViaApi(created.ref.session, created.ref.wsId);
        }
      }
    });
  });
}
