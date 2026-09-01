#!/usr/bin/env node
/**
 * E2E テストカタログ生成スクリプト。
 *
 * 「全 E2E テストの一覧」と「PR (GitHub Actions) で実際に実行されるか」を
 * まとめた静的データを docs/e2e-catalog/ に書き出す。
 *
 * 判定方法:
 *   1. `playwright test --list` … リポジトリの全テスト集合
 *   2. `<CI の env> playwright test --list <CI の spec 引数>` … CI で走る集合
 *   CI 側の env / spec 引数は `e2e/ci/run-e2e.sh` から抽出する。
 *   CI が spec を絞っていない場合は 1 と同じ集合になる (= 全 spec 実行)。
 *
 * 2 回 list する理由: CI サブセットは「spec ファイルの限定」だけでなく
 * `E2E_SCOPE=ci` のような環境変数によるテスト生成の絞り込み (マトリクスの
 * 代表モジュールのみ等) も伴うため、実際に collect させるのが最も正確。
 *
 * 出力:
 *   docs/e2e-catalog/catalog.js   … ブラウザ用 (window.E2E_CATALOG = {...})
 *                                    file:// でも fetch 無しで読めるよう JS にする
 *   docs/e2e-catalog/catalog.json … 機械可読用 (スクリプトから使う場合)
 *
 * 使い方:
 *   cd e2e && npm run e2e:catalog
 *
 * 注意: docs/ は .gitignore 対象 (docs/*)。出力はローカル確認用であり
 *       コミットされない。カタログが古くなったら本スクリプトを再実行する。
 */

import { execFileSync } from "node:child_process";
import { readFileSync, writeFileSync, mkdirSync, existsSync } from "node:fs";
import { dirname, resolve, relative } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const E2E_DIR = resolve(__dirname, "..");
const REPO_ROOT = resolve(E2E_DIR, "..");
const OUT_DIR = resolve(REPO_ROOT, "docs/e2e-catalog");
const WORKFLOW_FILE = resolve(REPO_ROOT, ".github/workflows/e2e.yml");
const CI_RUNNER = resolve(E2E_DIR, "ci/run-e2e.sh");

/* ------------------------------------------------------------------ *
 * 1. playwright --list の JSON を取得
 * ------------------------------------------------------------------ */

/**
 * @param {string[]} specArgs 位置引数で渡す spec パス (空なら全 spec)
 * @param {Record<string,string>} extraEnv 追加の環境変数
 */
function listTests(specArgs = [], extraEnv = {}) {
  const raw = execFileSync(
    "npx",
    ["playwright", "test", "--list", "--reporter=json", ...specArgs],
    {
      cwd: E2E_DIR,
      encoding: "utf8",
      maxBuffer: 256 * 1024 * 1024,
      env: { ...process.env, ...extraEnv },
    }
  );
  return JSON.parse(raw);
}

/** --list の JSON から「spec.id-projectId」の集合を作る */
function idSet(listJson) {
  const ids = new Set();
  const walk = (suite) => {
    for (const spec of suite.specs || [])
      for (const t of spec.tests || []) ids.add(`${spec.id}-${t.projectId}`);
    for (const child of suite.suites || []) walk(child);
  };
  for (const s of listJson.suites || []) walk(s);
  return ids;
}

/* ------------------------------------------------------------------ *
 * 2. ソースからの情報抽出 (概要コメント / スキップ理由)
 * ------------------------------------------------------------------ */

const srcCache = new Map();
function srcLines(relPath) {
  if (!srcCache.has(relPath)) {
    const abs = resolve(E2E_DIR, relPath);
    srcCache.set(
      relPath,
      existsSync(abs) ? readFileSync(abs, "utf8").split("\n") : []
    );
  }
  return srcCache.get(relPath);
}

/** JSDoc ブロックの中身を行配列で返す (先頭の `*` を除去) */
function stripDocBlock(lines) {
  return lines
    .map((l) => l.trim().replace(/^\/\*+\s?/, "").replace(/\s*\*+\/$/, "").replace(/^\*\s?/, ""))
    .filter((l, i, arr) => !(l === "" && (i === 0 || i === arr.length - 1)));
}

/**
 * ファイル冒頭 (import 群の直後) の JSDoc から概要を抽出する。
 * 「見出し行 + 空行 + 本文」形式なら1行目を見出しとして切り出す。
 */
function fileSummary(relPath) {
  const lines = srcLines(relPath);
  let start = -1;
  for (let i = 0; i < Math.min(lines.length, 60); i++) {
    if (lines[i].trim().startsWith("/**")) {
      start = i;
      break;
    }
  }
  if (start < 0) return null;
  let end = start;
  while (end < lines.length && !lines[end].includes("*/")) end++;
  const raw = stripDocBlock(lines.slice(start, end + 1));
  // 「※スキップ理由:」以降は理由側で扱うので概要からは落とす
  const cut = raw.findIndex((l) => /スキップ理由/.test(l));
  const kept = cut >= 0 ? raw.slice(0, cut) : raw;
  const body = kept.filter((l) => l !== "");
  if (!body.length) return null;
  const isHeadline = kept.length > 1 && kept[1] === "";
  return isHeadline
    ? { headline: kept[0], detail: body.slice(1).join(" ") }
    : { headline: null, detail: body.join(" ") };
}

const decomment = (t) =>
  t.replace(/^\/\/\s?/, "").replace(/^\/\*+\s?/, "").replace(/^\*\s?/, "").replace(/\s*\*\/$/, "");

/**
 * skip アノテーションの位置 (test.skip / describe.skip の行) からスキップ理由を拾う。
 *
 * 本リポジトリには2つの書き方があるため両方に対応する:
 *   A) skip 行の「直前」の JSDoc に `※スキップ理由: …`
 *   B) skip 行の「直後」の行コメント (例: `// 本環境には … 未導入のためスキップ`)
 * どちらも無い場合は skip 呼び出しの第2引数 (文字列リテラル) を理由として使う。
 */
function skipReason(relPath, line) {
  const lines = srcLines(relPath);
  if (!lines.length || !line) return null;

  // A) 直前のコメントブロックを遡る
  const buf = [];
  for (let i = line - 2; i >= 0; i--) {
    const t = lines[i].trim();
    if (t === "") {
      if (buf.length) break;
      continue;
    }
    if (t.startsWith("*/")) continue;
    if (t.startsWith("//") || t.startsWith("*") || t.startsWith("/*")) {
      buf.unshift(decomment(t));
      if (t.startsWith("/*")) break;
      continue;
    }
    break;
  }
  const before = buf.join(" ").replace(/\s+/g, " ");
  const m = before.match(/※?\s*スキップ理由[:：]\s*(.+)$/);
  if (m) return m[1].trim();

  // B) 直後の数行の行コメント (「スキップ」を含むものを理由とみなす)
  const after = [];
  for (let i = line; i < Math.min(lines.length, line + 4); i++) {
    const t = lines[i].trim();
    if (t.startsWith("//")) {
      after.push(decomment(t));
      continue;
    }
    if (t === "") continue;
    break;
  }
  const hit = after.find((l) => /スキップ|skip/i.test(l));
  if (hit) return hit.trim();

  // C) skip 呼び出しの引数に書かれた理由 (例: test.skip(true, "未有効化(展開ゲート)"))
  const arg = (lines[line - 1] || "").match(/skip\s*\([^,]*,\s*["'`](.+?)["'`]\s*\)/);
  return arg ? arg[1] : null;
}

/* ------------------------------------------------------------------ *
 * 2.5 実測結果 (playwright の json レポーター) の取り込み
 * ------------------------------------------------------------------ */

/**
 * `--results <path>`(複数可)で渡された json レポーター出力を読み、
 * テスト ID -> 実測ステータスの索引を作る。
 *
 * 指定が無ければ既定の出力先(e2e/playwright-results.json と
 * e2e/playwright-results-serial.json)を探す。
 *
 * **なぜ必要か**: `--list` は「収集結果」しか分からない。実行中に条件判定で
 * skip されるテスト(マトリクスの `test.skip(cap !== "run", …)` など)は収集時には
 * 見えず、カタログ上「PRで実行」に数えられてしまう。実測を突き合わせることで
 * 「収集されたが実行時に skip された」件数を分離できる。
 */
function parseResultArgs(argv) {
  const out = [];
  for (let i = 0; i < argv.length; i++) {
    if (argv[i] === "--results" && argv[i + 1]) {
      out.push(resolve(process.cwd(), argv[++i]));
    }
  }
  if (out.length) return out;
  for (const name of ["playwright-results.json", "playwright-results-serial.json"]) {
    const abs = resolve(E2E_DIR, name);
    if (existsSync(abs)) out.push(abs);
  }
  return out;
}

function loadRunResults(paths) {
  const byId = new Map();
  const sources = [];
  for (const p of paths) {
    if (!existsSync(p)) {
      console.log(`!! 実測結果が見つかりません(スキップ): ${p}`);
      continue;
    }
    let json;
    try {
      json = JSON.parse(readFileSync(p, "utf8"));
    } catch (e) {
      console.log(`!! 実測結果の解析に失敗(スキップ): ${p} (${e.message})`);
      continue;
    }
    sources.push({
      path: relative(REPO_ROOT, p),
      startedAt: json.stats?.startTime ?? null,
      durationMs: Math.round(json.stats?.duration ?? 0),
    });
    const walk = (suite) => {
      for (const spec of suite.specs || []) {
        for (const t of spec.tests || []) {
          const results = t.results || [];
          byId.set(`${spec.id}-${t.projectId}`, {
            // json レポーターの test.status: expected | unexpected | flaky | skipped
            status: t.status ?? "unknown",
            durationMs: results.reduce((a, r) => a + (r.duration || 0), 0),
            attempts: results.length,
          });
        }
      }
      for (const child of suite.suites || []) walk(child);
    };
    for (const s of json.suites || []) walk(s);
  }
  return { byId, sources };
}

/* ------------------------------------------------------------------ *
 * 3. CI (GitHub Actions) 側の実行条件を読み取る
 * ------------------------------------------------------------------ */

/**
 * run-e2e.sh から「CI が playwright に渡している spec 引数と環境変数」を抽出する。
 *
 * 対応する書き方:
 *   CI_SPECS=(
 *     tests/foo.spec.ts
 *     tests/bar.spec.ts
 *   )
 *   ...
 *   E2E_SCOPE=ci \
 *   E2E_BASE_URL="$BASE_URL" \
 *     npx playwright test "${CI_SPECS[@]}"
 *
 * 値にシェル変数($)を含む env は再現できないため除外する(URL や認証情報は
 * カタログ生成には不要)。spec を絞っていなければ specs は空配列。
 */
function ciConfig() {
  const info = {
    workflow: relative(REPO_ROOT, WORKFLOW_FILE),
    runner: relative(REPO_ROOT, CI_RUNNER),
    exists: existsSync(WORKFLOW_FILE),
    triggers: [],
    /** CI が対象にしている spec (空 = 全 spec) */
    specs: [],
    /** CI が付けている環境変数のうちリテラル値のもの */
    env: {},
    /** "subset" (spec 限定) / "all" (全 spec) / "unknown" */
    mode: "unknown",
    /** playwright 起動の段数(1段目=並列, 2段目=単独実行 など) */
    stages: 0,
    command: null,
    note: null,
  };

  if (info.exists) {
    const yml = readFileSync(WORKFLOW_FILE, "utf8");
    if (/^\s*pull_request:/m.test(yml)) info.triggers.push("pull_request (base: main)");
    if (/^\s*workflow_dispatch:/m.test(yml)) info.triggers.push("workflow_dispatch (手動)");
    if (/^\s*push:/m.test(yml)) info.triggers.push("push");
  }

  if (!existsSync(CI_RUNNER)) {
    info.note = "e2e/ci/run-e2e.sh が見つからないため CI 判定ができません。";
    return info;
  }
  const sh = readFileSync(CI_RUNNER, "utf8");
  const shLines = sh.split("\n");

  // playwright 起動行は複数段ありうる(通常の並列実行 + 単独実行が必要な spec)。
  // すべての段を走査して、対象 spec と env をマージする。
  const runIdxs = shLines
    .map((l, i) => (/npx playwright test/.test(l) ? i : -1))
    .filter((i) => i >= 0);
  if (!runIdxs.length) {
    info.note = "run-e2e.sh に playwright 起動行が見つかりません。";
    return info;
  }
  info.command = shLines[runIdxs[0]].trim();
  info.stages = runIdxs.length;

  for (const runIdx of runIdxs) {
    const cmd = shLines[runIdx].trim();

    // 起動行の直前に連なる `KEY=VALUE \` から、リテラル値のものだけ拾う。
    //
    // PLAYWRIGHT_* はレポーターの出力先など「テスト生成に無関係で、渡すと副作用がある」
    // 変数なので除外する(PLAYWRIGHT_JSON_OUTPUT_NAME を引き継ぐと、この後の --list 実行が
    // CI の実測 json を上書きしてしまう)。
    for (let i = runIdx - 1; i >= 0; i--) {
      const t = shLines[i].trim();
      const m = t.match(/^([A-Z][A-Z0-9_]*)=(.+?)\s*\\?$/);
      if (!m) break;
      const v = m[2].replace(/^"(.*)"$/, "$1").replace(/^'(.*)'$/, "$1");
      if (!v.includes("$") && !/^PLAYWRIGHT_/.test(m[1])) info.env[m[1]] = v;
    }

    // spec 引数: `"${CI_SPECS[@]}"` 形式なら配列定義を読む。直書きならその場で拾う。
    const arrRef = cmd.match(/\$\{([A-Z][A-Z0-9_]*)\[@\]\}/);
    if (arrRef) {
      const name = arrRef[1];
      // 配列の中身は「行頭の )」までを取る。[^)]* だと配列内コメントに括弧が
      // 含まれた時点で切れてしまい、対象 spec を取りこぼす。
      const block = sh.match(
        new RegExp(`^${name}=\\(\\s*$([\\s\\S]*?)^\\)\\s*$`, "m")
      );
      if (block) {
        for (const line of block[1].split("\n")) {
          const v = line.replace(/#.*$/, "").trim().replace(/^["']|["']$/g, "");
          if (v && !info.specs.includes(v)) info.specs.push(v);
        }
      }
    } else {
      const tail = cmd.split("npx playwright test")[1] || "";
      for (const raw of tail.split(/\s+/)) {
        const v = raw.replace(/^["']|["']$/g, "");
        if (v && !v.startsWith("-") && /\.(spec|setup)\.ts$/.test(v) && !info.specs.includes(v)) {
          info.specs.push(v);
        }
      }
    }
  }

  info.mode = info.specs.length ? "subset" : "all";
  info.note =
    info.mode === "subset"
      ? `CI は spec を ${info.specs.length} 本に限定して実行します` +
        `${info.stages > 1 ? `（${info.stages} 段構成: 並列実行 + 単独実行）` : ""}。フル実行はローカル運用。` +
        `「PRで実行」= この限定セットに含まれ、かつ skip されていないテストです。`
      : "CI は絞り込み無しで全 spec を実行します。「PR対象外」= コード上で skip されているテストのみです。";
  return info;
}

/* ------------------------------------------------------------------ *
 * 4. --list JSON を平坦化してカタログを組む
 * ------------------------------------------------------------------ */

/** 正規表現メタ文字のエスケープ (playwright --grep 用) */
function escapeRe(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

/** 管理設定テストのコード先頭文字 -> 設定画面上の区分 (旧 test/admin 構成用) */
const ADMIN_SECTIONS = {
  C: "ユーザー管理",
  D: "モジュール管理",
  E: "自動化",
  F: "システム構成",
  G: "マーケティングと営業",
  H: "販売管理",
  I: "個人設定",
};

/**
 * グループ (画面上の大分類) と区分を決める。
 *
 * 新構成: tests/<番号>_<分類>/[<サブ分類>/]<ファイル>
 *   例) tests/4_モジュール/4-8_カレンダー/1_基本.spec.ts
 *       → group "4_モジュール" / section "4-8_カレンダー"
 * 旧構成: test/ 直下と test/admin/ (main ブランチ時点の配置)
 */
function classify(relPath) {
  const parts = relPath.split("/");
  if (parts[0] === "tests") {
    const group = parts[1] || "その他";
    const section = parts.length > 3 ? parts[2] : null;
    return { group, section };
  }
  if (/\.setup\.ts$/.test(relPath)) return { group: "セットアップ (前提処理)", section: null };
  if (relPath.startsWith("test/admin/")) {
    const code = adminCode(relPath);
    return { group: "管理設定 (Settings)", section: ADMIN_SECTIONS[(code || "")[0]] || null };
  }
  if (relPath === "test/fr.common.spec.ts") return { group: "モジュール共通 CRUD", section: null };
  return { group: "一般 / 個別機能", section: null };
}

/** ファイル名から識別コードを拾う (admin.C-01. / C-04_ / 3-01_ / 2-1_ など) */
function adminCode(relPath) {
  const base = relPath.split("/").pop() || "";
  const m =
    base.match(/^admin\.([A-Z]-\d+)\./) ||
    base.match(/^([A-Z]-\d+)_/) ||
    base.match(/^(\d+-\d+)_/) ||
    base.match(/^(\d+)_/);
  return m ? m[1] : null;
}

function buildCatalog(listJson, ciIds, ci, run) {
  /** relPath -> file エントリ */
  const byFile = new Map();

  const walk = (suite, ancestors, relPath) => {
    const file = suite.file || relPath;
    const titles = suite.title && suite.title !== file ? [...ancestors, suite.title] : ancestors;

    for (const spec of suite.specs || []) {
      const specFile = spec.file || file;
      if (!byFile.has(specFile)) {
        const { group, section } = classify(specFile);
        byFile.set(specFile, {
          file: specFile,
          group,
          section,
          code: adminCode(specFile),
          summary: fileSummary(specFile),
          /**
           * CI の spec 限定リストに入っているか。
           * CI_SPECS はファイル指定とディレクトリ指定(末尾 /)が混在するため前方一致で見る。
           */
          inCISpecs:
            ci.mode === "all" ||
            ci.specs.some((sp) =>
              sp.endsWith("/") ? specFile.startsWith(sp) : specFile === sp
            ),
          tests: [],
        });
      }
      for (const t of spec.tests || []) {
        const ann = (t.annotations || []).find((a) => a.type === "skip" || a.type === "fixme");
        let reason = null;
        if (ann) {
          const annFile = ann.location ? relative(E2E_DIR, ann.location.file) : specFile;
          reason = skipReason(annFile, ann.location?.line);
        }
        const id = `${spec.id}-${t.projectId}`;
        const titlePath = [specFile, ...titles, spec.title];
        byFile.get(specFile).tests.push({
          id,
          project: t.projectName || t.projectId,
          suites: titles,
          title: spec.title,
          /** setup/seed プロジェクト (認証・シード) は依存として常に実行される */
          isSetup: /\.setup\.ts$/.test(specFile),
          line: spec.line,
          column: spec.column,
          /** playwright --grep に渡す正規表現 (titlePath はスペース連結でマッチする) */
          grep: titlePath.map(escapeRe).join(" "),
          location: `${specFile}:${spec.line}:${spec.column}`,
          skipped: !!ann,
          skipKind: ann ? ann.type : null,
          skipAt: ann?.location
            ? `${relative(E2E_DIR, ann.location.file)}:${ann.location.line}`
            : null,
          skipReason: reason,
          /** CI で collect されたか (spec 限定 + env による生成絞り込みの結果) */
          collectedInCI: ciIds.has(id),
          runsInCI: ciIds.has(id) && !ann,
          /**
           * 実測(json レポーター)の結果。無い場合は null。
           * runtimeSkipped = 収集時には skip が付いていないのに実測が skipped
           * (= 実行中の条件判定で外れた)。
           */
          lastRun: run.byId.has(id)
            ? {
                ...run.byId.get(id),
                runtimeSkipped:
                  !ann && run.byId.get(id).status === "skipped",
              }
            : null,
        });
      }
    }
    for (const child of suite.suites || []) walk(child, titles, file);
  };

  for (const s of listJson.suites || []) walk(s, [], s.file);

  const files = [...byFile.values()].sort(
    (a, b) =>
      a.group.localeCompare(b.group, "ja") ||
      (a.section || "").localeCompare(b.section || "", "ja") ||
      a.file.localeCompare(b.file, "ja")
  );

  const allTests = files.flatMap((f) => f.tests);

  return {
    schemaVersion: 2,
    generatedAt: new Date().toISOString(),
    configFile: relative(REPO_ROOT, listJson.config?.configFile || ""),
    ci,
    totals: {
      files: files.filter((f) => !/\.setup\.ts$/.test(f.file)).length,
      tests: allTests.length,
      runsInCI: allTests.filter((t) => t.runsInCI).length,
      skipped: allTests.filter((t) => t.skipped).length,
      /** CI の対象外 (skip ではなく、そもそも CI で collect されない) */
      notInCI: allTests.filter((t) => !t.collectedInCI).length,
      setup: allTests.filter((t) => t.isSetup).length,
      selectable: allTests.filter((t) => !t.isSetup).length,
    },
    /** 実測結果のサマリ(--results を渡したときだけ入る) */
    run: run.sources.length
      ? (() => {
          const measured = allTests.filter((t) => t.lastRun);
          const st = (s2) => measured.filter((t) => t.lastRun.status === s2).length;
          return {
            sources: run.sources,
            measured: measured.length,
            executed: st("expected") + st("flaky"),
            failed: st("unexpected"),
            flaky: st("flaky"),
            skipped: st("skipped"),
            runtimeSkipped: measured.filter((t) => t.lastRun.runtimeSkipped).length,
            staticSkipped: measured.filter(
              (t) => t.skipped && t.lastRun.status === "skipped"
            ).length,
            totalMs: measured.reduce((a, t) => a + t.lastRun.durationMs, 0),
          };
        })()
      : null,
    files,
  };
}

/* ------------------------------------------------------------------ *
 * 5. 実行
 * ------------------------------------------------------------------ */

const ci = ciConfig();

console.log("==> 全テストを list");
const full = listTests();

let ciIds;
if (ci.mode === "all") {
  console.log("==> CI は全 spec 対象 (追加 list は不要)");
  ciIds = idSet(full);
} else if (ci.mode === "subset") {
  const envStr = Object.entries(ci.env).map(([k, v]) => `${k}=${v}`).join(" ");
  console.log(`==> CI 相当を list (${ci.specs.length} spec${envStr ? " / " + envStr : ""})`);
  try {
    ciIds = idSet(listTests(ci.specs, ci.env));
  } catch (e) {
    console.error(`!! CI 相当の list に失敗: ${e.message}`);
    console.error("   CI 判定は「不明」として出力します。");
    ciIds = new Set();
    ci.mode = "unknown";
    ci.note = "CI 相当の list に失敗したため、PR 実行判定は行えていません。";
  }
} else {
  ciIds = new Set();
}

const resultPaths = parseResultArgs(process.argv.slice(2));
const run = loadRunResults(resultPaths);
if (run.sources.length) {
  console.log(
    `==> 実測結果を取り込み: ${run.sources.map((s2) => s2.path).join(", ")} (${run.byId.size} テスト)`
  );
} else {
  console.log("==> 実測結果なし(--results で json レポーター出力を渡すと実測列が出ます)");
}

const catalog = buildCatalog(full, ciIds, ci, run);
mkdirSync(OUT_DIR, { recursive: true });

const json = JSON.stringify(catalog, null, 2);
writeFileSync(resolve(OUT_DIR, "catalog.json"), json + "\n");
writeFileSync(
  resolve(OUT_DIR, "catalog.js"),
  `// 自動生成ファイル — 編集しないこと (生成: e2e/scripts/gen-test-catalog.mjs)\n` +
    `window.E2E_CATALOG = ${json};\n`
);

console.log(`==> 出力: ${relative(REPO_ROOT, OUT_DIR)}/catalog.{js,json}`);
console.log(
  `    テスト ${catalog.totals.tests} 件 / PR実行 ${catalog.totals.runsInCI} 件 / ` +
    `skip ${catalog.totals.skipped} 件 / CI未収集 ${catalog.totals.notInCI} 件 ` +
    `(spec ファイル ${catalog.totals.files}, CI モード: ${ci.mode})`
);
if (catalog.run) {
  const r = catalog.run;
  console.log(
    `    実測: 実行 ${r.executed} / 実行時skip ${r.runtimeSkipped} / 静的skip ${r.staticSkipped} / ` +
      `失敗 ${r.failed} / flaky ${r.flaky} (計 ${Math.round(r.totalMs / 1000)}秒, ${r.measured} テスト分)`
  );
}
