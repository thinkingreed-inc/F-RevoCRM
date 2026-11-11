import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { url, generateRandomString } from "../../utils/util";

/**
 * 共通機能: 通知ブロック（#1965）
 *
 * シード（0_準備/seed.setup.ts）で通知ブロックを置き「作成時に送信：はい」にした E2E 専用モジュール
 * （発注先）を使う。1 件のレコードを 作成 → 詳細 → 送信 → 直接編集 → 複製 の順に操作するため直列で動かす。
 * E2E の環境には送信メールサーバーが無いため、送信の結果は「送信メールサーバー未設定」になる。
 * 送れたとき・一部失敗・宛先の展開・メールの言語や本文は PHPUnit（tests/Integration/NotificationBlock/）で確かめる。
 *
 * - 項目設定：通知ブロックに説明・作成時に送信・メール文面の設定を出し、カスタム項目の追加を出さない。
 *   1 モジュールに 1 つなので「ブロックの追加」では通知ブロックを選べない
 * - 作成画面：役割・グループ・ユーザーのタブから通知先を選んで保存すると、作成時に送信し、
 *   移った先の画面に結果を出して送信履歴に「作成時」で記録する
 * - 詳細画面：通知先を種類のラベル付きで出し、「通知を送信」で送信ダイアログ（宛先の人数）から送る
 * - 送信履歴の「すべて表示」と、その中で切り替える宛先の一覧（送れなかった理由）・送信履歴に戻る
 * - 通知先の直接編集（保存しても送らない）と、宛先が 0 人のときは「送信」を押せないこと
 * - 複製では有効な通知先を引き継ぐ
 * - メール文面の設定：保存した文面がダイアログに出て、「既定の文面に戻す」で戻る
 */
const MODULE = "Vendors";
const NO_SERVER_MESSAGE = "送信メールサーバーが設定されていないため、通知を送信できません";

test.describe.configure({ mode: "serial" });

test.describe("共通: 通知ブロック", () => {
  const vendorName = `E2Enb${generateRandomString(6)}`;
  let recordId = "";
  let roleText = "";

  const layoutUrl = url(
    `index.php?module=LayoutEditor&parent=Settings&view=Index&sourceModule=${MODULE}`
  );
  const gotoDetail = async (page: Page) => {
    await page.goto(
      url(`index.php?module=${MODULE}&view=Detail&record=${recordId}&mode=showDetailViewByMode&requestMode=full`)
    );
    await page.waitForLoadState("domcontentloaded");
  };
  const detailBlock = (page: Page) => page.locator('.notificationBlockContainer[data-mode="detail"]');
  const notifyMessage = (page: Page) => page.locator('[data-notify="message"]');
  // 候補は入力が止まってから読み直すので、絞り込んだ結果が返ってから候補を押す
  const searchAndWait = async (page: Page, search: ReturnType<Page["locator"]>, query: string) => {
    const response = page.waitForResponse(
      (res) => res.url().includes("api=SearchTargets") && new URL(res.url()).searchParams.get("q") === query
    );
    await search.fill(query);
    await response;
  };

  test("項目設定：通知ブロックは説明と作成時に送信・メール文面の設定を出し、2 つ目は追加できない", async ({ page }) => {
    await page.goto(layoutUrl);
    const block = page.locator('div.editFieldsTable[data-notification-block="1"]');
    await expect(block).toHaveCount(1);
    await expect(block.locator(".blockLabel strong")).toHaveText("通知");
    await expect(block.locator(".notificationBlockDescription")).toContainText("このブロックには項目を置けません");
    await expect(block.locator('input[name="notificationSendOnCreate"]')).toHaveCount(1);
    await expect(block.locator("button.notificationBlockTemplate")).toHaveText("メール文面の設定");
    await expect(block.locator("button.addCustomField")).toHaveCount(0);
    await expect(block.locator("button.inActiveFields")).toHaveCount(0);

    await page.locator("button.addCustomBlock").click();
    const modal = page.locator(".addBlockModal:visible");
    await expect(modal.locator('select[name="blockType"] option[value="notification"]')).toBeDisabled();
    await expect(modal).toContainText("このモジュールには通知ブロックがあります");
  });

  test("作成画面で通知先を選んで保存すると、作成時に送信して結果と送信履歴を出す", async ({ page }) => {
    await page.goto(url(`index.php?module=${MODULE}&view=Edit`));
    await page.waitForLoadState("domcontentloaded");
    await page.fill('input[name="vendorname"]', vendorName);

    const editBlock = page.locator('.notificationBlockContainer[data-mode="edit"]');
    await expect(editBlock.locator(".fieldBlockHeader")).toHaveText("通知");
    const picker = editBlock.locator(".notificationBlockPicker");
    await picker.locator(".notificationBlockPickerInput").click();
    const panel = picker.locator(".notificationBlockPickerPanel");
    await expect(panel).toBeVisible();

    // 役割のタブ（最初に開く）から 1 つ選ぶ
    const firstRole = panel.locator(".notificationBlockCandidate").first();
    await expect(firstRole).toBeVisible();
    roleText = (await firstRole.textContent())?.trim() || "";
    await firstRole.click();
    // 選んだものは候補に出さない
    await expect(panel.locator(".notificationBlockCandidate", { hasText: roleText })).toHaveCount(0);

    // ユーザーのタブで名前を入れて絞り込む
    await panel.locator('.nav-tabs a[data-type="user"]').click();
    await searchAndWait(page, panel.locator(".notificationBlockPickerSearch"), "admin");
    const userCandidate = panel.locator(".notificationBlockCandidate").first();
    await expect(userCandidate).toBeVisible();
    await userCandidate.click();

    const chips = picker.locator(".notificationBlockChip");
    await expect(chips).toHaveCount(2);
    await expect(chips.nth(0)).toContainText(`[役割] ${roleText}`);
    await expect(chips.nth(1)).toContainText("[ユーザー]");

    await page.locator("button.saveButton").first().click();
    await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
    recordId = new URL(page.url()).searchParams.get("record") || "";
    expect(recordId).not.toBe("");
    // 作成時の送信の結果は、移った先の画面の上部に出す（E2E では送信メールサーバーが無い）
    await expect(notifyMessage(page).filter({ hasText: NO_SERVER_MESSAGE })).toBeVisible({ timeout: 15000 });

    await gotoDetail(page);
    const rows = detailBlock(page).locator("tr.notificationBlockHistoryRow");
    await expect(rows).toHaveCount(1);
    await expect(rows.first()).toContainText("作成時");
    await expect(rows.first()).toContainText("失敗");
  });

  test("詳細画面：通知先をラベル付きで出し、送信ダイアログから送ると送信履歴に記録する", async ({ page }) => {
    await gotoDetail(page);
    const block = detailBlock(page);
    await expect(block.locator(".notificationBlockTarget")).toHaveCount(2);
    await expect(block.locator(".notificationBlockTarget").first()).toHaveText(`[役割] ${roleText}`);

    await block.locator("button.notificationBlockSend").click();
    const dialog = page.locator("#notificationBlockSendDialog");
    await expect(dialog).toBeVisible();
    await expect(dialog.locator(".notificationBlockRecipientCount")).toContainText("人");
    await dialog.locator("#notificationBlockComment").fill("E2E のコメント");
    await dialog.locator("#notificationBlockSendSubmit").click();
    await expect(dialog).toBeHidden();
    await expect(notifyMessage(page).filter({ hasText: NO_SERVER_MESSAGE })).toBeVisible();
    await expect(block.locator("tr.notificationBlockHistoryRow")).toHaveCount(2);
    await expect(block.locator("tr.notificationBlockHistoryRow").first()).toContainText("手動");
  });

  test("送信履歴の「すべて表示」から宛先の一覧に切り替えて戻れ、直近の送信履歴からも宛先の一覧を開ける", async ({ page }) => {
    await gotoDetail(page);
    await detailBlock(page).locator(".notificationBlockShowAllHistory").click();
    const history = page.locator("#notificationBlockHistoryDialog");
    await expect(history).toBeVisible();
    await expect(history.locator("th")).toHaveText(["送信日時", "送信者", "送信方法", "宛先", "コメント", "結果"]);
    await expect(history.locator("tr.notificationBlockHistoryRow").first()).toContainText("E2E のコメント");

    // ダイアログを重ねて開かず、同じダイアログの中身を宛先の一覧に切り替える
    await history.locator(".notificationBlockShowRecipients").first().click();
    const content = history.locator(".notificationBlockRecipientContent");
    await expect(content).toBeVisible();
    await expect(content.locator("tr.notificationBlockRecipientRow").first()).toContainText("送信メールサーバーが設定されていません");
    await expect(history.locator(".notificationBlockHistoryListPane")).toBeHidden();
    // 前のダイアログが閉じ終わったときに中身が消える不具合があったので、少し待っても出たままで操作できることを確かめる
    await page.waitForTimeout(1500);
    await expect(content).toBeVisible();
    await history.locator(".notificationBlockBackToHistory").click();
    await expect(history.locator(".notificationBlockHistoryListPane")).toBeVisible();
    await expect(history.locator("tr.notificationBlockHistoryRow")).toHaveCount(2);
    await history.locator('[data-dismiss="modal"]').first().click();
    await expect(history).toBeHidden();

    // 通知ブロックの直近の送信履歴からは、宛先の一覧のダイアログを開く（中身は同じ）
    await detailBlock(page).locator(".notificationBlockShowRecipients").first().click();
    const recipients = page.locator("#notificationBlockRecipientDialog");
    await expect(recipients.locator(".notificationBlockRecipientContent")).toBeVisible();
    await page.waitForTimeout(1500);
    await expect(recipients.locator("tr.notificationBlockRecipientRow").first()).toContainText("送信メールサーバーが設定されていません");
  });

  test("通知先を直接編集して保存しても送らず、宛先が 0 人なら「送信」を押せない", async ({ page }) => {
    await gotoDetail(page);
    const block = detailBlock(page);
    await block.locator(".notificationBlockTargetsRow").hover();
    await block.locator(".notificationBlockEditTargets").click();
    const editor = block.locator(".notificationBlockInlineEditor");
    await expect(editor).toBeVisible();
    // 2 つとも外して保存する
    await editor.locator(".notificationBlockChipRemove").first().click();
    await editor.locator(".notificationBlockChipRemove").first().click();
    await expect(editor.locator(".notificationBlockChip")).toHaveCount(0);
    await editor.locator("button.notificationBlockSaveTargets").click();
    await expect(block.locator(".notificationBlockNoTargets")).toHaveText("通知先はありません");
    await expect(block.locator("tr.notificationBlockHistoryRow")).toHaveCount(2);

    await block.locator("button.notificationBlockSend").click();
    const dialog = page.locator("#notificationBlockSendDialog");
    await expect(dialog.locator(".notificationBlockNoRecipients")).toHaveText("宛先がありません");
    await expect(dialog.locator("#notificationBlockSendSubmit")).toBeDisabled();
  });

  test("複製では有効な通知先を引き継ぐ", async ({ page }) => {
    // 直接編集で役割を 1 つ戻してから複製する
    await gotoDetail(page);
    const block = detailBlock(page);
    await block.locator(".notificationBlockTargetsRow").hover();
    await block.locator(".notificationBlockEditTargets").click();
    const editor = block.locator(".notificationBlockInlineEditor");
    await editor.locator(".notificationBlockPickerInput").click();
    await searchAndWait(page, editor.locator(".notificationBlockPickerSearch"), roleText);
    await editor.locator(".notificationBlockCandidate", { hasText: roleText }).first().click();
    await expect(editor.locator(".notificationBlockChip")).toHaveCount(1);
    await editor.locator("button.notificationBlockSaveTargets").click();
    await expect(block.locator(".notificationBlockTarget")).toHaveCount(1);

    await page.goto(url(`index.php?module=${MODULE}&view=Edit&record=${recordId}&isDuplicate=true`));
    const chips = page.locator('.notificationBlockContainer[data-mode="edit"] .notificationBlockChip');
    await expect(chips).toHaveCount(1);
    await expect(chips.first()).toContainText(`[役割] ${roleText}`);
  });

  test("メール文面の設定：保存した件名がダイアログに出て、「既定の文面に戻す」で戻る", async ({ page }) => {
    const subject = `E2E 件名 {record_name} ${generateRandomString(4)}`;
    const openDialog = async () => {
      await page.goto(layoutUrl);
      await page.locator('div.editFieldsTable[data-notification-block="1"] button.notificationBlockTemplate').click();
      const dialog = page.locator("#notificationBlockTemplateDialog");
      await expect(dialog).toBeVisible();
      return dialog;
    };
    const save = async (dialog: ReturnType<Page["locator"]>) => {
      await dialog.locator("#notificationBlockTemplateSave").click();
      await expect(notifyMessage(page).filter({ hasText: "メール文面を保存しました" })).toBeVisible();
    };

    let dialog = await openDialog();
    await expect(dialog.locator("#notificationBlockSubject_ja_jp")).toHaveValue("【通知】{module_name}：{record_name}");
    await dialog.locator("#notificationBlockSubject_ja_jp").fill(subject);
    await save(dialog);

    dialog = await openDialog();
    await expect(dialog.locator("#notificationBlockSubject_ja_jp")).toHaveValue(subject);
    await dialog.locator("#notificationBlockTemplate_ja_jp .notificationBlockResetTemplate").click();
    await expect(dialog.locator("#notificationBlockSubject_ja_jp")).toHaveValue("【通知】{module_name}：{record_name}");
    await save(dialog);

    dialog = await openDialog();
    await expect(dialog.locator("#notificationBlockSubject_ja_jp")).toHaveValue("【通知】{module_name}：{record_name}");
  });

  test.afterAll(async ({ browser, workerStorageState }) => {
    // 作ったレコードは削除する（ごみ箱に移す）
    if (!recordId) {
      return;
    }
    const context = await browser.newContext({ storageState: workerStorageState });
    const page = await context.newPage();
    try {
      await page.goto(url(`index.php?module=${MODULE}&view=Detail&record=${recordId}`));
      await page.evaluate(async (record) => {
        const w = window as unknown as { csrfMagicName: string; csrfMagicToken: string };
        const body = new URLSearchParams({ module: "Vendors", action: "DeleteAjax", record });
        body.append(w.csrfMagicName, w.csrfMagicToken);
        await fetch("index.php", { method: "POST", body, credentials: "same-origin" });
      }, recordId);
    } finally {
      await context.close();
    }
  });
});
