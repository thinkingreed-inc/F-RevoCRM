<?php

declare(strict_types=1);

namespace Tests\Integration\Vtiger\Uitypes;

use Settings_LayoutEditor_Field_Model;
use Settings_LayoutEditor_Module_Model;
use Tests\Support\DocumentsTestCase;
use Users_Record_Model;
use Vtiger_Field_Model;
use Vtiger_Module_Model;
use Vtiger_Record_Model;
use Vtiger_RecordStructure_Model;
use Vtiger_Viewer;
use Vtiger_YearMonth_UIType;
use YearMonthHandler;

require_once dirname(__DIR__, 4) . '/tests/Support/DocumentsTestCase.php';

/**
 * 年月項目（uitype 996）
 *
 *   - レイアウトエディタで項目を追加できる（DATE 型の列、データ型は date のまま）
 *   - どの形で値が来ても、保存前に月初日に丸める。解釈できない値は保存しない
 *   - 表示は書式どおり。編集欄・Web コンポーネントには月初日（DB の形式）を渡す
 *
 * テスト用の項目は顧客企業に作り、クラスの最後に削除する。
 * マイグレーション（uitype の対応とハンドラの登録）は別のテストで確かめる。ここでは同じ状態を直接作る。
 */
final class YearMonthFieldTest extends DocumentsTestCase
{
    /** 作成した項目の ID（作る前は 0）と項目名 */
    private static int $fieldId = 0;
    private static string $fieldName = '';

    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Vtiger/handlers/YearMonthHandler.php';
        require_once 'includes/runtime/Viewer.php';
        // 保存後のワークフローがタスクの定義（tasks/*.inc）を相対パスで読む。本体の起動時に足される探索パスを足しておく
        $workflowPath = dirname(__DIR__, 4) . '/modules/com_vtiger_workflow';
        if (!str_contains(get_include_path(), $workflowPath)) {
            set_include_path($workflowPath . PATH_SEPARATOR . get_include_path());
        }
        $this->ensureMigrated();
        $this->cleanAccounts();
        if (self::$fieldId === 0) {
            $this->deleteLeftoverFields();
        }
        if (self::$fieldId === 0) {
            $field = $this->addYearMonthField('SPECTEST_年月', false);
            self::$fieldId = $field['fieldid'];
            self::$fieldName = $field['fieldname'];
        }
        YearMonthHandler::clearCache();
    }

    protected function tearDown(): void
    {
        $this->cleanAccounts();
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$fieldId !== 0) {
            $fieldModel = Settings_LayoutEditor_Field_Model::getInstance(self::$fieldId);
            if ($fieldModel) {
                $fieldModel->delete();
            }
            self::$fieldId = 0;
            self::$fieldName = '';
        }
        parent::tearDownAfterClass();
    }

    // ---- レイアウトエディタ -----------------------------------------------

    public function test_レイアウトエディタで年月項目を追加できる(): void
    {
        $result = $this->db->pquery('SELECT uitype, typeofdata FROM vtiger_field WHERE fieldid = ?', [self::$fieldId]);
        $this->assertSame('996', (string) $this->db->query_result($result, 0, 'uitype'));
        $this->assertSame('D~O', (string) $this->db->query_result($result, 0, 'typeofdata'));
        $this->assertSame('date', $this->columnType('vtiger_accountscf', self::$fieldName));
    }

    public function test_データ型は日付のままで専用のUITypeクラスが使われる(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $this->assertSame('date', $fieldModel->getFieldDataType());
        $this->assertInstanceOf(Vtiger_YearMonth_UIType::class, $fieldModel->getUITypeModel());
    }

    public function test_レイアウトエディタでは年月として表示され既定値は持たない(): void
    {
        $fieldModel = Settings_LayoutEditor_Field_Model::getInstance(self::$fieldId);
        $this->assertSame('YearMonth', $fieldModel->getFieldDataTypeLabel());
        $this->assertTrue($fieldModel->isDefaultValueOptionDisabled());
    }

    public function test_項目情報にuitypeと表示の書式を含める(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $info = $fieldModel->getFieldInfo();
        $this->assertSame('996', $info['uitype']);
        $this->assertSame('date', $info['type']);
        $this->assertSame(Vtiger_YearMonth_UIType::getDisplayFormat(), $info['displayFormat']);
        $this->assertArrayHasKey('LBL_YEARMONTH_PLACEHOLDER', $info['labels']);

        $textInfo = Vtiger_Field_Model::getInstance('accountname', Vtiger_Module_Model::getInstance('Accounts'))->getFieldInfo();
        $this->assertArrayNotHasKey('uitype', $textInfo, '専用クラスを持たない項目の項目情報は変えない');
    }

    // ---- 保存 -------------------------------------------------------------

    public function test_月の途中の日付は月初日に丸めて保存する(): void
    {
        $id = $this->saveAccount('mid', [self::$fieldName => '2026-10-28']);
        $this->assertSame('2026-10-01', $this->storedValue($id));
    }

    public function test_月初日はそのまま保存する(): void
    {
        $id = $this->saveAccount('first', [self::$fieldName => '2026-03-01']);
        $this->assertSame('2026-03-01', $this->storedValue($id));
    }

    public function test_年月だけの値も保存できる(): void
    {
        $id = $this->saveAccount('ym', [self::$fieldName => '2026-07']);
        $this->assertSame('2026-07-01', $this->storedValue($id));
    }

    public function test_日本語の年月も保存できる(): void
    {
        $id = $this->saveAccount('jp', [self::$fieldName => '2026年12月']);
        $this->assertSame('2026-12-01', $this->storedValue($id));
    }

    public function test_編集で別の日付に変えても月初日に丸める(): void
    {
        $id = $this->saveAccount('edit', [self::$fieldName => '2026-10-01']);
        $this->saveAccount('edit', [self::$fieldName => '2027-02-14'], $id);
        $this->assertSame('2027-02-01', $this->storedValue($id));
    }

    public function test_空にして保存できる(): void
    {
        $id = $this->saveAccount('empty', [self::$fieldName => '2026-10-01']);
        $this->saveAccount('empty', [self::$fieldName => ''], $id);
        $this->assertContains($this->storedValue($id), ['', null], '空にすると値は残らない');
    }

    public function test_項目に触れない保存では値を変えない(): void
    {
        $id = $this->saveAccount('keep', [self::$fieldName => '2026-10-01']);
        $recordModel = Vtiger_Record_Model::getInstanceById($id, 'Accounts');
        $recordModel->set('mode', 'edit');
        $recordModel->set('website', 'http://example.com');
        $recordModel->save();
        $this->assertSame('2026-10-01', $this->storedValue($id));
    }

    public function test_解釈できない値は保存せずエラーにする(): void
    {
        foreach (['2026-13', 'abc', '2026-02-30'] as $invalid) {
            try {
                $this->saveAccount('bad', [self::$fieldName => $invalid]);
                $this->fail("{$invalid} は保存できてはならない");
            } catch (\Exception $e) {
                $this->assertStringContainsString('SPECTEST_年月', $e->getMessage());
            }
        }
        $this->assertSame(0, $this->countAccounts('bad'), 'エラーのときはレコードを作らない');
    }

    // ---- 表示 -------------------------------------------------------------

    public function test_表示は書式どおりの年月で出す(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $uiType = $fieldModel->getUITypeModel();
        $expected = Vtiger_YearMonth_UIType::formatDisplay('2026-10-01', Vtiger_YearMonth_UIType::getDisplayFormat());
        $this->assertNotSame('', $expected);
        $this->assertSame($expected, $uiType->getDisplayValue('2026-10-01'));
        $this->assertSame($expected, $uiType->getDisplayValue('2026-10-28'), '日が月の途中でも年月で出す');
        $this->assertSame('', $uiType->getDisplayValue(''));
    }

    public function test_編集欄とWebコンポーネントには月初日を渡す(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $uiType = $fieldModel->getUITypeModel();
        $this->assertSame('2026-10-01', $uiType->getEditViewDisplayValue('2026-10-28'));
        $this->assertSame('', $uiType->getEditViewDisplayValue(''));
        $this->assertSame('', $uiType->getEditViewDisplayValue(null));

        $props = $uiType->getWebComponentProps('2026-10-28', 'edit', false);
        $this->assertSame('edit', $props['mode']);
        $this->assertSame('2026-10-01', $props['value']);
        $this->assertSame(self::$fieldName, $props['fieldName']);
        $this->assertFalse($props['mandatory']);
        $this->assertSame('fr-field-yearmonth', $uiType->getWebComponentTagName());
        $this->assertSame('uitypes/WebComponentField.tpl', $uiType->getTemplateName());
        $this->assertArrayHasKey('LBL_YEARMONTH_MONTH_12', $props['labels']);
    }

    public function test_画面から送られた値はDBの形式に揃える(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $uiType = $fieldModel->getUITypeModel();
        $this->assertSame('2026-10-01', $uiType->getDBInsertValue('2026-10-28'));
        $this->assertSame('2026-10-01', $uiType->getUserRequestValue('2026-10-01'));
        $this->assertSame('abc', $uiType->getDBInsertValue('abc'), '解釈できない値は保存前のハンドラがエラーにする');
    }

    // ---- 画面（テンプレート） -----------------------------------------------

    public function test_編集画面は値を持つhidden入力とWebコンポーネントを出す(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $fieldModel->set('fieldvalue', '2026-10-28');

        $html = $this->renderTemplate($fieldModel->getUITypeModel()->getTemplateName(), [
            'FIELD_MODEL' => $fieldModel,
            'MODULE' => 'Accounts',
            'REQ' => [],
            'RECORD_STRUCTURE_MODEL' => null,
        ]);

        $name = self::$fieldName;
        $this->assertMatchesRegularExpression('/<input type="hidden" id="Accounts_editView_fieldName_' . $name . '" name="' . $name . '" value="2026-10-01"/', $html);
        $this->assertStringContainsString('<fr-field-yearmonth props="', $html);
        $this->assertStringContainsString('&quot;value&quot;:&quot;2026-10-01&quot;', $html);
        $this->assertStringContainsString('&quot;inputId&quot;:&quot;Accounts_editView_fieldName_' . $name . '&quot;', $html);
    }

    public function test_複製のときは値を空にする(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $fieldModel->set('fieldvalue', '2026-10-01');

        $html = $this->renderTemplate($fieldModel->getUITypeModel()->getTemplateName(), [
            'FIELD_MODEL' => $fieldModel,
            'MODULE' => 'Accounts',
            'REQ' => ['isDuplicate' => true],
            'RECORD_STRUCTURE_MODEL' => null,
        ]);

        $this->assertMatchesRegularExpression('/name="' . self::$fieldName . '" value=""/', $html);
    }

    public function test_詳細画面は年月の文字を出す(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));
        $fieldModel->set('fieldvalue', '2026-10-01');
        $recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');

        $html = $this->renderTemplate($fieldModel->getUITypeModel()->getDetailViewTemplateName(), [
            'FIELD_MODEL' => $fieldModel,
            'MODULE' => 'Accounts',
            'RECORD' => $recordModel,
        ]);

        $this->assertStringContainsString(
            Vtiger_YearMonth_UIType::formatDisplay('2026-10-01', Vtiger_YearMonth_UIType::getDisplayFormat()),
            $html
        );
    }

    public function test_一覧の列検索は日付の入力欄を出す(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fieldName, Vtiger_Module_Model::getInstance('Accounts'));

        $html = $this->renderTemplate($fieldModel->getUITypeModel()->getListSearchTemplateName(), [
            'FIELD_MODEL' => $fieldModel,
            'SEARCH_INFO' => [],
            'USER_MODEL' => Users_Record_Model::getCurrentUserModel(),
        ]);

        $this->assertStringContainsString('listSearchContributor', $html);
        $this->assertStringContainsString('<input', $html);
    }

    public function test_フィルターの条件には日付項目として出す(): void
    {
        $recordStructure = Vtiger_RecordStructure_Model::getInstanceForModule(
            Vtiger_Module_Model::getInstance('Accounts'),
            Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER
        )->getStructure();

        $html = $this->renderTemplate('AdvanceFilterCondition.tpl', [
            'RECORD_STRUCTURE' => $recordStructure,
            'CONDITION_INFO' => [],
            'MODULE' => 'Accounts',
            'SOURCE_MODULE' => 'Accounts',
            'ADVANCED_FILTER_OPTIONS' => [],
            'ADVANCED_FILTER_OPTIONS_BY_TYPE' => [],
            'DATE_FILTERS' => [],
        ]);

        $this->assertMatchesRegularExpression(
            '/data-fieldtype="D" data-field-name="' . self::$fieldName . '"/',
            $html,
            '日付型の条件（今月・範囲指定など）が使える'
        );
    }

    // ---- 一覧 -------------------------------------------------------------

    public function test_一覧は日付ではなく年月で出す(): void
    {
        $suffix = 'list';
        $id = $this->saveAccount($suffix, [self::$fieldName => '2026-10-28']);
        $expected = Vtiger_YearMonth_UIType::formatDisplay('2026-10-01', Vtiger_YearMonth_UIType::getDisplayFormat());

        $rows = $this->listRows(self::PREFIX . $suffix);

        $this->assertSame($expected, $rows[$id][self::$fieldName]);
    }

    public function test_一覧で値が空なら空欄にする(): void
    {
        $suffix = 'listempty';
        $id = $this->saveAccount($suffix);

        $rows = $this->listRows(self::PREFIX . $suffix);

        $this->assertSame('', $rows[$id][self::$fieldName]);
    }

    // ---- インライン編集・Webservice ----------------------------------------

    public function test_インライン編集の保存結果は年月の文字で返す(): void
    {
        $id = $this->saveAccount('inline', [self::$fieldName => '2026-03-01']);
        $request = $this->request([
            'module' => 'Accounts', 'action' => 'SaveAjax', 'record' => (string) $id,
            'field' => self::$fieldName, 'value' => '2026-11-15',
        ]);

        $action = new \Vtiger_SaveAjax_Action();
        ob_start();
        try {
            $action->process($request);
        } finally {
            $output = (string) ob_get_clean();
        }

        $response = json_decode($output, true);
        $this->assertIsArray($response, $output);
        $this->assertTrue($response['success'], $output);
        $this->assertSame('2026-11-01', $response['result'][self::$fieldName]['value'], '保存する値は月初日');
        $this->assertSame(
            Vtiger_YearMonth_UIType::formatDisplay('2026-11-01', Vtiger_YearMonth_UIType::getDisplayFormat()),
            $response['result'][self::$fieldName]['display_value'],
            '画面に出す値は年月の文字'
        );
        $this->assertSame('2026-11-01', $this->storedValue($id));
    }

    public function test_Webservice経由の作成と更新でも月初日に丸める(): void
    {
        require_once 'include/Webservices/Create.php';
        require_once 'include/Webservices/Revise.php';
        $entityUser = \CRMEntity::getInstance('Users');
        $entityUser->retrieveCurrentUserInfoFromFile(self::ADMIN_USER_ID);

        $created = vtws_create('Accounts', [
            'accountname' => self::PREFIX . 'ws',
            'assigned_user_id' => vtws_getWebserviceEntityId('Users', self::ADMIN_USER_ID),
            self::$fieldName => '2026-10-28',
        ], $entityUser);
        $recordId = (int) explode('x', (string) $created['id'])[1];
        $this->assertSame('2026-10-01', $this->storedValue($recordId));

        vtws_revise(['id' => $created['id'], self::$fieldName => '2027-02-14'], $entityUser);
        $this->assertSame('2027-02-01', $this->storedValue($recordId));
    }

    public function test_Webservice経由で解釈できない値はエラーにする(): void
    {
        require_once 'include/Webservices/Create.php';
        $entityUser = \CRMEntity::getInstance('Users');
        $entityUser->retrieveCurrentUserInfoFromFile(self::ADMIN_USER_ID);

        $this->expectException(\Exception::class);
        vtws_create('Accounts', [
            'accountname' => self::PREFIX . 'wsbad',
            'assigned_user_id' => vtws_getWebserviceEntityId('Users', self::ADMIN_USER_ID),
            self::$fieldName => 'not a month',
        ], $entityUser);
    }

    // ---- 補助 -------------------------------------------------------------

    /** マイグレーションが入れるものを、無ければ直接入れる（マイグレーション自体は別のテスト） */
    private function ensureMigrated(): void
    {
        $result = $this->db->pquery("SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = '996'", []);
        if ($this->db->num_rows($result) === 0) {
            $this->db->pquery("INSERT INTO vtiger_ws_fieldtype (uitype, fieldtype) VALUES ('996', 'date')", []);
        }
        $result = $this->db->pquery("SELECT 1 FROM vtiger_eventhandlers WHERE handler_class = 'YearMonthHandler'", []);
        if ($this->db->num_rows($result) === 0) {
            require_once 'include/events/include.inc';
            $em = new \VTEventsManager($this->db);
            $em->registerHandler('vtiger.entity.beforesave', 'modules/Vtiger/handlers/YearMonthHandler.php', 'YearMonthHandler');
        }
    }

    /**
     * @return array{fieldid: int, fieldname: string}
     */
    private function addYearMonthField(string $label, bool $mandatory): array
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName('Accounts');
        $result = $this->db->pquery('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [getTabid('Accounts')]);
        $blockId = (int) $this->db->query_result($result, 0, 'blockid');

        $fieldModel = $moduleModel->addField('YearMonth', $blockId, [
            'fieldType' => 'YearMonth',
            'fieldLabel' => $label,
            'mandatory' => $mandatory ? 'M' : 'O',
            'quickcreate' => 1,
            'masseditable' => 2,
            'summaryfield' => 0,
            'headerfield' => 0,
            'fieldDefaultValue' => '',
        ]);

        return ['fieldid' => (int) $fieldModel->getId(), 'fieldname' => $fieldModel->getName()];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function saveAccount(string $suffix, array $values = [], ?int $recordId = null): int
    {
        $recordModel = $recordId
            ? Vtiger_Record_Model::getInstanceById($recordId, 'Accounts')
            : Vtiger_Record_Model::getCleanInstance('Accounts');
        if ($recordId) {
            $recordModel->set('mode', 'edit');
        } else {
            $recordModel->set('accountname', self::PREFIX . $suffix);
            $recordModel->set('assigned_user_id', self::ADMIN_USER_ID);
        }
        foreach ($values as $name => $value) {
            $recordModel->set($name, $value);
        }
        $recordModel->save();
        return (int) $recordModel->getId();
    }

    /**
     * 画面のテンプレート（layouts/v7/modules/Vtiger）を描画した HTML を返す
     *
     * @param array<string, mixed> $values
     */
    private function renderTemplate(string $template, array $values): string
    {
        $viewer = new Vtiger_Viewer();
        foreach ($values as $name => $value) {
            $viewer->assign($name, $value);
        }
        return (string) $viewer->view($template, 'Accounts', true);
    }

    /**
     * 一覧の行（レコードID => 項目名 => 表示用の値）。名前で絞った対象のレコードだけを取る
     *
     * @return array<int, array<string, mixed>>
     */
    private function listRows(string $accountName): array
    {
        require_once 'include/ListView/ListViewController.php';
        require_once 'include/QueryGenerator/QueryGenerator.php';
        $user = \Users_Record_Model::getCurrentUserModel();
        $queryGenerator = new \EnhancedQueryGenerator('Accounts', $user);
        $queryGenerator->setFields(['accountname', self::$fieldName, 'id']);
        $queryGenerator->addCondition('accountname', $accountName, 'e');
        $result = $this->db->pquery($queryGenerator->getQuery(), []);
        $controller = new \ListViewController($this->db, $user, $queryGenerator);
        $focus = \CRMEntity::getInstance('Accounts');
        $rows = $controller->getListViewRecords($focus, 'Accounts', $result);
        return $rows;
    }

    private function storedValue(int $accountId): ?string
    {
        $result = $this->db->pquery(
            'SELECT ' . self::$fieldName . ' AS v FROM vtiger_accountscf WHERE accountid = ?',
            [$accountId]
        );
        $value = $this->db->query_result($result, 0, 'v');
        return $value === null ? null : (string) $value;
    }

    private function columnType(string $table, string $column): string
    {
        $result = $this->db->pquery(
            'SELECT DATA_TYPE AS data_type FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        return (string) $this->db->query_result($result, 0, 'data_type');
    }

    private function countAccounts(string $suffix): int
    {
        $result = $this->db->pquery(
            'SELECT COUNT(*) AS cnt FROM vtiger_account a INNER JOIN vtiger_crmentity c ON c.crmid = a.accountid
              WHERE c.deleted = 0 AND a.accountname = ?',
            [self::PREFIX . $suffix]
        );
        return (int) $this->db->query_result($result, 0, 'cnt');
    }

    /** 途中で中断したテストが残した、このテスト用の項目を消す */
    private function deleteLeftoverFields(): void
    {
        $result = $this->db->pquery('SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldlabel LIKE ?', [getTabid('Accounts'), 'SPECTEST\\_年月%']);
        $ids = [];
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $ids[] = (int) $this->db->query_result($result, $i, 'fieldid');
        }
        foreach ($ids as $id) {
            $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($id);
            if ($fieldModel) {
                $fieldModel->delete();
            }
        }
    }

    private function cleanAccounts(): void
    {
        $result = $this->db->pquery('SELECT accountid FROM vtiger_account WHERE accountname LIKE ?', [self::PREFIX . '%']);
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $accountId = (int) $this->db->query_result($result, $i, 'accountid');
            foreach (['vtiger_accountscf' => 'accountid', 'vtiger_accountbillads' => 'accountaddressid',
                      'vtiger_accountshipads' => 'accountaddressid', 'vtiger_account' => 'accountid', 'vtiger_crmentity' => 'crmid'] as $table => $column) {
                $this->db->pquery("DELETE FROM {$table} WHERE {$column} = ?", [$accountId]);
            }
        }
    }
}
