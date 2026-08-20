import { test, expect } from "@playwright/test";
import { format, addDays } from "date-fns";
import { login, frCreate, frRetrieve } from "../model/fetcher";
import { url, generateRandomString } from "../utils/util";

/**
 * 概要ページ(サマリー)の活動ペインからステータスを変更したときに、
 * リクエストへ含まれない項目が既定値で上書きされないことを検証する。
 *
 * 上書きが起きると「行動(Call/Meeting)がToDo(Task)に変わる」
 * 「非公開の活動が公開になる」といった不具合になる。
 */

/** API セッション名 */
let sessionName = "";
/** 担当者(admin)の Webservice ID */
let ownerWsId = "";

/** Webservice ID (`<prefix>x<recordId>`) からレコードIDを取り出す */
const toRecordId = (webserviceId: string) => webserviceId.split("x")[1];

/** 活動の親となる案件をAPIで作成する */
const createPotential = async (hash: string) => {
  const created = await frCreate(sessionName, "Potentials", {
    potentialname: `E2E活動ペイン_${hash}`,
    closingdate: format(addDays(new Date(), 30), "yyyy-MM-dd"),
    assigned_user_id: ownerWsId,
    sales_stage: "Prospecting",
  });
  if (!created) {
    throw new Error("案件の作成に失敗しました");
  }
  return created.id;
};

/** 行動(電話・非公開)をAPIで作成する */
const createCall = async (parentId: string, hash: string) => {
  const day = format(addDays(new Date(), 1), "yyyy-MM-dd");
  const created = await frCreate(sessionName, "Events", {
    subject: `E2E行動_${hash}`,
    assigned_user_id: ownerWsId,
    date_start: day,
    time_start: "10:00",
    due_date: day,
    time_end: "11:00",
    duration_hours: "1",
    eventstatus: "Planned",
    activitytype: "Call",
    visibility: "Private",
    parent_id: parentId,
  });
  if (!created) {
    throw new Error("行動の作成に失敗しました");
  }
  return created.id;
};

/** ToDo(非公開)をAPIで作成する */
const createTask = async (parentId: string, hash: string) => {
  const created = await frCreate(sessionName, "Calendar", {
    subject: `E2EToDo_${hash}`,
    assigned_user_id: ownerWsId,
    date_start: format(addDays(new Date(), 1), "yyyy-MM-dd"),
    time_start: "10:00",
    due_date: format(addDays(new Date(), 2), "yyyy-MM-dd"),
    taskstatus: "Not Started",
    activitytype: "Task",
    visibility: "Private",
    parent_id: parentId,
  });
  if (!created) {
    throw new Error("ToDoの作成に失敗しました");
  }
  return created.id;
};

// APIログイン(getchallenge)を同時に走らせるとトークンが競合するため直列で実行する
test.describe.serial("概要ページの活動ペイン", () => {
  test.beforeAll(async () => {
    const response = await login(
      process.env.E2E_USER_NAME || "",
      process.env.E2E_USER_ACCESSKEY || ""
    );
    if (!response) {
      throw new Error("API login failed (activityPane)");
    }
    sessionName = response.sessionName;
    ownerWsId = response.userId;
  });

  test("行動のステータス変更で行動種別と公開範囲が変わらない", async ({
    page,
  }) => {
    const hash = generateRandomString(8);
    const potentialId = await createPotential(hash);
    const eventId = await createCall(potentialId, hash);

    await page.goto(
      url(
        `index.php?module=Potentials&view=Detail&record=${toRecordId(
          potentialId
        )}`
      )
    );

    const pane = page.locator("#relatedActivities");
    await expect(pane.getByTitle(`E2E行動_${hash}`)).toBeVisible();

    // ステータスのバッジをクリックして編集モードにし、「完了」へ変更する
    await pane
      .getByRole("button", { name: "計画済み - クリックして編集" })
      .click();
    await pane.getByLabel("Select eventstatus").click();
    await page.getByRole("option", { name: "完了" }).click();
    await pane.getByRole("button", { name: "保存" }).click();

    // 一覧が再取得され、バッジが「完了」になる
    await expect(
      pane.getByRole("button", { name: "完了 - クリックして編集" })
    ).toBeVisible();

    // 保存値はステータスだけが変わり、行動種別・公開範囲は元のまま
    const record = (await frRetrieve(sessionName, eventId)) as Record<
      string,
      string
    >;
    expect(record).toBeTruthy();
    expect(record.eventstatus).toBe("Held");
    expect(record.activitytype).toBe("Call");
    expect(record.visibility).toBe("Private");
  });

  test("ToDoのステータス変更で行動種別と公開範囲が変わらない", async ({
    page,
  }) => {
    const hash = generateRandomString(8);
    const potentialId = await createPotential(hash);
    const taskId = await createTask(potentialId, hash);

    await page.goto(
      url(
        `index.php?module=Potentials&view=Detail&record=${toRecordId(
          potentialId
        )}`
      )
    );

    const pane = page.locator("#relatedActivities");
    await expect(pane.getByTitle(`E2EToDo_${hash}`)).toBeVisible();

    // ステータスのバッジをクリックして編集モードにし、「完了」へ変更する
    await pane
      .getByRole("button", { name: "未着手 - クリックして編集" })
      .click();
    await pane.getByLabel("Select taskstatus").click();
    await page.getByRole("option", { name: "完了" }).click();
    await pane.getByRole("button", { name: "保存" }).click();

    // 一覧が再取得され、バッジが「完了」になる
    await expect(
      pane.getByRole("button", { name: "完了 - クリックして編集" })
    ).toBeVisible();

    // 保存値はステータスだけが変わり、行動種別・公開範囲は元のまま
    const record = (await frRetrieve(sessionName, taskId)) as Record<
      string,
      string
    >;
    expect(record).toBeTruthy();
    expect(record.taskstatus).toBe("Completed");
    expect(record.activitytype).toBe("Task");
    expect(record.visibility).toBe("Private");
  });
});
