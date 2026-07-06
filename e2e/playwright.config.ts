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
  /* CI の並列度。per-worker セッション分離(fixtures/isolated.ts)+ 個別競合の根治
   * (列検索の CustomView 汚染→All 固定 / 保存前のモーダル閉じ保証 / 条件追加の再試行)で
   * workers=2 は実測フレーク 0・CI 緑で安定。
   *
   * ※ 3 以上に上げると、モーダル多用テスト(common.customview の切替/複製 等)が
   *   非決定的にタイムアウトする(worker 数の問題ではなく、待ち条件の脆さ:
   *   networkidle 依存が多数 / 固定 waitForTimeout / 非同期リフレッシュ待ちの甘さ)。
   *   4 で回すには先にこれらの「OK 条件」を条件ベース待ちへ根治する必要がある。
   *   それまでの安定上限は 2(retry で隠さず実際に安定させる方針)。 */
  workers: process.env.CI ? 2 : undefined,
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
