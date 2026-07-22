import { test, expect } from "../../../fixtures/isolated";
import { url, generateRandomString } from "../../../utils/util";
import { loginInIsolatedContext } from "../../../utils/settings";
import { passwordFor } from "../../../fixtures/seedSpec";
import {
  createEventViaModal,
  deleteCalendarEvent,
  deleteEventViaDetail,
  expectEventDeleted,
} from "../../../utils/calendar";

/**
 * カレンダー(複数ユーザー) — 元スプレッドシート `_カレンダー招待`
 *
 * 作成者(admin)が他ユーザーを招待した予定を、その招待ユーザー自身のログインで
 * 閲覧できることを検証する(招待された予定は招待者のカレンダー/詳細から見える)。
 * seed ユーザー: 一般A=e2e_rep_a(「E2E 1課員」)、一般B=e2e_rep_b(「E2E 2課員」)。
 */
test.describe.serial("カレンダー(複数ユーザー): 招待", () => {
  test("招待された一般Aが、その予定を自分のログインで閲覧できる", async ({
    page,
    browser,
  }) => {
    test.setTimeout(120000);
    const subject = `E2Emuinv${generateRandomString(5)}`;
    let wsId = "";
    try {
      // 作成者(admin)が「E2E 1課員」(一般A=e2e_rep_a)を招待して予定作成
      const rec = await createEventViaModal(page, {
        subject,
        invitees: ["1課員"],
      });
      wsId = rec.wsId;

      // 一般A としてログインし、招待された予定の詳細を開けて件名が見える
      const { context, page: repPage } = await loginInIsolatedContext(
        browser,
        "e2e_rep_a",
        passwordFor("e2e_rep_a")
      );
      try {
        for (let attempt = 0; attempt < 3; attempt++) {
          await repPage.goto(
            url(
              `index.php?module=Calendar&view=Detail&record=${rec.recordId}&app=SALES`
            )
          );
          await repPage.waitForLoadState("networkidle");
          const hit = repPage
            .getByText(subject, { exact: false })
            .and(repPage.locator(":visible"))
            .first();
          if (await hit.isVisible().catch(() => false)) break;
          await repPage.waitForTimeout(1500);
        }
        await expect(
          repPage
            .getByText(subject, { exact: false })
            .and(repPage.locator(":visible"))
            .first()
        ).toBeVisible({ timeout: 15000 });
      } finally {
        await context.close();
      }
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("複数招待: 一般A・一般B を招集し、両者が予定を閲覧できる", async ({
    page,
    browser,
  }) => {
    test.setTimeout(150000);
    const subject = `E2Emumulti${generateRandomString(5)}`;
    let wsId = "";
    try {
      // 一般A(1課員)と 一般B(2課員)を招集
      const rec = await createEventViaModal(page, {
        subject,
        invitees: ["1課員", "2課員"],
      });
      wsId = rec.wsId;

      for (const u of ["e2e_rep_a", "e2e_rep_b"]) {
        const { context, page: p } = await loginInIsolatedContext(
          browser,
          u,
          passwordFor(u)
        );
        try {
          let ok = false;
          for (let attempt = 0; attempt < 3; attempt++) {
            await p.goto(
              url(
                `index.php?module=Calendar&view=Detail&record=${rec.recordId}&app=SALES`
              )
            );
            await p.waitForLoadState("networkidle");
            ok = await p
              .getByText(subject, { exact: false })
              .and(p.locator(":visible"))
              .first()
              .isVisible()
              .catch(() => false);
            if (ok) break;
            await p.waitForTimeout(1500);
          }
          expect(ok, `${u} が招待予定を閲覧できること`).toBe(true);
        } finally {
          await context.close();
        }
      }
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("3名招待: 一般A・一般B・部長を招集し、全員が予定を閲覧できる", async ({
    page,
    browser,
  }) => {
    test.setTimeout(180000);
    const subject = `E2Emu3${generateRandomString(5)}`;
    let wsId = "";
    try {
      // 一般A(1課員)・一般B(2課員)・部長 の 3 名を招集
      const rec = await createEventViaModal(page, {
        subject,
        invitees: ["1課員", "2課員", "部長"],
      });
      wsId = rec.wsId;

      for (const u of ["e2e_rep_a", "e2e_rep_b", "e2e_director"]) {
        const { context, page: p } = await loginInIsolatedContext(
          browser,
          u,
          passwordFor(u)
        );
        try {
          let ok = false;
          for (let attempt = 0; attempt < 3; attempt++) {
            await p.goto(
              url(
                `index.php?module=Calendar&view=Detail&record=${rec.recordId}&app=SALES`
              )
            );
            await p.waitForLoadState("networkidle");
            ok = await p
              .getByText(subject, { exact: false })
              .and(p.locator(":visible"))
              .first()
              .isVisible()
              .catch(() => false);
            if (ok) break;
            await p.waitForTimeout(1500);
          }
          expect(ok, `${u} が招待予定を閲覧できること`).toBe(true);
        } finally {
          await context.close();
        }
      }
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });

  test("#864 参加者(招待された一般A)が他者(admin)の予定を削除できる", async ({
    page,
    browser,
  }) => {
    test.setTimeout(120000);
    const subject = `E2Emudel${generateRandomString(5)}`;
    let wsId = "";
    try {
      // admin 作成・一般A を招待
      const rec = await createEventViaModal(page, {
        subject,
        invitees: ["1課員"],
      });
      wsId = rec.wsId;

      // 一般A としてログインし、参加者として他者(admin)の予定を削除する(#864 の仕様)
      const { context, page: repPage } = await loginInIsolatedContext(
        browser,
        "e2e_rep_a",
        passwordFor("e2e_rep_a")
      );
      try {
        await deleteEventViaDetail(repPage, rec.recordId);
      } finally {
        await context.close();
      }
      // admin 側 API で不在(削除済み)を確認
      await expectEventDeleted(subject);
      wsId = "";
    } finally {
      if (wsId) await deleteCalendarEvent(wsId);
    }
  });
});
