import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { url, generateRandomString } from "../../utils/util";

/**
 * 共通機能: 版管理（#1964）
 *
 * シード（0_準備/seed.setup.ts）で版管理「はい」にした E2E 専用モジュール（契約）を使う。
 * 1 件のレコードを、改版 → 版履歴 → 過去の版の表示 の順に操作するため直列で動かす。
 *
 * - 詳細画面の「改版」で改版理由を入れて改版すると、版が 1 上がり編集画面が開く（表B）
 * - 改版理由が空なら「改版理由を入力してください」と出して改版しない（表B ルール1）
 * - 版履歴に現在の版と過去の版（改版理由）が出る
 * - 過去の版の表示は当時の値を出し、編集などのボタンを出さない。「次の版」で現在の版に戻る
 * - 版管理「いいえ」のモジュールは、版履歴を URL から開いても出さない（動作定義 No.29）
 */
const MODULE = "ServiceContracts";

test.describe.configure({ mode: "serial" });

test.describe("共通: 版管理", () => {
  const subject = `E2Erv${generateRandomString(6)}`;
  const reason = `E2E 改版理由 ${generateRandomString(4)}`;
  let recordId = "";

  const gotoDetail = async (page: Page) => {
    await page.goto(
      url(`index.php?module=${MODULE}&view=Detail&record=${recordId}&mode=showDetailViewByMode&requestMode=full`)
    );
    await page.waitForLoadState("domcontentloaded");
  };
  const versionValue = (page: Page) =>
    page.locator(`#${MODULE}_detailView_fieldValue_record_version`);

  test("作成したレコードは版 1 で、詳細画面に「改版」「版履歴」が出る", async ({ page }) => {
    await page.goto(url(`index.php?module=${MODULE}&view=Edit`));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="subject"]', subject);
    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
    recordId = new URL(page.url()).searchParams.get("record") || "";
    expect(recordId).not.toBe("");

    await gotoDetail(page);
    await expect(versionValue(page)).toContainText("1");
    // ボタン名・項目名は共通の言語ファイルで翻訳する
    await expect(page.locator(`#${MODULE}_detailView_basicAction_LBL_RECORD_VERSION_REVISE`)).toHaveText("改版");
    await expect(page.locator(`#${MODULE}_detailView_basicAction_LBL_RECORD_VERSION_HISTORY`)).toHaveText("版履歴");
  });

  test("改版理由が空なら改版せず、入れて改版すると版が上がり編集画面が開く", async ({ page }) => {
    await gotoDetail(page);
    await page.locator(`#${MODULE}_detailView_basicAction_LBL_RECORD_VERSION_REVISE`).click();
    const dialog = page.locator("#recordVersionReviseDialog");
    await expect(dialog).toBeVisible();
    await expect(dialog.locator("#recordVersionCurrentNo")).toHaveText("1");
    await expect(dialog.locator("#recordVersionNextNo")).toHaveText("2");

    await dialog.locator("#recordVersionReviseSubmit").click();
    await expect(dialog.locator(".recordVersionReviseError")).toHaveText("改版理由を入力してください");

    await dialog.locator("#recordVersionReason").fill(reason);
    await dialog.locator("#recordVersionReviseSubmit").click();
    await page.waitForURL(/view=Edit/, { timeout: 15000 });

    // 改版したあとの版で件名を変えて保存する（現在の版の中の変更）
    await page.fill('input[name="subject"]', `${subject}_v2`);
    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/view=Detail/, { timeout: 15000 });
    await gotoDetail(page);
    await expect(versionValue(page)).toContainText("2");
  });

  test("版履歴に現在の版と過去の版が出て、過去の版は当時の値を表示のみで出す", async ({ page }) => {
    await gotoDetail(page);
    await page.locator(`#${MODULE}_detailView_basicAction_LBL_RECORD_VERSION_HISTORY`).click();
    await page.waitForURL(/module=RecordVersion&view=History/);
    const table = page.locator("#recordVersionHistoryTable");
    await expect(table.locator("tr.recordVersionCurrentRow")).toContainText("2");
    const pastRow = table.locator('tr.recordVersionPastRow[data-version="1"]');
    await expect(pastRow).toContainText(reason);

    await pastRow.locator("a.recordVersionLink").click();
    await page.waitForURL(/module=RecordVersion&view=Show/);
    await expect(page.locator("#recordVersionNotice")).toContainText("版 1 を表示しています");
    await expect(page.locator("#recordVersionNotice")).toContainText(reason);
    const show = page.locator("#recordVersionShowView");
    await expect(show).toContainText(subject);
    await expect(show).not.toContainText(`${subject}_v2`);
    // 版に含めた関連（ドキュメント）が当時 0 件なら「（関連名）はありません」と出す（名前と「は」の間に空白を入れない）
    await expect(show.locator(".recordVersionRelationEmpty")).toHaveText("ドキュメントはありません");
    // 過去の版は変更できない：編集ボタン・直接編集の鉛筆は出さない。版 1 では「前の版」も出さない
    await expect(page.locator(`#${MODULE}_detailView_basicAction_LBL_EDIT`)).toHaveCount(0);
    await expect(show.locator(".editAction, .fa-pencil")).toHaveCount(0);
    await expect(page.locator("#recordVersionPrev")).toHaveCount(0);

    // いちばん新しい過去の版の「次の版」は、レコードの詳細画面（現在の版）を開く
    await page.locator("#recordVersionNext").click();
    await page.waitForURL(new RegExp(`module=${MODULE}&view=Detail&record=${recordId}`));
  });

  test("版管理「いいえ」のモジュールは、版履歴を URL から開いても出さない", async ({ page }) => {
    const setEnabled = async (enabled: "0" | "1") => {
      await page.goto(url("index.php?module=RecordVersion&parent=Settings&view=Index"));
      const tabid = await page
        .locator(`tr.recordVersionModuleRow[data-module="${MODULE}"]`)
        .getAttribute("data-tabid");
      const result = await page.evaluate(
        async ({ tabid, enabled }) => {
          const w = window as unknown as { csrfMagicName: string; csrfMagicToken: string };
          const body = new URLSearchParams({
            module: "RecordVersion", parent: "Settings", api: "Save", mode: "module",
            tabid: tabid || "", enabled,
          });
          body.append(w.csrfMagicName, w.csrfMagicToken);
          const res = await fetch("index.php", { method: "POST", body, credentials: "same-origin" });
          return res.json();
        },
        { tabid, enabled }
      );
      expect(result.enabled).toBe(enabled === "1");
    };

    await setEnabled("0");
    try {
      await page.goto(url(`index.php?module=RecordVersion&view=History&record=${recordId}`));
      await expect(page.locator("body")).toContainText("このモジュールは版管理の対象ではありません");
      await expect(page.locator("#recordVersionHistoryTable")).toHaveCount(0);
      await gotoDetail(page);
      await expect(page.locator(`#${MODULE}_detailView_basicAction_LBL_RECORD_VERSION_REVISE`)).toHaveCount(0);
    } finally {
      await setEnabled("1");
    }
    // 「はい」に戻せば、同じ URL でまた見られる
    await page.goto(url(`index.php?module=RecordVersion&view=History&record=${recordId}`));
    await expect(page.locator("#recordVersionHistoryTable")).toBeVisible();
  });
});
