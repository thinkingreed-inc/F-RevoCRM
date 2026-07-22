import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { generateRandomString } from "../../utils/util";
import { gotoSettings, saveAndSettle, confirmYes } from "../../utils/settings";

/**
 * D-06 入力制限 (モジュール管理 > 入力制限 / CustomValidation)
 *
 * 一覧CRUD型。入力制限名(validationname)と検証コード(CodeMirror)を持つ
 * ルールを、追加→編集→削除で直列に検証する。
 * 一意トークンで名前を分離し、作成したルールのみを操作・後始末する。
 *
 * 検証コードは CodeMirror エディタに入るため、DOM の .CodeMirror インスタンス
 * 経由で setValue する(通常の input.fill では反映されない)。
 *
 * 注意: temp 由来では URL に block/fieldid を付けていたが、これらは左メニュー
 * ハイライト用で一覧表示には不要なため付けない(gotoSettings 方針)。
 */
test.describe.skip("管理: 入力制限 (CustomValidation)", () => {
  // 本環境(このcheckout)には CustomValidation モジュールが未導入のためスキップ
  const listParams = { module: "CustomValidation", view: "List" };
  const token = generateRandomString(8);
  // 追加時と編集時で名前を重複させない(部分一致による誤検出を避ける)
  const nameAdd = `e2evalidadd${token}`;
  const nameEdit = `e2evalidedit${token}`;

  // 検証コードを組み立てる(最小文字数を引数で切り替える)
  const buildValidationCode = (method: string, min: number, message: string) =>
    `$.validator.addMethod('${method}', function (value, element) {
  if (this.optional(element)) {
    return true;
  }
  var returnvalue = true;
  if (Array.isArray(value)) {
    value.forEach(function (arrayvalue) {
      if (arrayvalue.length < ${min}) {
        returnvalue = false;
      }
    });
  } else {
    if (value.length < ${min}) {
      returnvalue = false;
    }
  }
  return returnvalue;
}, '${message}');`;

  // CodeMirror エディタへコードを流し込む(インスタンス経由)
  const setCodeMirrorValue = async (page: Page, code: string) => {
    await page.waitForSelector(".CodeMirror");
    await page.evaluate((value) => {
      const cm = (document.querySelector(".CodeMirror") as any)?.CodeMirror;
      cm?.setValue(value);
    }, code);
  };

  // データ行(入力制限名を含む一覧行)を名前で特定
  const row = (page: Page, name: string) =>
    page.locator("tr").filter({ hasText: name });

  test("入力制限の追加", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 「追加」で編集フォームを開く
    await page.getByText("追加").first().click();
    await expect(page.locator('input[name="validationname"]')).toBeVisible();

    await page.locator('input[name="validationname"]').fill(nameAdd);
    await setCodeMirrorValue(
      page,
      buildValidationCode("morethan5count", 5, "5文字以上入力して下さい")
    );

    await saveAndSettle(page, page.getByText("保存").first());

    // 一覧に追加した入力制限名が現れること(リロード後=永続化を確認)
    await gotoSettings(page, listParams);
    await expect(row(page, nameAdd)).toBeVisible();
  });

  test("入力制限の編集", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 追加した行をクリックして編集フォームを開く
    await row(page, nameAdd).first().click();
    await expect(page.locator('input[name="validationname"]')).toBeVisible();

    await page.locator('input[name="validationname"]').fill(nameEdit);
    await setCodeMirrorValue(
      page,
      buildValidationCode("morethan5count", 7, "7文字以上入力して下さい")
    );

    await saveAndSettle(page, page.getByText("保存").first());

    // 新しい名前が現れ、元の名前は消えていること(リロード後=永続化を確認)
    await gotoSettings(page, listParams);
    await expect(row(page, nameEdit)).toBeVisible();
    await expect(row(page, nameAdd)).toHaveCount(0);
  });

  test("入力制限の削除", async ({ page }) => {
    await gotoSettings(page, listParams);

    // 対象行の削除アイコンを押す
    const target = row(page, nameEdit).first();
    await target.locator('a[title="削除"]').click();

    // 確認ダイアログの「はい」
    await confirmYes(page);
    await page.waitForLoadState("networkidle").catch(() => {});

    // 一覧から削除した入力制限名が消えていること(リロード後=永続化を確認)
    await gotoSettings(page, listParams);
    await expect(row(page, nameEdit)).toHaveCount(0);
  });
});
