import { test, expect } from "../../../fixtures/isolated";
import { url, generateRandomString } from "../../../utils/util";
import { confirmYes } from "../../../utils/settings";
import {
  createRecurringEvent,
  deleteAllEventsBySubject,
  findEventBySubjectOrNull,
} from "../../../utils/calendar";

/**
 * カレンダーの一括削除 — TEST_COVERAGE P0-B / B3
 *
 * `modules/Calendar/actions/MassDelete.php` は `process()` を丸ごと override し、
 * 共通実装に無い次の処理を行う:
 *   1. 削除前に `vtiger_activity_recurring_info` から
 *      (親の繰り返し ID, 対象レコード) の行を DELETE する
 *   2. `$recordModel->delete()` の戻り(= 連動して消えたレコード群)を集約し、
 *      **既に消えたレコードを二重削除しない**ようスキップする
 *      (繰り返し予定は 1 件消すと関連インスタンスも消えるため必須)
 *   3. `deleteRecordFromDetailViewNavigationRecords()` でナビ情報を掃除する
 *
 * 共通機能側の一括削除テスト(3-26_ゴミ箱)は Accounts 固定なので、
 * この固有実装は誰も通していなかった(2026-08-26 の棚卸しで判明)。
 *
 * 検証は **繰り返し予定** を対象にする(上記 1 と 2 を同時に通せる)。
 */

/** 件名で絞った活動一覧を開く(Calendar は列検索より URL 検索が確実)。 */
async function gotoCalendarListBySubject(
  page: import("@playwright/test").Page,
  subject: string
) {
  await page.goto(
    url(
      `index.php?module=Calendar&view=List&app=SALES` +
        `&search_key=subject&search_value=${encodeURIComponent(subject)}&operator=e`
    )
  );
  await page.waitForLoadState("networkidle");
}

test.describe("カレンダー: 一括削除(繰り返し予定)", () => {
  test("繰り返し予定を一括削除すると全インスタンスが消える", async ({
    page,
  }) => {
    test.setTimeout(180000);
    const subject = `E2E繰返一括${generateRandomString(6)}`;

    try {
      // 繰り返し(毎日)の予定を作る。複数インスタンスが生成される。
      await createRecurringEvent(page, subject, "Daily");

      await gotoCalendarListBySubject(page, subject);
      const targetRows = page
        .locator("tr.listViewEntries")
        .filter({ hasText: subject });
      await expect(
        targetRows.first(),
        "作成した予定が一覧に出ること"
      ).toBeVisible({ timeout: 30000 });

      // 対象の行だけを個別に選択して一括削除する。
      // ヘッダの全選択(input.listViewEntriesMainCheckBox)は、検索が効いていない
      // 状態だと並列実行中の他テストの予定まで消してしまうため使わない。
      const targetCount = await targetRows.count();
      expect(targetCount, "繰り返しのインスタンスが並ぶこと").toBeGreaterThan(0);
      for (let i = 0; i < targetCount; i++) {
        await targetRows.nth(i).locator("input.listViewEntriesCheckBox").check();
      }
      const del = page.locator("#Calendar_listView_massAction_LBL_DELETE");
      await expect(del).toBeEnabled();
      await del.click();
      await confirmYes(page);

      // 削除は Ajax。goto で POST を中断しないよう、まずその場で消えるのを待つ。
      // 削除後の再読込では URL で指定した検索条件(search_key=subject)が外れて
      // 全件が並ぶので、全行数ではなく **件名で絞った行数** で判定する。
      await expect(
        page.locator("tr.listViewEntries").filter({ hasText: subject })
      ).toHaveCount(0, { timeout: 30000 });

      // 一覧を開き直しても消えている
      await gotoCalendarListBySubject(page, subject);
      await expect(
        page.locator("tr.listViewEntries").filter({ hasText: subject })
      ).toHaveCount(0, { timeout: 15000 });

      // API でも残っていない(ゴミ箱送りではなく deleted=1 になる)
      expect(
        await findEventBySubjectOrNull(subject, 3),
        "一括削除後は API からも引けないこと"
      ).toBeNull();
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });
});
