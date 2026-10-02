import { test, expect } from "../../../fixtures/isolated";
import { generateRandomString } from "../../../utils/util";
import {
  convertEventToRecurring,
  createEventViaModal,
  createRecurringEvent,
  dayStr,
  deleteAllEventsBySubject,
  listEventsBySubject,
  resolveUserWsId,
  updateEventLocationWithScope,
} from "../../../utils/calendar";

/**
 * カレンダー 繰り返し予定の「回数」と「更新範囲」 — #1857 / #1858
 *
 * 既存の `3_繰り返し.spec.ts` は新規作成で繰り返しを作り recurringtype の保存値だけを
 * 見ており、次の 3 点がどこからも通っていなかった。
 *
 *  1. **回が実際に作られたか**(件数)。recurringtype だけ見ると、2 回目以降が
 *     1 件も作られなくてもテストは通ってしまう。
 *  2. **既存の予定を編集して繰り返しに変更する経路**。新規作成(mode='')とは別処理で、
 *     確認ダイアログが出ないため recurringEditMode が空のまま送られる。
 *     #1858 のレビューで、ここが塞がって 2 回目以降が作られない回帰が見つかった。
 *  3. **更新範囲(この活動のみ / 以降の活動を含む / 全ての活動)を選んだ結果**。
 *     参加者を招待すると回ごとに参加者用のコピーが作られるが、コピーは
 *     `vtiger_activity_recurring_info` に載らない。#1857 は、このコピーを起点に
 *     「全ての活動」を選んでも編集した回にしか届かない不具合。
 *
 * 検証は件名で全レコードを引き、「場所(location)」をどの回まで書き換えられたかで追う。
 *
 * 【なぜ招集者(admin)が招待コピーを編集するのか】
 * 起点が「系列に載っていない招待コピー」であることが #1857 の要点で、これは誰が
 * 保存しても同じ経路(invitee_parentid をたどって系列を解決する)を通る。
 * 一方、権限を絞った参加者(e2e_rep_a)でログインして保存すると、その参加者の
 * 編集画面では参加者ピッカーに招集者が出ないため、保存時に参加者リストから
 * 招集者が外れて招集者側の回が削除される(#1857/#1858 とは別の既存挙動)。
 * 本テストの対象から外すため、更新は招集者のログインで行う。
 */
test.describe.serial("カレンダー: 繰り返しの回数と更新範囲", () => {
  test("新規作成した繰り返しは終了日までの回数分の活動が作られる", async ({
    page,
  }) => {
    test.setTimeout(180000);
    const subject = `E2E繰返件数${generateRandomString(6)}`;
    try {
      // 当日から 3 日後まで毎日 → 当日を含めて 4 回
      await createRecurringEvent(page, subject, "Daily", false, {
        limitDate: dayStr(3),
      });

      const events = await listEventsBySubject(subject, 4);
      expect(
        events.map((e) => e.dateStart),
        "終了日までの各日に 1 回ずつ作られること"
      ).toEqual([dayStr(0), dayStr(1), dayStr(2), dayStr(3)]);
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });

  test("既存の単発の活動を繰り返しに変更すると 2 回目以降が作られる", async ({
    page,
  }) => {
    test.setTimeout(180000);
    const subject = `E2E単発繰返${generateRandomString(6)}`;
    try {
      // まず繰り返しでない活動を作る
      const single = await createEventViaModal(page, { subject });
      const before = await listEventsBySubject(subject, 1);
      expect(before, "作成直後は 1 件だけ").toHaveLength(1);

      // 編集画面で繰り返しに変更する(この経路は確認ダイアログが出ない)
      await convertEventToRecurring(page, single.recordId, "Daily", dayStr(3));

      const after = await listEventsBySubject(subject, 4);
      expect(
        after.map((e) => e.dateStart),
        "繰り返しに変更した時点で終了日までの回が作られること"
      ).toEqual([dayStr(0), dayStr(1), dayStr(2), dayStr(3)]);
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });

  test("#1857 招待コピーから「全ての活動」で更新すると全ての回に届く", async ({
    page,
  }) => {
    test.setTimeout(240000);
    const subject = `E2E招待全て${generateRandomString(6)}`;
    const location = `ALL${generateRandomString(4)}`;
    try {
      // 当日から 2 日後まで毎日 = 3 回。一般A を招待するので回ごとにコピーが付き計 6 件
      await createRecurringEvent(page, subject, "Daily", false, {
        limitDate: dayStr(2),
        inviteeUserNames: ["e2e_rep_a"],
      });

      const repWsId = await resolveUserWsId("e2e_rep_a");
      const created = await listEventsBySubject(subject, 6);
      expect(created, "本体 3 件 + 招待コピー 3 件").toHaveLength(6);

      const dates = Array.from(new Set(created.map((e) => e.dateStart))).sort();
      expect(dates, "3 回分の日付が並ぶこと").toHaveLength(3);

      // 2 回目の「招待された一般A のコピー」を起点にする(系列に載っていないレコード)
      const target = created.find(
        (e) => e.dateStart === dates[1] && e.assignedUserId === repWsId
      );
      expect(target, "2 回目の招待コピーが存在すること").toBeTruthy();

      await updateEventLocationWithScope(
        page,
        target!.recordId,
        location,
        "all"
      );

      await expect
        .poll(
          async () => {
            const rows = await listEventsBySubject(subject, undefined, 1);
            return rows.filter((e) => e.location === location).length;
          },
          {
            timeout: 60000,
            intervals: [1000, 1000, 2000, 3000],
            message: "本体・招待コピーの全 6 件に反映されること",
          }
        )
        .toBe(6);

      const after = await listEventsBySubject(subject, undefined, 1);
      expect(after, "回が増減していないこと").toHaveLength(6);
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });

  test("#1857 招待コピーから「以降の活動を含む」で更新すると以降の回だけに届く", async ({
    page,
  }) => {
    test.setTimeout(240000);
    const subject = `E2E招待以降${generateRandomString(6)}`;
    const location = `FUT${generateRandomString(4)}`;
    try {
      await createRecurringEvent(page, subject, "Daily", false, {
        limitDate: dayStr(2),
        inviteeUserNames: ["e2e_rep_a"],
      });

      const repWsId = await resolveUserWsId("e2e_rep_a");
      const created = await listEventsBySubject(subject, 6);
      expect(created, "本体 3 件 + 招待コピー 3 件").toHaveLength(6);

      const dates = Array.from(new Set(created.map((e) => e.dateStart))).sort();
      expect(dates).toHaveLength(3);

      const target = created.find(
        (e) => e.dateStart === dates[1] && e.assignedUserId === repWsId
      );
      expect(target, "2 回目の招待コピーが存在すること").toBeTruthy();

      await updateEventLocationWithScope(
        page,
        target!.recordId,
        location,
        "future"
      );

      // 2 回目・3 回目の 本体+コピー = 4 件だけに届く
      await expect
        .poll(
          async () => {
            const rows = await listEventsBySubject(subject, undefined, 1);
            return rows.filter((e) => e.location === location).length;
          },
          {
            timeout: 60000,
            intervals: [1000, 1000, 2000, 3000],
            message: "2 回目以降の 4 件に反映されること",
          }
        )
        .toBe(4);

      const after = await listEventsBySubject(subject, undefined, 1);
      expect(
        after.filter((e) => e.dateStart === dates[0]).map((e) => e.location),
        "1 回目には届かないこと"
      ).toEqual(["", ""]);
      expect(
        after
          .filter((e) => e.dateStart > dates[0])
          .every((e) => e.location === location),
        "2 回目以降には全て届くこと"
      ).toBe(true);
      // 「以降の活動を含む」で編集した回そのものが消えないこと(#1858 で修正した退行)
      expect(after, "回が増減していないこと").toHaveLength(6);
    } finally {
      await deleteAllEventsBySubject(subject);
    }
  });
});
