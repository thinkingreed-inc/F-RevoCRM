import { test, expect } from "../../fixtures/isolated";
import { generateRandomString } from "../../utils/util";
import { loginInIsolatedContext } from "../../utils/settings";
import { passwordFor } from "../../fixtures/seedSpec";
import { apiSession } from "../../utils/api";
import { frDelete } from "../../model/fetcher";
import {
  createEventViaApi,
  resolveUserWsId,
  fetchCalendarFeed,
  dayStr,
} from "../../utils/calendar";

/**
 * カレンダー(共有・可視性) — 元スプレッドシート `_カレンダー`
 *
 * 予定フィード(index.php?module=Calendar&action=Feed)を「他ユーザーの視点」で取得し、
 *  - 公開予定は非招待の他者からも件名まで見える(共有カレンダー表示)
 *  - 非公開予定は他者には「(名前) - 予定あり*」でマスクされ、URL(遷移リンク)も出ない
 * ことを検証する。閲覧者は上位役割 e2e_director(部長・非管理者)を用いる
 * (役割階層で配下 e2e_rep_a の予定をフィード取得できるため安定)。
 */
test.describe.serial("カレンダー(共有・可視性)", () => {
  test("公開予定は非招待の他ユーザーのカレンダーから件名まで閲覧できる(共有カレンダー)", async ({
    browser,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Eshare${generateRandomString(5)}`;
    const repAId = await resolveUserWsId("e2e_rep_a");
    // rep_a 所有の公開予定(director は招待していない)
    const wsId = await createEventViaApi({
      subject,
      ownerWsId: repAId,
      date: dayStr(0),
      timeStart: "10:00",
      timeEnd: "11:00",
      visibility: "Public",
    });
    try {
      const { context, page } = await loginInIsolatedContext(
        browser,
        "e2e_director",
        passwordFor("e2e_director")
      );
      try {
        const items = await fetchCalendarFeed(
          page,
          repAId,
          dayStr(-1),
          dayStr(2)
        );
        const hit = items.find((i) => i.title.includes(subject));
        expect(hit, `公開予定 ${subject} が共有カレンダーに件名で見えること`).toBeTruthy();
        // 公開予定は遷移リンク(url)も付与される
        expect(hit!.url).not.toBe("");
        expect(hit!.visibility).toBe("Public");
      } finally {
        await context.close();
      }
    } finally {
      const sn = await apiSession();
      await frDelete(sn, wsId);
    }
  });

  test("非公開予定は他ユーザーのカレンダーには「予定あり*」でマスクされる", async ({
    browser,
  }) => {
    test.setTimeout(90000);
    const subject = `E2Epriv${generateRandomString(5)}`;
    const repAId = await resolveUserWsId("e2e_rep_a");
    // rep_a 所有の非公開予定
    const wsId = await createEventViaApi({
      subject,
      ownerWsId: repAId,
      date: dayStr(0),
      timeStart: "14:00",
      timeEnd: "15:00",
      visibility: "Private",
    });
    try {
      const { context, page } = await loginInIsolatedContext(
        browser,
        "e2e_director",
        passwordFor("e2e_director")
      );
      try {
        const items = await fetchCalendarFeed(
          page,
          repAId,
          dayStr(-1),
          dayStr(2)
        );
        // 件名は決して露出しない
        const leaked = items.find((i) => i.title.includes(subject));
        expect(leaked, `非公開予定の件名 ${subject} は他者に露出しないこと`).toBeFalsy();
        // 該当時間帯の非公開項目が「予定あり*」でマスクされ、遷移リンクも空
        const masked = items.find(
          (i) =>
            i.visibility === "Private" &&
            i.title.includes("予定あり") &&
            i.title.includes("*")
        );
        expect(masked, "非公開予定が「予定あり*」でマスク表示されること").toBeTruthy();
        expect(masked!.url).toBe("");
      } finally {
        await context.close();
      }
    } finally {
      const sn = await apiSession();
      await frDelete(sn, wsId);
    }
  });
});
