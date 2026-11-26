import { readFileSync, readdirSync, statSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { describe, expect, it } from "vitest";
import { RECORD_HISTORY_DEFAULTS } from "../translations";
import { readLanguageStrings } from "./languageStrings";

const here = dirname(fileURLToPath(import.meta.url));
const componentRoot = resolve(here, "..");

function listSources(dir: string): string[] {
  return readdirSync(dir).flatMap((name) => {
    const path = join(dir, name);
    if (statSync(path).isDirectory()) {
      return name === "__tests__" ? [] : listSources(path);
    }
    return /\.(ts|tsx)$/.test(name) ? [path] : [];
  });
}

const placeholders = (value: string) => (value.match(/%s/g) ?? []).length;

const ja = readLanguageStrings("ja_jp");
const en = readLanguageStrings("en_us");
const defaultKeys = Object.keys(RECORD_HISTORY_DEFAULTS);

describe("record-history の翻訳キー", () => {
  it("既定値の全キーが ja_jp / en_us の Vtiger.php にある", () => {
    expect(defaultKeys.filter((k) => !(k in ja))).toEqual([]);
    expect(defaultKeys.filter((k) => !(k in en))).toEqual([]);
  });

  it("ja_jp の値がコンポーネントの既定値（フォールバック）と一致する", () => {
    const mismatched = defaultKeys.filter(
      (k) => ja[k] !== RECORD_HISTORY_DEFAULTS[k],
    );
    expect(mismatched).toEqual([]);
  });

  it("%s の個数が日英で一致する", () => {
    const mismatched = defaultKeys.filter(
      (k) => placeholders(ja[k] ?? "") !== placeholders(en[k] ?? ""),
    );
    expect(mismatched).toEqual([]);
  });

  it("ソースで使っているキーがすべて定義されている", () => {
    const used = new Set<string>();
    for (const file of listSources(componentRoot)) {
      const code = readFileSync(file, "utf8");
      for (const m of code.matchAll(/["`](LBL_HISTORY_[A-Za-z0-9_]+)["`]/g)) {
        used.add(m[1]);
      }
    }
    // テンプレートリテラルで組み立てるキー
    const eventTypes = [
      "create",
      "update",
      "delete",
      "restore",
      "link",
      "unlink",
    ];
    eventTypes.forEach((type) => {
      used.add(`LBL_HISTORY_TYPE_${type}`);
      used.add(`LBL_HISTORY_VERB_${type}`);
    });
    for (let day = 0; day < 7; day++) used.add(`LBL_HISTORY_WEEKDAY_${day}`);

    expect([...used].filter((k) => !(k in RECORD_HISTORY_DEFAULTS))).toEqual(
      [],
    );
  });
});
