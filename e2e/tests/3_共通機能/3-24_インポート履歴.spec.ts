import { test, expect } from "../../fixtures/isolated";
import { runAccountsImport } from "../../utils/import";
import { url, generateRandomString } from "../../utils/util";
import { apiSession } from "../../utils/api";
import { frQuery, frDelete } from "../../model/fetcher";

/**
 * 追加機能(インポート/エクスポート): インポート結果が保存され履歴から確認できる
 * (元スプレッドシート `2_〇×_OSS版_基本機能.xlsx` の `_インポート エクスポート機能` シート No.1)
 *
 * 顧客企業(Accounts)を CSV インポートし、インポート画面ヘッダの
 * 「インポート履歴」(#showImportHistory)から直近のインポート履歴が確認できることを検証する。
 * (履歴コンテナ #importHistoryContainer が表示され、「履歴がありません」ではないこと)
 *
 * インポートは 1 ユーザーにつき同時実行不可(ロック)のため serial 化。
 */
test.describe.serial("追加(インポート): インポート履歴", () => {
  async function cleanup(sn: string, prefix: string): Promise<void> {
    const rows = await frQuery(
      sn,
      `SELECT id,accountname FROM Accounts WHERE accountname LIKE '${prefix}%';`
    );
    for (const r of rows) {
      if (r.id) await frDelete(sn, r.id);
    }
  }

  test("インポート後にインポート履歴が確認できる", async ({ page }) => {
    test.setTimeout(90000);
    const base = `E2Eimphist${generateRandomString(6)}`;
    await runAccountsImport(page, {
      csv: `accountname\n${base}_1\n${base}_2\n`,
      mappings: ["accountname"],
    });

    // インポート画面へ移動し「インポート履歴」を開く。
    await page.goto(url("index.php?module=Accounts&view=Import"));
    await page.waitForLoadState("networkidle");
    const historyBtn = page.locator("#showImportHistory");
    await expect(historyBtn).toBeVisible({ timeout: 15000 });
    await historyBtn.click();

    // 履歴コンテナが表示され、「インポート履歴がありません」ではない
    // (直近のインポートが 1 件以上並ぶ)。
    const container = page.locator("#importHistoryContainer");
    await expect(container).toBeVisible({ timeout: 15000 });
    await expect(container).not.toContainText("インポート履歴がありません");

    const sn = await apiSession();
    await cleanup(sn, base);
  });
});
