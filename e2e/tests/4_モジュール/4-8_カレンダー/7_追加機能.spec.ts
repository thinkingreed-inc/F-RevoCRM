import { test, expect } from "../../../fixtures/isolated";
import { url } from "../../../utils/util";
import { loginInIsolatedContext } from "../../../utils/settings";
import { passwordFor } from "../../../fixtures/seedSpec";
import { resolveUserWsId } from "../../../utils/calendar";

/**
 * カレンダー(追加機能) — 元スプレッドシート `_その他_カレンダー追加機能`
 *
 * このシートの 3 項目のうち #1191(共有メモ) は 2_モーダル.spec.ts で実装済み。
 * 本ファイルは残りの 2 項目を検証する:
 *   - #1186 システム管理者が他ユーザーのカレンダー設定を変更可能に
 *   - #1193 マイグループのフィード選択状態を記憶(組織/役割切替後も維持)
 *
 * 登場人物と seed ユーザーの対応:
 *   マネージャーA=e2e_mgr_a / マネージャーB=e2e_mgr_b / 一般A=e2e_rep_a / 一般B=e2e_rep_b
 */

/** webservice ユーザーID("19x7")から内部の数値ユーザーID("7")を取り出す。 */
function internalUserId(wsId: string): string {
  return wsId.includes("x") ? wsId.split("x")[1] : wsId;
}

test.describe.serial("カレンダー(追加機能)", () => {
  /**
   * #1186: システム管理者(admin)が、別ユーザー(マネージャーB=e2e_mgr_b)の
   * カレンダー設定「一週間の始めの曜日(dayoftheweek)」を変更して保存できる。
   *
   * Users_Calendar_View::checkPermission は admin もしくは本人のみ許可。admin が
   * `module=Users&view=Calendar&record=<対象>&mode=Edit` を開いて編集できることを、
   * 保存後に編集画面を開き直して値が永続していることで検証する。
   */
  test("#1186 管理者が他ユーザー(マネージャーB)のカレンダー設定「週の始まり」を変更できる", async ({
    page,
  }) => {
    test.setTimeout(120000);
    const targetId = internalUserId(await resolveUserWsId("e2e_mgr_b"));
    const editUrl = url(
      `index.php?module=Users&view=Calendar&record=${targetId}&mode=Edit`
    );

    const daySelect = page.locator('select[name="dayoftheweek"]');

    // 対象ユーザーの現在値を読み、別の曜日へ切り替える(既定 Monday → Sunday。
    // 既に Sunday の場合は Monday にして、テストの繰り返し実行でも値が必ず変わるようにする)。
    await page.goto(editUrl);
    await page.waitForLoadState("networkidle");
    await expect(daySelect).toBeAttached({ timeout: 15000 });
    const before = await daySelect.inputValue();
    const next = before === "Sunday" ? "Monday" : "Sunday";

    // dayoftheweek は select2 ウィジェット(select2-offscreen)。ネイティブ selectOption
    // だと select2 の初期化・同期と競合して稀に値が submit に乗らないため、
    // jQuery の val().trigger('change') で native と select2 の両方を確実に更新する。
    await page.evaluate((v) => {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (window as any).jQuery('select[name="dayoftheweek"]').val(v).trigger("change");
    }, next);
    // クライアント側で確実に値が切り替わってから保存する
    await expect(daySelect).toHaveValue(next);

    // 保存はネイティブ POST → カレンダー設定の詳細ビュー(view=Calendar・mode=Edit なし)へ
    // リダイレクトされる。クリック直後に画面遷移を待たずに次の goto を投げると進行中の
    // POST が中断され保存が飛ぶため、リダイレクト完了を待ってから検証に進む。
    await Promise.all([
      page.waitForURL(
        (u) =>
          u.toString().includes("view=Calendar") &&
          !u.toString().includes("mode=Edit"),
        { timeout: 20000 }
      ),
      page.locator("button.saveButton").click(),
    ]);
    await page.waitForLoadState("networkidle");

    // 編集画面を開き直し、対象ユーザーの週の始まりが保存値になっていることを確認
    await page.goto(editUrl);
    await page.waitForLoadState("networkidle");
    await expect(daySelect).toBeAttached({ timeout: 15000 });
    await expect(
      daySelect,
      `管理者による他ユーザーの週の始まり変更(${before}→${next})が永続すること`
    ).toHaveValue(next);
  });

  /**
   * #1193: 共有カレンダーの「マイグループ」でフィード(ユーザー)のチェックを外すと、
   * 組織/役割へ切り替えても(そこでは全選択で表示)、マイグループへ戻したとき外した状態が
   * 記憶されている。
   *
   * 記憶の実体は localStorage の disabledFeeds(Calendar.js)。マイグループ(select2 の
   * 既定値 'default')では CALENDAR_REMEMBER_FEED_SELECTION フラグに関係なく記憶が効く
   * (SharedCalendar.js::isRememberSelection)。役割/組織では(既定フラグ false のとき)
   * 記憶を無視して常に全選択になる。
   *
   * 前提(マイグループに一般A・一般Bが載っている状態)は、マネージャーA 自身が
   * CalendarUserActions::addUserCalendar(=「カレンダーの追加」)で追加して用意する。
   * seed ユーザーの calendarsharedtype は既定 'public'(Users.php)のためマイグループに出る。
   */
  test("#1193 マイグループのフィード選択状態を記憶する(役割切替後も維持)", async ({
    browser,
  }) => {
    test.setTimeout(150000);
    const repA = internalUserId(await resolveUserWsId("e2e_rep_a"));
    const repB = internalUserId(await resolveUserWsId("e2e_rep_b"));

    const { context, page } = await loginInIsolatedContext(
      browser,
      "e2e_mgr_a",
      passwordFor("e2e_mgr_a")
    );
    try {
      await page.goto(url(`index.php?module=Calendar&view=SharedCalendar&app=SALES`));
      await page.waitForLoadState("networkidle");
      await expect(page.locator("#calendar-groups")).toBeVisible({
        timeout: 15000,
      });

      // マネージャーA が 一般A・一般B を「マイグループ」に追加(visible=1)
      for (const id of [repA, repB]) {
        await page.evaluate(
          (sel) =>
            new Promise<boolean>((resolve, reject) => {
              // vtiger の app.request.post は CSRF トークンを付与して送る
              // eslint-disable-next-line @typescript-eslint/no-explicit-any
              (window as any).app.request
                .post({
                  data: {
                    module: "Calendar",
                    action: "CalendarUserActions",
                    mode: "addUserCalendar",
                    selectedUser: sel,
                    selectedColor: "#4a87ee",
                  },
                })
                .then((err: unknown) => (err ? reject(err) : resolve(true)));
            }),
          id
        );
      }
      await page.reload();
      await page.waitForLoadState("networkidle");
      await expect(page.locator("#calendar-groups")).toBeVisible({
        timeout: 15000,
      });

      const feedCb = (uid: string) =>
        page.locator(
          `#calendarview-feeds input.toggleCalendarFeed[data-calendar-userid="${uid}"]:not(.toggleSharedTodo)`
        );

      // select2(#calendar-groups)を jQuery 経由で切り替えて changeUserList を発火させる
      // (select2 のネイティブ change ではハンドラが確実に走らないため)
      const switchGroup = async (value: string) => {
        await page.evaluate((v) => {
          // eslint-disable-next-line @typescript-eslint/no-explicit-any
          (window as any).jQuery("#calendar-groups").val(v).trigger("change");
        }, value);
        // フィード一覧が再構築されるのを待つ
        await page.waitForTimeout(1500);
      };

      // マイグループ(default)を JS 経路で描画し直し、一般A・一般Bのフィードを確定させる
      await switchGroup("default");
      await expect(feedCb(repA)).toHaveCount(1);
      await expect(feedCb(repB)).toHaveCount(1);
      await expect(feedCb(repA)).toBeChecked();
      await expect(feedCb(repB)).toBeChecked();

      // 一般A のチェックを外す(change ハンドラが disableFeed('Events_<repA>') を実行)
      await feedCb(repA).uncheck();
      await expect(feedCb(repA)).not.toBeChecked();

      // localStorage の disabledFeeds に一般Aのキーが記録されている(記憶の実体)
      const disabledAfterUncheck = await page.evaluate(() => {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const key = (window as any).Calendar_Calendar_Js.disabledFeedsStorageKey;
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        return (window as any).app.storage.get(key, []) as string[];
      });
      expect(
        disabledAfterUncheck,
        "一般Aのフィードが記憶(disabledFeeds)に登録されること"
      ).toContain(`Events_${repA}`);
      expect(disabledAfterUncheck).not.toContain(`Events_${repB}`);

      // select2 で「役割」に切り替える(数値でない・default でもない値=役割)。
      // 役割ビューでは記憶を無視し、表示される全フィードがチェック済みになる。
      const roleValue = await page.evaluate(() => {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const opts = Array.from(
          document.querySelectorAll<HTMLOptionElement>(
            "#calendar-groups option"
          )
        );
        const role = opts.find(
          (o) => o.value !== "default" && !/^[0-9]+$/.test(o.value)
        );
        return role ? role.value : "";
      });
      expect(roleValue, "役割の選択肢が存在すること").not.toBe("");
      await switchGroup(roleValue);

      // 役割ビュー: 表示されている全ユーザーフィードがチェック済み(全選択)
      const allCheckedInRole = await page.evaluate(() => {
        const cbs = Array.from(
          document.querySelectorAll<HTMLInputElement>(
            "#calendarview-feeds input.toggleCalendarFeed:not(.toggleSharedTodo)"
          )
        ).filter((c) => c.getAttribute("data-calendar-userid"));
        return cbs.length > 0 && cbs.every((c) => c.checked);
      });
      expect(
        allCheckedInRole,
        "役割ビューでは記憶を無視して全フィードがチェック済みであること"
      ).toBe(true);

      // マイグループへ戻すと、外した一般Aは外れたまま・一般Bと自分はチェック済み(記憶が復元)
      await switchGroup("default");
      await expect(
        feedCb(repA),
        "マイグループへ戻すと一般Aのチェックは外れたまま(記憶)"
      ).not.toBeChecked();
      await expect(
        feedCb(repB),
        "マイグループへ戻しても一般Bはチェック済み"
      ).toBeChecked();
    } finally {
      await context.close();
    }
  });
});
