<?php

declare(strict_types=1);

namespace Tests\Integration\Vtiger\Uitypes;

use AppException;
use Settings_LayoutEditor_Field_Model;
use Settings_LayoutEditor_Module_Model;
use Settings_ModuleManager_Module_Model;
use Settings_Vtiger_CustomRecordNumbering_View;
use Settings_Vtiger_CustomRecordNumberingAjax_Action;
use Settings_Vtiger_CustomRecordNumberingModule_Model;
use Tests\Support\DocumentsTestCase;
use Vtiger_AutoNumber;
use Vtiger_AutoNumberValue;
use Vtiger_Module_Model;

require_once dirname(__DIR__, 4) . '/tests/Support/DocumentsTestCase.php';

/**
 * 「設定 > レコード番号」（連番管理）の画面と、そこへつながる入口
 *
 *   - sourceModule / field を変えると、項目の一覧・書式・その他の設定が、そのモジュール・項目のものに変わる
 *   - 保存の Ajax: 成功・エラーの文言・必須の入力
 *   - モジュールの設定リンクと、連番を持つモジュールの一覧に、自動採番(997)の項目も含まれる
 *   - 画面の文言（ja / en）に抜けが無い
 *
 * 標準モジュール（リード・顧客企業など）の自動採番項目で確かめる。変更した設定は元に戻す。
 */
final class AutoNumberNumberingScreenTest extends DocumentsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Vtiger/utils/AutoNumber.php';
        require_once 'includes/runtime/Viewer.php';
        require_once 'includes/runtime/Theme.php';
        require_once 'includes/runtime/JavaScript.php';
        if ($this->scalar("SELECT COUNT(*) FROM vtiger_field WHERE uitype = 4") !== '0') {
            self::markTestSkipped('テスト用DBに連番項目の移行（20261028110000）が適用されていない');
        }
        Vtiger_AutoNumber::clearCache();
    }

    // ---- 画面（切り替え） -------------------------------------------------

    public function test_モジュールを変えるとそのモジュールの項目と書式が出る(): void
    {
        [$first, $second] = $this->twoModulesWithDifferentFormats();

        $firstHtml = $this->renderScreen(['sourceModule' => $first['module']]);
        $secondHtml = $this->renderScreen(['sourceModule' => $second['module']]);

        $this->assertStringContainsString('data-module="' . $first['module'] . '"', $firstHtml);
        $this->assertStringContainsString('data-module="' . $second['module'] . '"', $secondHtml);
        $this->assertStringContainsString('value="' . $first['format'] . '"', $firstHtml);
        $this->assertStringNotContainsString('value="' . $second['format'] . '"', $firstHtml, '別のモジュールの書式は出ない');
        $this->assertStringContainsString('value="' . $second['format'] . '"', $secondHtml);
        $this->assertStringNotContainsString('value="' . $first['format'] . '"', $secondHtml);
        $this->assertSelected($firstHtml, 'sourceModule', $first['module']);
        $this->assertSelected($secondHtml, 'sourceModule', $second['module']);
    }

    public function test_項目の選択肢はそのモジュールの自動採番項目だけ(): void
    {
        $configs = Vtiger_AutoNumber::getModuleConfigs('Accounts');
        $this->assertNotEmpty($configs);
        $html = $this->renderScreen(['sourceModule' => 'Accounts']);

        $options = $this->optionValues($html, 'field');
        $this->assertSame(array_keys($configs), $options);
        foreach (Vtiger_AutoNumber::getModuleConfigs('Leads') as $fieldName => $unused) {
            $this->assertNotContains($fieldName, $options, '別のモジュールの項目は出ない');
        }
    }

    public function test_項目を変えるとその項目の設定が出る(): void
    {
        $module = 'Accounts';
        $extra = $this->addExtraField($module, 'SPECTEST_切替用', 'SW-{YYYY}-{000}');
        try {
            $names = array_keys(Vtiger_AutoNumber::getModuleConfigs($module));
            $this->assertGreaterThanOrEqual(2, count($names));
            $configs = Vtiger_AutoNumber::getModuleConfigs($module);

            foreach ($names as $name) {
                $html = $this->renderScreen(['sourceModule' => $module, 'field' => $name]);
                $this->assertSelected($html, 'field', $name);
                $this->assertStringContainsString('value="' . Vtiger_AutoNumberValue::string($configs[$name]['format']) . '"', $html);
                $this->assertStringContainsString('data-field="' . $name . '"', $html, '遡及採番の欄も選んだ項目のもの');
            }
            $this->assertContains($extra['fieldname'], $names, '追加した項目が選択肢に出る');
        } finally {
            $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($extra['fieldid']);
            if ($fieldModel) {
                $fieldModel->delete();
            }
            Vtiger_AutoNumber::clearCache();
        }
    }

    public function test_項目の指定が無い・不正なら先頭の項目になる(): void
    {
        $configs = Vtiger_AutoNumber::getModuleConfigs('Accounts');
        $first = (string) array_key_first($configs);

        $this->assertSelected($this->renderScreen(['sourceModule' => 'Accounts']), 'field', $first);
        $this->assertSelected($this->renderScreen(['sourceModule' => 'Accounts', 'field' => 'no_such_field']), 'field', $first);
    }

    public function test_モジュールの指定が無い・不正なら先頭のモジュールになる(): void
    {
        $modules = Settings_Vtiger_CustomRecordNumberingModule_Model::getSupportedModules();
        $first = reset($modules);
        $this->assertNotFalse($first);

        $this->assertSelected($this->renderScreen([]), 'sourceModule', $first->getName());
        $this->assertSelected($this->renderScreen(['sourceModule' => 'NoSuchModule']), 'sourceModule', $first->getName());
        $this->assertSelected($this->renderScreen(['sourceModule' => 'Home']), 'sourceModule', $first->getName(), '自動採番項目を持たないモジュール');
    }

    public function test_設定の入力欄には保存済みの値が入る(): void
    {
        $module = 'Accounts';
        $fieldName = (string) array_key_first(Vtiger_AutoNumber::getModuleConfigs($module));
        $fieldId = $this->fieldId($module, $fieldName);
        $original = Vtiger_AutoNumber::getConfig($fieldId);
        $this->assertNotNull($original);
        try {
            Vtiger_AutoNumber::saveConfig($fieldId, [
                'format' => 'SCR-{YY}-{000}', 'reset_type' => 'year', 'start_number' => 5, 'base_date' => '', 'effective_from' => null, 'active' => 0,
            ]);
            $html = $this->renderScreen(['sourceModule' => $module, 'field' => $fieldName]);

            $this->assertStringContainsString('name="format"', $html);
            $this->assertStringContainsString('value="SCR-{YY}-{000}"', $html);
            $this->assertMatchesRegularExpression('/<option value="year" selected="selected">/', $html);
            $this->assertMatchesRegularExpression('/name="start_number"[^>]*value="5"/', $html);
            $this->assertDoesNotMatchRegularExpression('/data-test-id="autonumber-active"[^>]*checked/', $html, '停止中はチェックしない');
        } finally {
            Vtiger_AutoNumber::saveConfig($fieldId, $original);
        }
    }

    public function test_自動採番項目が無ければ案内を出して保存欄は出さない(): void
    {
        $viewer = new \Vtiger_Viewer();
        $viewer->assign('SUPPORTED_MODULES', []);
        $viewer->assign('DEFAULT_MODULE_MODEL', false);
        $viewer->assign('FIELD_CONFIGS', []);
        $viewer->assign('SELECTED_FIELD_NAME', '');
        $viewer->assign('SELECTED_CONFIG', []);
        $viewer->assign('DATE_FIELDS', []);
        $viewer->assign('QUALIFIED_MODULE', 'Settings:Vtiger');
        $viewer->assign('CURRENT_USER_MODEL', \Users_Record_Model::getCurrentUserModel());
        $html = (string) $viewer->view('CustomRecordNumbering.tpl', 'Settings:Vtiger', true);

        $this->assertStringContainsString('data-test-id="autonumber-empty"', $html);
        $this->assertStringNotContainsString('data-test-id="autonumber-save"', $html);
        $this->assertStringNotContainsString('name="format"', $html);
    }

    public function test_自動採番項目を持つモジュールだけが選べる(): void
    {
        $modules = Settings_Vtiger_CustomRecordNumberingModule_Model::getSupportedModules();
        foreach ($modules as $tabId => $module) {
            $this->assertNotEmpty(Vtiger_AutoNumber::getModuleConfigs($module->getName()), $module->getName() . ' は自動採番項目を持つ');
            $this->assertSame((int) $tabId, (int) getTabid($module->getName()));
        }
        $this->assertArrayHasKey(getTabid('Leads'), $modules);
    }

    // ---- 保存の Ajax ------------------------------------------------------

    public function test_保存の入口は成功を返し設定が変わる(): void
    {
        $fieldId = $this->fieldId('Accounts', (string) array_key_first(Vtiger_AutoNumber::getModuleConfigs('Accounts')));
        $fieldName = (string) array_key_first(Vtiger_AutoNumber::getModuleConfigs('Accounts'));
        $original = Vtiger_AutoNumber::getConfig($fieldId);
        $this->assertNotNull($original);
        try {
            $response = $this->callSave($this->saveParams('Accounts', $fieldName, ['format' => 'AJX-{00}', 'start_number' => '3']));
            $this->assertNull($response['error'] ?? null, '成功ではエラーを返さない');
            $this->assertNotEmpty($response['result']);

            Vtiger_AutoNumber::clearCache();
            $saved = Vtiger_AutoNumber::getConfig($fieldId);
            $this->assertNotNull($saved);
            $this->assertSame('AJX-{00}', $saved['format']);
            $this->assertSame(3, $saved['start_number']);
        } finally {
            Vtiger_AutoNumber::saveConfig($fieldId, $original);
        }
    }

    public function test_不正な書式はエラーの文言を返し保存しない(): void
    {
        $fieldName = (string) array_key_first(Vtiger_AutoNumber::getModuleConfigs('Accounts'));
        $fieldId = $this->fieldId('Accounts', $fieldName);
        $before = Vtiger_AutoNumber::getConfig($fieldId);

        $response = $this->callSave($this->saveParams('Accounts', $fieldName, ['format' => 'NO-SEQ']));

        $this->assertIsArray($response['error']);
        $this->assertSame(vtranslate('LBL_AUTONUMBER_ERR_FORMAT_NO_SEQ', 'Vtiger'), $response['error']['message']);
        Vtiger_AutoNumber::clearCache();
        $this->assertSame($before, Vtiger_AutoNumber::getConfig($fieldId));
    }

    public function test_存在しない項目はエラーを返す(): void
    {
        $response = $this->callSave($this->saveParams('Accounts', 'no_such_field', ['format' => 'X-{0}']));

        $this->assertIsArray($response['error']);
        $this->assertSame(vtranslate('LBL_AUTONUMBER_ERR_FIELD_NOT_FOUND', 'Vtiger'), $response['error']['message']);
    }

    public function test_保存の入口はモジュールの指定が必須(): void
    {
        $action = new Settings_Vtiger_CustomRecordNumberingAjax_Action();
        $request = $this->request(['module' => 'Vtiger', 'parent' => 'Settings', 'action' => 'CustomRecordNumberingAjax', 'mode' => 'saveModuleCustomNumberingData']);

        $this->expectException(AppException::class);
        $action->checkPermission($request);
    }

    public function test_保存の入口に残っている古い動作は呼べない(): void
    {
        $action = new Settings_Vtiger_CustomRecordNumberingAjax_Action();
        $request = $this->request(['module' => 'Vtiger', 'parent' => 'Settings', 'action' => 'CustomRecordNumberingAjax', 'sourceModule' => 'Accounts', 'mode' => 'getModuleCustomNumberingData']);

        $this->expectException(\Exception::class);
        ob_start();
        try {
            $action->process($request);
        } finally {
            ob_end_clean();
        }
    }

    // ---- 設定リンク・モジュール一覧 ----------------------------------------

    public function test_自動採番項目を持つモジュールには連番管理のリンクが出る(): void
    {
        $module = Vtiger_Module_Model::getInstance('Accounts');
        $this->assertNotNull($module);
        $this->assertTrue($module->hasSequenceNumberField());

        $links = $module->getSettingLinks();
        $urls = array_map(static fn (array $link): string => (string) ($link['linkurl'] ?? ''), $links);
        $this->assertContains('index.php?parent=Settings&module=Vtiger&view=CustomRecordNumbering&sourceModule=Accounts', $urls);
    }

    public function test_連番の項目を持たないモジュールにはリンクが出ない(): void
    {
        $name = $this->scalar(
            "SELECT t.name FROM vtiger_tab t WHERE t.isentitytype = 1 AND t.presence = 0
               AND t.tabid NOT IN (SELECT tabid FROM vtiger_field WHERE uitype IN (4, 997)) ORDER BY t.tabid LIMIT 1"
        );
        if ($name === '') {
            self::markTestSkipped('連番の項目を持たないモジュールがテスト用DBに無い');
        }
        $module = Vtiger_Module_Model::getInstance($name);
        $this->assertNotNull($module);
        $this->assertFalse($module->hasSequenceNumberField(), $name);
        $urls = array_map(static fn (array $link): string => (string) ($link['linkurl'] ?? ''), $module->getSettingLinks());
        $this->assertNotContains('index.php?parent=Settings&module=Vtiger&view=CustomRecordNumbering&sourceModule=' . $name, $urls);
    }

    public function test_連番を持つモジュールの一覧に自動採番のモジュールが入る(): void
    {
        $modules = Settings_ModuleManager_Module_Model::getModulesSupportingSequenceNumbering();
        $this->assertArrayHasKey('Accounts', $modules);
        $this->assertArrayHasKey('Leads', $modules);
    }

    // ---- 文言 -------------------------------------------------------------

    public function test_自動採番の文言は日本語と英語の両方にそろっている(): void
    {
        $ja = $this->languageStrings('ja_jp', 'Vtiger.php');
        $en = $this->languageStrings('en_us', 'Vtiger.php');
        $jaKeys = array_values(array_filter(array_keys($ja), static fn (string $key): bool => str_starts_with($key, 'LBL_AUTONUMBER_')));
        $enKeys = array_values(array_filter(array_keys($en), static fn (string $key): bool => str_starts_with($key, 'LBL_AUTONUMBER_')));
        sort($jaKeys);
        sort($enKeys);

        $this->assertNotEmpty($jaKeys);
        $this->assertSame($jaKeys, $enKeys, '片方の言語にしか無い LBL_AUTONUMBER_ キーがある');
        foreach ($jaKeys as $key) {
            $this->assertNotSame('', trim(Vtiger_AutoNumberValue::string($ja[$key])), "ja_jp {$key}");
            $this->assertNotSame('', trim(Vtiger_AutoNumberValue::string($en[$key])), "en_us {$key}");
        }
    }

    public function test_書式の検証エラーは全て文言を持つ(): void
    {
        $ja = $this->languageStrings('ja_jp', 'Vtiger.php');
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/modules/Vtiger/utils/AutoNumberFormat.php')
            . (string) file_get_contents(dirname(__DIR__, 4) . '/modules/Vtiger/utils/AutoNumber.php');
        preg_match_all("/'(FORMAT_[A-Z_]+|SEQ_[A-Z_]+|RESET_[A-Z_]+|FIELD_TOKEN_[A-Z_]+|BASE_DATE_[A-Z_]+|START_[A-Z_]+|EFFECTIVE_FROM_[A-Z_]+|LOCKED_BY_JOB)'/", $source, $matches);
        $codes = array_unique($matches[1]);
        $this->assertNotEmpty($codes);
        foreach ($codes as $code) {
            $this->assertArrayHasKey('LBL_AUTONUMBER_ERR_' . $code, $ja, "エラー {$code} の文言が無い");
        }
    }

    public function test_保存したときの通知の文言がある(): void
    {
        foreach (['ja_jp', 'en_us'] as $language) {
            $strings = $this->languageStrings($language, 'Settings/Vtiger.php', 'jsLanguageStrings');
            $this->assertArrayHasKey('JS_RECORD_NUMBERING_SAVED_SUCCESSFULLY_FOR', $strings, $language);
        }
    }

    // ---- 補助 -------------------------------------------------------------

    /**
     * @param array<string, string> $params
     */
    private function renderScreen(array $params): string
    {
        $view = new Settings_Vtiger_CustomRecordNumbering_View();
        $request = $this->request(['module' => 'Vtiger', 'parent' => 'Settings', 'view' => 'CustomRecordNumbering'] + $params);
        ob_start();
        try {
            $view->process($request);
        } finally {
            $html = (string) ob_get_clean();
        }
        return $html;
    }

    /**
     * @param array<string, string> $changes
     * @return array<string, string>
     */
    private function saveParams(string $module, string $field, array $changes): array
    {
        return $changes + [
            'module' => 'Vtiger', 'parent' => 'Settings', 'action' => 'CustomRecordNumberingAjax', 'mode' => 'saveModuleCustomNumberingData',
            'sourceModule' => $module, 'field' => $field, 'format' => '{0000}', 'reset_type' => 'none', 'start_number' => '1',
            'base_date' => '', 'effective_from' => '', 'active' => '1',
        ];
    }

    /**
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    private function callSave(array $params): array
    {
        $action = new Settings_Vtiger_CustomRecordNumberingAjax_Action();
        $request = $this->request($params);
        $action->checkPermission($request);
        ob_start();
        try {
            $action->process($request);
        } finally {
            $output = (string) ob_get_clean();
        }
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded, '応答が JSON ではない: ' . $output);
        return $decoded;
    }

    /**
     * 書式の違う 2 つのモジュール（リード・顧客企業など）を選ぶ
     *
     * @return array{0: array{module: string, format: string}, 1: array{module: string, format: string}}
     */
    private function twoModulesWithDifferentFormats(): array
    {
        $picked = [];
        foreach (Settings_Vtiger_CustomRecordNumberingModule_Model::getSupportedModules() as $module) {
            $configs = Vtiger_AutoNumber::getModuleConfigs($module->getName());
            $firstConfig = reset($configs);
            $this->assertIsArray($firstConfig);
            $format = Vtiger_AutoNumberValue::string($firstConfig['format']);
            foreach ($picked as $other) {
                if ($other['format'] === $format) {
                    continue 2;
                }
            }
            $picked[] = ['module' => $module->getName(), 'format' => $format];
            if (count($picked) === 2) {
                return [$picked[0], $picked[1]];
            }
        }
        self::markTestSkipped('書式の違う自動採番項目のモジュールが 2 つ無い');
    }

    /**
     * 同じモジュールに自動採番項目を 1 つ足す（呼び出し側で消す）
     *
     * @return array{fieldid: int, fieldname: string}
     */
    private function addExtraField(string $module, string $label, string $format): array
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName($module);
        $result = $this->db->pquery('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [getTabid($module)]);
        $blockId = (int) $this->db->query_result($result, 0, 'blockid');
        $fieldModel = $moduleModel->addField('AutoNumber', $blockId, [
            'fieldType' => 'AutoNumber', 'fieldLabel' => $label, 'fieldLength' => '50', 'mandatory' => 'O', 'quickcreate' => 1,
            'masseditable' => 2, 'summaryfield' => 0, 'headerfield' => 0, 'fieldDefaultValue' => '',
        ]);
        $fieldId = (int) $fieldModel->getId();
        Vtiger_AutoNumber::saveConfig($fieldId, ['format' => $format] + Vtiger_AutoNumber::defaultConfig());
        Vtiger_AutoNumber::clearCache();
        return ['fieldid' => $fieldId, 'fieldname' => $fieldModel->getName()];
    }

    private function fieldId(string $module, string $fieldName): int
    {
        return (int) $this->scalar('SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?', [getTabid($module), $fieldName]);
    }

    private function assertSelected(string $html, string $selectName, string $value, string $message = ''): void
    {
        $this->assertMatchesRegularExpression(
            '/<select[^>]*name="' . preg_quote($selectName, '/') . '"[^>]*>.*?<option value="' . preg_quote($value, '/') . '"\s+selected\s*>/s',
            $html,
            $message !== '' ? $message : "{$selectName} は {$value} が選ばれている"
        );
    }

    /**
     * @return list<string>
     */
    private function optionValues(string $html, string $selectName): array
    {
        if (preg_match('/<select[^>]*name="' . preg_quote($selectName, '/') . '"[^>]*>(.*?)<\/select>/s', $html, $select) !== 1) {
            $this->fail("select {$selectName} が無い");
        }
        preg_match_all('/<option value="([^"]*)"/', $select[1], $options);
        return $options[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function languageStrings(string $language, string $file, string $variable = 'languageStrings'): array
    {
        $load = static function (string $path, string $name): mixed {
            $languageStrings = [];
            $jsLanguageStrings = [];
            include $path;
            return ${$name};
        };
        $strings = $load(dirname(__DIR__, 4) . "/languages/{$language}/{$file}", $variable);
        return is_array($strings) ? $strings : [];
    }

    /**
     * @param list<mixed> $params
     */
    private function scalar(string $sql, array $params = []): string
    {
        $result = $this->db->pquery($sql, $params);
        if ($result === false || $this->db->num_rows($result) === 0) {
            return '';
        }
        $row = $this->db->fetch_array($result);
        return Vtiger_AutoNumberValue::string(is_array($row) ? ($row[0] ?? '') : '');
    }
}
