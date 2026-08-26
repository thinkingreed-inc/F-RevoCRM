import { test } from "../../../fixtures/isolated";
import { url } from "../../../utils/util";
import { apiSession } from "../../../utils/api";
import { frQuery } from "../../../model/fetcher";

/**
 * 【調査用・一時】CI で カレンダー クイック作成モーダルの必須ピックリストに
 * 既定値が入らない原因を切り分けるための spec。
 *
 * ローカルでは `GetFields` が `defaultValue: "Planned" / "Call"` を返し、
 * モーダルの ステータス / 活動タイプ が埋まる。CI ではこの 2 つだけ空になる
 * （担当・表示は入る）。API レスポンスと DB 側の状態を CI のログに出して比較する。
 *
 * 原因が判明したら本ファイルは削除する。
 */
test.describe("DEBUG: カレンダー既定値の切り分け", () => {
  test("GetFields のレスポンスと DB 状態をログ出力する", async ({ page }) => {
    // モーダルが叩くのと同じ API を、ブラウザのセッションを共有して直接叩く
    await page.goto(url("index.php?module=Calendar&view=Calendar&app=SALES"));
    await page.waitForLoadState("networkidle");

    for (const moduleName of ["Events", "Calendar"]) {
      const res = await page.request.get(
        url(
          `index.php?module=${moduleName}&api=GetFields&view=quickcreate&include_recordtype_info=1`
        )
      );
      const body = await res.text();
      let json: any = null;
      try {
        json = JSON.parse(body);
      } catch {
        console.log(
          `DEBUG_GETFIELDS ${moduleName} status=${res.status()} 非JSON body(先頭300字)=${body.slice(0, 300)}`
        );
        continue;
      }
      const picked = (json.fields ?? [])
        .filter((f: any) =>
          [
            "eventstatus",
            "taskstatus",
            "activitytype",
            "visibility",
            "assigned_user_id",
            "taskpriority",
          ].includes(f.name)
        )
        .map((f: any) => ({
          name: f.name,
          mandatory: f.mandatory,
          defaultValue: f.defaultValue ?? null,
          fieldinfoDefault: f.fieldinfo?.defaultvalue ?? null,
          picklist: Object.keys(f.fieldinfo?.picklistvalues ?? {}),
          editablePicklist: Object.keys(f.fieldinfo?.editablepicklistvalues ?? {}),
        }));
      console.log(
        `DEBUG_GETFIELDS ${moduleName} status=${res.status()} totalFields=${json.totalFields} ` +
          `recordTypeInfo=${JSON.stringify(json.recordTypeInfo ?? null)} fields=${JSON.stringify(picked)}`
      );
    }

    // DB 側: ピックリスト値とロール割当、項目のデフォルト値
    const sn = await apiSession();
    const queries: Array<[string, string]> = [
      ["eventstatus_values", "SELECT eventstatus FROM Events LIMIT 1;"],
    ];
    for (const [label, q] of queries) {
      try {
        const rows = await frQuery(sn, q);
        console.log(`DEBUG_QUERY ${label} rows=${JSON.stringify(rows)?.slice(0, 300)}`);
      } catch (e) {
        console.log(`DEBUG_QUERY ${label} error=${String(e).slice(0, 200)}`);
      }
    }

    // 画面側: モーダルを開いた直後の各フィールドの値を時系列で確認する
    await page.evaluate(() => {
      // @ts-ignore - グローバルのカレンダーJS
      Calendar_Calendar_Js.showCreateEventModal();
    });
    for (const waitMs of [500, 2000, 5000, 10000]) {
      await page.waitForTimeout(waitMs === 500 ? 500 : 1500);
      const snapshot = await page.evaluate(() => {
        const val = (id: string) =>
          (document.querySelector(`#${id}`) as HTMLInputElement | null)?.value ?? "(要素なし)";
        return {
          subject: val("field_subject"),
          assigned: val("field_assigned_user_id"),
          eventstatus: val("field_eventstatus"),
          activitytype: val("field_activitytype"),
          visibility: val("field_visibility"),
        };
      });
      console.log(`DEBUG_MODAL after~${waitMs}ms ${JSON.stringify(snapshot)}`);
    }

    // コンソールエラーがあれば拾う(React 側の例外で既定値設定が止まる可能性)
    const errors: string[] = [];
    page.on("pageerror", (e) => errors.push(String(e).slice(0, 200)));
    page.on("console", (m) => {
      if (m.type() === "error") errors.push(m.text().slice(0, 200));
    });
    await page.waitForTimeout(1000);
    console.log(`DEBUG_CONSOLE_ERRORS ${JSON.stringify(errors)}`);
  });
});
