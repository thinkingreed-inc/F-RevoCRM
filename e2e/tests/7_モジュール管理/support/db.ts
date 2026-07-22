import { execFileSync } from "child_process";
import * as path from "path";

/**
 * モジュール管理テスト用の DB / キャッシュ操作ヘルパ。
 *
 * トグル自体は ModuleManager の UI 経由(CSRF・キャッシュ再生成込み)で行うため、
 * ここでの DB 直接操作は「元の presence の読み取り」と「復帰セーフティネット」専用。
 * config.inc.php を読む PHP ワンライナーを project root で実行する。
 */

// playwright の cwd は e2e/。config.inc.php とスクリプトは 1 つ上の project root。
const PROJECT_ROOT = path.resolve(process.cwd(), "..");

function php(code: string): string {
  return execFileSync("php", ["-r", code], {
    cwd: PROJECT_ROOT,
    encoding: "utf-8",
  }).trim();
}

/** vtiger_tab.presence を name→presence(数値)で取得する。 */
export function getPresence(names: string[]): Record<string, number> {
  const list = names.map((n) => `'${n.replace(/'/g, "")}'`).join(",");
  const out = php(
    `include 'config.inc.php';` +
      `$m=new mysqli($dbconfig['db_server'],$dbconfig['db_username'],$dbconfig['db_password'],$dbconfig['db_name']);` +
      `$r=$m->query("SELECT name,presence FROM vtiger_tab WHERE name IN (${list})");` +
      `$o=[];while($x=$r->fetch_assoc()){$o[$x['name']]=(int)$x['presence'];}` +
      `echo json_encode($o);`
  );
  return JSON.parse(out) as Record<string, number>;
}

/**
 * セーフティネット: 対象モジュールを全て presence=0(有効)へ強制復帰し、
 * キャッシュ(module meta / 共有ルール)を再生成して live メニューを整合させる。
 * UI 復帰が失敗しても共有 CRM に無効モジュールを残さないための最終防波堤。
 */
export function forceRestoreAll(names: string[]): void {
  const list = names.map((n) => `'${n.replace(/'/g, "")}'`).join(",");
  php(
    `include 'config.inc.php';` +
      `$m=new mysqli($dbconfig['db_server'],$dbconfig['db_username'],$dbconfig['db_password'],$dbconfig['db_name']);` +
      `$m->query("UPDATE vtiger_tab SET presence=0 WHERE name IN (${list})");`
  );
  // 直接 SQL はキャッシュを更新しないため、メニュー系ファイル/メタを再生成する。
  execFileSync("php", ["setup/scripts/RecreateUserFiles.php"], {
    cwd: PROJECT_ROOT,
    encoding: "utf-8",
  });
}

/** 無効(presence<>0)のまま残った対象モジュール名を返す。空なら安全。 */
export function findStillDisabled(names: string[]): string[] {
  const p = getPresence(names);
  return names.filter((n) => (p[n] ?? 0) !== 0);
}
