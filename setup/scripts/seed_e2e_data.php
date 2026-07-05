<?php
/**
 * E2E 拡充ベースライン用 シードデータ投入スクリプト
 *
 * TEST_COVERAGE.md が「弱い」と明記する領域(権限/可視範囲・ページング・ソート・検索・CustomView)を
 * E2E で検証できるように、複数ユーザー / ロール階層 / グループ / 所有者違いのレコード群 / 一定量の
 * データを「決定論的な data island」として投入する。
 *
 * 投入内容は e2e/fixtures/seed-spec.json(単一の真実)に従う。数値/名前を変える時はそちらを直すこと。
 *
 * 実行方法(コンテナ内 / リポジトリルート基準):
 *   docker compose exec -T php php setup/scripts/seed_e2e_data.php
 *
 * 位置づけ: これは "migration" ではない(本番 install では走らせない)。E2E dump のビルド時に
 * build-e2e-dump.sh から一度だけ呼ばれる。冪等(存在チェックで二重投入を防ぐ)なので再実行可能。
 * 実行後は必ず setup/scripts/RecreateUserFiles.php で権限/共有キャッシュを再生成すること。
 */

// --- ブートストラップ(run_migration.php と同じ考え方: ルートへ chdir し runtime を読み込む) ---
$rootDir = dirname(dirname(__DIR__));
chdir($rootDir);

require_once 'config.inc.php';
// FRMigrationClass を require すると PearDatabase / vtlib / Loader / runtime / Vtiger models が一括で揃う。
// (migration として登録はしない。runtime 読み込みの為だけに利用する)
require_once 'setup/migration/FRMigrationClass.php';
require_once 'modules/Users/Users.php';
require_once 'include/utils/UserInfoUtil.php';
require_once 'modules/Settings/Roles/models/Record.php';
require_once 'modules/Settings/Groups/models/Record.php';
require_once 'modules/Settings/Groups/models/Member.php';

$adb = PearDatabase::getInstance();

// レコード保存やモデル層が参照する current_user を管理者(id=1)で確立する。
$current_user = new Users();
$current_user->id = 1;
$current_user->retrieveCurrentUserInfoFromFile(1);
vglobal('current_user', $current_user);

// --- spec 読み込み ---
$specPath = 'e2e/fixtures/seed-spec.json';
if (!file_exists($specPath)) {
    fwrite(STDERR, "!! spec が見つかりません: {$specPath}\n");
    exit(1);
}
$spec = json_decode(file_get_contents($specPath), true);
if (!$spec) {
    fwrite(STDERR, "!! spec の JSON パースに失敗しました: {$specPath}\n");
    exit(1);
}

function out($msg) { echo '[seed] ' . $msg . "\n"; }

/**
 * 既存の roleid を rolename から引く(無ければ null)。
 */
function findRoleIdByName($name) {
    global $adb;
    $r = $adb->pquery('SELECT roleid FROM vtiger_role WHERE rolename = ?', array($name));
    return ($adb->num_rows($r) > 0) ? $adb->query_result($r, 0, 'roleid') : null;
}

function findUserIdByName($userName) {
    global $adb;
    $r = $adb->pquery('SELECT id FROM vtiger_users WHERE user_name = ?', array($userName));
    return ($adb->num_rows($r) > 0) ? (int) $adb->query_result($r, 0, 'id') : null;
}

function findGroupIdByName($groupName) {
    global $adb;
    $r = $adb->pquery('SELECT groupid FROM vtiger_groups WHERE groupname = ?', array($groupName));
    return ($adb->num_rows($r) > 0) ? (int) $adb->query_result($r, 0, 'groupid') : null;
}

// ============================================================================
// 1. ロール階層(H2 管理者 配下に E2E 専用ツリー)
//    seed-spec.roles を parent 依存順に作る。roleKey -> 採番された roleid の対応を保持。
// ============================================================================
out('--- ロール ---');
$roleIdByKey = array(); // roleKey => H<n>
foreach ($spec['roles'] as $role) {
    $existing = findRoleIdByName($role['name']);
    if ($existing) {
        $roleIdByKey[$role['key']] = $existing;
        out("既存ロール流用: {$role['name']} = {$existing}");
        continue;
    }
    // 親 roleid を解決(H2 等の固定 or 直前に作った E2E ロールの key)
    $parentKey = $role['parent'];
    $parentRoleId = (strpos($parentKey, 'H') === 0 && ctype_digit(substr($parentKey, 1)))
        ? $parentKey
        : (isset($roleIdByKey[$parentKey]) ? $roleIdByKey[$parentKey] : null);
    if (!$parentRoleId) {
        fwrite(STDERR, "!! 親ロールを解決できません: {$role['name']} parent={$parentKey}\n");
        exit(1);
    }
    $parent = Settings_Roles_Record_Model::getInstanceById($parentRoleId);
    if (!$parent) {
        fwrite(STDERR, "!! 親ロールインスタンス取得失敗: {$parentRoleId}\n");
        exit(1);
    }
    $child = new Settings_Roles_Record_Model();
    $child->set('rolename', $role['name']);
    $child->set('allowassignedrecordsto', 2); // 自ロール+配下へ割当可
    $child->set('profileIds', array(2));       // 既存 Sales Profile(非 admin) を流用
    $parent->addChildRole($child);
    // Settings_Roles_Record_Model::save() は roleid をモデルに書き戻さない為 getId() が空になる。
    // 採番済みの roleid を rolename から引き直す。
    $newId = $child->getId();
    if (!$newId) {
        $newId = findRoleIdByName($role['name']);
    }
    if (!$newId) {
        fwrite(STDERR, "!! ロール作成後に roleid を解決できません: {$role['name']}\n");
        exit(1);
    }
    $roleIdByKey[$role['key']] = $newId;
    out("作成: {$role['name']} = {$newId} (親 {$parentRoleId})");
}

// ============================================================================
// 2. ユーザー(各ロールに 1 名, 全員 非 admin, 固定パスワード)
// ============================================================================
out('--- ユーザー ---');
$userIdByName = array();
foreach ($spec['users'] as $u) {
    $existing = findUserIdByName($u['userName']);
    if ($existing) {
        $userIdByName[$u['userName']] = $existing;
        out("既存ユーザー流用: {$u['userName']} = {$existing}");
        continue;
    }
    $roleId = $roleIdByKey[$u['roleKey']];
    $userModel = new Users_Record_Model();
    $userModel->setModule('Users');
    $userModel->set('user_name', $u['userName']);
    $userModel->set('user_password', $spec['password']);
    $userModel->set('confirm_password', $spec['password']);
    $userModel->set('first_name', 'E2E');
    $userModel->set('last_name', $u['lastName']);
    $userModel->set('email1', $u['userName'] . '@example.com');
    $userModel->set('roleid', $roleId);
    $userModel->set('is_admin', 'off');
    $userModel->set('status', 'Active');
    $userModel->set('user_type', 'EndUser');
    $userModel->set('currency_id', 1);
    $userModel->set('date_format', 'yyyy-mm-dd');
    $userModel->set('hour_format', '24');
    $userModel->set('start_hour', '09:00');
    $userModel->set('end_hour', '18:00');
    $userModel->set('activity_view', 'Today');
    $userModel->set('reminder_interval', 'None');
    $userModel->set('time_zone', 'Asia/Tokyo');
    $userModel->set('language', 'ja_jp');
    $userModel->set('mode', '');
    $userModel->save();

    $newId = (int) $userModel->getId();
    if (!$newId) {
        // getId が取れない場合は user_name から引き直す
        $newId = findUserIdByName($u['userName']);
    }
    if (!$newId) {
        fwrite(STDERR, "!! ユーザー作成失敗: {$u['userName']}\n");
        exit(1);
    }
    // ロール割当を確実にする(vtiger_user2role)
    updateUser2RoleMapping($roleId, $newId);
    $userIdByName[$u['userName']] = $newId;
    out("作成: {$u['userName']} = {$newId} role={$roleId}");
}

// ============================================================================
// 3. グループ(別ブランチの平社員 2 名を束ねる)
// ============================================================================
out('--- グループ ---');
$groupName = $spec['group']['name'];
$groupId = findGroupIdByName($groupName);
if ($groupId) {
    out("既存グループ流用: {$groupName} = {$groupId}");
} else {
    $members = array();
    foreach ($spec['group']['memberUserNames'] as $un) {
        $members[] = Settings_Groups_Member_Model::MEMBER_TYPE_USERS . ':' . $userIdByName[$un];
    }
    $groupModel = new Settings_Groups_Record_Model();
    $groupModel->set('groupname', $groupName);
    $groupModel->set('description', 'E2E: クロスブランチ共有検証用グループ');
    $groupModel->set('group_members', $members);
    $groupModel->save();
    $groupId = (int) $groupModel->getId();
    if (!$groupId) { $groupId = findGroupIdByName($groupName); }
    out("作成: {$groupName} = {$groupId} members=" . implode(',', $members));
}

// ============================================================================
// 4. 共有設定: 権限島モジュール(Leads)を Private(3) に
// ============================================================================
out('--- 共有設定(Private 化) ---');
foreach ($spec['privateModules'] as $moduleName) {
    $r = $adb->pquery('SELECT tabid FROM vtiger_tab WHERE name = ?', array($moduleName));
    if ($adb->num_rows($r) === 0) { continue; }
    $tabid = (int) $adb->query_result($r, 0, 'tabid');
    $adb->pquery('UPDATE vtiger_def_org_share SET permission = 3 WHERE tabid = ?', array($tabid));
    out("{$moduleName}(tabid {$tabid}) を Private(3) に設定");
}

// ============================================================================
// レコード生成ヘルパ
// ============================================================================
/**
 * 指定モジュールに 1 レコード作成。$fields は列名=>値。owner は数値 crmid。
 */
function createRecord($moduleName, array $fields) {
    $rm = Vtiger_Record_Model::getCleanInstance($moduleName);
    foreach ($fields as $k => $v) {
        $rm->set($k, $v);
    }
    $rm->set('mode', '');
    $rm->save();
    return $rm->getId();
}

/**
 * company プレフィックスに一致する Leads 件数(冪等判定用)。
 */
function countLeadsByCompanyPrefix($prefix) {
    global $adb;
    $r = $adb->pquery(
        'SELECT COUNT(*) AS c FROM vtiger_leaddetails l JOIN vtiger_crmentity e ON e.crmid=l.leadid WHERE e.deleted=0 AND l.company LIKE ?',
        array($prefix . '%')
    );
    return (int) $adb->query_result($r, 0, 'c');
}

function countAccountsByNamePrefix($prefix) {
    global $adb;
    $r = $adb->pquery(
        'SELECT COUNT(*) AS c FROM vtiger_account a JOIN vtiger_crmentity e ON e.crmid=a.accountid WHERE e.deleted=0 AND a.accountname LIKE ?',
        array($prefix . '%')
    );
    return (int) $adb->query_result($r, 0, 'c');
}

// ============================================================================
// 5. 権限島: [E2E-PERM] Leads (各ロールユーザー所有で perOwner 件ずつ)
// ============================================================================
out('--- 権限島 Leads ---');
$perm = $spec['leadPerm'];
$permTotal = $perm['perOwner'] * count($perm['ownerCodes']);
if (countLeadsByCompanyPrefix($perm['prefix']) >= $permTotal) {
    out("既に投入済み: {$perm['prefix']} (skip)");
} else {
    // ownerCode -> userId (ownerCode は users[].ownerCode に対応)
    $userIdByOwnerCode = array();
    foreach ($spec['users'] as $u) {
        $userIdByOwnerCode[$u['ownerCode']] = $userIdByName[$u['userName']];
    }
    foreach ($perm['ownerCodes'] as $code) {
        $ownerId = $userIdByOwnerCode[$code];
        for ($i = 1; $i <= $perm['perOwner']; $i++) {
            $seq = str_pad($i, 4, '0', STR_PAD_LEFT);
            createRecord($perm['module'], array(
                'company'          => sprintf('%s %s %s', $perm['prefix'], $code, $seq),
                'lastname'         => $code,
                'assigned_user_id' => $ownerId,
            ));
        }
        out("{$code}: {$perm['perOwner']} 件 (owner {$ownerId})");
    }
}

// ============================================================================
// 6. グループ島: [E2E-GRP] Leads (グループ所有)
// ============================================================================
out('--- グループ島 Leads ---');
$lg = $spec['leadGroup'];
if (countLeadsByCompanyPrefix($lg['prefix']) >= $lg['count']) {
    out("既に投入済み: {$lg['prefix']} (skip)");
} else {
    for ($i = 1; $i <= $lg['count']; $i++) {
        $seq = str_pad($i, 4, '0', STR_PAD_LEFT);
        createRecord($lg['module'], array(
            'company'          => sprintf('%s GRP %s', $lg['prefix'], $seq),
            'lastname'         => 'GRP',
            'assigned_user_id' => $groupId, // グループ所有(smownerid = groupid)
        ));
    }
    out("{$lg['count']} 件 (group {$groupId})");
}

// ============================================================================
// 7. ページング/ソート島: [E2E-PAGE] Accounts (admin 所有, ゼロ埋め連番)
// ============================================================================
out('--- ページング島 Accounts ---');
$pg = $spec['accountPaging'];
$have = countAccountsByNamePrefix($pg['prefix']);
if ($have >= $pg['count']) {
    out("既に投入済み: {$pg['prefix']} ({$have}) (skip)");
} else {
    for ($i = $have + 1; $i <= $pg['count']; $i++) {
        $seq = str_pad($i, 4, '0', STR_PAD_LEFT);
        createRecord($pg['module'], array(
            'accountname'      => sprintf('%s %s', $pg['prefix'], $seq),
            'assigned_user_id' => 1, // admin
        ));
        if ($i % 50 === 0) { out("... {$i}/{$pg['count']}"); }
    }
    out("{$pg['count']} 件");
}

// ============================================================================
// 8. 検索/CustomView 島: [E2E-SRCH] Accounts (industry を均等分布)
// ============================================================================
out('--- 検索島 Accounts ---');
$sr = $spec['accountSearch'];
$srTotal = count($sr['industries']) * $sr['perIndustry'];
if (countAccountsByNamePrefix($sr['prefix']) >= $srTotal) {
    out("既に投入済み: {$sr['prefix']} (skip)");
} else {
    $globalTokenAssigned = false;
    foreach ($sr['industries'] as $iIdx => $industry) {
        for ($j = 1; $j <= $sr['perIndustry']; $j++) {
            $seq = str_pad(($iIdx * $sr['perIndustry'] + $j), 4, '0', STR_PAD_LEFT);
            $rating = $sr['ratings'][($j - 1) % count($sr['ratings'])];
            $fields = array(
                'accountname'      => sprintf('%s %s %s', $sr['prefix'], $industry, $seq),
                'industry'         => $industry,
                'rating'           => $rating,
                'assigned_user_id' => 1,
            );
            // グローバル一意トークンを 1 件だけ website に埋める(global search 単一ヒット検証用)
            if (!$globalTokenAssigned) {
                $fields['website'] = 'https://' . strtolower($sr['globalToken']) . '.example.com';
                $fields['accountname'] = sprintf('%s %s %s', $sr['prefix'], $sr['globalToken'], $seq);
                $globalTokenAssigned = true;
            }
            createRecord($sr['module'], $fields);
        }
        out("industry={$industry}: {$sr['perIndustry']} 件");
    }
}

// ============================================================================
// 9. アクション権限ペルソナ(プロファイル/役割による 非表示/閲覧のみ/削除不可)
//    Sales Profile(id=2) を複製し、対象モジュールの権限だけ書き換えた制限プロファイルを
//    専用ロール+ユーザーに割り当てる。可視範囲(sharing)と切り離す為 Public 共有の
//    Accounts を対象にする(誰でも全レコードを閲覧でき、差はアクション権限のみに出る)。
// ============================================================================
function findProfileIdByName($name) {
    global $adb;
    $r = $adb->pquery('SELECT profileid FROM vtiger_profile WHERE profilename = ?', array($name));
    return ($adb->num_rows($r) > 0) ? (int) $adb->query_result($r, 0, 'profileid') : null;
}

/**
 * Sales Profile(=$base) を全権限テーブルごと複製し新 profileid を返す。存在すれば流用。
 * (全モジュール分を手組みせず、複製後に対象モジュールの行だけ書き換える)
 */
function cloneProfile($name, $base) {
    global $adb;
    $existing = findProfileIdByName($name);
    if ($existing) { return $existing; }
    $pid = $adb->getUniqueID('vtiger_profile');
    $adb->pquery('INSERT INTO vtiger_profile(profileid, profilename, description) VALUES (?,?,?)',
        array($pid, $name, 'E2E: アクション権限検証用(Sales Profile 複製)'));
    $adb->pquery('INSERT INTO vtiger_profile2tab(profileid,tabid,permissions) SELECT ?,tabid,permissions FROM vtiger_profile2tab WHERE profileid=?', array($pid, $base));
    $adb->pquery('INSERT INTO vtiger_profile2standardpermissions(profileid,tabid,operation,permissions) SELECT ?,tabid,operation,permissions FROM vtiger_profile2standardpermissions WHERE profileid=?', array($pid, $base));
    $adb->pquery('INSERT INTO vtiger_profile2field(profileid,tabid,fieldid,visible,readonly) SELECT ?,tabid,fieldid,visible,readonly FROM vtiger_profile2field WHERE profileid=?', array($pid, $base));
    $adb->pquery('INSERT INTO vtiger_profile2utility(profileid,tabid,activityid,permission) SELECT ?,tabid,activityid,permission FROM vtiger_profile2utility WHERE profileid=?', array($pid, $base));
    $adb->pquery('INSERT INTO vtiger_profile2globalpermissions(profileid,globalactionid,globalactionpermission) SELECT ?,globalactionid,globalactionpermission FROM vtiger_profile2globalpermissions WHERE profileid=?', array($pid, $base));
    return $pid;
}

/** E2E テストユーザーを作成し crmid を返す(section 2 と同じ設定)。 */
function createE2EUser($userName, $lastName, $roleId) {
    global $spec;
    $u = new Users_Record_Model();
    $u->setModule('Users');
    $u->set('user_name', $userName);
    $u->set('user_password', $spec['password']);
    $u->set('confirm_password', $spec['password']);
    $u->set('first_name', 'E2E');
    $u->set('last_name', $lastName);
    $u->set('email1', $userName . '@example.com');
    $u->set('roleid', $roleId);
    $u->set('is_admin', 'off');
    $u->set('status', 'Active');
    $u->set('user_type', 'EndUser');
    $u->set('currency_id', 1);
    $u->set('date_format', 'yyyy-mm-dd');
    $u->set('hour_format', '24');
    $u->set('start_hour', '09:00');
    $u->set('end_hour', '18:00');
    $u->set('activity_view', 'Today');
    $u->set('reminder_interval', 'None');
    $u->set('time_zone', 'Asia/Tokyo');
    $u->set('language', 'ja_jp');
    $u->set('mode', '');
    $u->save();
    $uid = (int) $u->getId();
    if (!$uid) { $uid = findUserIdByName($userName); }
    if ($uid) { updateUser2RoleMapping($roleId, $uid); }
    return $uid;
}

out('--- アクション権限ペルソナ ---');
$ap = $spec['actionPerm'];
$rt = $adb->pquery('SELECT tabid FROM vtiger_tab WHERE name = ?', array($ap['module']));
$apTabid = (int) $adb->query_result($rt, 0, 'tabid');
$BASE_PROFILE = 2; // Sales Profile を複製元にする

foreach ($ap['personas'] as $persona) {
    if (findUserIdByName($persona['userName'])) {
        out("既存ペルソナ流用: {$persona['userName']} (skip)");
        continue;
    }
    // 1) プロファイル複製 + 制限適用
    $pid = cloneProfile($persona['profileName'], $BASE_PROFILE);
    switch ($persona['restriction']) {
        case 'module_hidden':
            // モジュール非表示 + 標準アクション全拒否
            $adb->pquery('UPDATE vtiger_profile2tab SET permissions=1 WHERE profileid=? AND tabid=?', array($pid, $apTabid));
            $adb->pquery('UPDATE vtiger_profile2standardpermissions SET permissions=1 WHERE profileid=? AND tabid=?', array($pid, $apTabid));
            break;
        case 'read_only':
            // 閲覧のみ: Save(0)/EditView(1)/Delete(2)/CreateView(7) を拒否、index(3)/DetailView(4) は許可
            $adb->pquery('UPDATE vtiger_profile2standardpermissions SET permissions=1 WHERE profileid=? AND tabid=? AND operation IN (0,1,2,7)', array($pid, $apTabid));
            break;
        case 'no_delete':
            // 削除(2)のみ拒否
            $adb->pquery('UPDATE vtiger_profile2standardpermissions SET permissions=1 WHERE profileid=? AND tabid=? AND operation=2', array($pid, $apTabid));
            break;
        default:
            fwrite(STDERR, "!! 未知の restriction: {$persona['restriction']}\n");
            exit(1);
    }
    // 2) 制限プロファイルを紐付けたロール(H2 配下)を作成
    $roleId = findRoleIdByName($persona['roleName']);
    if (!$roleId) {
        $parent = Settings_Roles_Record_Model::getInstanceById('H2');
        $child = new Settings_Roles_Record_Model();
        $child->set('rolename', $persona['roleName']);
        $child->set('allowassignedrecordsto', 2);
        $child->set('profileIds', array($pid));
        $parent->addChildRole($child);
        $roleId = $child->getId();
        if (!$roleId) { $roleId = findRoleIdByName($persona['roleName']); }
    }
    // 3) ユーザー作成
    $uid = createE2EUser($persona['userName'], $persona['roleName'], $roleId);
    if (!$uid) {
        fwrite(STDERR, "!! ペルソナ作成失敗: {$persona['userName']}\n");
        exit(1);
    }
    out("作成: {$persona['userName']} profile={$pid}({$persona['restriction']}) role={$roleId} user={$uid}");
}

// ============================================================================
// 10. 項目レベル権限ペルソナ(この項目だけ 非表示 / 編集不可)
//     Sales Profile を複製し Accounts の特定項目だけ profile2field で制限する
//     (モジュール/アクションは通常のまま)。visible=1 で非表示、readonly=1 で編集不可。
// ============================================================================
function fieldIdOf($tabid, $name) {
    global $adb;
    $r = $adb->pquery('SELECT fieldid FROM vtiger_field WHERE tabid=? AND fieldname=?', array($tabid, $name));
    return ($adb->num_rows($r) > 0) ? (int) $adb->query_result($r, 0, 'fieldid') : null;
}

out('--- 項目レベル権限ペルソナ ---');
$fp = $spec['fieldPerm'];
$fpTab = (int) $adb->query_result(
    $adb->pquery('SELECT tabid FROM vtiger_tab WHERE name=?', array($fp['module'])), 0, 'tabid');
if (findUserIdByName($fp['userName'])) {
    out("既存ペルソナ流用: {$fp['userName']} (skip)");
} else {
    $pid = cloneProfile($fp['profileName'], 2);
    $hid = fieldIdOf($fpTab, $fp['hiddenField']);
    $rid = fieldIdOf($fpTab, $fp['readonlyField']);
    // 非表示: visible=1 / 編集不可: visible=0(表示可) かつ readonly=1
    $adb->pquery('UPDATE vtiger_profile2field SET visible=1 WHERE profileid=? AND fieldid=?', array($pid, $hid));
    $adb->pquery('UPDATE vtiger_profile2field SET visible=0, readonly=1 WHERE profileid=? AND fieldid=?', array($pid, $rid));
    $roleId = findRoleIdByName($fp['roleName']);
    if (!$roleId) {
        $parent = Settings_Roles_Record_Model::getInstanceById('H2');
        $child = new Settings_Roles_Record_Model();
        $child->set('rolename', $fp['roleName']);
        $child->set('allowassignedrecordsto', 2);
        $child->set('profileIds', array($pid));
        $parent->addChildRole($child);
        $roleId = $child->getId();
        if (!$roleId) { $roleId = findRoleIdByName($fp['roleName']); }
    }
    $uid = createE2EUser($fp['userName'], $fp['roleName'], $roleId);
    out("作成: {$fp['userName']} profile={$pid}(hide {$fp['hiddenField']} / readonly {$fp['readonlyField']}) role={$roleId} user={$uid}");
}

// ============================================================================
// 11. カスタム共有ルール(ROLE→ROLE datashare)+ 観測者ユーザー
//     何も所有しない観測者ロールに、Leads の『1課長(MGRA)ロール所有』を read-only 共有する。
//     階層では見えないレコードが共有ルールで見えるようになることを検証する
//     (source=共有元ロール, target=受け手ロール。既存の階層テストの件数は変えない)。
// ============================================================================
require_once 'modules/Settings/SharingAccess/models/Rule.php';
require_once 'modules/Settings/SharingAccess/models/RuleMember.php';
require_once 'modules/Settings/SharingAccess/models/Module.php';

out('--- 共有ルール(観測者) ---');
$srule = $spec['sharingRule'];
$observerRoleId = findRoleIdByName($srule['observerRoleName']);
if (!$observerRoleId) {
    $parent = Settings_Roles_Record_Model::getInstanceById('H2');
    $child = new Settings_Roles_Record_Model();
    $child->set('rolename', $srule['observerRoleName']);
    $child->set('allowassignedrecordsto', 2);
    $child->set('profileIds', array(2)); // 通常 Sales Profile(Leads 閲覧可)
    $parent->addChildRole($child);
    $observerRoleId = $child->getId();
    if (!$observerRoleId) { $observerRoleId = findRoleIdByName($srule['observerRoleName']); }
}
if (!findUserIdByName($srule['observerUserName'])) {
    $ouid = createE2EUser($srule['observerUserName'], $srule['observerRoleName'], $observerRoleId);
    out("作成: 観測者 {$srule['observerUserName']} role={$observerRoleId} user={$ouid}");
} else {
    out("既存: 観測者 {$srule['observerUserName']} (skip)");
}

$sourceRoleId = findRoleIdByName($srule['sourceRoleName']);
$leadsTab = (int) $adb->query_result(
    $adb->pquery('SELECT tabid FROM vtiger_tab WHERE name=?', array($srule['module'])), 0, 'tabid');
$existsRule = $adb->pquery(
    'SELECT r.shareid FROM vtiger_datashare_role2role r JOIN vtiger_datashare_module_rel m ON m.shareid=r.shareid WHERE m.tabid=? AND r.share_roleid=? AND r.to_roleid=?',
    array($leadsTab, $sourceRoleId, $observerRoleId));
if ($adb->num_rows($existsRule) > 0) {
    out("既存: 共有ルール {$srule['sourceRoleName']}→観測者 (skip)");
} else {
    $rule = new Settings_SharingAccess_Rule_Model();
    $rule->setModule($srule['module']);
    $rule->set('source_id', Settings_SharingAccess_RuleMember_Model::getQualifiedId(
        Settings_SharingAccess_RuleMember_Model::RULE_MEMBER_TYPE_ROLES, $sourceRoleId));
    $rule->set('target_id', Settings_SharingAccess_RuleMember_Model::getQualifiedId(
        Settings_SharingAccess_RuleMember_Model::RULE_MEMBER_TYPE_ROLES, $observerRoleId));
    $rule->set('permission', Settings_SharingAccess_Rule_Model::READ_ONLY_PERMISSION);
    $rule->save(); // 両テーブル書込 + recalculateSharingRules(共有キャッシュ再生成)
    out("作成: 共有ルール {$srule['module']} {$srule['sourceRoleName']}({$sourceRoleId})→観測者({$observerRoleId}) read-only");
}

out('=== 完了。setup/scripts/RecreateUserFiles.php を実行してキャッシュを再生成すること ===');
