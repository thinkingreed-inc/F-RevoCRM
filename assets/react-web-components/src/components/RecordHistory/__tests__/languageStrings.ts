import { readFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const here = dirname(fileURLToPath(import.meta.url));
export const repoRoot = resolve(here, "../../../../../..");

/** languages/<lang>/Vtiger.php の $languageStrings を key => value で読む（テスト用） */
export function readLanguageStrings(lang: string): Record<string, string> {
  const php = readFileSync(
    join(repoRoot, "languages", lang, "Vtiger.php"),
    "utf8",
  );
  const start = php.indexOf("$languageStrings");
  const end = php.indexOf("$jsLanguageStrings");
  const body = php.slice(start, end === -1 ? undefined : end);
  const strings: Record<string, string> = {};
  const entry = /'((?:[^'\\]|\\.)*)'\s*=>\s*'((?:[^'\\]|\\.)*)'/g;
  for (const m of body.matchAll(entry)) {
    strings[m[1]] = m[2].replace(/\\(['\\])/g, "$1");
  }
  return strings;
}
