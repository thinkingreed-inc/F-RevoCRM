import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// import dotenv from 'dotenv';
// dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: '.',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* CI の並列度 = 4(実行時間を最優先)。per-worker セッション分離(fixtures/isolated.ts)+
   * 高並列で顕在化する待ち条件の根治を積み上げている:
   *  - 列検索の CustomView 汚染 → 一覧を All CV に固定(utils/listview.ts)
   *  - 保存ボタンのモーダル横取り → 保存前に #popupModal の閉じ切りを保証(model/FrTest.ts)
   *  - 条件追加ボタンの空振り → 行が出るまで再試行(common.customview-condition)
   *  - サイドバー CV が 10 件超で隠れる → 「もっと」トグルを展開してから探す(common.customview)
   * 残る単一コンテナ由来の稀なタイムアウトは retries=2 で吸収する(flaky 許容の運用方針)。
   * 新たな flaky が出たら「固定待ち/networkidle → 条件ベース待ち」へ都度根治していく。 */
  workers: process.env.CI ? 4 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  // CI では GitHub 注釈(失敗をrun/PRにインライン表示) + HTMLレポート(artifact) +
  // JSON(ジョブサマリ生成用) を出す。ローカルは従来どおり HTML のみ。
  reporter: process.env.CI
    ? [
        ['list'],
        ['github'],
        ['html', { open: 'never' }],
        ['json', { outputFile: 'playwright-results.json' }],
      ]
    : 'html',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    // baseURL: 'http://127.0.0.1:3000',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    /* 失敗時のみスクリーンショットを取得。HTMLレポート(artifact)に失敗テストと共に表示される。 */
    screenshot: 'only-on-failure',
  },
  // timeout: 10000,

  /* Configure projects for major browsers */
  projects: [
    // 起点: ブラウザ認証 + API セッション(sessionName/userId)を「一度だけ」取得。
    { name: 'setup', testMatch: /auth\.setup\.ts/ },
    // シード: setup 完了後に実行し、保存済みセッションを使い回す(再 login しない)。
    // これにより getchallenge の競合が起きず、workers=1 に頼らなくてよい。
    {
      name: 'seed',
      testMatch: /seed\.setup\.ts/,
      dependencies: ['setup'],
    },
    {
      name: 'chrome',
      use: {
        ...devices['Desktop Chrome'],
        headless: true,
        launchOptions: {
          args: [],
        },
        storageState: '.auth/user.json',
      },
      dependencies: ['setup', 'seed'],
    },
  
    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },

    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://127.0.0.1:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});
