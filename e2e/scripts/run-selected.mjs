#!/usr/bin/env node
/**
 * カタログ画面 (docs/e2e-catalog/index.html) が書き出した選択 JSON を読み、
 * 選ばれたテストだけを Playwright で実行する。
 *
 * 使い方:
 *   cd e2e
 *   node scripts/run-selected.mjs ~/Downloads/e2e-selection.json
 *   npm run test:e2e:selected -- ~/Downloads/e2e-selection.json
 *
 *   # Playwright への追加オプションはそのまま後ろに渡せる
 *   node scripts/run-selected.mjs sel.json --ui
 *   node scripts/run-selected.mjs sel.json --headed --workers=1
 *
 *   # 実行せずコマンドだけ見る
 *   node scripts/run-selected.mjs sel.json --dry-run
 *
 * 選択 JSON の形式 (schemaVersion: 1):
 *   { "tests": [ { "grep": "<正規表現>", "location": "...", "title": "...", "skipped": false }, ... ] }
 *
 * 絞り込みは `--grep` に「選択テストのフルタイトル正規表現」を OR 結合して渡す。
 * (Playwright の grep は `<プロジェクト> <ファイル> <describe…> <テスト名>` を
 *  スペースで連結した文字列に対してマッチするため、同名テストでもファイルパスを
 *  含めることで一意に指定できる)
 *
 * 認証情報等はリポジトリルートの .env から読む (npm run test:e2e と同じ挙動)。
 * setup プロジェクト (auth/seed) は依存関係により常に実行される。
 */

import { spawn } from "node:child_process";
import { readFileSync, existsSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const E2E_DIR = resolve(__dirname, "..");
const REPO_ROOT = resolve(E2E_DIR, "..");

const argv = process.argv.slice(2);
if (!argv.length || argv[0].startsWith("-")) {
  console.error(
    [
      "使い方: node scripts/run-selected.mjs <選択JSONのパス> [playwright への追加オプション]",
      "",
      "例:",
      "  node scripts/run-selected.mjs ~/Downloads/e2e-selection.json",
      "  node scripts/run-selected.mjs sel.json --ui",
      "  node scripts/run-selected.mjs sel.json --dry-run",
      "",
      "選択JSON は docs/e2e-catalog/index.html の「選択をJSONで保存」で作成する。",
    ].join("\n")
  );
  process.exit(2);
}

const jsonPath = resolve(process.cwd(), argv[0]);
const passthrough = argv.slice(1).filter((a) => a !== "--dry-run");
const dryRun = argv.includes("--dry-run");

if (!existsSync(jsonPath)) {
  console.error(`!! 選択JSON が見つかりません: ${jsonPath}`);
  process.exit(1);
}

let selection;
try {
  selection = JSON.parse(readFileSync(jsonPath, "utf8"));
} catch (e) {
  console.error(`!! 選択JSON の解析に失敗しました: ${e.message}`);
  process.exit(1);
}

const tests = Array.isArray(selection) ? selection : selection.tests;
if (!Array.isArray(tests) || tests.length === 0) {
  console.error("!! 選択されたテストがありません (tests が空)。");
  process.exit(1);
}

/* --- 選択内容の要約表示 ------------------------------------------------ */

const skipped = tests.filter((t) => t.skipped);
console.log(`==> 選択: ${tests.length} 件 (${jsonPath})`);
for (const t of tests) {
  console.log(`    ${t.skipped ? "[skip]" : "      "} ${t.location || ""} ${t.title || ""}`);
}
if (skipped.length) {
  console.log(
    `\n!! うち ${skipped.length} 件はコード上で skip 指定されています。` +
      `実行しても skipped 扱いになります (spec 側の test.skip / describe.skip を外す必要があります)。`
  );
}

const patterns = tests
  .map((t) => t.grep)
  .filter((g) => typeof g === "string" && g.length > 0);
if (!patterns.length) {
  console.error("!! grep パターンを含むテストがありません。カタログを再生成してください。");
  process.exit(1);
}
const grep = patterns.map((p) => `(?:${p})`).join("|");

/* --- .env の読み込み (npm run test:e2e と同じ挙動) --------------------- */

function loadDotenv(file) {
  if (!existsSync(file)) return {};
  const out = {};
  for (const line of readFileSync(file, "utf8").split("\n")) {
    const m = line.match(/^\s*([\w.-]+)\s*=\s*(.*)\s*$/);
    if (!m || line.trim().startsWith("#")) continue;
    let v = m[2].trim();
    if (
      (v.startsWith('"') && v.endsWith('"')) ||
      (v.startsWith("'") && v.endsWith("'"))
    ) {
      v = v.slice(1, -1);
    }
    out[m[1]] = v;
  }
  return out;
}

const dotenv = loadDotenv(resolve(REPO_ROOT, ".env"));
// 既に環境変数が指定されていればそちらを優先する
const env = { ...dotenv, ...process.env };

/* --- 実行 -------------------------------------------------------------- */

const args = ["playwright", "test", "--grep", grep, ...passthrough];

console.log(`\n==> 実行コマンド (cwd: e2e)\n    npx playwright test --grep '<選択 ${patterns.length} 件>' ${passthrough.join(" ")}`.trimEnd());
if (dryRun) {
  console.log("\n--dry-run のため実行しません。実際の grep は以下:\n");
  console.log(grep);
  process.exit(0);
}

const child = spawn("npx", args, { cwd: E2E_DIR, env, stdio: "inherit" });
child.on("exit", (code) => process.exit(code ?? 1));
