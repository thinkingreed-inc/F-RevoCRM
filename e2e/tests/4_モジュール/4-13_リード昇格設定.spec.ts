import type { Page } from "@playwright/test";
import { test, expect } from "../../fixtures/isolated";
import { gotoSettings } from "../../utils/settings";
import { generateRandomString } from "../../utils/util";
import {
  getConvertSettings,
  saveDefaults,
  saveLayout,
  saveMatchRules,
  withConvertSettingsLock,
  type ConvertSettingsInfo,
} from "../../utils/leadConvert";

/**
 * リードマッピング設定画面（Web コンポーネント <lead-mapping-settings>）— ローカル確認用
 * （CI の CI_SPECS には入れない）
 *
 * 昇格画面に出す項目・昇格時デフォルト値・昇格済み判定条件を画面から保存し、
 * 再読込しても保持されることを検証する。従来の画面（legacy=1）は G-01_リード設定 が担保する。
 *
 * 設定は CRM 全体で共有のため、テスト全体を withConvertSettingsLock で包み
 * （4-12_リード昇格 の前提づくりと排他）、変更前の設定を退避して最後に戻す。
 * 追加するのは案件の任意項目・案件の任意項目のデフォルト値・担当者の判定条件（携帯電話）で、
 * 並行して動く昇格テストの結果に影響しないものを選ぶ。
 */
test.describe("リードマッピング設定（Web コンポーネント）", () => {
  const openSettings = async (page: Page) => {
    await gotoSettings(page, { module: "Leads", view: "MappingDetail" });
    await expect(page.getByTestId("lead-mapping-row").first()).toBeVisible({ timeout: 20000 });
  };

  const openTab = async (page: Page, tabName: string, moduleLabel: string, panelTestId: string) => {
    await page.getByRole("tab", { name: tabName }).click();
    const panel = page.getByTestId(panelTestId);
    await panel.getByRole("radio", { name: moduleLabel }).click();
    return panel;
  };

  const saveAndWait = async (page: Page, saveTestId: string) => {
    await page.getByTestId(saveTestId).click();
    await expect(page.getByText("保存しました")).toBeVisible({ timeout: 15000 });
  };

  test("表示項目・昇格時デフォルト値・判定条件を保存でき、再読込しても保持される", async ({ page }) => {
    await openSettings(page);
    await withConvertSettingsLock(async () => {
      const original: ConvertSettingsInfo = await getConvertSettings(page);
      const potentials = original.modules.Potentials;
      const contacts = original.modules.Contacts;
      try {
        // ---- 昇格画面に出す項目: 案件に任意項目を 1 つ足す ----
        const layoutField = potentials.inputFields.find(
          (field) => !field.mandatory && !potentials.layout.includes(field.name)
        );
        expect(layoutField, "案件に追加できる任意項目がある").toBeTruthy();
        let panel = await openTab(page, "昇格画面に出す項目", potentials.label, "lead-mapping-tab-layout");
        await panel.getByRole("button", { name: `追加 ${layoutField!.label}` }).click();
        await saveAndWait(page, "lead-mapping-save-layout");

        // ---- 昇格時デフォルト値: 案件の「次のステップ」に値を入れる ----
        const defaultField = potentials.inputFields.find((field) => field.name === "nextstep");
        expect(defaultField, "案件に次のステップの項目がある").toBeTruthy();
        const defaultValue = `E2E ${generateRandomString(8)}`;
        panel = await openTab(page, "昇格時デフォルト値", potentials.label, "lead-mapping-tab-defaults");
        await panel.getByTestId("lead-mapping-defaults-add").selectOption("nextstep");
        await panel.locator('input[name="nextstep"]').fill(defaultValue);
        await saveAndWait(page, "lead-mapping-save-defaults");

        // ---- 判定条件: 担当者に「携帯電話」の条件を足す ----
        const ruleField = contacts.matchableFields.find((field) => field.name === "mobile");
        expect(ruleField, "担当者の判定条件に携帯電話を使える").toBeTruthy();
        panel = await openTab(page, "昇格済み判定条件", contacts.label, "lead-mapping-tab-match");
        await panel.getByTestId("lead-mapping-add-rule").click();
        const newRule = panel.getByTestId("lead-mapping-rule").last();
        await newRule.getByRole("combobox").selectOption("mobile");
        await saveAndWait(page, "lead-mapping-save-match");

        // ---- 再読込しても保持されている ----
        await openSettings(page);
        panel = await openTab(page, "昇格画面に出す項目", potentials.label, "lead-mapping-tab-layout");
        await expect(
          panel.getByTestId("lead-mapping-layout-displayed").locator(`li[data-field="${layoutField!.name}"]`)
        ).toBeVisible();

        panel = await openTab(page, "昇格時デフォルト値", potentials.label, "lead-mapping-tab-defaults");
        await expect(panel.locator('input[name="nextstep"]')).toHaveValue(defaultValue);

        panel = await openTab(page, "昇格済み判定条件", contacts.label, "lead-mapping-tab-match");
        await expect(panel.getByTestId("lead-mapping-rule").last()).toContainText(ruleField!.label);

        // API から見ても保存されている
        const saved = await getConvertSettings(page);
        expect(saved.modules.Potentials.layout).toContain(layoutField!.name);
        expect(saved.modules.Potentials.defaults.nextstep).toBe(defaultValue);
        expect(saved.modules.Contacts.matchRules).toContainEqual(["mobile"]);
      } finally {
        // 変更前の設定に戻す
        await saveLayout(page, "Potentials", potentials.layout);
        await saveDefaults(page, "Potentials", potentials.defaults);
        await saveMatchRules(page, "Contacts", contacts.matchRules);
      }
    });
  });
});
