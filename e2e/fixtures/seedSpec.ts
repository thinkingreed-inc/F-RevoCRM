import * as fs from "fs";
import * as path from "path";

/**
 * E2E 拡充ベースラインの seed 仕様(単一の真実)を型付きで読み込む。
 *
 * 実体は同ディレクトリの seed-spec.json。投入側(setup/scripts/seed_e2e_data.php)と
 * 検証側(本ファイルを import するスペック)が同じ数値/名前/期待可視数を参照するための唯一の出所。
 * 数値を変える時は seed-spec.json を直し、build-e2e-dump.sh で dump を再生成すること。
 */

export interface SeedRole {
  key: string;
  name: string;
  parent: string;
}

export interface SeedUser {
  userName: string;
  lastName: string;
  roleKey: string;
  ownerCode: string;
}

/** userName -> 期待可視件数 の対応。 */
export type VisibleCounts = Record<string, number>;

export interface SeedSpec {
  password: string;
  roles: SeedRole[];
  users: SeedUser[];
  group: { name: string; memberUserNames: string[] };
  privateModules: string[];
  leadPerm: {
    module: string;
    prefix: string;
    perOwner: number;
    ownerCodes: string[];
    expectedVisible: VisibleCounts;
  };
  leadGroup: {
    module: string;
    prefix: string;
    count: number;
    expectedVisible: VisibleCounts;
  };
  accountPaging: {
    module: string;
    prefix: string;
    count: number;
    pageSize: number;
  };
  accountSearch: {
    module: string;
    prefix: string;
    industries: string[];
    perIndustry: number;
    ratings: string[];
    globalToken: string;
  };
  actionPerm: {
    module: string;
    personas: ActionPersona[];
  };
  fieldPerm: {
    module: string;
    userName: string;
    roleName: string;
    profileName: string;
    hiddenField: string;
    readonlyField: string;
    normalField: string;
  };
  sharingRule: {
    module: string;
    observerUserName: string;
    observerRoleName: string;
    sourceRoleName: string;
    leadPrefix: string;
    sharedOwnerCode: string;
    notSharedOwnerCodes: string[];
    expectedSharedCount: number;
    expectedNotSharedCount: number;
  };
  exportImportPerm: {
    module: string;
    userName: string;
    roleName: string;
    profileName: string;
    negativeUserName: string;
  };
  tagFilter: {
    module: string;
    tagName: string;
    taggedNamePrefix: string;
    taggedCount: number;
  };
}

/** プロファイル(役割)によるアクション権限ペルソナ。 */
export interface ActionPersona {
  userName: string;
  roleName: string;
  profileName: string;
  restriction: "module_hidden" | "read_only" | "no_delete";
  expect: {
    moduleVisible: boolean;
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
  };
}

const specPath = path.resolve(__dirname, "seed-spec.json");

export const seedSpec: SeedSpec = JSON.parse(
  fs.readFileSync(specPath, "utf-8")
);

/** admin 以外のテストユーザーは共通の固定パスワードを使う。 */
export function passwordFor(userName: string): string {
  if (userName === "admin") {
    return process.env.E2E_USER_PASSWORD || "Admin1234/";
  }
  return seedSpec.password;
}
