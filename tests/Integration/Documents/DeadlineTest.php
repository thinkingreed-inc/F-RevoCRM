<?php

namespace Tests\Integration\Documents;

use Documents_DeadlineCalculator;
use FR_BusinessDay;
use InvalidArgumentException;
use Tests\Support\DocumentsTestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';
require_once dirname(__DIR__, 3) . '/modules/Documents/utils/DeadlineCalculator.php';

/**
 * スキャナ保存の入力期限
 *
 * 対応する仕様書: docs/tests/Documents/TS-03_入力期限.md
 *   4.1 設定の既定値（BV-5 / BV-6）
 *   4.2 期限の計算（DT-1 / BV-1〜BV-3）
 *   4.3 期限状態（DT-2 / BV-4）
 *   4.4 レコードへの反映（DT-3）
 *   4.5 一括更新（DT-4）
 *   4.6 既存ドキュメントの一括再計算
 */
final class DeadlineTest extends DocumentsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->applyDefaultSettings();
    }

    /** 期限の設定を既定値に戻す */
    private function applyDefaultSettings(): void
    {
        $this->setDocumentsSetting('input_deadline_policy', 'prompt');
        $this->setDocumentsSetting('input_deadline_business_days', '7');
        $this->setDocumentsSetting('input_deadline_cycle_months', '2');
        $this->setDocumentsSetting('input_deadline_warning_days', '3');
    }

    // ---- 4.1 設定の既定値 -----------------------------------------------

    public function test_TC_DL_001_設定が無ければ既定値を使う(): void
    {
        $this->db->pquery("DELETE FROM vtiger_documents_settings WHERE name LIKE 'input_deadline%'", []);
        Documents_DeadlineCalculator::clearCache();

        $settings = Documents_DeadlineCalculator::getSettings();
        $this->assertSame('prompt', $settings['input_deadline_policy'], 'TC-DL-001 方針');
        $this->assertSame(7, $settings['input_deadline_business_days'], 'TC-DL-001 営業日数');
        $this->assertSame(2, $settings['input_deadline_cycle_months'], 'TC-DL-001 サイクル月数');
        $this->assertSame(3, $settings['input_deadline_warning_days'], 'TC-DL-001 警告日数');
    }

    /**
     * 不正な設定値と、そのとき使われる営業日数
     *
     * @return array<string,array{0:string,1:int}>
     */
    public static function 営業日数の設定値(): array
    {
        return [
            'TC-DL-002 0 は既定値へフォールバック' => ['0', 7],
            'TC-DL-003 非数値は既定値へフォールバック' => ['abc', 7],
            'TC-DL-004 1 は下限として有効' => ['1', 1],
        ];
    }

    /**
     * @dataProvider 営業日数の設定値
     */
    public function test_設定値が不正なら既定値にフォールバックする(string $value, int $expected): void
    {
        $this->setDocumentsSetting('input_deadline_business_days', $value);

        $this->assertSame($expected, Documents_DeadlineCalculator::getBusinessDays());
    }

    public function test_TC_DL_005_未知の方針はpromptとして扱う(): void
    {
        $this->setDocumentsSetting('input_deadline_policy', 'unknown');

        $this->assertSame('prompt', Documents_DeadlineCalculator::getPolicy(), 'TC-DL-005');
    }

    public function test_TC_DL_007_保存後はキャッシュが破棄される(): void
    {
        Documents_DeadlineCalculator::saveSettings(['input_deadline_business_days' => 5]);

        $this->assertSame(5, Documents_DeadlineCalculator::getBusinessDays(), 'TC-DL-007');
    }

    public function test_TC_DL_008_許可外の設定名は保存しない(): void
    {
        Documents_DeadlineCalculator::saveSettings(['unknown_setting' => 'x']);

        $result = $this->db->pquery(
            'SELECT name FROM vtiger_documents_settings WHERE name = ?',
            ['unknown_setting']
        );
        $this->assertSame(0, $this->db->num_rows($result), 'TC-DL-008');
    }

    // ---- 4.2 期限の計算 -------------------------------------------------

    /**
     * 受領日と期限
     *
     * @return array<string,array{0:string,1:string|null,2:string}>
     */
    public static function 期限の計算(): array
    {
        return [
            'TC-DL-010 7営業日後' => ['2035-01-01', null, '2035-01-10'],
            'TC-DL-011 cycle は+2か月後を起算日にする' => ['2035-01-01', 'cycle', '2035-03-12'],
            'TC-DL-012 金曜受領は週休を2回跨ぐ' => ['2035-01-05', null, '2035-01-16'],
            'TC-DL-013 土曜受領でも翌営業日から数える' => ['2035-01-06', null, '2035-01-16'],
        ];
    }

    /**
     * @dataProvider 期限の計算
     */
    public function test_受領日から入力期限を計算する(
        string $receiptDate,
        ?string $policy,
        string $expected
    ): void {
        $this->assertSame($expected, Documents_DeadlineCalculator::calculate($receiptDate, $policy));
    }

    public function test_TC_DL_014_マスタの休日の分だけ後ろにずれる(): void
    {
        $this->addHoliday('2035-01-02', 'holiday');

        $this->assertSame('2035-01-11', Documents_DeadlineCalculator::calculate('2035-01-01'), 'TC-DL-014');
    }

    public function test_TC_DL_015_週休が日曜のみなら土曜も営業日として数える(): void
    {
        $this->setWeeklyHolidays([0]);

        $this->assertSame('2035-01-09', Documents_DeadlineCalculator::calculate('2035-01-01'), 'TC-DL-015');
    }

    public function test_TC_DL_016_月末をまたぐ起算日は月末に丸める(): void
    {
        $this->setDocumentsSetting('input_deadline_cycle_months', '1');

        // 1/31 + 1か月 は 2/28（3/3 にあふれさせない）
        $this->assertSame(
            FR_BusinessDay::addBusinessDays('2035-02-28', 7),
            Documents_DeadlineCalculator::calculate('2035-01-31', 'cycle'),
            'TC-DL-016'
        );
    }

    public function test_TC_DL_017_閏年の月末に丸める(): void
    {
        // 2035-12-31 + 2か月 は閏年の 2036-02-29
        $this->assertSame(
            FR_BusinessDay::addBusinessDays('2036-02-29', 7),
            Documents_DeadlineCalculator::calculate('2035-12-31', 'cycle'),
            'TC-DL-017'
        );
    }

    public function test_TC_DL_018_019_営業日数の設定が計算に反映される(): void
    {
        $this->setDocumentsSetting('input_deadline_business_days', '1');
        $this->assertSame(
            '2035-01-02',
            Documents_DeadlineCalculator::calculate('2035-01-01'),
            'TC-DL-018 1営業日設定'
        );

        $this->setDocumentsSetting('input_deadline_business_days', '60');
        $this->assertIsString(
            Documents_DeadlineCalculator::calculate('2035-01-01'),
            'TC-DL-019 60営業日設定でも日付が返る'
        );
    }

    /**
     * 未入力として扱う受領日
     *
     * @return array<string,array{0:string|null}>
     */
    public static function 未入力の受領日(): array
    {
        return [
            '空文字' => [''],
            '0000-00-00' => ['0000-00-00'],
            'null' => [null],
        ];
    }

    /**
     * @dataProvider 未入力の受領日
     */
    public function test_TC_DL_020_未入力の受領日はnullを返す(?string $receiptDate): void
    {
        $this->assertNull(Documents_DeadlineCalculator::calculate($receiptDate), 'TC-DL-020');
    }

    /**
     * @return array<string,array{0:string}>
     */
    public static function 不正な受領日(): array
    {
        return [
            'abc' => ['abc'],
            '2026-02-30（繰り上げない）' => ['2026-02-30'],
        ];
    }

    /**
     * @dataProvider 不正な受領日
     */
    public function test_TC_DL_021_不正な受領日は例外にする(string $receiptDate): void
    {
        $this->expectException(InvalidArgumentException::class);
        Documents_DeadlineCalculator::calculate($receiptDate);
    }

    public function test_TC_DL_022_全曜日が週休ならnullを返す(): void
    {
        $this->setWeeklyHolidays([0, 1, 2, 3, 4, 5, 6]);

        // 計算できないだけなので例外にはしない
        $this->assertNull(Documents_DeadlineCalculator::calculate('2035-01-01'), 'TC-DL-022');
    }

    // ---- 4.3 期限状態 ---------------------------------------------------

    public function test_TC_DL_030_未入力の期限はnullを返す(): void
    {
        $this->assertNull(Documents_DeadlineCalculator::calculateStatus(''), 'TC-DL-030 空文字');
        $this->assertNull(Documents_DeadlineCalculator::calculateStatus('0000-00-00'), 'TC-DL-030');
    }

    public function test_TC_DL_030b_不正な期限は例外にする(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Documents_DeadlineCalculator::calculateStatus('abc');
    }

    /**
     * 期限日・基準日と期限状態
     *
     * @return array<string,array{0:string,1:string,2:string}>
     */
    public static function 期限状態(): array
    {
        return [
            'TC-DL-031 期限 < 基準日 は overdue' => ['2035-01-07', '2035-01-08', 'overdue'],
            'TC-DL-032 残り3営業日は warning（境界）' => ['2035-01-10', '2035-01-08', 'warning'],
            'TC-DL-033 残り4営業日は within（境界）' => ['2035-01-11', '2035-01-08', 'within'],
            'TC-DL-034 当日は warning' => ['2035-01-08', '2035-01-08', 'warning'],
            'TC-DL-035 期限日が休日でも当日は overdue にしない' => ['2035-01-06', '2035-01-06', 'warning'],
            'TC-DL-035b 期限日の翌日は overdue' => ['2035-01-06', '2035-01-07', 'overdue'],
        ];
    }

    /**
     * @dataProvider 期限状態
     */
    public function test_期限状態を判定する(string $deadline, string $today, string $expected): void
    {
        $this->assertSame($expected, Documents_DeadlineCalculator::calculateStatus($deadline, $today));
    }

    public function test_TC_DL_036_037_警告日数の設定が判定に反映される(): void
    {
        $this->setDocumentsSetting('input_deadline_warning_days', '1');
        $this->assertSame(
            'within',
            Documents_DeadlineCalculator::calculateStatus('2035-01-09', '2035-01-08'),
            'TC-DL-036 警告1営業日なら残り2営業日は within'
        );

        $this->setDocumentsSetting('input_deadline_warning_days', '60');
        $this->assertSame(
            'warning',
            Documents_DeadlineCalculator::calculateStatus('2035-01-31', '2035-01-08'),
            'TC-DL-037 警告60営業日ならほぼ warning'
        );
    }

    // ---- 4.4 レコードへの反映 -------------------------------------------

    /**
     * @return array<string,array{0:int}>
     */
    public static function 対象にならないID(): array
    {
        return [
            'TC-DL-040 notesId=0' => [0],
            'TC-DL-041 存在しないID' => [99999999],
        ];
    }

    /**
     * @dataProvider 対象にならないID
     */
    public function test_存在しないIDの再計算は空を返す(int $notesId): void
    {
        $this->assertSame(
            ['input_deadline' => null, 'input_deadline_status' => null],
            Documents_DeadlineCalculator::recalculate($notesId)
        );
    }

    public function test_TC_DL_042_043_スキャナ保存と受領日から期限を入れる(): void
    {
        $docId = $this->createScannerDocument('DL_SCANNER', '2035-01-01');

        $result = Documents_DeadlineCalculator::recalculate($docId);
        $this->assertSame('2035-01-10', $result['input_deadline'], 'TC-DL-042 期限が入る');
        $this->assertNotNull($result['input_deadline_status'], 'TC-DL-042 状態も設定される');
        $this->assertSame(
            '2035-01-10',
            $this->notesColumn($docId, 'input_deadline'),
            'TC-DL-042 DBにも反映される'
        );

        $this->assertSame(
            $result,
            Documents_DeadlineCalculator::recalculate($docId),
            'TC-DL-043 再実行しても同じ値（冪等）'
        );
    }

    public function test_TC_DL_044_対象外になったら期限を消す(): void
    {
        $docId = $this->createScannerDocument('DL_SCANNER', '2035-01-01');
        Documents_DeadlineCalculator::recalculate($docId);

        $this->updateNotes($docId, ['preservation_type' => 'electronic_transaction']);
        $result = Documents_DeadlineCalculator::recalculate($docId);

        $this->assertNull($result['input_deadline'], 'TC-DL-044 期限を消す');
        $this->assertNull($result['input_deadline_status'], 'TC-DL-044 状態も消す');
    }

    public function test_TC_DL_045_受領日が無くなったら期限を消す(): void
    {
        $docId = $this->createScannerDocument('DL_SCANNER', '2035-01-01');
        Documents_DeadlineCalculator::recalculate($docId);

        $this->updateNotes($docId, ['receipt_date' => null]);

        $this->assertNull(
            Documents_DeadlineCalculator::recalculate($docId)['input_deadline'],
            'TC-DL-045'
        );
    }

    public function test_TC_DL_063c_不正な受領日でも例外にしない(): void
    {
        $docId = $this->createScannerDocument('DL_SCANNER', '2035-01-01');
        $this->updateNotes($docId, ['receipt_date' => '9999-99-99']);

        // 1件の不正データで一括処理が止まらないことの確認
        $result = Documents_DeadlineCalculator::recalculate($docId);

        $this->assertNull($result['input_deadline'], 'TC-DL-063c 期限は空になる');
    }

    // ---- 4.5 一括更新 ---------------------------------------------------

    public function test_TC_DL_060_061_期限を過ぎたらoverdueに更新する(): void
    {
        $docId = $this->createScannerDocument('DL_BATCH', '2035-01-01');
        Documents_DeadlineCalculator::recalculate($docId);

        $result = Documents_DeadlineCalculator::updateStatuses('2035-01-20');
        $this->assertGreaterThanOrEqual(1, $result['updated'], 'TC-DL-060 更新される');
        $this->assertSame(
            'overdue',
            $this->notesColumn($docId, 'input_deadline_status'),
            'TC-DL-060 overdue になる'
        );

        $again = Documents_DeadlineCalculator::updateStatuses('2035-01-20');
        $this->assertSame(0, $again['updated'], 'TC-DL-061 同じ基準日での再実行は更新0件');
    }

    public function test_TC_DL_064_基準日に応じて状態が遷移する(): void
    {
        $docId = $this->createScannerDocument('DL_BATCH', '2035-01-01');
        Documents_DeadlineCalculator::recalculate($docId);

        Documents_DeadlineCalculator::updateStatuses('2035-01-02');
        $this->assertSame('within', $this->notesColumn($docId, 'input_deadline_status'), 'TC-DL-064 期限内');

        Documents_DeadlineCalculator::updateStatuses('2035-01-08');
        $this->assertSame('warning', $this->notesColumn($docId, 'input_deadline_status'), 'TC-DL-064 期限間近');

        Documents_DeadlineCalculator::updateStatuses('2035-01-20');
        $this->assertSame('overdue', $this->notesColumn($docId, 'input_deadline_status'), 'TC-DL-064 期限超過');
    }

    public function test_TC_DL_062_電子取引は一括更新の対象外(): void
    {
        $docId = $this->createDocument('DL_OUT');
        $this->updateNotes($docId, [
            'preservation_type' => 'electronic_transaction',
            'input_deadline' => '2035-01-10',
            'input_deadline_status' => 'within',
        ]);

        Documents_DeadlineCalculator::updateStatuses('2035-01-20');
        $this->assertSame(
            'within',
            $this->notesColumn($docId, 'input_deadline_status'),
            'TC-DL-062 更新されない'
        );

        // スキャナ保存に変えれば対象になる
        $this->updateNotes($docId, ['preservation_type' => 'scanner']);
        $after = Documents_DeadlineCalculator::updateStatuses('2035-01-20');

        $this->assertGreaterThanOrEqual(1, $after['updated'], 'TC-DL-062 対象になる');
        $this->assertSame(
            'overdue',
            $this->notesColumn($docId, 'input_deadline_status'),
            'TC-DL-062 overdue に更新される'
        );
    }

    public function test_TC_DL_063b_不正な期限が混ざっても一括更新が完走する(): void
    {
        $brokenId = $this->createDocument('DL_BROKEN');
        $this->updateNotes($brokenId, [
            'preservation_type' => 'scanner',
            'input_deadline' => '9999-99-99',
        ]);

        Documents_DeadlineCalculator::updateStatuses('2035-01-20');

        $status = $this->notesColumn($brokenId, 'input_deadline_status');
        $this->assertTrue(
            $status === null || $status === '',
            'TC-DL-063b 不正な期限は状態を設定しない: ' . var_export($status, true)
        );
    }

    public function test_TC_DL_068_洗い替えは日付が変わった最初の1回だけ(): void
    {
        Documents_DeadlineCalculator::clearStatusUpdatedOn();

        $first = Documents_DeadlineCalculator::updateStatusesIfDateChanged('2035-02-10');
        $this->assertFalse($first['skipped'], 'TC-DL-068 前回実行日が無ければ洗い替える');

        $second = Documents_DeadlineCalculator::updateStatusesIfDateChanged('2035-02-10');
        $this->assertTrue($second['skipped'], 'TC-DL-068 同じ日の再実行はとばす');

        $nextDay = Documents_DeadlineCalculator::updateStatusesIfDateChanged('2035-02-11');
        $this->assertFalse($nextDay['skipped'], 'TC-DL-068 日付が変われば洗い替える');
    }

    public function test_TC_DL_069_設定変更後は同じ日でも洗い替える(): void
    {
        Documents_DeadlineCalculator::clearStatusUpdatedOn();
        Documents_DeadlineCalculator::updateStatusesIfDateChanged('2035-02-11');

        Documents_DeadlineCalculator::saveSettings(['input_deadline_warning_days' => 5]);
        $after = Documents_DeadlineCalculator::updateStatusesIfDateChanged('2035-02-11');

        $this->assertFalse($after['skipped'], 'TC-DL-069');
    }

    // ---- 4.6 既存ドキュメントの一括再計算 -------------------------------

    public function test_TC_DL_070_072_方針を変えると既存の期限が再計算される(): void
    {
        $this->createScannerDocument('DL_RECALC', '2035-01-01');

        $result = Documents_DeadlineCalculator::recalculateAll();
        $this->assertGreaterThanOrEqual(1, $result['checked'], 'TC-DL-070 スキャナ保存＋受領日ありが対象');

        $this->setDocumentsSetting('input_deadline_policy', 'cycle');
        $changed = Documents_DeadlineCalculator::recalculateAll();
        $this->assertGreaterThanOrEqual(1, $changed['updated'], 'TC-DL-071 方針変更で期限が更新される');

        $again = Documents_DeadlineCalculator::recalculateAll();
        $this->assertSame(0, $again['updated'], 'TC-DL-072 再実行では更新0件（冪等）');
    }

    // ---- 保存経路からの自動計算（S-01 / S-14） --------------------------

    public function test_TC_DL_046_保存時に期限が自動設定される(): void
    {
        $docId = $this->createDocument('DL_SAVE', [
            'document_category' => 'invoice',
            'preservation_type' => 'scanner',
            'receipt_date' => '2035-01-01',
        ]);

        $this->assertSame('2035-01-10', $this->notesColumn($docId, 'input_deadline'), 'TC-DL-046');
    }

    public function test_TC_DL_048_期限が計算できなくても保存は成功する(): void
    {
        $this->setWeeklyHolidays([0, 1, 2, 3, 4, 5, 6]);

        $docId = $this->createDocument('DL_NOCALC', [
            'preservation_type' => 'scanner',
            'receipt_date' => '2035-01-01',
        ]);

        $this->assertGreaterThan(0, $docId, 'TC-DL-048 保存自体は成功する');
    }

    /**
     * スキャナ保存・受領日ありのドキュメントを作る
     */
    private function createScannerDocument(string $suffix, string $receiptDate): int
    {
        $docId = $this->createDocument($suffix);
        $this->updateNotes($docId, [
            'document_category' => 'invoice',
            'preservation_type' => 'scanner',
            'receipt_date' => $receiptDate,
        ]);

        return $docId;
    }
}
