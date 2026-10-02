<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Settings\Parameters;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 4);

require_once $root . '/tests/Support/ParametersApiTestSupport.php';
require_once $root . '/modules/Settings/Parameters/apis/GetRecord.php';

/**
 * protected のメソッドをテストから呼ぶためのサブクラス
 */
class GetRecordApiTestDouble extends \Settings_Parameters_GetRecord_Api
{
    public function exposeProcessApi(\Vtiger_Request $request): \Vtiger_Response
    {
        return $this->processApi($request);
    }
}

final class GetRecordApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \ParametersApiTestState::reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        \ParametersApiTestState::reset();
        unset($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function runApi(array $values): array
    {
        $api = new GetRecordApiTestDouble();
        $result = $api->exposeProcessApi(new \Vtiger_Request($values, $values))->getResult();

        $this->assertIsArray($result);
        $normalized = [];
        foreach ($result as $key => $value) {
            $normalized[(string)$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function runApiExpectingError(array $values): string
    {
        $api = new GetRecordApiTestDouble();

        try {
            $api->exposeProcessApi(new \Vtiger_Request($values, $values));
        } catch (\ApiException $e) {
            return $e->getMessage();
        }

        $this->fail('ApiException が投げられること');
    }

    public function test_レコードの内容を返す(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => 'ロック時間',
        ]);

        $result = $this->runApi(['id' => '2']);

        $this->assertSame(2, $result['id']);
        $this->assertSame('USER_LOCK_TIME', $result['key']);
        $this->assertSame('30', $result['value']);
        $this->assertSame('integer', $result['type']);
        $this->assertSame(0, $result['secret']);
        $this->assertSame('ロック時間', $result['description']);
    }

    public function test_シークレット変数は値を返さない(): void
    {
        \ParametersApiTestState::seed([
            'id' => 5,
            'key' => 'EXAMPLE_API_TOKEN',
            'value' => 'secret-token',
            'type' => 'string',
            'secret' => 1,
            'description' => '備考',
        ]);

        $result = $this->runApi(['id' => '5']);

        $this->assertSame('', $result['value']);
        $this->assertSame(1, $result['secret']);
    }

    public function test_HTMLエスケープされた値を元の文字列へ戻して返す(): void
    {
        // PearDatabase::query_result() は to_html() で HTML エスケープした値を返す。
        // そのまま返すと編集ダイアログに実体参照が表示され、保存のたびに二重化していく
        \ParametersApiTestState::seed([
            'id' => 5,
            'key' => 'EXAMPLE_API_TOKEN',
            'value' => 'A&amp;B&lt;C &quot;q&quot;',
            'type' => 'string',
            'secret' => 0,
            'description' => '&lt;img src=x&gt; &amp; a &lt; b',
        ]);

        $result = $this->runApi(['id' => '5']);

        $this->assertSame('A&B<C "q"', $result['value']);
        $this->assertSame('<img src=x> & a < b', $result['description']);
    }

    public function test_実体参照を含む文字列は元の見た目のまま返す(): void
    {
        // 保存されている文字列自体が `&amp;` の場合、to_html() は `&amp;amp;` を返す。
        // 1 段だけ戻すことで元の `&amp;` に復元される
        \ParametersApiTestState::seed([
            'id' => 5,
            'key' => 'EXAMPLE_API_TOKEN',
            'value' => '&amp;amp;',
            'type' => 'string',
            'secret' => 0,
            'description' => '備考',
        ]);

        $result = $this->runApi(['id' => '5']);

        $this->assertSame('&amp;', $result['value']);
    }

    public function test_不正なidはエラーを返す(): void
    {
        $this->assertSame('Invalid ID', $this->runApiExpectingError(['id' => '0']));
        $this->assertSame('Invalid ID', $this->runApiExpectingError(['id' => 'abc']));
    }

    public function test_存在しないレコードはエラーを返す(): void
    {
        $this->assertSame('Record not found', $this->runApiExpectingError(['id' => '999']));
    }

    public function test_管理者以外は拒否される(): void
    {
        \ParametersApiTestState::$isAdmin = false;

        $api = new GetRecordApiTestDouble();

        $this->expectException(\ApiForbiddenException::class);
        $api->checkPermission(new \Vtiger_Request(['id' => '2'], ['id' => '2']));
    }

    public function test_管理者は許可される(): void
    {
        \ParametersApiTestState::$isAdmin = true;

        $api = new GetRecordApiTestDouble();

        $this->assertTrue($api->checkPermission(new \Vtiger_Request(['id' => '2'], ['id' => '2'])));
    }
}
