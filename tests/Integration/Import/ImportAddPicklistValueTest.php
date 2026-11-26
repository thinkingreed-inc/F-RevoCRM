<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Integration\Import;

use Import_Data_Action;
use Import_Map_Model;
use Import_Queue_Action;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings_Picklist_Field_Model;
use Settings_Picklist_Module_Model;
use Tests\Support\DocumentsTestCase;
use Vtiger_Cache;
use Vtiger_Request;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

/**
 * インポート時の選択肢の自動追加 (#1906)
 *
 * 項目マッピング画面でチェックされた項目（add_picklist_fields）だけ、選択肢に無い値を
 * 選択肢へ追加して transformForImport() が行を返すことを、選択肢テーブルと
 * vtiger_role2picklist の実データで確かめる。
 *
 * テストで追加する選択肢の値は PREFIX で始め、setUp / tearDown で消す。
 */
final class ImportAddPicklistValueTest extends DocumentsTestCase
{
    /** 既存のインポート履歴と衝突しない ID */
    private const IMPORT_ID = 990002;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once dirname(__DIR__, 3) . '/modules/Import/actions/Data.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanPicklistValues();
    }

    protected function tearDown(): void
    {
        $this->cleanPicklistValues();
        parent::tearDown();
    }

    // ---- 正常系 -----------------------------------------------------------

    /** C1: ロール別の選択肢に無い値を追加し、全ロールに割り当てて行を返す */
    public function testAddsRoleBasedValueToAllRoles(): void
    {
        $value = self::PREFIX . 'ロール別';

        $data = $this->transform($this->action('Accounts', ['rating']), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => $value]);

        $this->assertNotNull($data);
        $this->assertSame($value, $data['rating']);
        $this->assertSame(1, $this->countValue('rating', $value));
        // 設定画面で「全ロール」を選んだときと同じく、ユーザーに割り当てられない最上位ロール（depth=0）は除く
        $result = $this->db->pquery('SELECT COUNT(*) AS c FROM vtiger_role WHERE depth != 0', []);
        $this->assertSame((int) $this->db->query_result($result, 0, 'c'), $this->countRoleAssignments('rating', $value));
    }

    /** C2: 選択肢エディタに出ない項目（日報の集計期間。値で週報かを分岐している）はチェックボックスの対象外 */
    public function testExcludesFieldsHiddenFromPicklistEditor(): void
    {
        $this->assertFalse(Import_Data_Action::isPicklistAddableField('reportsterm', 'Dailyreports'));
        $this->assertTrue(Import_Data_Action::isPicklistAddableField('rating', 'Accounts'));
    }

    /** C2: 設定画面で選択肢を編集できない項目（受注の支払条件）は、チェックされていても追加せず失敗 */
    public function testDoesNotAddValueToNonEditablePicklist(): void
    {
        $value = self::PREFIX . '編集不可';

        $data = $this->transform($this->action('SalesOrder', ['payment_duration']), 'SalesOrder', ['subject' => self::PREFIX . '受注', 'payment_duration' => $value]);

        $this->assertNull($data);
        $this->assertSame(0, $this->countValue('payment_duration', $value));
    }

    /** C12: 列の文字セットが utf8mb4 でなければ、4バイト文字を含む値は追加せず失敗 */
    public function testDoesNotAddFourByteValueToNonUtf8mb4Column(): void
    {
        $result = $this->db->pquery(
            "SELECT CHARACTER_SET_NAME AS charset FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtiger_rating' AND COLUMN_NAME = 'rating'",
            []
        );
        if ((string) $this->db->query_result($result, 0, 'charset') === 'utf8mb4') {
            self::markTestSkipped('vtiger_rating.rating が utf8mb4 のため4バイト文字も保存できる');
        }

        $data = $this->transform($this->action('Accounts', ['rating']), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => self::PREFIX . '絵文字😀']);

        $this->assertNull($data);
        $this->assertSame(0, $this->countValueLike('rating', self::PREFIX . '絵文字%'));
    }

    /** C3: 同じインポート内で同じ新しい値が2行あっても追加は1行 */
    public function testAddsSameValueOnlyOnceWithinOneImport(): void
    {
        $value = self::PREFIX . '二行';
        $action = $this->action('Accounts', ['rating']);

        $first = $this->transform($action, 'Accounts', ['accountname' => self::PREFIX . '顧客1', 'rating' => $value]);
        $second = $this->transform($action, 'Accounts', ['accountname' => self::PREFIX . '顧客2', 'rating' => $value]);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($value, $second['rating']);
        $this->assertSame(1, $this->countValue('rating', $value));
    }

    /** C4: 大文字小文字だけ違う値がロールに割り当て済みなら追加しない */
    public function testDoesNotAddValueDifferingOnlyInCase(): void
    {
        $existing = self::PREFIX . 'CaseValue';
        $this->addValue('rating', $existing, $this->allRoleIds());

        $data = $this->transform($this->action('Accounts', ['rating']), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => strtolower($existing)]);

        $this->assertNotNull($data);
        $this->assertSame($existing, $data['rating']);
        $this->assertSame(1, $this->countValue('rating', $existing));
    }

    /** C14: 選択肢項目が2つあり片方（rating）だけチェックしたとき、チェックした項目だけ追加される */
    public function testAddsOnlyCheckedField(): void
    {
        $action = $this->action('Accounts', ['rating']);
        $ratingValue = self::PREFIX . 'チェック有';
        $industryValue = self::PREFIX . 'チェック無';

        $checked = $this->transform($action, 'Accounts', ['accountname' => self::PREFIX . '顧客1', 'rating' => $ratingValue]);
        $unchecked = $this->transform($action, 'Accounts', ['accountname' => self::PREFIX . '顧客2', 'industry' => $industryValue]);

        $this->assertNotNull($checked);
        $this->assertSame(1, $this->countValue('rating', $ratingValue));
        $this->assertNull($unchecked);
        $this->assertSame(0, $this->countValue('industry', $industryValue));
    }

    // ---- 異常系 -----------------------------------------------------------

    /** C5: 選択肢テーブルにあるが実行者のロールに無い値は追加も割り当てもせず失敗 */
    public function testFailsWhenValueExistsButNotAssignedToUserRole(): void
    {
        $value = self::PREFIX . '他ロール';
        $result = $this->db->pquery('SELECT roleid FROM vtiger_user2role WHERE userid = ?', [$this->user()->id]);
        $userRole = (string) $this->db->query_result($result, 0, 'roleid');
        $this->addValue('rating', $value, array_values(array_diff($this->allRoleIds(), [$userRole])));
        $before = $this->countRoleAssignments('rating', $value);

        $data = $this->transform($this->action('Accounts', ['rating']), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => $value]);

        $this->assertNull($data);
        $this->assertSame(1, $this->countValue('rating', $value));
        $this->assertSame($before, $this->countRoleAssignments('rating', $value));
    }

    /** C6: 選択肢テーブルの列の長さを超える値は追加せず失敗 */
    public function testFailsWhenValueIsLongerThanColumn(): void
    {
        $value = self::PREFIX . str_repeat('長', 200 - mb_strlen(self::PREFIX) + 1);

        $data = $this->transform($this->action('Accounts', ['rating']), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => $value]);

        $this->assertNull($data);
        $this->assertSame(0, $this->countValueLike('rating', self::PREFIX . '長%'));
    }

    /** C6 境界: 列の長さちょうどの値は追加する */
    public function testAddsValueOfExactColumnLength(): void
    {
        $value = self::PREFIX . str_repeat('長', 200 - mb_strlen(self::PREFIX));

        $data = $this->transform($this->action('Accounts', ['rating']), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => $value]);

        $this->assertNotNull($data);
        $this->assertSame(1, $this->countValue('rating', $value));
    }

    /** C11: 選択肢以外の項目で失敗した行の値は、選択肢に追加しない */
    public function testDoesNotAddValueWhenRowFailsOnAnotherField(): void
    {
        $value = self::PREFIX . '失敗行';

        // 必須の顧客企業名が空のため、必須チェックで行が失敗する
        $data = $this->transform($this->action('Accounts', ['rating']), 'Accounts', ['accountname' => '', 'rating' => $value], true);

        $this->assertNull($data);
        $this->assertSame(0, $this->countValue('rating', $value));
    }

    /** C7: 活動は、チェックされていても追加せず失敗 */
    public function testDoesNotAddValueForCalendar(): void
    {
        $value = self::PREFIX . '活動';

        $data = $this->transform($this->action('Calendar', ['taskstatus']), 'Calendar', [
            'subject' => self::PREFIX . 'TODO',
            'taskstatus' => $value,
        ]);

        $this->assertNull($data);
        $this->assertSame(0, $this->countValue('taskstatus', $value));
    }

    /**
     * C8: チェックしない項目（add_picklist_fields に無い・空・NULL・配列以外）は失敗し、選択肢は増えない
     *
     * @param mixed $addPicklistFields
     */
    #[DataProvider('uncheckedProvider')]
    public function testFailsWithoutAddingWhenUnchecked($addPicklistFields): void
    {
        $value = self::PREFIX . '未チェック';

        $data = $this->transform($this->action('Accounts', $addPicklistFields), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => $value]);

        $this->assertNull($data);
        $this->assertSame(0, $this->countValue('rating', $value));
    }

    /** @return array<string, array{0: mixed}> */
    public static function uncheckedProvider(): array
    {
        return [
            '別の項目のみ' => [['industry']],
            '空配列' => [[]],
            'NULL' => [null],
            '配列以外' => ['rating'],
        ];
    }

    // ---- 保存経路 ---------------------------------------------------------

    /** C15: チェックの状態は待ち行列に保存され、読み出した情報に項目名の配列で入る（NULL の行は空扱い） */
    public function testAddPicklistFieldsRoundTripsThroughQueue(): void
    {
        $this->ensureQueueColumn();
        $user = $this->user();
        // 画面のフォームは JSON 文字列で送られ、Vtiger_Request::get() が配列に戻す
        $request = new Vtiger_Request([
            'module' => 'Accounts',
            'field_mapping' => '{"accountname":0,"rating":1}',
            'default_values' => '{}',
            'merge_type' => 0,
            'merge_fields' => '[]',
            'add_picklist_fields' => '["rating"]',
        ]);
        $importIds = [];
        try {
            Import_Queue_Action::add($request, $user);
            $info = Import_Queue_Action::getImportInfo('Accounts', $user);
            $importIds[] = $info['id'];
            $this->assertSame(['rating'], $info['add_picklist_fields']);

            // 列追加前に作られた行（NULL）はどの項目もチェックなしとして扱う
            $this->db->pquery('UPDATE vtiger_import_queue SET add_picklist_fields = NULL WHERE importid = ?', [$info['id']]);
            $info = Import_Queue_Action::getImportInfoById($info['id']);
            $this->assertNull($info['add_picklist_fields']);
            $value = self::PREFIX . '行なし';
            $this->assertNull($this->transform(new Import_Data_Action($info, $user), 'Accounts', ['accountname' => self::PREFIX . '顧客', 'rating' => $value]));
        } finally {
            foreach ($importIds as $importId) {
                Import_Queue_Action::remove($importId, $user);
            }
        }
    }

    /** C17・C18: 保存したマッピングはチェックした項目名を持ち、機能追加前に保存したマッピング（NULL）は空で読み込む */
    public function testSavedMapKeepsAddPicklistFields(): void
    {
        $this->ensureMapsColumn();
        $user = $this->user();
        $names = [self::PREFIX . 'マッピング', self::PREFIX . '旧マッピング'];
        try {
            foreach ($names as $i => $name) {
                $map = ['name' => $name, 'content' => ['顧客企業名' => 'accountname', '格付け' => 'rating'],
                    'module' => 'Accounts', 'has_header' => 1, 'assigned_user_id' => $user->id];
                if ($i === 0) {
                    $map['add_picklist_fields'] = '["rating"]';
                }
                (new Import_Map_Model($map, $user))->save();
            }

            $saved = [];
            foreach (Import_Map_Model::getAllByModule('Accounts') as $map) {
                $saved[$map->getValue('name')] = $map;
            }
            $this->assertSame(['rating'], $saved[$names[0]]->getAddPicklistFields());
            $this->assertSame([], $saved[$names[1]]->getAddPicklistFields());
            // 項目の対応付けは従来どおり読み込める
            $this->assertSame('顧客企業名=accountname&格付け=rating', $saved[$names[1]]->getStringifiedContent());
        } finally {
            $this->db->pquery('DELETE FROM vtiger_import_maps WHERE name IN (?, ?)', $names);
        }
    }

    // ---- ヘルパ -----------------------------------------------------------

    private function user(): \Users
    {
        $user = $GLOBALS['current_user'];
        if (!$user instanceof \Users) {
            throw new \RuntimeException('実行ユーザーが設定されていません');
        }

        return $user;
    }

    /**
     * @param mixed $addPicklistFields チェックされた項目名の配列（待ち行列から読み出した値の想定）
     */
    private function action(string $module, $addPicklistFields): Import_Data_Action
    {
        return new Import_Data_Action([
            'id' => self::IMPORT_ID,
            'module' => $module,
            'field_mapping' => [],
            'merge_type' => 0,
            'merge_fields' => [],
            'default_values' => [],
            'lineitem_currency_id' => null,
            'paging' => 0,
            'add_picklist_fields' => $addPicklistFields,
        ], $GLOBALS['current_user']);
    }

    /**
     * @param array<string, string> $fieldData
     * @return array<string, mixed>|null
     */
    private function transform(Import_Data_Action $action, string $module, array $fieldData, bool $checkMandatory = false): ?array
    {
        $meta = vtws_getModuleHandlerFromName($module, $GLOBALS['current_user'])->getMeta();
        $result = $action->transformForImport($fieldData, $meta, [], false, $checkMandatory);

        return $result === null ? null : (array) $result;
    }

    /** テスト用DBに vtiger_import_maps.add_picklist_fields が無ければ、マイグレーションと同じ列を足す */
    private function ensureMapsColumn(): void
    {
        require_once dirname(__DIR__, 3) . '/setup/migration/scripts/20261104164206_add_picklist_fields_to_import_maps.php';
        ob_start();
        $class = 'Migration20261104164206_AddPicklistFieldsToImportMaps';
        $migration = new $class(); // @phpstan-ignore class.notFound
        $migration->process();
        ob_end_clean();
    }

    /** テスト用DBに vtiger_import_queue.add_picklist_fields が無ければ、マイグレーションと同じ列を足す */
    private function ensureQueueColumn(): void
    {
        require_once dirname(__DIR__, 3) . '/setup/migration/scripts/20261104161239_add_picklist_fields_to_import_queue.php';
        // マイグレーションの log() は標準出力に書くため、テストの出力として扱われないよう捨てる
        ob_start();
        // require_once した setup/migration のクラスは、このファイル単体の静的解析からは見えない
        $class = 'Migration20261104161239_AddPicklistFieldsToImportQueue';
        $migration = new $class(); // @phpstan-ignore class.notFound
        $migration->process();
        ob_end_clean();
    }

    /**
     * 前提データとして選択肢を追加する（設定画面と同じ経路）
     *
     * @param array<int, string> $roleIds
     */
    private function addValue(string $fieldName, string $value, array $roleIds): void
    {
        $moduleModel = Settings_Picklist_Module_Model::getInstance('Accounts');
        $fieldModel = Settings_Picklist_Field_Model::getInstance($fieldName, $moduleModel);
        $moduleModel->addPickListValues($fieldModel, $value, $roleIds);
    }

    private function countValue(string $fieldName, string $value): int
    {
        $result = $this->db->pquery("SELECT COUNT(*) AS c FROM vtiger_{$fieldName} WHERE {$fieldName} = ?", [$value]);

        return (int) $this->db->query_result($result, 0, 'c');
    }

    private function countValueLike(string $fieldName, string $pattern): int
    {
        $result = $this->db->pquery("SELECT COUNT(*) AS c FROM vtiger_{$fieldName} WHERE {$fieldName} LIKE ?", [$pattern]);

        return (int) $this->db->query_result($result, 0, 'c');
    }

    private function countRoleAssignments(string $fieldName, string $value): int
    {
        $result = $this->db->pquery(
            "SELECT COUNT(*) AS c FROM vtiger_role2picklist r
             INNER JOIN vtiger_{$fieldName} p ON p.picklist_valueid = r.picklistvalueid
             WHERE p.{$fieldName} = ?",
            [$value]
        );

        return (int) $this->db->query_result($result, 0, 'c');
    }

    /** @return array<int, string> */
    private function allRoleIds(): array
    {
        $result = $this->db->pquery('SELECT roleid FROM vtiger_role', []);
        $ids = [];
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $ids[] = (string) $this->db->query_result($result, $i, 'roleid');
        }

        return $ids;
    }

    /** テストで追加した選択肢とロールの割り当てを消す */
    private function cleanPicklistValues(): void
    {
        foreach (['rating' => true, 'industry' => true, 'taskstatus' => true, 'payment_duration' => false] as $fieldName => $roleBased) {
            if ($roleBased) {
                $this->db->pquery(
                    "DELETE r FROM vtiger_role2picklist r
                     INNER JOIN vtiger_{$fieldName} p ON p.picklist_valueid = r.picklistvalueid
                     WHERE p.{$fieldName} LIKE ?",
                    [self::PREFIX . '%']
                );
            }
            $this->db->pquery("DELETE FROM vtiger_{$fieldName} WHERE {$fieldName} LIKE ?", [self::PREFIX . '%']);
            // インポートの照合（WebserviceField::getPicklistDetails）が読むキャッシュを捨てる
            Vtiger_Cache::delete('PicklistDetails', $fieldName);
        }
    }
}
