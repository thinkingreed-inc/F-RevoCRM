import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings } from "../../utils/settings";

/**
 * C-04 共有ルール (ユーザー管理 > 共有ルール / 組織全体の共有設定)
 *
 * 【元スキップ理由】共有設定の *保存* は全レコードの共有権限の再計算を伴う重い全体操作で、
 *   反映が遅い環境ではレースし後続テストにも波及するため、書き込み検証は避ける。
 *
 * 【現方針】拡充ベースラインが用意する組織共有の状態を **読み取りのみ** で検証する（保存しない）:
 *   - Leads は Private(=3)（権限テスト用に Private 化済み）
 *   - Accounts は Public: Read/Create/Edit/Delete(=2)（既定）
 * 画面は module 行ごとに org 共有アクションのラジオを持つ（value = 0:公開読取専用 /
 * 1:公開R+CE / 2:公開R+CE+削除 / 3:非公開）。チェック済みラジオの value で判定する。
 */
test.describe("管理: 共有ルール (SharingAccess)", () => {
  const params = { module: "SharingAccess", view: "Index" };

  const checkedRadio = (page: Page, moduleName: string) =>
    page.locator(`tr[data-module-name="${moduleName}"] input[type="radio"]:checked`);

  test("組織共有の設定値が表示される(Leads=非公開 / Accounts=公開)", async ({
    page,
  }) => {
    await gotoSettings(page, params);
    await expect(checkedRadio(page, "Leads")).toHaveValue("3");
    await expect(checkedRadio(page, "Accounts")).toHaveValue("2");
  });
});
