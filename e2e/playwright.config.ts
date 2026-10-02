import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// import dotenv from 'dotenv';
// dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * user_privileges_<id>.php を作り直す管理設定テスト。
 *
 * ロール/プロファイル/グループ/ユーザーの更新は所属ユーザー全員分の権限ファイルを
 * 再生成する。再生成中はファイルが一時的に空になり、それを読んだ別リクエストが
 * Fatal error で白画面になるため、一般テストとは時間を分けて実行する
 * (下の 'chrome-privileges' プロジェクト)。
 * 詳細は fixtures/userPrivilegesLock.ts 参照。
 */
const PRIVILEGE_SPECS = /5_管理設定[\\/](C-0[1-5]|I-0[12])_/;

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: '.',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* CI は 2 回、ローカルは 1 回リトライする。
   * ローカルを 0 にしていると、並列実行の負荷で出た一過性のタイムアウトと
   * 実際のバグが見分けられず、無い原因を追うことになる。1 回リトライして
   * 通れば flaky、落ちれば真の不具合、と結果だけで切り分けられる。 */
  retries: process.env.CI ? 2 : 1,
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
      testIgnore: PRIVILEGE_SPECS,
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
    // 権限ファイルを作り直す管理設定テスト。chrome の完了後に実行して一般テストを
    // 巻き込まないようにし、テスト同士は fixtures/privileges.ts のロックで直列化する。
    // ロック待ちの間も実行時間に算入されるため、既定の 30 秒では待ちきれない。
    // ワーカーのログインもロックを取る(fixtures/isolated.ts)ので、
    // 他ワーカーのテスト 1 件分を待てる長さにしておく。
    {
      name: 'chrome-privileges',
      testMatch: PRIVILEGE_SPECS,
      timeout: 120_000,
      use: {
        ...devices['Desktop Chrome'],
        headless: true,
        launchOptions: {
          args: [],
        },
        storageState: '.auth/user.json',
      },
      dependencies: ['setup', 'seed', 'chrome'],
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
