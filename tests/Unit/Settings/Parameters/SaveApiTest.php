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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 4);

require_once $root . '/tests/Support/ParametersApiTestSupport.php';
require_once $root . '/modules/Settings/Parameters/apis/Save.php';

/**
 * protected / private のメソッドをテストから呼ぶためのサブクラス
 */
class SaveApiTestDouble extends \Settings_Parameters_Save_Api
{
    public function exposeProcessApi(\Vtiger_Request $request): \Vtiger_Response
    {
        return $this->processApi($request);
    }

    public function exposeValidateValue(mixed $value, string $type): mixed
    {
        $method = new \ReflectionMethod(\Settings_Parameters_Save_Api::class, 'validateValue');

        return $method->invoke($this, $value, $type);
    }
}

final class SaveApiTest extends TestCase
{
    private ?string $errorLogFile = null;

    private string $previousErrorLog = '';

    protected function setUp(): void
    {
        parent::setUp();

        // API は予期しないエラーを error_log へ出す。テスト出力に混ざるので一時ファイルへ退避する
        $this->previousErrorLog = (string)ini_get('error_log');
        $logFile = tempnam(sys_get_temp_dir(), 'phpunit-errorlog-');
        if (is_string($logFile)) {
            $this->errorLogFile = $logFile;
            ini_set('error_log', $logFile);
        }

        \ParametersApiTestState::reset();
        $GLOBALS['__test_csrf_pass'] = true;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['HTTP_REFERER']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        ini_set('error_log', $this->previousErrorLog);
        if ($this->errorLogFile !== null && file_exists($this->errorLogFile)) {
            unlink($this->errorLogFile);
        }
        $this->errorLogFile = null;

        \ParametersApiTestState::reset();
        unset($GLOBALS['__test_csrf_pass'], $_SERVER['REQUEST_METHOD']);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function makeRequest(array $values): \Vtiger_Request
    {
        return new \Vtiger_Request($values, $values);
    }

    /**
     * API を実行し、成功時の結果を返す
     *
     * 失敗時は ApiException が投げられるため、呼び出し側は runApiExpectingError() を使う。
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function runApi(array $values): array
    {
        $api = new SaveApiTestDouble();
        $result = $api->exposeProcessApi($this->makeRequest($values))->getResult();

        $this->assertIsArray($result);
        $normalized = [];
        foreach ($result as $key => $value) {
            $normalized[(string)$key] = $value;
        }

        return $normalized;
    }

    /**
     * API を実行し、投げられた例外のメッセージを返す
     *
     * @param array<string, mixed> $values
     */
    private function runApiExpectingError(array $values): string
    {
        $api = new SaveApiTestDouble();

        try {
            $api->exposeProcessApi($this->makeRequest($values));
        } catch (\ApiException $e) {
            return $e->getMessage();
        }

        $this->fail('ApiException が投げられること');
    }

    // ------------------------------------------------------------------
    // validateValue: 型ごとの正規化
    // ------------------------------------------------------------------

    /**
     * @return array<array-key, array{0: mixed, 1: string}>
     */
    public static function booleanTrueProvider(): array
    {
        return [
            'true' => ['true', 'true'],
            '1' => ['1', 'true'],
            'yes' => ['yes', 'true'],
            'on' => ['on', 'true'],
            '大文字TRUE' => ['TRUE', 'true'],
        ];
    }

    #[DataProvider('booleanTrueProvider')]
    public function test_boolean型は真を表す値をtrueへ正規化する(mixed $input, string $expected): void
    {
        $api = new SaveApiTestDouble();

        $this->assertSame($expected, $api->exposeValidateValue($input, 'boolean'));
    }

    /**
     * @return array<array-key, array{0: mixed}>
     */
    public static function booleanFalseProvider(): array
    {
        return [
            'false' => ['false'],
            '0' => ['0'],
            'no' => ['no'],
            'off' => ['off'],
            '空文字' => [''],
        ];
    }

    #[DataProvider('booleanFalseProvider')]
    public function test_boolean型は偽を表す値をfalseへ正規化する(mixed $input): void
    {
        $api = new SaveApiTestDouble();

        $this->assertSame('false', $api->exposeValidateValue($input, 'boolean'));
    }

    public function test_boolean型は解釈できない値を拒否する(): void
    {
        $api = new SaveApiTestDouble();

        $this->expectException(\ApiBadRequestException::class);
        $api->exposeValidateValue('maybe', 'boolean');
    }

    public function test_integer型は空文字を0として扱う(): void
    {
        $api = new SaveApiTestDouble();

        $this->assertSame('0', $api->exposeValidateValue('', 'integer'));
    }

    public function test_integer型は数値を整数文字列へ正規化する(): void
    {
        $api = new SaveApiTestDouble();

        $this->assertSame('45', $api->exposeValidateValue('45', 'integer'));
        $this->assertSame('-3', $api->exposeValidateValue('-3', 'integer'));
    }

    public function test_integer型は数値でない値を拒否する(): void
    {
        $api = new SaveApiTestDouble();

        $this->expectException(\ApiBadRequestException::class);
        $api->exposeValidateValue('abc', 'integer');
    }

    public function test_string型は512文字までを許可する(): void
    {
        $api = new SaveApiTestDouble();
        $value = str_repeat('あ', 512);

        $this->assertSame($value, $api->exposeValidateValue($value, 'string'));
    }

    public function test_string型は512文字を超えると拒否する(): void
    {
        $api = new SaveApiTestDouble();

        $this->expectException(\ApiBadRequestException::class);
        $api->exposeValidateValue(str_repeat('あ', 513), 'string');
    }

    public function test_スカラーでない値は拒否する(): void
    {
        $api = new SaveApiTestDouble();

        $this->expectException(\ApiBadRequestException::class);
        $api->exposeValidateValue(['array'], 'string');
    }

    // ------------------------------------------------------------------
    // processApi: value 未送信時の既存値維持（シークレット変数の値破壊防止）
    // ------------------------------------------------------------------

    public function test_value未送信なら既存値を維持する_integer(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 1,
            'description' => '旧備考',
        ]);

        $response = $this->runApi([
            'id' => '2',
            'description' => '新備考',
            'secret' => '1',
        ]);

        $this->assertSame(true, $response['saved']);
        $this->assertSame(
            '30',
            \ParametersApiTestState::savedValue(2, 'value'),
            'value 未送信時は既存値を維持すること'
        );
        $this->assertSame('新備考', \ParametersApiTestState::savedValue(2, 'description'));
    }

    public function test_value未送信なら既存値を維持する_boolean(): void
    {
        // boolean はシークレットを持てないが、value 未送信で false に落ちないことは変わらない
        \ParametersApiTestState::seed([
            'id' => 4,
            'key' => 'SHOW_SCHEDULE_CONFIRM_FLAG',
            'value' => 'true',
            'type' => 'boolean',
            'secret' => 0,
            'description' => '旧備考',
        ]);

        $this->runApi([
            'id' => '4',
            'description' => '新備考',
        ]);

        $this->assertSame(
            'true',
            \ParametersApiTestState::savedValue(4, 'value'),
            'boolean でも false に落ちないこと'
        );
    }

    public function test_value送信時は値を更新する(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '備考',
        ]);

        $this->runApi([
            'id' => '2',
            'value' => '45',
            'description' => '備考',
        ]);

        $this->assertSame('45', \ParametersApiTestState::savedValue(2, 'value'));
    }

    public function test_value空文字送信時はサーバー側の正規化が働く(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '備考',
        ]);

        $this->runApi([
            'id' => '2',
            'value' => '',
            'description' => '備考',
        ]);

        $this->assertSame('0', \ParametersApiTestState::savedValue(2, 'value'));
    }

    // ------------------------------------------------------------------
    // シークレットの切り替え
    // ------------------------------------------------------------------

    public function test_シークレットを有効にできる(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '備考',
        ]);

        $this->runApi([
            'id' => '2',
            'value' => '30',
            'description' => '備考',
            'secret' => '1',
        ]);

        $this->assertSame(1, \ParametersApiTestState::savedValue(2, 'secret'));
    }

    public function test_新しい値を伴えばシークレットを解除できる(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 1,
            'description' => '備考',
        ]);

        $this->runApi([
            'id' => '2',
            'value' => '45',
            'description' => '備考',
            'secret' => '0',
        ]);

        $this->assertSame(0, \ParametersApiTestState::savedValue(2, 'secret'));
        $this->assertSame('45', \ParametersApiTestState::savedValue(2, 'value'));
    }

    public function test_boolean型にシークレットは設定できない(): void
    {
        // 値が true / false の 2 択しかなく、マスクしても値を推測できる
        \ParametersApiTestState::seed([
            'id' => 1,
            'key' => 'FORCE_MULTI_FACTOR_AUTH',
            'value' => 'false',
            'type' => 'boolean',
            'secret' => 0,
            'description' => '備考',
        ]);

        $message = $this->runApiExpectingError([
            'id' => '1',
            'value' => 'false',
            'description' => '備考',
            'secret' => '1',
        ]);

        $this->assertSame('Secret is not available for boolean parameters', $message);
        $this->assertFalse(\ParametersApiTestState::hasSaved());
    }

    public function test_boolean型のシークレットは保存時に解除される(): void
    {
        // 不整合なデータが残っていても、保存すれば解消される
        \ParametersApiTestState::seed([
            'id' => 1,
            'key' => 'FORCE_MULTI_FACTOR_AUTH',
            'value' => 'false',
            'type' => 'boolean',
            'secret' => 1,
            'description' => '旧備考',
        ]);

        $response = $this->runApi([
            'id' => '1',
            'description' => '新備考',
        ]);

        $this->assertSame(true, $response['saved']);
        $this->assertSame(0, \ParametersApiTestState::savedValue(1, 'secret'));
        // 値の再入力を求めずに解除する（boolean は解除チェックの対象外）
        $this->assertSame('false', \ParametersApiTestState::savedValue(1, 'value'));
    }

    public function test_値を伴わないシークレット解除は拒否する(): void
    {
        // 値を入力せずに解除できると、秘匿していた値をそのまま画面へ露出させられてしまう
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 1,
            'description' => '備考',
        ]);

        $message = $this->runApiExpectingError([
            'id' => '2',
            'description' => '備考',
            'secret' => '0',
        ]);

        $this->assertSame('A new value is required to turn off the secret setting', $message);
        $this->assertFalse(\ParametersApiTestState::hasSaved());
    }

    public function test_空文字を伴うシークレット解除も拒否する(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 1,
            'description' => '備考',
        ]);

        $message = $this->runApiExpectingError([
            'id' => '2',
            'value' => '',
            'description' => '備考',
            'secret' => '0',
        ]);

        $this->assertSame('A new value is required to turn off the secret setting', $message);
        $this->assertFalse(\ParametersApiTestState::hasSaved());
    }

    public function test_シークレットのまま値を省略した保存は許可する(): void
    {
        // 解除ではないため、備考だけの編集は従来どおり通る
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 1,
            'description' => '旧備考',
        ]);

        $response = $this->runApi([
            'id' => '2',
            'description' => '新備考',
            'secret' => '1',
        ]);

        $this->assertSame(true, $response['saved']);
        $this->assertSame('30', \ParametersApiTestState::savedValue(2, 'value'));
    }

    public function test_もともとシークレットでなければ値の省略を許可する(): void
    {
        // secret=0 のまま保存する場合は解除にあたらない
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '旧備考',
        ]);

        $response = $this->runApi([
            'id' => '2',
            'description' => '新備考',
            'secret' => '0',
        ]);

        $this->assertSame(true, $response['saved']);
        $this->assertSame('30', \ParametersApiTestState::savedValue(2, 'value'));
    }

    // ------------------------------------------------------------------
    // processApi: 入力値を加工せずに保存する
    // ------------------------------------------------------------------

    public function test_HTML特殊文字を含む値をそのまま保存する(): void
    {
        // Vtiger_Request::get() は vtlib_purify() を通すため < が &lt; に変換される。
        // システム変数はトークンなど任意の文字列を持つため、生の値を保存する
        \ParametersApiTestState::seed([
            'id' => 5,
            'key' => 'EXAMPLE_API_TOKEN',
            'value' => '',
            'type' => 'string',
            'secret' => 0,
            'description' => '備考',
        ]);

        $this->runApi([
            'id' => '5',
            'value' => 'x & y < z',
        ]);

        $this->assertSame('x & y < z', \ParametersApiTestState::savedValue(5, 'value'));
    }

    public function test_JSON形式の文字列も値として保存できる(): void
    {
        // Vtiger_Request::get() は { や [ で始まる値を配列へデコードしてしまう
        \ParametersApiTestState::seed([
            'id' => 5,
            'key' => 'EXAMPLE_JSON',
            'value' => '',
            'type' => 'string',
            'secret' => 0,
            'description' => '備考',
        ]);

        $this->runApi([
            'id' => '5',
            'value' => '{"a":1}',
        ]);

        $this->assertSame('{"a":1}', \ParametersApiTestState::savedValue(5, 'value'));
    }

    public function test_HTML特殊文字を含む備考をそのまま保存する(): void
    {
        \ParametersApiTestState::seed([
            'id' => 5,
            'key' => 'EXAMPLE_API_TOKEN',
            'value' => 'token',
            'type' => 'string',
            'secret' => 0,
            'description' => '旧備考',
        ]);

        $this->runApi([
            'id' => '5',
            'description' => '条件: a < b & c',
        ]);

        $this->assertSame('条件: a < b & c', \ParametersApiTestState::savedValue(5, 'description'));
    }

    public function test_配列で送られた値は拒否し保存しない(): void
    {
        \ParametersApiTestState::seed([
            'id' => 5,
            'key' => 'EXAMPLE_API_TOKEN',
            'value' => 'token',
            'type' => 'string',
            'secret' => 0,
            'description' => '備考',
        ]);

        $message = $this->runApiExpectingError([
            'id' => '5',
            'value' => ['a', 'b'],
        ]);

        $this->assertSame('Invalid value type', $message);
        $this->assertFalse(\ParametersApiTestState::hasSaved());
    }

    public function test_不正なidはエラーを返し保存しない(): void
    {
        $message = $this->runApiExpectingError([
            'id' => '0',
            'description' => '備考',
        ]);

        $this->assertSame('Invalid ID', $message);
        $this->assertFalse(\ParametersApiTestState::hasSaved());
    }

    public function test_存在しないレコードはエラーを返す(): void
    {
        $message = $this->runApiExpectingError([
            'id' => '999',
            'description' => '備考',
        ]);

        $this->assertSame('Record not found', $message);
    }

    public function test_型に合わない値はエラーを返し保存しない(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '備考',
        ]);

        $message = $this->runApiExpectingError([
            'id' => '2',
            'value' => 'abc',
            'description' => '備考',
        ]);

        $this->assertSame('Invalid integer value', $message);
        $this->assertFalse(\ParametersApiTestState::hasSaved());
    }

    // ------------------------------------------------------------------
    // 備考（description）の扱い
    // ------------------------------------------------------------------

    public function test_description未送信なら既存の備考を維持する(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '元の備考',
        ]);

        $response = $this->runApi([
            'id' => '2',
            'value' => '45',
        ]);

        $this->assertSame(true, $response['saved']);
        $this->assertSame('元の備考', \ParametersApiTestState::savedValue(2, 'description'));
        $this->assertSame('45', \ParametersApiTestState::savedValue(2, 'value'));
    }

    public function test_description空文字送信なら備考を空にする(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '元の備考',
        ]);

        $this->runApi([
            'id' => '2',
            'description' => '',
        ]);

        $this->assertSame('', \ParametersApiTestState::savedValue(2, 'description'));
    }

    // ------------------------------------------------------------------
    // エラー応答
    // ------------------------------------------------------------------

    public function test_保存に失敗しても内部のエラー詳細を返さない(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '備考',
        ]);
        \ParametersApiTestState::$throwOnSave = true;

        $message = $this->runApiExpectingError([
            'id' => '2',
            'value' => '45',
            'description' => '備考',
        ]);

        $this->assertSame('Failed to save the parameter', $message);
        // SQL エラーの内容がクライアントへ漏れないこと
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('vtiger_parameters', $message);
    }

    public function test_入力値の誤りはそのまま利用者へ返す(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '備考',
        ]);

        $message = $this->runApiExpectingError([
            'id' => '2',
            'value' => 'abc',
            'description' => '備考',
        ]);

        $this->assertSame('Invalid integer value', $message);
    }

    // ------------------------------------------------------------------
    // checkPermission
    // ------------------------------------------------------------------

    public function test_管理者以外は拒否される(): void
    {
        \ParametersApiTestState::$isAdmin = false;
        $api = new SaveApiTestDouble();

        $this->expectException(\ApiForbiddenException::class);
        $api->checkPermission($this->makeRequest(['id' => '2']));
    }

    public function test_管理者は許可される(): void
    {
        $api = new SaveApiTestDouble();

        $this->assertTrue($api->checkPermission($this->makeRequest(['id' => '2'])));
    }

    public function test_CSRF検証に失敗すると保存しない(): void
    {
        \ParametersApiTestState::seed([
            'id' => 2,
            'key' => 'USER_LOCK_TIME',
            'value' => '30',
            'type' => 'integer',
            'secret' => 0,
            'description' => '備考',
        ]);
        $GLOBALS['__test_csrf_pass'] = false;

        $api = new SaveApiTestDouble();

        try {
            $api->exposeProcessApi($this->makeRequest([
                'id' => '2',
                'value' => '45',
                'description' => '備考',
            ]));
            $this->fail('CSRF 検証に失敗した場合は例外が投げられること');
        } catch (\Exception $e) {
            $this->assertSame('Unsupported request', $e->getMessage());
        }

        $this->assertFalse(\ParametersApiTestState::hasSaved());
    }
}
