import { test, expect } from '@playwright/test';
import { describe } from 'node:test';
import { url, waitSeconds } from '../utils/util';
import { sidebarTest } from '../utils/test';

describe('基本機能のテスト', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(url('index.php'));
    await page.waitForLoadState('networkidle');
  });

  test('トップページに表示されているべき要素のテスト', async ({ page }) => {
    await expect(page.getByText('マイダッシュボード').first()).toBeVisible();
    await expect(page.getByText('デフォルト').first()).toBeVisible();
    await expect(page.getByText('その他').first()).toBeVisible();
    await expect(page.getByText('ウィジェットの追加').first()).toBeVisible();

    // その他を押したときの動作確認
    await page.getByText('その他').first().click();
    await expect(page.getByText('ダッシュボードの追加').first()).toBeVisible();
    await expect(page.getByText('タブの並び替え').first()).toBeVisible();
    await page.getByText('その他').first().click();

    // ウィジェットの追加を押したときの動作確認
    await page.getByText('ウィジェットの追加').first().click();
    await expect(page.getByText('トップ案件').first()).toBeVisible();
    await expect(page.getByText('期限切れの活動').first()).toBeVisible();
    await expect(page.getByText('ミニリスト').first()).toBeVisible();
    await page.getByText('ウィジェットの追加').first().click();
  });

  test('サイドバーの開閉テスト', async ({ page }) => {
    await sidebarTest(page);
  });
});

