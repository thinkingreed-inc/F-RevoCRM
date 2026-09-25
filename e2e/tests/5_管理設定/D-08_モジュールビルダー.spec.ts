import { test, expect } from "../../fixtures/isolated";
import type { Page } from "@playwright/test";
import { gotoSettings } from "../../utils/settings";

/**
 * D-08 モジュールビルダー / モジュール管理 (ModuleManager)
 *
 * 【スコープの選択】
 * temp 版は `module=ModuleBuilder` でモジュール本体を新規作成・保存していたが、
 * 本リポジトリには「モジュールを画面から新規作成するウィザード」自体が存在せず
 * (ModuleBuilder モジュールは無く、モジュール管理=ModuleManager が持つのは
 *  モジュール一覧と「Zipファイルからインストール」の取り込みウィザードのみ)、
 * モジュールの作成/取り込みは不可逆・永続かつ Zip 実体も要するため実行しない。
 *
 * そこで課題ルールの代替方針に従い、
 *   1) モジュール管理一覧が表示され、モジュールカードが並ぶこと
 *   2) 「Zipファイルからインストール」取り込みウィザードが開き、
 *      最初のステップの必須 UI(免責同意チェック・ファイル選択・取り込みボタン)が存在すること
 *   3) 免責同意にチェックするとファイル選択 UI が有効化される(=フォームが機能する)こと
 * を、破壊的操作(実際の取り込み)を行わずに検証する。
 * 実ファイルのアップロードや取り込み実行は行わないため、環境に副作用を残さない。
 */
test.describe.serial("管理: モジュールビルダー (ModuleManager)", () => {
  // 一覧画面 (モジュール管理)
  const listParams = { module: "ModuleManager", view: "List" };
  // 取り込みウィザードの最初のステップ (Zip からインストール)
  const importParams = {
    module: "ModuleManager",
    view: "ModuleImport",
    mode: "importUserModuleStep1",
  };

  // 取り込みウィザード(ImportUserModuleStep1.tpl)にスコープする。
  // フォーム #importUserModule は hidden input と Bootstrap グリッド行が主で、
  // フォーム要素自体は不可視判定になり得るため form:visible には依存しない。
  // ラッパ #importModules は DOM に存在すれば十分(見出し・チェック・ボタンで表示検証する)。
  const importForm = (page: Page) => page.locator("#importUserModule");

  test("モジュール管理一覧が表示される", async ({ page }) => {
    await gotoSettings(page, listParams);

    // モジュール一覧テーブルと、少なくとも1つのモジュールカードが表示されること
    await expect(page.locator("table.modulesTable")).toBeVisible();
    await expect(
      page.locator("input[name='moduleStatus']").first()
    ).toBeVisible();

    // Zip からインストールへ入る導線ボタンが存在すること
    await expect(
      page.getByRole("button", { name: "Zipファイルからインストール" })
    ).toBeVisible();
  });

  test("Zip 取り込みウィザードの最初のステップが開き必須 UI が揃う", async ({
    page,
  }) => {
    await gotoSettings(page, importParams);

    // ウィザードのラッパと見出しが表示されること(ImportUserModuleStep1.tpl)
    await expect(
      page.getByRole("heading", { name: "Zipファイルからインストール" })
    ).toBeVisible();

    // フォームは DOM 上に存在すること(要素自体の可視性には依存しない)
    const form = importForm(page);
    await expect(form).toHaveCount(1);

    // 最初のステップの必須要素: 免責同意チェック / ファイル選択 / 取り込みボタン
    const disclaimer = form.locator('input[name="acceptDisclaimer"]');
    const fileInput = form.locator('input[name="moduleZip"]');
    const importButton = form.locator('button[name="importFromZip"]');

    // 免責同意チェックと取り込みボタンは表示されている(取り込みボタンは初期非活性)
    await expect(disclaimer).toBeVisible();
    await expect(importButton).toBeVisible();
    await expect(importButton).toBeDisabled();

    // ファイル選択 input は DOM 上には存在する(初期はラッパごと非表示)
    await expect(fileInput).toHaveCount(1);

    // ファイル選択ラッパ(.fileUploadBtn の親 div)は免責同意前は非表示
    const fileUploadWrap = form.locator(".fileUploadBtn").locator("..");
    await expect(fileUploadWrap).toBeHidden();
  });

  test("免責同意にチェックするとファイル選択 UI が有効化される", async ({
    page,
  }) => {
    await gotoSettings(page, importParams);

    const form = importForm(page);
    await expect(form).toHaveCount(1);

    const disclaimer = form.locator('input[name="acceptDisclaimer"]');
    const fileUploadWrap = form.locator(".fileUploadBtn").locator("..");

    // 初期は非表示 → チェックで表示に変わること(フォームの JS が機能している)
    await expect(disclaimer).toBeVisible();
    await expect(fileUploadWrap).toBeHidden();
    await disclaimer.check();
    await expect(fileUploadWrap).toBeVisible();

    // チェックを外すと再び非表示に戻ること
    await disclaimer.uncheck();
    await expect(fileUploadWrap).toBeHidden();

    // ここまでで実際の取り込みは一切行わない(破壊的操作なし)。
  });
});
