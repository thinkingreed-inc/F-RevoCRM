import { test, expect } from "../../fixtures/isolated";
import { url, generateRandomString } from "../../utils/util";
import {
  createEventViaModal,
  deleteCalendarEvent,
} from "../../utils/calendar";

/**
 * カレンダー: 新規作成モーダル(React QuickCreate)でのみ設定できる項目の検証。
 * 元スプレッドシート `_カレンダー基本パターン`(終日) / `_その他_カレンダー追加機能`(#1191 共有メモ)。
 *
 * 保存は POST(200) → 画面表示 → 再フェッチ と反映が遅いため、作成ヘルパ側で件名クエリを
 * 寛大にリトライして特定する。詳細表示の確認も反映待ちを入れる。
 */
test.describe.serial("カレンダー(モーダル): 終日・共有メモ", () => {
  /** 詳細ビューで指定テキストが出るまで、反映遅延に耐えて待つ(必要なら再読込)。 */
  async function expectOnDetail(page, recordId: string, text: string) {
    for (let attempt = 0; attempt < 3; attempt++) {
      await page.goto(
        url(
          `index.php?module=Calendar&view=Detail&record=${recordId}&app=SALES`
        )
      );
      await page.waitForLoadState("networkidle");
      const hit = page
        .getByText(text, { exact: false })
        .and(page.locator(":visible"))
        .first();
      if (await hit.isVisible().catch(() => false)) return;
      await page.waitForTimeout(1500);
    }
    await expect(
      page.getByText(text, { exact: false }).and(page.locator(":visible")).first()
    ).toBeVisible({ timeout: 10000 });
  }

  test("#1191 共有メモを入力して作成でき、詳細に表示される", async ({ page }) => {
    test.setTimeout(120000);
    const subject = `E2Ecmemo${generateRandomString(5)}`;
    const memo = `E2Ememo${generateRandomString(5)}`;
    let wsId = "";
    try {
      const rec = await createEventViaModal(page, { subject, commonMemo: memo });
      wsId = rec.wsId;
      await expectOnDetail(page, rec.recordId, memo);
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("3.1 終日予定を登録でき、詳細に表示される", async ({ page }) => {
    test.setTimeout(120000);
    const subject = `E2Eallday${generateRandomString(5)}`;
    let wsId = "";
    try {
      const rec = await createEventViaModal(page, { subject, allDay: true });
      wsId = rec.wsId;
      // 詳細に件名が出る(=登録され表示される)ことを確認
      await expectOnDetail(page, rec.recordId, subject);
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("招待: 他ユーザーを招集して作成でき、詳細に招待者が表示される", async ({
    page,
  }) => {
    test.setTimeout(120000);
    const subject = `E2Einvite${generateRandomString(5)}`;
    // seed のユーザー「E2E 1課員」(e2e_rep_a)を招待する
    const inviteeName = "1課員";
    let wsId = "";
    try {
      const rec = await createEventViaModal(page, {
        subject,
        invitees: [inviteeName],
      });
      wsId = rec.wsId;
      // 詳細に招待者名が表示される(＝招待できた)
      await expectOnDetail(page, rec.recordId, inviteeName);
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });
});
