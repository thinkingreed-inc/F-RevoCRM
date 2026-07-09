import { test, expect, type Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle } from "../../utils/settings";

/**
 * C-02 役割 (ユーザー管理 > 役割)
 *
 * 役割ツリー。既存の役割「一般」の配下にテスト用の子役割を作成 → 名称変更 →
 * 削除(所属レコードは既存役割へ移譲)し、テストで作成した役割のみを対象にする
 * (組み込み役割は変更しない)。
 *
 * ツリーの1ノードは `li[data-roleid] > div.toolbar-handle` で、handle 内に
 * 役割リンク(a.btn)と、追加(+)/削除(ゴミ箱)の toolbar がある。
 */
test.describe.serial("管理: 役割 (Roles)", () => {
  const params = { module: "Roles", view: "Index" };
  const token = generateRandomString(6);
  const nameAdd = `e2eロール${token}`;
  const nameEdit = `e2eロール編集${token}`;

  // 役割名でその役割ノードの toolbar-handle を特定する
  const handle = (page: Page, name: string) =>
    page
      .locator("div.toolbar-handle")
      .filter({ has: page.getByRole("link", { name, exact: true }) })
      .first();

  test("役割の追加", async ({ page }) => {
    await gotoSettings(page, params);

    const parent = handle(page, "一般");
    await parent.hover();
    await parent.locator("span.fa.fa-plus-circle").click();

    await page.locator('input[name="rolename"]').fill(nameAdd);
    await saveAndSettle(page, page.locator("button.saveButton"));

    await gotoSettings(page, params);
    await expect(
      page.getByRole("link", { name: nameAdd, exact: true })
    ).toBeVisible();
  });

  test("役割の編集", async ({ page }) => {
    await gotoSettings(page, params);
    await page.getByRole("link", { name: nameAdd, exact: true }).click();

    const nameInput = page.locator('input[name="rolename"]');
    await expect(nameInput).toBeVisible();
    await nameInput.fill(nameEdit);
    await saveAndSettle(page, page.locator("button.saveButton"));

    await gotoSettings(page, params);
    await expect(
      page.getByRole("link", { name: nameEdit, exact: true })
    ).toBeVisible();
  });

  test("役割の削除", async ({ page }) => {
    await gotoSettings(page, params);

    // 移譲先(既存役割「一般」)の roleid を退避しておく
    const transferRoleId = await page
      .locator("li[data-roleid]")
      .filter({ has: page.getByRole("link", { name: "一般", exact: true }) })
      .first()
      .getAttribute("data-roleid");

    const node = handle(page, nameEdit);
    await node.hover();
    await node.locator("span.fa.fa-trash").click();

    // 移譲先ポップアップは別ウィンドウのため、移譲先フィールドを直接設定して回避する
    await expect(page.locator("#transfer_record_display")).toBeVisible();
    await page
      .locator("#transfer_record")
      .evaluate((el, v) => ((el as HTMLInputElement).value = v), transferRoleId || "");
    await page.locator("#transfer_record_display").fill("一般");
    await saveAndSettle(
      page,
      page.locator('.modal-content:visible button[name="saveButton"]')
    );

    await gotoSettings(page, params);
    await expect(
      page.getByRole("link", { name: nameEdit, exact: true })
    ).toHaveCount(0);
  });
});
