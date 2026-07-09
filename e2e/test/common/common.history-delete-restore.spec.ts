import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import { gotoList, listRows, listSearch, clearListSearch } from "../../utils/listview";
import { confirmYes } from "../../utils/settings";
import {
  createRecordViaApi,
  deleteRecordViaApi,
  type CreatedRecord,
} from "../../utils/record";

/**
 * 追加機能(UI/UX): 更新履歴に「削除」と「復元」の情報が表示される
 * (元スプレッドシート `2_〇×_OSS版_基本機能.xlsx` の `_UI UX` シート No.1)
 *
 * 案件(Potentials)レコードを作成 → 一覧から一括削除(ゴミ箱へ)→ ゴミ箱から復元 し、
 * そのレコードの更新履歴(mode=showRecentActivities)に
 * 「削除」(LBL_DELETED)と「復元」(LBL_RESTORED)の履歴が残ることを検証する。
 *
 * ModTracker::$DELETED='1' / $RESTORED='3'。表示ラベルは languages/ja_jp/Vtiger.php の
 * LBL_DELETED='削除' / LBL_RESTORED='復元'。
 * 削除/復元導線は common.recyclebin.spec.ts と同じ実績のある一覧一括操作を使う。
 */
test.describe("追加(UI/UX): 更新履歴の削除・復元", () => {
  test("案件を削除→復元すると更新履歴に削除・復元が残る", async ({ page }) => {
    test.setTimeout(90000);
    const module = "Potentials";
    const app = "SALES";

    let rec: CreatedRecord | undefined;
    const name = `E2Edr${generateRandomString(6)}`;
    try {
      // --- 使い捨て案件を API で作成(必須項目は自動補完、名前は一意トークン)---
      rec = await createRecordViaApi(module, { potentialname: name });
      const recordId = rec.recordId;

      // --- 一覧で検索して選択 → 一括削除(ゴミ箱へ)---
      await gotoList(page, module, app);
      await listSearch(page, "potentialname", name);
      const row = listRows(page).filter({ hasText: name }).first();
      await expect(row).toBeVisible({ timeout: 15000 });
      await row.locator("input.listViewEntriesCheckBox").check();
      const del = page.locator(`#${module}_listView_massAction_LBL_DELETE`);
      await expect(del).toBeEnabled();
      await del.click();
      await confirmYes(page);
      await page.waitForLoadState("networkidle");
      await clearListSearch(page).catch(() => {});

      // --- ゴミ箱から復元(名前で検索して対象行を一意に絞り込む)---
      await page.goto(
        url(
          `index.php?module=RecycleBin&view=List&sourceModule=${module}` +
            `&search_key=potentialname&search_value=${name}&operator=e`
        )
      );
      await page.waitForLoadState("networkidle");
      const binRow = listRows(page).filter({ hasText: name }).first();
      await expect(binRow).toBeVisible({ timeout: 15000 });
      await binRow.locator("input.listViewEntriesCheckBox").check();
      const restore = page.locator("#RecycleBin_listView_massAction_LBL_RESTORE");
      await expect(restore).toBeEnabled();
      await restore.click();
      await confirmYes(page);
      await page.waitForLoadState("networkidle");

      // --- 更新履歴に「削除」「復元」が表示される ---
      await page.goto(
        url(
          `index.php?module=${module}&view=Detail&record=${recordId}&mode=showRecentActivities&page=1&app=${app}`
        )
      );
      await page.waitForLoadState("networkidle");
      // レスポンシブ用の非表示複製があるため :visible なものに限定する
      // (RecentActivities.tpl は「<担当者> 削除」「<担当者> 復元」で表示する)。
      await expect(
        page.getByText("削除").and(page.locator(":visible")).first()
      ).toBeVisible({ timeout: 15000 });
      await expect(
        page.getByText("復元").and(page.locator(":visible")).first()
      ).toBeVisible();
    } finally {
      if (rec) await deleteRecordViaApi(rec.session, rec.wsId);
    }
  });
});
