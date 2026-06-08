import { test, expect } from "@playwright/test";
import { describe } from "node:test";
import { generateRandomString, url } from "../utils/util";
import { sidebarTest } from "../utils/test";

describe("顧客企業モジュールのテスト", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(
      url("index.php?module=Accounts&view=List&viewname=4&app=MARKETING")
    );
  });

  test("顧客企業リストに表示されているべき要素のテスト", async ({ page }) => {
    await expect(page.getByText("顧客企業の追加").first()).toBeVisible();
    await expect(page.getByText("インポート").first()).toBeVisible();
    await expect(page.getByText("カスタマイズ").first()).toBeVisible();
    await expect(page.getByText("個人リスト").first()).toBeVisible();
    await expect(page.getByText("共有リスト").first()).toBeVisible();

    // カスタマイズを押したときの動作確認
    await page.getByText("カスタマイズ").first().click();
    await expect(page.getByText("顧客企業 項目の編集").first()).toBeVisible();
    await expect(
      page.getByText("顧客企業 ワークフローの編集").first()
    ).toBeVisible();
    await page.getByText("カスタマイズ").first().click(); //閉じる
  });

  test("サイドバーの開閉テスト", async ({ page }) => {
    await sidebarTest(page);
  });

  test("顧客企業の追加", async ({ page }) => {
    // 作成画面へ遷移
    await page.goto(url("index.php?module=Accounts&view=Edit&app=MARKETING"));

    const hash = generateRandomString(8);
    const accountValues = {
      accountname: `テスト企業${hash}`,
      email1: `email${hash}@example.com`,
    };

    // accountValuesの key をname属性に持つ要素に、valueを入力する
    for (const [key, value] of Object.entries(accountValues)) {
      await page.fill(`input[name="${key}"]`, value);
    }

    // 保存ボタンをクリック
    await page.click("text=保存");
    await page.waitForLoadState("networkidle");

    // リストへ遷移
    await page.goto(
      url("index.php?module=Accounts&view=List&viewname=4&app=MARKETING")
    );

    // 作成した企業がリストに表示されているか確認
    await expect(
      page.getByText(accountValues.accountname).first()
    ).toBeVisible();
  });

  test("顧客企業の編集", async ({ page }) => {
    // 作成画面へ遷移
    await page.goto(
      url("index.php?module=Accounts&view=List&viewname=4&app=MARKETING")
    );
    await page.getByText("テスト企業").first().click();
    // index.php?module=Accounts&view=Detail&record=8&app=MARKETING に遷移するのを待つ
    await page.waitForURL(/record=\d+&app=MARKETING$/);

    // Ajax関係の通信を適切にHandlingできなかったため、1秒間待つ処理をいれるが基本的にはアンチパターン
    await page.waitForTimeout(1000);

    /**
     * page.locator('.detailViewButtoncontainer')を取得し、その配下の要素の中から
     * テキストが「編集」の要素を取得し、
     * 最初の要素をクリックする
     * ※補足： 単純に編集の最初の要素、だけで取得しようとすると、「顧客企業 項目の編集」が隠れた箇所にあるため
     *         クリックしたいボタンをクリックすることができない。
     *         このようなケースの場合は、親子関係を利用してClassやID、その他のHTML属性を利用して取得する
     */
    await page
      .locator(".detailViewButtoncontainer")
      .getByText("編集")
      .first()
      .click();

    const hash = generateRandomString(8);
    const accountValues = {
      accountname: `テスト企業${hash}`,
      email1: `email${hash}@example.com`,
    };

    // accountValuesの key をname属性に持つ要素に、valueを入力する
    for (const [key, value] of Object.entries(accountValues)) {
      await page.fill(`input[name="${key}"]`, value);
    }

    // 保存ボタンをクリック
    await page.click("text=保存");
    await page.waitForLoadState("networkidle");

    // リストへ遷移
    await page.goto(
      url("index.php?module=Accounts&view=List&viewname=4&app=MARKETING")
    );

    // 作成した企業がリストに表示されているか確認
    await expect(
      page.getByText(accountValues.accountname).first()
    ).toBeVisible();
  });
});
