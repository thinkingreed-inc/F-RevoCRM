<?php

declare(strict_types=1);

namespace Tests\Integration\Vtiger\Uitypes;

use DateTimeImmutable;
use DateTimeZone;
use Settings_LayoutEditor_Field_Model;
use Settings_LayoutEditor_Module_Model;
use Tests\Support\DocumentsTestCase;
use Vtiger_AutoNumber;
use Vtiger_AutoNumberBackfill;
use Vtiger_AutoNumberValue;
use Vtiger_Field_Model;
use Vtiger_Module_Model;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 4) . '/tests/Support/DocumentsTestCase.php';

/**
 * 自動採番項目（uitype 997）
 *
 *   - レイアウトエディタで項目を追加でき、設定が不正なら項目を作らない
 *   - 新規レコードには書式どおりの番号を付け、既存レコードの値は書き換えさせない
 *   - リセット（年・月・日）・基準日・適用開始日時・停止中・{項目名}・複数項目
 *   - 別プロセスから同時に払い出しても重複しない
 *   - 遡及採番: 作成日時順・静止期間・他で付いた番号を壊さない・冪等・ロック・一時停止と中止
 *
 * テスト用の項目は顧客企業に作り、クラスの最後に削除する。
 * マイグレーション（テーブル・ws_fieldtype・ハンドラ）は適用済みの前提（別のテストで確かめる）。
 */
final class AutoNumberFieldTest extends DocumentsTestCase
{
    /** @var array<string, array{fieldid: int, fieldname: string}> 作成した項目（キー => fieldid / fieldname） */
    private static array $fields = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Vtiger/utils/AutoNumber.php';
        require_once 'includes/runtime/Viewer.php';
        require_once 'includes/runtime/Theme.php';
        $workflowPath = dirname(__DIR__, 4) . '/modules/com_vtiger_workflow';
        if (!str_contains(get_include_path(), $workflowPath)) {
            set_include_path($workflowPath . PATH_SEPARATOR . get_include_path());
        }
        require_once 'modules/Vtiger/apis/AutoNumber.php';
        $this->requireMigrated();
        $this->cleanAccounts();
        if (empty(self::$fields)) {
            $this->deleteLeftoverFields();
        }
        if (empty(self::$fields)) {
            self::$fields['main'] = $this->addAutoNumberField('SPECTEST_採番', ['autonumber_format' => 'SPEC-{YYYY}{MM}-{0000}', 'autonumber_reset_type' => 'month']);
            self::$fields['date'] = $this->addPlainField('Date', 'SPECTEST_採番基準日');
        }
        Vtiger_AutoNumber::clearCache();
        $this->resetMain();
        $this->markExistingAccounts();
    }

    protected function tearDown(): void
    {
        $this->cleanAccounts();
        $this->db->pquery('DELETE FROM vtiger_autonumber_job WHERE fieldid = ?', [self::$fields['main']['fieldid']]);
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$fields as $field) {
            $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($field['fieldid']);
            if ($fieldModel) {
                $fieldModel->delete();
            }
        }
        self::$fields = [];
        parent::tearDownAfterClass();
    }

    // ---- レイアウトエディタ -----------------------------------------------

    public function test_レイアウトエディタで自動採番項目を追加できる(): void
    {
        $row = $this->fieldRow(self::$fields['main']['fieldid']);
        $this->assertSame('997', Vtiger_AutoNumberValue::string($row['uitype']));
        $this->assertSame('V~O', $row['typeofdata']);
        $this->assertSame('3', Vtiger_AutoNumberValue::string($row['quickcreate']), 'クイック作成には出さない');
        $this->assertSame('varchar', $this->columnType('vtiger_accountscf', self::$fields['main']['fieldname']));

        $config = $this->config(self::$fields['main']['fieldid']);
        $this->assertSame('SPEC-{YYYY}{MM}-{0000}', $config['format']);
        $this->assertSame('month', $config['reset_type']);
        $this->assertSame(1, $config['start_number']);
        $this->assertSame(1, $config['active']);
    }

    public function test_データ型は文字のままで専用のUITypeクラスが使われる(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fields['main']['fieldname'], Vtiger_Module_Model::getInstance('Accounts'));
        $this->assertSame('string', $fieldModel->getFieldDataType());
        $this->assertInstanceOf(\Vtiger_AutoNumber_UIType::class, $fieldModel->getUITypeModel());
        $this->assertFalse($fieldModel->isEditable(), '編集できない');
        $this->assertFalse($fieldModel->isAjaxEditable(), 'インライン編集できない');
        $this->assertTrue($fieldModel->isViewableInDetailView());
        $this->assertSame('AutoNumber', Settings_LayoutEditor_Field_Model::getInstance(self::$fields['main']['fieldid'])->getFieldDataTypeLabel());
    }

    /** @return array<string, array{0: array<string, string>, 1: string}> */
    public static function invalidConfigProvider(): array
    {
        return [
            '書式が空' => [['autonumber_format' => ''], 'FORMAT_EMPTY'],
            '連番が無い' => [['autonumber_format' => 'A-{YYYY}'], 'FORMAT_NO_SEQ'],
            '年リセットに年が無い' => [['autonumber_format' => 'A-{000}', 'autonumber_reset_type' => 'year'], 'RESET_NEEDS_YEAR'],
            '存在しない項目' => [['autonumber_format' => '{nonexistent_field}-{000}'], 'FIELD_TOKEN_UNKNOWN'],
            '基準日が日付でない' => [['autonumber_format' => 'A-{000}', 'autonumber_base_date' => 'accountname'], 'BASE_DATE_INVALID'],
            '開始番号が0' => [['autonumber_format' => 'A-{000}', 'autonumber_start_number' => '0'], 'START_INVALID'],
            '適用開始日時の形式' => [['autonumber_format' => 'A-{000}', 'autonumber_effective_from' => '2026/10/01'], 'EFFECTIVE_FROM_INVALID'],
        ];
    }

    /**
     * @param array<string, string> $params
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidConfigProvider')]
    public function test_不正な設定は保存しない(array $params, string $expectedError): void
    {
        $fieldId = self::$fields['main']['fieldid'];
        $before = $this->config($fieldId);
        $errors = $this->applyConfig(self::$fields['main']['fieldname'], $params + ['autonumber_format' => 'A-{000}']);
        $this->assertContains($expectedError, $errors);
        $this->assertSame($before, $this->config($fieldId), '設定は変えない');
    }

    public function test_存在しない項目の設定は保存できない(): void
    {
        $this->assertSame(['FIELD_NOT_FOUND'], $this->applyConfig('cf_nonexistent', ['autonumber_format' => '{000}']));
    }

    public function test_項目を追加した直後は既定の設定で、連番管理の画面で設定する(): void
    {
        $field = $this->addField('AutoNumber', 'SPECTEST_採番既定', []);
        try {
            $config = $this->config($field['fieldid']);
            $this->assertSame(Vtiger_AutoNumber::defaultConfig()['format'], $config['format']);
            $this->assertSame(1, $config['active']);
            $this->assertSame([], $this->applyConfig($field['fieldname'], ['autonumber_format' => 'DEF-{00}']));
            $this->assertSame('DEF-{00}', $this->config($field['fieldid'])['format']);
        } finally {
            Settings_LayoutEditor_Field_Model::getInstance($field['fieldid'])->delete();
        }
    }

    public function test_書式で別の自動採番項目は参照できない(): void
    {
        $name = self::$fields['main']['fieldname'];
        $errors = Vtiger_AutoNumber::validateConfig('Accounts', 0, ['format' => '{' . $name . '}-{000}', 'reset_type' => 'none', 'start_number' => 1]);
        $this->assertContains('FIELD_TOKEN_AUTONUMBER', $errors);
    }

    // ---- 新規保存 ---------------------------------------------------------

    public function test_新規レコードに書式どおりの番号を付ける(): void
    {
        $id = $this->saveAccount('first');
        $expected = 'SPEC-' . Vtiger_AutoNumber::now()->format('Ym') . '-0001';
        $this->assertSame($expected, $this->storedValue($id));
        $id2 = $this->saveAccount('second');
        $this->assertSame('SPEC-' . Vtiger_AutoNumber::now()->format('Ym') . '-0002', $this->storedValue($id2));
    }

    public function test_送られた値は無視してサーバーが付ける(): void
    {
        $id = $this->saveAccount('tamper', [self::$fields['main']['fieldname'] => 'HACK-1']);
        $this->assertStringStartsWith('SPEC-', $this->storedValue($id));
    }

    public function test_編集では値を変えず送られた値も捨てる(): void
    {
        $id = $this->saveAccount('edit');
        $stored = $this->storedValue($id);
        $this->saveAccount('edit', [self::$fields['main']['fieldname'] => 'HACK-2'], $id);
        $this->assertSame($stored, $this->storedValue($id), '送られた値で書き換えない');
        $this->saveAccount('edit', [self::$fields['main']['fieldname'] => ''], $id);
        $this->assertSame($stored, $this->storedValue($id), '空で保存しても消えない');
        $this->saveAccount('edit', ['website' => 'http://example.com'], $id);
        $this->assertSame($stored, $this->storedValue($id));
    }

    public function test_複製は新規として採番する(): void
    {
        $id = $this->saveAccount('orig');
        $copy = Vtiger_Record_Model::getInstanceById($id, 'Accounts');
        $copy->set('id', '');
        $copy->set('mode', '');
        $copy->set('accountname', self::PREFIX . 'copy');
        $copy->save();
        $this->assertNotSame($this->storedValue($id), $this->storedValue((int) $copy->getId()));
        $this->assertStringEndsWith('0002', $this->storedValue((int) $copy->getId()));
    }

    public function test_停止中の新規レコードは空のままで後から再開できる(): void
    {
        $this->setConfig(['active' => 0]);
        $id = $this->saveAccount('inactive');
        $this->assertSame('', $this->storedValue($id));
        $this->setConfig(['active' => 1]);
        $id2 = $this->saveAccount('active');
        $this->assertStringEndsWith('0001', $this->storedValue($id2), '停止中は連番を進めない');
    }

    public function test_適用開始日時より前の新規レコードには付けない(): void
    {
        $this->setConfig(['effective_from' => gmdate('Y-m-d H:i:s', time() + 3600)]);
        $id = $this->saveAccount('before');
        $this->assertSame('', $this->storedValue($id));
        $this->setConfig(['effective_from' => gmdate('Y-m-d H:i:s', time() - 3600)]);
        $id2 = $this->saveAccount('after');
        $this->assertStringStartsWith('SPEC-', $this->storedValue($id2));
    }

    public function test_項目の値を書式に使える(): void
    {
        $this->setConfig(['format' => '{accountname}-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('tok');
        $this->assertSame(self::PREFIX . 'tok-001', $this->storedValue($id));
    }

    public function test_基準日に日付項目を指定できる(): void
    {
        $this->setConfig(['format' => '{YYYY}{MM}-{000}', 'reset_type' => 'month', 'base_date' => self::$fields['date']['fieldname']]);
        $id = $this->saveAccount('base1', [self::$fields['date']['fieldname'] => '2020-03-15']);
        $this->assertSame('202003-001', $this->storedValue($id));
        $id2 = $this->saveAccount('base2', [self::$fields['date']['fieldname'] => '2020-03-20']);
        $this->assertSame('202003-002', $this->storedValue($id2));
        $id3 = $this->saveAccount('base3', [self::$fields['date']['fieldname'] => '2020-04-01']);
        $this->assertSame('202004-001', $this->storedValue($id3), '別の月は 1 から数え直す');
    }

    public function test_基準日の項目が空なら保存した日時を使う(): void
    {
        $this->setConfig(['format' => '{YYYY}{MM}-{000}', 'reset_type' => 'month', 'base_date' => self::$fields['date']['fieldname']]);
        $id = $this->saveAccount('basenone');
        $this->assertSame(Vtiger_AutoNumber::now()->format('Ym') . '-001', $this->storedValue($id));
    }

    public function test_リセットの境界で連番を数え直す(): void
    {
        $this->setConfig(['format' => '{YYYY}{MM}{DD}-{00}', 'reset_type' => 'day']);
        $config = Vtiger_AutoNumber::getModuleConfigs('Accounts')[self::$fields['main']['fieldname']];
        $zone = new DateTimeZone('Asia/Tokyo');
        $issue = static fn (string $date): string => Vtiger_AutoNumber::issue($config, new DateTimeImmutable($date, $zone), []);

        $this->assertSame('20261231-01', $issue('2026-12-31 23:59:59'));
        $this->assertSame('20261231-02', $issue('2026-12-31 00:00:00'));
        $this->assertSame('20270101-01', $issue('2027-01-01 00:00:00'), '日をまたぐと 1 から');
        $this->assertSame('20240229-01', $issue('2024-02-29 12:00:00'), 'うるう日');
        $this->assertSame('20261231-03', $issue('2026-12-31 12:00:00'), '前の日のカウンターは続きから');
    }

    public function test_開始番号から始まり桁があふれたら桁が増える(): void
    {
        $this->setConfig(['format' => 'N{00}', 'reset_type' => 'none', 'start_number' => 99]);
        $config = Vtiger_AutoNumber::getModuleConfigs('Accounts')[self::$fields['main']['fieldname']];
        $now = new DateTimeImmutable('2026-10-01');
        $this->assertSame('N99', Vtiger_AutoNumber::issue($config, $now, []));
        $this->assertSame('N100', Vtiger_AutoNumber::issue($config, $now, []));
    }

    public function test_開始番号を上げると作成済みのカウンターも進み戻すことはしない(): void
    {
        $this->setConfig(['format' => 'N{000}', 'reset_type' => 'none']);
        $config = Vtiger_AutoNumber::getModuleConfigs('Accounts')[self::$fields['main']['fieldname']];
        $now = new DateTimeImmutable('2026-10-01');
        Vtiger_AutoNumber::issue($config, $now, []);
        Vtiger_AutoNumber::issue($config, $now, []);
        $this->setConfig(['start_number' => 100]);
        $config = Vtiger_AutoNumber::getModuleConfigs('Accounts')[self::$fields['main']['fieldname']];
        $this->assertSame('N100', Vtiger_AutoNumber::issue($config, $now, []));
        $this->setConfig(['start_number' => 5]);
        $config = Vtiger_AutoNumber::getModuleConfigs('Accounts')[self::$fields['main']['fieldname']];
        $this->assertSame('N101', Vtiger_AutoNumber::issue($config, $now, []), '開始番号を下げても戻らない（重複させない）');
    }

    public function test_1レコードの複数の連番項目はそれぞれ独立して採番する(): void
    {
        $second = $this->addAutoNumberField('SPECTEST_採番2', ['autonumber_format' => 'B{00}', 'autonumber_reset_type' => 'none']);
        try {
            Vtiger_AutoNumber::clearCache();
            $id = $this->saveAccount('multi1');
            $id2 = $this->saveAccount('multi2');
            $this->assertStringEndsWith('0002', $this->storedValue($id2));
            $this->assertSame('B01', $this->storedValue($id, $second['fieldname']));
            $this->assertSame('B02', $this->storedValue($id2, $second['fieldname']));

            $this->db->pquery('UPDATE ' . Vtiger_AutoNumber::CONFIG_TABLE . ' SET active = 0 WHERE fieldid = ?', [$second['fieldid']]);
            Vtiger_AutoNumber::clearCache();
            $id3 = $this->saveAccount('multi3');
            $this->assertStringEndsWith('0003', $this->storedValue($id3), '片方を停止してももう片方は付く');
            $this->assertSame('', $this->storedValue($id3, $second['fieldname']));
        } finally {
            Settings_LayoutEditor_Field_Model::getInstance($second['fieldid'])->delete();
        }
    }

    public function test_他のモジュールの保存には影響しない(): void
    {
        $recordModel = Vtiger_Record_Model::getCleanInstance('Contacts');
        $recordModel->set('lastname', self::PREFIX . 'contact');
        $recordModel->set('assigned_user_id', self::ADMIN_USER_ID);
        $recordModel->save();
        $this->assertGreaterThan(0, (int) $recordModel->getId());
        $this->db->pquery('DELETE FROM vtiger_contactdetails WHERE contactid = ?', [$recordModel->getId()]);
        $this->db->pquery('DELETE FROM vtiger_contactscf WHERE contactid = ?', [$recordModel->getId()]);
        $this->db->pquery('DELETE FROM vtiger_contactaddress WHERE contactaddressid = ?', [$recordModel->getId()]);
        $this->db->pquery('DELETE FROM vtiger_contactsubdetails WHERE contactsubscriptionid = ?', [$recordModel->getId()]);
        $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', [$recordModel->getId()]);
    }

    // ---- 同時採番 -----------------------------------------------------------

    public function test_別プロセスから同時に払い出しても番号が重複しない(): void
    {
        $fieldId = self::$fields['main']['fieldid'];
        $processes = 4;
        $perProcess = 25;
        $worker = dirname(__DIR__, 3) . '/Support/autonumber_allocate_worker.php';

        $running = [];
        for ($i = 0; $i < $processes; $i++) {
            $command = [PHP_BINARY, '-d', 'error_reporting=0', '-d', 'display_errors=0', $worker, (string) $fieldId, 'concurrent', (string) $perProcess];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 4), ['XDEBUG_MODE' => 'off'] + $_ENV + getenv());
            $this->assertIsResource($process);
            $running[] = [$process, $pipes];
        }

        $numbers = [];
        foreach ($running as [$process, $pipes]) {
            $output = (string) stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            foreach (preg_split('/\s+/', trim($output)) ?: [] as $line) {
                if ($line !== '' && ctype_digit($line)) {
                    $numbers[] = (int) $line;
                }
            }
        }

        $this->assertCount($processes * $perProcess, $numbers, '全部の払い出しが終わっている');
        $this->assertCount(count($numbers), array_unique($numbers), '同じ番号を 2 回出さない');
        sort($numbers);
        $this->assertSame(range(1, $processes * $perProcess), $numbers, '欠けもない');
    }

    // ---- 遡及採番 -----------------------------------------------------------

    public function test_遡及採番は作成日時の古い順に番号を付ける(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'BF-{000}', 'reset_type' => 'none']);
        // 作成の順（ID）と作成日時の順を逆にして、作成日時順で付くことを確かめる
        $ids = [];
        foreach (['a', 'b', 'c'] as $suffix) {
            $ids[$suffix] = $this->saveAccount('bf_' . $suffix);
        }
        $this->setCreated($ids['a'], 1);
        $this->setCreated($ids['b'], 2);
        $this->setCreated($ids['c'], 3);
        $this->setConfig(['active' => 1]);

        $jobId = Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);
        $this->runJobToEnd($jobId);

        $this->assertSame('BF-001', $this->storedValue($ids['c']), '一番古い');
        $this->assertSame('BF-002', $this->storedValue($ids['b']));
        $this->assertSame('BF-003', $this->storedValue($ids['a']), '一番新しい');
        $job = $this->job($jobId);
        $this->assertSame(Vtiger_AutoNumberBackfill::STATUS_DONE, $job['status']);
        $this->assertSame(3, $job['total']);
        $this->assertSame(3, $job['processed']);
        $this->assertSame('BF-003', $job['last_number']);
    }

    public function test_プレビューは連番を進めずに予定を返す(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'PV-{000}', 'reset_type' => 'none']);
        $a = $this->saveAccount('pv_a');
        $b = $this->saveAccount('pv_b');
        $this->setCreated($a, 2);
        $this->setCreated($b, 1);
        $this->setConfig(['active' => 1]);

        $preview = Vtiger_AutoNumberBackfill::preview(self::$fields['main']['fieldid'], null, null, self::ADMIN_USER_ID);
        $this->assertSame(2, $preview['total']);
        $this->assertSame('PV-001', $preview['first']);
        $this->assertSame('PV-002', $preview['last']);

        $preview2 = Vtiger_AutoNumberBackfill::preview(self::$fields['main']['fieldid'], null, null, self::ADMIN_USER_ID);
        $this->assertSame('PV-001', $preview2['first'], '何度見ても連番は進まない');
    }

    public function test_遡及採番は基準日の項目と過去の年月のカウンターを使う(): void
    {
        $this->setConfig(['active' => 0, 'format' => '{YYYY}{MM}-{00}', 'reset_type' => 'month', 'base_date' => self::$fields['date']['fieldname']]);
        $a = $this->saveAccount('bd_a', [self::$fields['date']['fieldname'] => '2019-05-10']);
        $b = $this->saveAccount('bd_b', [self::$fields['date']['fieldname'] => '2019-05-20']);
        $c = $this->saveAccount('bd_c', [self::$fields['date']['fieldname'] => '2019-06-01']);
        foreach ([$a => 3, $b => 2, $c => 1] as $id => $days) {
            $this->setCreated($id, $days);
        }
        $this->setConfig(['active' => 1]);

        $this->runJobToEnd(Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID));

        $this->assertSame('201905-01', $this->storedValue($a));
        $this->assertSame('201905-02', $this->storedValue($b));
        $this->assertSame('201906-01', $this->storedValue($c));
    }

    public function test_作成日時の範囲で対象を絞れる(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'RG-{000}', 'reset_type' => 'none']);
        $old = $this->saveAccount('rg_old');
        $new = $this->saveAccount('rg_new');
        $this->setCreated($old, 10);
        $this->setCreated($new, 1);
        $this->setConfig(['active' => 1]);

        $from = gmdate('Y-m-d H:i:s', time() - 5 * 86400);
        $jobId = Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID, $from, null);
        $this->runJobToEnd($jobId);

        $this->assertSame('', $this->storedValue($old), '範囲外には付けない');
        $this->assertSame('RG-001', $this->storedValue($new));
    }

    public function test_作成・更新から間もないレコードは飛ばして後で処理する(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'QT-{000}', 'reset_type' => 'none']);
        $old = $this->saveAccount('qt_old');
        $fresh = $this->saveAccount('qt_fresh');
        $this->setCreated($old, 1);
        $this->setConfig(['active' => 1]);

        $jobId = Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);
        $job = $this->batch($jobId);

        $this->assertSame('QT-001', $this->storedValue($old));
        $this->assertSame('', $this->storedValue($fresh), '保存して間もないレコードには触らない');
        $this->assertSame(Vtiger_AutoNumberBackfill::STATUS_QUEUED, $job['status'], '残りがあるので終わらない');

        $this->setCreated($fresh, 1);
        $job = $this->batch($jobId);
        $this->assertSame('QT-002', $this->storedValue($fresh), '静止期間を過ぎたら次の回で付く');
        $this->assertSame(Vtiger_AutoNumberBackfill::STATUS_DONE, $job['status']);
    }

    public function test_他で番号が付いたレコードや編集済みの値を壊さない(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'CK-{000}', 'reset_type' => 'none']);
        $a = $this->saveAccount('ck_a');
        $b = $this->saveAccount('ck_b');
        $this->setCreated($a, 2);
        $this->setCreated($b, 1);
        $this->setConfig(['active' => 1]);
        // a には、遡及採番より先に別の経路で値が入った
        $this->db->pquery('UPDATE vtiger_accountscf SET ' . self::$fields['main']['fieldname'] . ' = ? WHERE accountid = ?', ['MANUAL-1', $a]);

        $this->runJobToEnd(Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID));

        $this->assertSame('MANUAL-1', $this->storedValue($a), '既に値があるレコードは対象外');
        $this->assertSame('CK-001', $this->storedValue($b));
        $result = $this->db->pquery('SELECT website FROM vtiger_account WHERE accountid = ?', [$b]);
        $this->assertNotFalse($result, '他の項目の列を書き換えていない');
    }

    public function test_遡及採番は更新日時を変えずほかの項目に触れない(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'MT-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('mt', ['website' => 'http://keep.example.com']);
        $this->setCreated($id, 1);
        $before = $this->scalar('SELECT modifiedtime FROM vtiger_crmentity WHERE crmid = ?', [$id]);
        $this->setConfig(['active' => 1]);

        $this->runJobToEnd(Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID));

        $after = $this->scalar('SELECT modifiedtime FROM vtiger_crmentity WHERE crmid = ?', [$id]);
        $this->assertSame($before, $after);
        $website = $this->scalar('SELECT website FROM vtiger_account WHERE accountid = ?', [$id]);
        $this->assertSame('http://keep.example.com', $website);
    }

    public function test_削除済みのレコードは対象にしない(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'DL-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('dl');
        $this->setCreated($id, 1);
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$id]);
        $this->setConfig(['active' => 1]);

        $preview = Vtiger_AutoNumberBackfill::preview(self::$fields['main']['fieldid'], null, null, self::ADMIN_USER_ID);
        $this->assertSame(0, $preview['total']);
    }

    public function test_保存途中で基幹の行しか無いレコードには触らない(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'HF-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('hf');
        $this->setCreated($id, 1);
        // 項目の行がまだ作られていない状態
        $this->db->pquery('DELETE FROM vtiger_accountscf WHERE accountid = ?', [$id]);
        $this->setConfig(['active' => 1]);

        $preview = Vtiger_AutoNumberBackfill::preview(self::$fields['main']['fieldid'], null, null, self::ADMIN_USER_ID);
        $this->assertSame(0, $preview['total']);
    }

    public function test_2回目の実行は何もせず冪等(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'ID-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('idem');
        $this->setCreated($id, 1);
        $this->setConfig(['active' => 1]);
        $this->runJobToEnd(Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID));
        $value = $this->storedValue($id);

        $second = Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);
        $this->assertSame(0, $this->job($second)['total']);
        $this->runJobToEnd($second);
        $this->assertSame($value, $this->storedValue($id));
        $this->assertSame('ID-001', $value);
    }

    public function test_実行中のロックがあれば別の処理は進めない(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'LK-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('lk');
        $this->setCreated($id, 1);
        $this->setConfig(['active' => 1]);
        $jobId = Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);

        $this->db->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET status = ?, lock_until = ? WHERE jobid = ?',
            ['running', date('Y-m-d H:i:s', time() + 600), $jobId]
        );
        Vtiger_AutoNumberBackfill::processBatch($jobId);
        $this->assertSame('', $this->storedValue($id), 'ロック期限内は他が処理中');

        $this->db->pquery('UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET lock_until = ? WHERE jobid = ?', [date('Y-m-d H:i:s', time() - 5), $jobId]);
        Vtiger_AutoNumberBackfill::processBatch($jobId);
        $this->assertSame('LK-001', $this->storedValue($id), 'ロック期限が切れたら引き継げる');
    }

    public function test_一時停止と中止(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'PS-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('ps');
        $this->setCreated($id, 1);
        $this->setConfig(['active' => 1]);
        $jobId = Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);

        $this->assertTrue(Vtiger_AutoNumberBackfill::pause($jobId));
        Vtiger_AutoNumberBackfill::processBatch($jobId);
        $this->assertSame('', $this->storedValue($id), '一時停止中は進まない');

        $this->assertTrue(Vtiger_AutoNumberBackfill::resume($jobId));
        $this->assertTrue(Vtiger_AutoNumberBackfill::cancel($jobId));
        Vtiger_AutoNumberBackfill::processBatch($jobId);
        $this->assertSame('', $this->storedValue($id), '中止したら進まない');
        $this->assertSame('cancelled', $this->job($jobId)['status']);
        $this->assertFalse(Vtiger_AutoNumberBackfill::pause($jobId), '終わったジョブは操作できない');
    }

    public function test_終わっていないジョブがあるあいだは二重に開始できない(): void
    {
        Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);
        $this->expectExceptionMessage('ALREADY_RUNNING');
        Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);
    }

    public function test_実行中は書式などの変更と項目の削除を拒否する(): void
    {
        Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);
        $config = $this->config(self::$fields['main']['fieldid']);

        foreach (['format' => 'X-{000}', 'reset_type' => 'none', 'base_date' => self::$fields['date']['fieldname'], 'start_number' => 50] as $key => $value) {
            $errors = Vtiger_AutoNumber::validateConfig('Accounts', self::$fields['main']['fieldid'], array_merge($this->config(self::$fields['main']['fieldid']), [$key => $value]));
            $this->assertContains('LOCKED_BY_JOB', $errors, $key);
        }
        $errors = Vtiger_AutoNumber::validateConfig('Accounts', self::$fields['main']['fieldid'], array_merge($config, ['active' => 0]));
        $this->assertNotContains('LOCKED_BY_JOB', $errors, '停止（ON/OFF）は変えられる');

        $second = $this->addAutoNumberField('SPECTEST_採番削除対象', ['autonumber_format' => 'DEL-{000}', 'autonumber_reset_type' => 'none']);
        Vtiger_AutoNumberBackfill::start($second['fieldid'], self::ADMIN_USER_ID);
        try {
            Settings_LayoutEditor_Field_Model::getInstance($second['fieldid'])->delete();
            $this->fail('実行中の項目は削除できない');
        } catch (\Exception $e) {
            $this->assertStringContainsString(vtranslate('LBL_AUTONUMBER_ERR_LOCKED_BY_JOB', 'Vtiger'), $e->getMessage());
        }
        Vtiger_AutoNumberBackfill::cancel($this->latestJobId($second['fieldid']));
        Settings_LayoutEditor_Field_Model::getInstance($second['fieldid'])->delete();
    }

    public function test_項目を削除すると設定とカウンターとジョブも消える(): void
    {
        $second = $this->addAutoNumberField('SPECTEST_採番掃除', ['autonumber_format' => 'CL-{000}', 'autonumber_reset_type' => 'none']);
        Vtiger_AutoNumber::allocate($second['fieldid'], '', 1);
        $jobId = Vtiger_AutoNumberBackfill::start($second['fieldid'], self::ADMIN_USER_ID);
        Vtiger_AutoNumberBackfill::cancel($jobId);

        Settings_LayoutEditor_Field_Model::getInstance($second['fieldid'])->delete();

        foreach ([Vtiger_AutoNumber::CONFIG_TABLE, Vtiger_AutoNumber::COUNTER_TABLE, Vtiger_AutoNumber::JOB_TABLE] as $table) {
            $this->assertSame('0', $this->scalar("SELECT COUNT(*) FROM {$table} WHERE fieldid = ?", [$second['fieldid']]), $table);
        }
    }

    public function test_定期ジョブは進めるジョブが無ければ何もしない(): void
    {
        $this->db->pquery('UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . " SET status = 'cancelled' WHERE status IN ('queued','running','paused')", []);
        $this->assertNull(Vtiger_AutoNumberBackfill::tick());
    }

    public function test_定期ジョブは待機中のジョブを進める(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'TK-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('tk');
        $this->setCreated($id, 1);
        $this->setConfig(['active' => 1]);
        $this->db->pquery('UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . " SET status = 'cancelled' WHERE status IN ('queued','running','paused')", []);
        $jobId = Vtiger_AutoNumberBackfill::start(self::$fields['main']['fieldid'], self::ADMIN_USER_ID);

        $job = Vtiger_AutoNumberBackfill::tick();

        $this->assertNotNull($job);
        $this->assertSame($jobId, $job['jobid']);
        $this->assertSame('TK-001', $this->storedValue($id));
    }

    // ---- 画面（テンプレート）・表示 -----------------------------------------

    public function test_編集画面は入力欄にせず送信もしない(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fields['main']['fieldname'], Vtiger_Module_Model::getInstance('Accounts'));
        $fieldModel->set('fieldvalue', '');
        $html = $this->renderTemplate($fieldModel->getUITypeModel()->getTemplateName(), ['FIELD_MODEL' => $fieldModel, 'MODULE' => 'Accounts', 'REQ' => []]);
        $this->assertStringContainsString('disabled="disabled"', $html);
        $this->assertStringNotContainsString(' name="', $html, '値を送信しない');
        $this->assertStringContainsString(vtranslate('LBL_AUTONUMBER_PLACEHOLDER', 'Vtiger'), $html);

        $fieldModel->set('fieldvalue', 'SPEC-202610-0001');
        $html = $this->renderTemplate($fieldModel->getUITypeModel()->getTemplateName(), ['FIELD_MODEL' => $fieldModel, 'MODULE' => 'Accounts', 'REQ' => []]);
        $this->assertStringContainsString('SPEC-202610-0001', $html);
        $html = $this->renderTemplate($fieldModel->getUITypeModel()->getTemplateName(), ['FIELD_MODEL' => $fieldModel, 'MODULE' => 'Accounts', 'REQ' => ['isDuplicate' => true]]);
        $this->assertStringNotContainsString('SPEC-202610-0001', $html, '複製では値を出さない');
    }

    public function test_表示は文字のままエスケープする(): void
    {
        $fieldModel = Vtiger_Field_Model::getInstance(self::$fields['main']['fieldname'], Vtiger_Module_Model::getInstance('Accounts'));
        $uiType = $fieldModel->getUITypeModel();
        $this->assertSame('SPEC-1', $uiType->getDisplayValue('SPEC-1'));
        $this->assertSame('', $uiType->getDisplayValue(''));
        $this->assertStringNotContainsString('<script>', $uiType->getDisplayValue('<script>alert(1)</script>'));
        $this->assertSame('http://x', $uiType->getDisplayValue('http://x'), 'URL を自動でリンクにしない');
    }

    public function test_項目の編集画面には設定欄を出さず連番管理への案内を出す(): void
    {
        $html = $this->renderFieldEdit(self::$fields['main']['fieldid']);

        $this->assertStringNotContainsString('name="autonumber_format"', $html);
        $this->assertStringNotContainsString('data-test-id="autonumber-backfill"', $html);
        $this->assertStringContainsString('data-test-id="autonumber-settings-link"', $html);
        $this->assertStringContainsString('view=CustomRecordNumbering&sourceModule=Accounts&field=' . self::$fields['main']['fieldname'], $html);
        $this->assertStringContainsString('data-autonumbersupported="1"', $html, '項目タイプの選択肢で案内を出す印がある');
    }

    public function test_日付項目の編集画面には自動採番の案内を出さない(): void
    {
        $html = $this->renderFieldEdit(self::$fields['date']['fieldid']);

        $this->assertStringNotContainsString('data-test-id="autonumber-settings-link"', $html);
        $this->assertStringNotContainsString('autonumber-backfill', $html);
    }

    // ---- 連番管理の画面 ---------------------------------------------------------

    public function test_連番管理では自動採番項目を持つモジュールを選べる(): void
    {
        $names = array_map(static fn ($model) => $model->getName(), \Settings_Vtiger_CustomRecordNumberingModule_Model::getSupportedModules());
        $this->assertContains('Accounts', $names);
        $configs = \Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance('Accounts')->getFieldConfigs();
        $this->assertArrayHasKey(self::$fields['main']['fieldname'], $configs);
        $this->assertSame('SPEC-{YYYY}{MM}-{0000}', $configs[self::$fields['main']['fieldname']]['format']);
    }

    public function test_連番管理の画面に設定と遡及採番の欄を出す(): void
    {
        $html = $this->renderNumberingScreen();

        $this->assertStringContainsString('name="format"', $html);
        $this->assertStringContainsString('value="SPEC-{YYYY}{MM}-{0000}"', $html);
        $this->assertMatchesRegularExpression('/<option value="month" selected="selected">/', $html, '保存済みのリセットを選んでいる');
        $this->assertStringContainsString('name="base_date"', $html);
        $this->assertStringContainsString(self::$fields['date']['fieldname'], $html, '基準日に日付項目を選べる');
        $this->assertStringContainsString('data-test-id="autonumber-backfill"', $html);
        $this->assertStringContainsString('data-field="' . self::$fields['main']['fieldname'] . '"', $html);
        $this->assertStringContainsString('data-module="Accounts"', $html);
    }

    public function test_連番管理の画面のJSは更新日時付きで読み込む(): void
    {
        require_once 'includes/runtime/JavaScript.php';
        $view = new \Settings_Vtiger_CustomRecordNumbering_View();
        $request = new \Vtiger_Request(['module' => 'Vtiger', 'parent' => 'Settings', 'view' => 'CustomRecordNumbering']);
        $srcs = [];
        foreach ($view->getHeaderScripts($request) as $script) {
            $srcs[] = (string) $script->getSrc();
        }
        $matched = array_values(array_filter($srcs, static fn (string $src): bool => str_contains($src, 'CustomRecordNumbering.js')));
        $this->assertCount(1, $matched, 'JS が読み込まれる: ' . implode(', ', $srcs));
        $this->assertMatchesRegularExpression('/CustomRecordNumbering\.js\?t=\d+$/', $matched[0]);
    }

    public function test_連番管理から保存した設定で採番される(): void
    {
        $this->assertSame([], $this->applyConfig(self::$fields['main']['fieldname'], [
            'autonumber_format' => 'NEW-{0}', 'autonumber_reset_type' => 'none', 'autonumber_start_number' => '7',
        ]));
        $this->db->pquery('DELETE FROM ' . Vtiger_AutoNumber::COUNTER_TABLE . ' WHERE fieldid = ?', [self::$fields['main']['fieldid']]);
        $id = $this->saveAccount('screen');
        $this->assertSame('NEW-7', $this->storedValue($id));
        $this->assertSame('NEW-8', $this->storedValue($this->saveAccount('screen2')));
    }

    // ---- 一括保存（インポートなど） --------------------------------------------------

    public function test_保存前のイベントが動かない一括保存でも番号が付く(): void
    {
        $previous = $GLOBALS['VTIGER_BULK_SAVE_MODE'] ?? null;
        $GLOBALS['VTIGER_BULK_SAVE_MODE'] = true;
        try {
            $id = $this->saveAccount('bulk');
            $id2 = $this->saveAccount('bulk2');
        } finally {
            $GLOBALS['VTIGER_BULK_SAVE_MODE'] = $previous;
        }
        $month = Vtiger_AutoNumber::now()->format('Ym');
        $this->assertSame('SPEC-' . $month . '-0001', $this->storedValue($id));
        $this->assertSame('SPEC-' . $month . '-0002', $this->storedValue($id2), '1 件に 1 つだけ払い出す');
    }

    // ---- API -----------------------------------------------------------------

    public function test_APIは管理者だけが使える(): void
    {
        $this->loginAs(7);
        try {
            $api = new \Vtiger_AutoNumber_Api();
            $api->checkPermission($this->request(['module' => 'Accounts', 'api' => 'AutoNumber', 'mode' => 'status', 'field' => self::$fields['main']['fieldname']]));
            $this->fail('一般ユーザーは使えない');
        } catch (\ApiForbiddenException $e) {
            $this->assertNotSame('', $e->getMessage());
        } finally {
            $this->loginAs(self::ADMIN_USER_ID);
        }
    }

    public function test_APIでプレビューから開始と状態の取得と中止ができる(): void
    {
        $this->setConfig(['active' => 0, 'format' => 'AP-{000}', 'reset_type' => 'none']);
        $id = $this->saveAccount('api');
        $this->setCreated($id, 1);
        $this->setConfig(['active' => 1]);
        $params = ['module' => 'Accounts', 'api' => 'AutoNumber', 'field' => self::$fields['main']['fieldname']];

        $preview = $this->callAutoNumberApi($params + ['mode' => 'preview']);
        $this->assertSame(1, $preview['total']);
        $this->assertSame('AP-001', $preview['first']);

        $this->assertNull($this->callAutoNumberApi($params + ['mode' => 'status'])['job'] ?? null, '開始前はジョブが無い');

        $started = $this->callAutoNumberApi($params + ['mode' => 'start']);
        $this->assertIsArray($started['job']);
        $this->assertSame('queued', $started['job']['status']);
        $this->assertTrue($started['job']['unfinished']);

        $kicked = $this->callAutoNumberApi($params + ['mode' => 'kick']);
        $this->assertIsArray($kicked['job']);
        $this->assertSame('done', $kicked['job']['status']);
        $this->assertSame(1, $kicked['job']['processed']);
        $this->assertSame('AP-001', $this->storedValue($id));
        $this->assertFalse($kicked['job']['unfinished']);

        $second = $this->callAutoNumberApi($params + ['mode' => 'start']);
        $this->assertIsArray($second['job']);
        $cancelled = $this->callAutoNumberApi($params + ['mode' => 'cancel']);
        $this->assertIsArray($cancelled['job']);
        $this->assertSame('cancelled', $cancelled['job']['status']);
    }

    public function test_APIは不正な日付と項目名を断る(): void
    {
        $params = ['module' => 'Accounts', 'api' => 'AutoNumber', 'field' => self::$fields['main']['fieldname']];
        try {
            $this->callAutoNumberApi($params + ['mode' => 'preview', 'created_from' => '2026/10/01']);
            $this->fail('不正な日付は受けない');
        } catch (\ApiBadRequestException $e) {
            $this->assertStringContainsString('Invalid date', $e->getMessage());
        }
        try {
            $this->callAutoNumberApi(['field' => 'accountname', 'mode' => 'status'] + $params);
            $this->fail('自動採番でない項目は扱わない');
        } catch (\ApiNotFoundException $e) {
            $this->assertNotSame('', $e->getMessage());
        }
    }

    // ---- Webservice ----------------------------------------------------------

    public function test_Webservice経由の作成でも番号が付き送った値は無視する(): void
    {
        require_once 'include/Webservices/Create.php';
        require_once 'include/Webservices/Revise.php';
        $entityUser = \CRMEntity::getInstance('Users');
        $entityUser->retrieveCurrentUserInfoFromFile(self::ADMIN_USER_ID);
        $created = vtws_create('Accounts', [
            'accountname' => self::PREFIX . 'ws',
            'assigned_user_id' => vtws_getWebserviceEntityId('Users', self::ADMIN_USER_ID),
            self::$fields['main']['fieldname'] => 'HACK-WS',
        ], $entityUser);
        $recordId = (int) explode('x', Vtiger_AutoNumberValue::string($created['id']))[1];
        $this->assertStringStartsWith('SPEC-', $this->storedValue($recordId));
        $stored = $this->storedValue($recordId);

        vtws_revise([
            'id' => $created['id'],
            self::$fields['main']['fieldname'] => 'HACK-WS2',
            'website' => 'http://example.com',
        ], $entityUser);
        $this->assertSame($stored, $this->storedValue($recordId), '更新で送った値では書き換わらない');
    }

    // ---- 補助 -------------------------------------------------------------

    private function renderNumberingScreen(): string
    {
        $model = \Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance('Accounts');
        $configs = $model->getFieldConfigs();
        $fieldName = self::$fields['main']['fieldname'];
        $viewer = new \Vtiger_Viewer();
        $viewer->assign('SUPPORTED_MODULES', \Settings_Vtiger_CustomRecordNumberingModule_Model::getSupportedModules());
        $viewer->assign('DEFAULT_MODULE_MODEL', $model);
        $viewer->assign('FIELD_CONFIGS', $configs);
        $viewer->assign('SELECTED_FIELD_NAME', $fieldName);
        $viewer->assign('SELECTED_CONFIG', $configs[$fieldName]);
        $viewer->assign('DATE_FIELDS', $model->getDateFields());
        $viewer->assign('QUALIFIED_MODULE', 'Settings:Vtiger');
        $viewer->assign('CURRENT_USER_MODEL', \Users_Record_Model::getCurrentUserModel());
        return (string) $viewer->view('CustomRecordNumbering.tpl', 'Settings:Vtiger', true);
    }

    private function renderFieldEdit(int $fieldId): string
    {
        $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName('Accounts');
        $viewer = new \Vtiger_Viewer();
        $viewer->assign('FIELD_INFO', $fieldModel->getFieldInfo());
        $viewer->assign('SELECTED_MODULE_NAME', 'Accounts');
        $viewer->assign('ADD_SUPPORTED_FIELD_TYPES', $moduleModel->getAddSupportedFieldTypes());
        $viewer->assign('FIELD_TYPE_INFO', $moduleModel->getAddFieldTypeInfo());
        $viewer->assign('FIELD_MODEL', $fieldModel);
        $viewer->assign('IS_FIELD_EDIT_MODE', true);
        $viewer->assign('QUALIFIED_MODULE', 'Settings:LayoutEditor');
        $viewer->assign('USER_MODEL', \Users_Record_Model::getCurrentUserModel());
        $viewer->assign('HEADER_FIELDS_COUNT', 0);
        $viewer->assign('IS_NAME_FIELD', false);
        return (string) $viewer->view('FieldCreate.tpl', 'Settings:LayoutEditor', true);
    }

    /**
     * 先頭の列の値を文字で返す（結果が無ければ空文字）
     *
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

    /**
     * @return array<string, mixed>
     */
    private function config(int $fieldId): array
    {
        $config = Vtiger_AutoNumber::getConfig($fieldId);
        $this->assertNotNull($config);
        return $config;
    }

    /**
     * @return array{jobid: int, fieldid: int, status: string, created_from: string|null, created_to: string|null, total: int, processed: int, failed: int, failed_ids: list<int>, last_number: string, lock_until: string|null, userid: int, created_at: string, started_at: string|null, finished_at: string|null}
     */
    private function job(int $jobId): array
    {
        $job = Vtiger_AutoNumberBackfill::getJob($jobId);
        $this->assertNotNull($job);
        return $job;
    }

    /**
     * @return array{jobid: int, fieldid: int, status: string, created_from: string|null, created_to: string|null, total: int, processed: int, failed: int, failed_ids: list<int>, last_number: string, lock_until: string|null, userid: int, created_at: string, started_at: string|null, finished_at: string|null}
     */
    private function batch(int $jobId): array
    {
        $job = Vtiger_AutoNumberBackfill::processBatch($jobId);
        $this->assertNotNull($job);
        return $job;
    }

    private function latestJobId(int $fieldId): int
    {
        $job = Vtiger_AutoNumberBackfill::getLatestJob($fieldId);
        $this->assertNotNull($job);
        return $job['jobid'];
    }

    /**
     * 自動採番 API を呼び、応答（sendSuccess に渡した配列）を返す
     *
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    private function callAutoNumberApi(array $params): array
    {
        $api = new \Vtiger_AutoNumber_Api();
        $request = $this->request($params);
        $api->checkPermission($request);
        $method = new \ReflectionMethod($api, 'processApi');
        $response = $method->invoke($api, $request);
        $this->assertIsObject($response);
        return $this->responseResult($response);
    }

    private function requireMigrated(): void
    {
        $result = $this->db->pquery("SHOW TABLES LIKE 'vtiger_autonumber_config'", []);
        if ($result === false || $this->db->num_rows($result) === 0) {
            self::markTestSkipped('テスト用DBに自動採番のマイグレーションが適用されていない');
        }
        $result = $this->db->pquery("SELECT 1 FROM vtiger_eventhandlers WHERE handler_class = 'AutoNumberHandler'", []);
        if ($this->db->num_rows($result) === 0) {
            self::markTestSkipped('テスト用DBに AutoNumberHandler が登録されていない');
        }
    }

    /**
     * @param array<string, string> $params
     * @return array{fieldid: int, fieldname: string}
     */
    private function addAutoNumberField(string $label, array $params): array
    {
        $field = $this->addField('AutoNumber', $label, []);
        $errors = $this->applyConfig($field['fieldname'], $params + ['autonumber_format' => '{0000}', 'autonumber_reset_type' => 'none', 'autonumber_start_number' => '1']);
        if (!empty($errors)) {
            throw new \Exception(\Settings_Vtiger_CustomRecordNumberingModule_Model::getErrorMessage($errors));
        }
        return $field;
    }

    /**
     * 連番管理の画面と同じ経路で設定を保存する
     *
     * @param array<string, string> $params autonumber_ で始まるキー
     * @return list<string> エラーのキー
     */
    private function applyConfig(string $fieldName, array $params): array
    {
        $input = [];
        foreach ($params as $key => $value) {
            $input[preg_replace('/\Aautonumber_/', '', $key)] = $value;
        }
        $model = \Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance('Accounts');
        $config = \Settings_Vtiger_CustomRecordNumberingModule_Model::getConfigFromParams($input);
        return $model->saveFieldConfig($fieldName, $config);
    }

    /**
     * @return array{fieldid: int, fieldname: string}
     */
    private function addPlainField(string $type, string $label): array
    {
        return $this->addField($type, $label, []);
    }

    /**
     * @param array<string, string> $params
     * @return array{fieldid: int, fieldname: string}
     */
    private function addField(string $type, string $label, array $params): array
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName('Accounts');
        $result = $this->db->pquery('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [getTabid('Accounts')]);
        $blockId = (int) $this->db->query_result($result, 0, 'blockid');
        $fieldModel = $moduleModel->addField($type, $blockId, $params + [
            'fieldType' => $type, 'fieldLabel' => $label, 'fieldLength' => '50', 'mandatory' => 'O', 'quickcreate' => 1,
            'masseditable' => 2, 'summaryfield' => 0, 'headerfield' => 0, 'fieldDefaultValue' => '',
        ]);
        return ['fieldid' => (int) $fieldModel->getId(), 'fieldname' => $fieldModel->getName()];
    }

    /**
     * テスト用DBにもともとある顧客企業は、遡及採番の対象から外す（値が空のままだと対象に入るため）
     */
    private function markExistingAccounts(): void
    {
        $this->db->pquery(
            'UPDATE vtiger_accountscf SET ' . self::$fields['main']['fieldname'] . " = 'PRE' WHERE accountid NOT IN
                (SELECT accountid FROM vtiger_account WHERE accountname LIKE ?) AND (" . self::$fields['main']['fieldname'] . " IS NULL OR " . self::$fields['main']['fieldname'] . " = '')",
            [self::PREFIX . '%']
        );
    }

    private function resetMain(): void
    {
        $this->setConfig(['format' => 'SPEC-{YYYY}{MM}-{0000}', 'reset_type' => 'month', 'start_number' => 1, 'base_date' => '', 'effective_from' => null, 'active' => 1]);
        $this->db->pquery('DELETE FROM ' . Vtiger_AutoNumber::COUNTER_TABLE . ' WHERE fieldid = ?', [self::$fields['main']['fieldid']]);
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function setConfig(array $changes): void
    {
        $config = array_merge(Vtiger_AutoNumber::getConfig(self::$fields['main']['fieldid']) ?? [], $changes);
        Vtiger_AutoNumber::saveConfig(self::$fields['main']['fieldid'], $config);
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

    private function storedValue(int $accountId, ?string $fieldName = null): string
    {
        $fieldName = $fieldName ?? self::$fields['main']['fieldname'];
        $result = $this->db->pquery("SELECT {$fieldName} AS v FROM vtiger_accountscf WHERE accountid = ?", [$accountId]);
        return Vtiger_AutoNumberValue::string($this->db->query_result($result, 0, 'v'));
    }

    /** レコードの作成・更新日時を、指定した日数前の時刻にする（静止期間を過ぎた状態にする） */
    private function setCreated(int $recordId, int $daysAgo): void
    {
        $time = gmdate('Y-m-d H:i:s', time() - $daysAgo * 86400);
        $this->db->pquery('UPDATE vtiger_crmentity SET createdtime = ?, modifiedtime = ? WHERE crmid = ?', [$time, $time, $recordId]);
    }

    private function runJobToEnd(int $jobId): void
    {
        for ($i = 0; $i < 20; $i++) {
            $job = $this->batch($jobId);
            if (!in_array($job['status'], [Vtiger_AutoNumberBackfill::STATUS_QUEUED, Vtiger_AutoNumberBackfill::STATUS_RUNNING], true)) {
                return;
            }
        }
        $this->fail('ジョブが終わらない');
    }


    /** @return array<string, mixed> */
    private function fieldRow(int $fieldId): array
    {
        $result = $this->db->pquery('SELECT * FROM vtiger_field WHERE fieldid = ?', [$fieldId]);
        return $this->db->fetchByAssoc($result, -1, false);
    }

    private function columnType(string $table, string $column): string
    {
        $result = $this->db->pquery(
            'SELECT DATA_TYPE AS data_type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        return (string) $this->db->query_result($result, 0, 'data_type');
    }

    /**
     * @param array<string, mixed> $values
     */
    private function renderTemplate(string $template, array $values): string
    {
        $viewer = new \Vtiger_Viewer();
        foreach ($values as $name => $value) {
            $viewer->assign($name, $value);
        }
        return (string) $viewer->view($template, 'Accounts', true);
    }

    /** 途中で中断したテストが残した、このテスト用の項目を消す */
    private function deleteLeftoverFields(): void
    {
        $result = $this->db->pquery('SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldlabel LIKE ?', [getTabid('Accounts'), 'SPECTEST\\_採番%']);
        $ids = [];
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $ids[] = (int) $this->db->query_result($result, $i, 'fieldid');
        }
        foreach ($ids as $id) {
            $this->db->pquery('DELETE FROM vtiger_autonumber_job WHERE fieldid = ?', [$id]);
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
        // 削除済みにして残したレコードも片付ける
        $this->db->pquery("DELETE FROM vtiger_crmentity WHERE setype = 'Accounts' AND label LIKE ?", [self::PREFIX . '%']);
    }
}
