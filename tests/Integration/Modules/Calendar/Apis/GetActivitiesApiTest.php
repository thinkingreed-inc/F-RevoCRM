<?php
/**
 * Calendar GetActivities API 統合テスト
 *
 * Calendar_GetActivities_Api クラスのテストです。
 * 実際のデータベースを使用してAPIの動作を検証します。
 */

declare(strict_types=1);

namespace Tests\Integration\Modules\Calendar\Apis;

use Tests\Support\FRIntegrationTestCase;
use Vtiger_Record_Model;
use Vtiger_Request;

class GetActivitiesApiTest extends FRIntegrationTestCase
{
    private \Calendar_GetActivities_Api $api;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // コア読み込み後にAPIクラスを読み込む
        require_once ROOT_DIR . '/includes/http/Request.php';
        require_once ROOT_DIR . '/includes/http/Response.php';
        require_once ROOT_DIR . '/includes/runtime/Controller.php';
        require_once ROOT_DIR . '/modules/Calendar/apis/GetActivities.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = new \Calendar_GetActivities_Api();
    }

    /**
     * テスト用のAccountレコードを作成
     */
    private function createTestAccount(): int
    {
        $recordModel = createTestRecord('Accounts', [
            'accountname' => $this->generateUniqueName('[TEST-API]'),
        ]);
        $recordId = (int)$recordModel->getId();
        self::$createdRecords['Accounts'][] = $recordId;
        return $recordId;
    }

    /**
     * テスト用のCalendar（Activity）レコードを作成
     * @param int $parentId 親レコードID（Accounts等）
     * @param string $parentModule 親モジュール名
     * @param array $overrides 上書きするフィールド
     * @return int ActivityのレコードID
     */
    private function createTestActivity(int $parentId, string $parentModule = 'Accounts', array $overrides = []): int
    {
        // Calendar_Record_Model::save()が$_REQUESTを使うため設定
        $_REQUEST['time_start'] = $overrides['time_start'] ?? '10:00';
        $_REQUEST['time_end'] = $overrides['time_end'] ?? '11:00';

        $data = array_merge([
            'subject' => $this->generateUniqueName('[TEST-ACTIVITY]'),
            'activitytype' => 'Task',
            'date_start' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+1 day')),
            'time_start' => '10:00',
            'time_end' => '11:00',
            'taskstatus' => 'Not Started',
            'taskpriority' => 'High',
            'visibility' => 'all',  // Required: NOT NULL column
            'sendnotification' => '0',
            'parent_id' => $parentId,
        ], $overrides);

        $recordModel = createTestRecord('Calendar', $data);
        $recordId = (int)$recordModel->getId();
        self::$createdRecords['Calendar'][] = $recordId;

        // $_REQUESTをクリア
        unset($_REQUEST['time_start'], $_REQUEST['time_end']);

        return $recordId;
    }

    /**
     * Vtiger_Request のモックを作成
     */
    private function createRequest(array $params): Vtiger_Request
    {
        return new Vtiger_Request($params, [], false);
    }

    // ============================================
    // 基本機能テスト
    // ============================================

    /**
     * loginRequired() がtrueを返すことをテスト
     */
    public function test_loginRequired_returns_true(): void
    {
        $this->assertTrue($this->api->loginRequired());
    }

    /**
     * requiresPermission() が正しい権限設定を返すことをテスト
     */
    public function test_requiresPermission_returns_correct_permissions(): void
    {
        $request = $this->createRequest([]);
        $permissions = $this->api->requiresPermission($request);

        $this->assertIsArray($permissions);
        $this->assertCount(1, $permissions);
        $this->assertEquals('parent_module', $permissions[0]['module_parameter']);
        $this->assertEquals('DetailView', $permissions[0]['action']);
        $this->assertEquals('parent_id', $permissions[0]['record_parameter']);
    }

    // ============================================
    // パラメータバリデーションテスト
    // ============================================

    /**
     * parent_module が空の場合エラーになること
     */
    public function test_processApi_error_when_parent_module_empty(): void
    {
        $request = $this->createRequest([
            'parent_module' => '',
            'parent_id' => '123',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('parent_module is required');

        $this->api->process($request);
    }

    /**
     * parent_module が不正な形式の場合エラーになること
     */
    public function test_processApi_error_when_parent_module_invalid_format(): void
    {
        $request = $this->createRequest([
            'parent_module' => 'Invalid@Module',
            'parent_id' => '123',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid parent_module format');

        $this->api->process($request);
    }

    /**
     * parent_id が空の場合エラーになること
     */
    public function test_processApi_error_when_parent_id_empty(): void
    {
        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => '',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('parent_id is required');

        $this->api->process($request);
    }

    /**
     * parent_id が数値でない場合エラーになること
     * 注: Vtiger_Request のバリデーションでBad Requestになる場合がある
     */
    public function test_processApi_error_when_parent_id_not_numeric(): void
    {
        $this->expectException(\Exception::class);

        // Vtiger_RequestがBad Requestを投げるか、APIがparent_id must be numericを投げる
        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => 'abc',
        ]);

        $this->api->process($request);
    }

    /**
     * mode が不正な場合エラーになること
     */
    public function test_processApi_error_when_mode_invalid(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
            'mode' => 'invalid_mode',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid mode');

        $this->api->process($request);
    }

    /**
     * 存在しないモジュールの場合エラーになること
     */
    public function test_processApi_error_when_module_not_found(): void
    {
        $request = $this->createRequest([
            'parent_module' => 'NonExistentModule',
            'parent_id' => '123',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Module 'NonExistentModule' not found");

        $this->api->process($request);
    }

    /**
     * 存在しないレコードの場合エラーになること
     */
    public function test_processApi_error_when_record_not_found(): void
    {
        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => '999999999',
        ]);

        $this->expectException(\Exception::class);

        $this->api->process($request);
    }

    // ============================================
    // 正常系テスト
    // ============================================

    /**
     * 有効なリクエストで成功レスポンスを返すこと
     */
    public function test_processApi_returns_success_response(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['activities']);
        $this->assertArrayHasKey('hasMore', $result);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['limit']);
    }

    // ============================================
    // Activity作成・取得テスト
    // ============================================

    /**
     * Account に紐づいた Activity を作成し、APIで取得できることをテスト
     * これが最も重要なテストケース：Account → Activity 作成 → API取得 → 検証
     */
    public function test_processApi_retrieves_activities_linked_to_account(): void
    {
        // 1. Account を作成
        $accountId = $this->createTestAccount();

        // 2. Account に紐づいた Activity を作成
        $activityId = $this->createTestActivity($accountId, 'Accounts', [
            'subject' => 'Test Activity for API',
            'activitytype' => 'Task',
            'taskstatus' => 'Not Started',
        ]);

        // 3. API でアクティビティを取得
        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        // 4. 検証
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['activities']);
        $this->assertGreaterThanOrEqual(1, count($result['activities']), 'At least one activity should be returned');

        // アクティビティの詳細を確認
        $foundActivity = null;
        foreach ($result['activities'] as $activity) {
            if ((int)$activity['id'] === $activityId) {
                $foundActivity = $activity;
                break;
            }
        }

        $this->assertNotNull($foundActivity, "Created activity (ID: {$activityId}) should be in the response");
        $this->assertEquals('Test Activity for API', $foundActivity['subject']);
        $this->assertEquals('Task', $foundActivity['activityType']);
        $this->assertEquals('Not Started', $foundActivity['status']);
    }

    /**
     * 複数の Activity を作成し、正しく取得できることをテスト
     */
    public function test_processApi_retrieves_multiple_activities(): void
    {
        // 1. Account を作成
        $accountId = $this->createTestAccount();

        // 2. 複数の Activity を作成
        $activityId1 = $this->createTestActivity($accountId, 'Accounts', [
            'subject' => 'First Activity',
            'activitytype' => 'Task',
        ]);
        $activityId2 = $this->createTestActivity($accountId, 'Accounts', [
            'subject' => 'Second Activity',
            'activitytype' => 'Meeting',
            'eventstatus' => 'Planned',
        ]);

        // 3. API で取得
        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        // 4. 検証
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(2, count($result['activities']), 'At least two activities should be returned');

        // 両方のアクティビティが含まれているか確認
        $foundIds = array_map(fn($a) => (int)$a['id'], $result['activities']);
        $this->assertContains($activityId1, $foundIds, "First activity should be in response");
        $this->assertContains($activityId2, $foundIds, "Second activity should be in response");
    }

    /**
     * Activity がない Account の場合、空の配列が返ることをテスト
     */
    public function test_processApi_returns_empty_when_no_activities(): void
    {
        // Activity なしの Account を作成
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['activities']);
        // 新規作成した Account なので Activity は 0 件のはず
        $this->assertCount(0, $result['activities']);
    }

    /**
     * Meeting（イベント）タイプのActivityを作成し取得できることをテスト
     */
    public function test_processApi_retrieves_meeting_event(): void
    {
        $accountId = $this->createTestAccount();

        $activityId = $this->createTestActivity($accountId, 'Accounts', [
            'subject' => 'Important Meeting',
            'activitytype' => 'Meeting',
            'eventstatus' => 'Planned',
            'location' => 'Conference Room A',
        ]);

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);

        $foundActivity = null;
        foreach ($result['activities'] as $activity) {
            if ((int)$activity['id'] === $activityId) {
                $foundActivity = $activity;
                break;
            }
        }

        $this->assertNotNull($foundActivity);
        $this->assertEquals('Meeting', $foundActivity['activityType']);
        $this->assertEquals('Planned', $foundActivity['status']);
        $this->assertEquals('Conference Room A', $foundActivity['location']);
    }

    // ============================================
    // ページネーションテスト
    // ============================================

    /**
     * limit パラメータが正しく動作すること
     */
    public function test_processApi_respects_limit_parameter(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
            'limit' => '3',
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['limit']);
    }

    /**
     * page パラメータが正しく動作すること
     */
    public function test_processApi_respects_page_parameter(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
            'page' => '2',
            'limit' => '10',
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['page']);
    }

    /**
     * limit が100を超える場合、100に制限されること
     */
    public function test_processApi_limits_max_to_100(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
            'limit' => '200',
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertEquals(100, $result['limit']);
    }

    // ============================================
    // モードテスト
    // ============================================

    /**
     * mode=upcoming が正しく動作すること
     */
    public function test_processApi_mode_upcoming(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
            'mode' => 'upcoming',
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['activities']);
    }

    /**
     * mode=overdue が正しく動作すること
     */
    public function test_processApi_mode_overdue(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
            'mode' => 'overdue',
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['activities']);
    }

    /**
     * mode が空の場合も正常に動作すること
     */
    public function test_processApi_mode_empty_returns_all(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
            'mode' => '',
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['activities']);
    }

    // ============================================
    // レスポンス構造テスト
    // ============================================

    /**
     * レスポンスに必要なフィールドがすべて含まれていること
     */
    public function test_processApi_response_has_required_fields(): void
    {
        $accountId = $this->createTestAccount();

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('activities', $result);
        $this->assertArrayHasKey('hasMore', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('totalCount', $result);
    }

    // ============================================
    // formatActivity テスト（プライベートメソッドのテスト）
    // ============================================

    /**
     * formatActivity が正しいフォーマットでデータを返すことをテスト
     */
    public function test_formatActivity_returns_correct_format(): void
    {
        // モックのActivityModelを作成
        $activityModel = $this->createMockActivityModel([
            'id' => 123,
            'activitytype' => 'Task',
            'subject' => 'Test Task',
            'eventstatus' => '',
            'taskstatus' => 'Not Started',
            'date_start' => '2026-01-20',
            'time_start' => '10:00:00',
            'due_date' => '2026-01-21',
            'time_end' => '11:00:00',
            'description' => 'Test description',
            'smownerid' => 1,
            'taskpriority' => 'High',
            'location' => '',
        ]);

        // プライベートメソッドにアクセス
        $result = $this->invokePrivateMethod($this->api, 'formatActivity', [$activityModel]);

        $this->assertIsArray($result);
        $this->assertEquals(123, $result['id']);
        $this->assertEquals('Test Task', $result['subject']);
        $this->assertEquals('Task', $result['activityType']);
        $this->assertEquals('Not Started', $result['status']);
        $this->assertEquals('2026-01-20', $result['dateStart']);
        $this->assertEquals('10:00:00', $result['timeStart']);
        $this->assertEquals('2026-01-21', $result['dueDate']);
        $this->assertEquals('11:00:00', $result['timeEnd']);
        $this->assertEquals('Test description', $result['description']);
        $this->assertEquals('High', $result['priority']);
        $this->assertArrayHasKey('detailViewUrl', $result);
        $this->assertStringContainsString('record=123', $result['detailViewUrl']);

        // Task 1.1: 新しいフィールドの検証
        $this->assertArrayHasKey('statusField', $result, 'statusField should be present');
        $this->assertArrayHasKey('statusOptions', $result, 'statusOptions should be present');
        $this->assertArrayHasKey('canEdit', $result, 'canEdit should be present');
        $this->assertEquals('taskstatus', $result['statusField'], 'Task should use taskstatus field');
        $this->assertIsArray($result['statusOptions'], 'statusOptions should be an array');
        $this->assertIsBool($result['canEdit'], 'canEdit should be boolean');
    }

    /**
     * formatActivity がイベントのステータスを正しく取得することをテスト
     */
    public function test_formatActivity_uses_eventstatus_for_events(): void
    {
        $activityModel = $this->createMockActivityModel([
            'id' => 456,
            'activitytype' => 'Meeting',
            'subject' => 'Test Meeting',
            'eventstatus' => 'Planned',
            'taskstatus' => '',
            'date_start' => '2026-01-20',
            'time_start' => '14:00:00',
            'due_date' => '2026-01-20',
            'time_end' => '15:00:00',
            'description' => '',
            'smownerid' => 1,
            'taskpriority' => '',
            'location' => 'Conference Room A',
        ]);

        $result = $this->invokePrivateMethod($this->api, 'formatActivity', [$activityModel]);

        $this->assertEquals('Planned', $result['status']);
        $this->assertEquals('Conference Room A', $result['location']);

        // Task 1.1: Meeting/Call は eventstatus を使用することを検証
        $this->assertEquals('eventstatus', $result['statusField'], 'Meeting should use eventstatus field');
        $this->assertIsArray($result['statusOptions'], 'statusOptions should be an array');
        $this->assertIsBool($result['canEdit'], 'canEdit should be boolean');
    }

    /**
     * Task 1.1: statusField が activityType に応じて正しく設定されることをテスト
     */
    public function test_statusField_is_correct_based_on_activityType(): void
    {
        // Task の場合
        $taskModel = $this->createMockActivityModel([
            'id' => 100,
            'activitytype' => 'Task',
            'subject' => 'Test Task',
            'taskstatus' => 'Not Started',
            'eventstatus' => '',
            'date_start' => '2026-01-20',
            'time_start' => '10:00:00',
            'due_date' => '2026-01-21',
            'time_end' => '11:00:00',
            'description' => '',
            'smownerid' => 1,
            'taskpriority' => '',
            'location' => '',
        ]);

        $result = $this->invokePrivateMethod($this->api, 'formatActivity', [$taskModel]);
        $this->assertEquals('taskstatus', $result['statusField']);

        // Meeting の場合
        $meetingModel = $this->createMockActivityModel([
            'id' => 101,
            'activitytype' => 'Meeting',
            'subject' => 'Test Meeting',
            'eventstatus' => 'Planned',
            'taskstatus' => '',
            'date_start' => '2026-01-20',
            'time_start' => '10:00:00',
            'due_date' => '2026-01-20',
            'time_end' => '11:00:00',
            'description' => '',
            'smownerid' => 1,
            'taskpriority' => '',
            'location' => '',
        ]);

        $result = $this->invokePrivateMethod($this->api, 'formatActivity', [$meetingModel]);
        $this->assertEquals('eventstatus', $result['statusField']);

        // Call の場合
        $callModel = $this->createMockActivityModel([
            'id' => 102,
            'activitytype' => 'Call',
            'subject' => 'Test Call',
            'eventstatus' => 'Planned',
            'taskstatus' => '',
            'date_start' => '2026-01-20',
            'time_start' => '10:00:00',
            'due_date' => '2026-01-20',
            'time_end' => '11:00:00',
            'description' => '',
            'smownerid' => 1,
            'taskpriority' => '',
            'location' => '',
        ]);

        $result = $this->invokePrivateMethod($this->api, 'formatActivity', [$callModel]);
        $this->assertEquals('eventstatus', $result['statusField']);
    }

    /**
     * Task 1.1: statusOptions が正しい形式で返されることをテスト
     */
    public function test_statusOptions_format(): void
    {
        $accountId = $this->createTestAccount();
        $activityId = $this->createTestActivity($accountId, 'Accounts', [
            'subject' => 'Test Activity with Status Options',
            'activitytype' => 'Task',
            'taskstatus' => 'Not Started',
        ]);

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['activities']);

        // 作成したアクティビティを検索
        $foundActivity = null;
        foreach ($result['activities'] as $activity) {
            if ((int)$activity['id'] === $activityId) {
                $foundActivity = $activity;
                break;
            }
        }

        $this->assertNotNull($foundActivity);
        $this->assertArrayHasKey('statusOptions', $foundActivity);
        $this->assertIsArray($foundActivity['statusOptions']);

        // statusOptions の構造を検証
        if (!empty($foundActivity['statusOptions'])) {
            $firstOption = $foundActivity['statusOptions'][0];
            $this->assertArrayHasKey('value', $firstOption, 'Each option should have a value');
            $this->assertArrayHasKey('label', $firstOption, 'Each option should have a label');
            $this->assertIsString($firstOption['value'], 'Option value should be string');
            $this->assertIsString($firstOption['label'], 'Option label should be string');
        }
    }

    /**
     * Task 1.1: canEdit が boolean 型で返されることをテスト
     */
    public function test_canEdit_is_boolean(): void
    {
        $accountId = $this->createTestAccount();
        $activityId = $this->createTestActivity($accountId, 'Accounts');

        $request = $this->createRequest([
            'parent_module' => 'Accounts',
            'parent_id' => (string)$accountId,
        ]);

        $response = $this->api->process($request);
        $result = $response->getResult();

        $this->assertTrue($result['success']);

        // 作成したアクティビティを検索
        $foundActivity = null;
        foreach ($result['activities'] as $activity) {
            if ((int)$activity['id'] === $activityId) {
                $foundActivity = $activity;
                break;
            }
        }

        $this->assertNotNull($foundActivity);
        $this->assertArrayHasKey('canEdit', $foundActivity);
        $this->assertIsBool($foundActivity['canEdit']);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Activity Model のモックを作成
     */
    private function createMockActivityModel(array $data): object
    {
        $mock = new class($data) {
            private array $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function getId()
            {
                return $this->data['id'];
            }

            public function get(string $key)
            {
                return $this->data[$key] ?? '';
            }
        };

        return $mock;
    }

    /**
     * プライベートメソッドを呼び出す
     */
    private function invokePrivateMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
