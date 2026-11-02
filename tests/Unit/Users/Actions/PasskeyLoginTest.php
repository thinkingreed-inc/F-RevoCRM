<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

declare(strict_types=1);

namespace Tests\Unit\Users\Actions;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 4);

require_once $root . '/includes/runtime/Controller.php';
require_once $root . '/vtlib/Vtiger/Functions.php';
require_once $root . '/includes/http/Request.php';
require_once $root . '/modules/Users/helpers/MultiFactorAuthentication.php';
require_once $root . '/modules/Users/models/PasskeyLoginService.php';
require_once $root . '/modules/Users/actions/PasskeyLogin.php';

/**
 * authenticate() が呼ばれたかを記録するだけのサービス
 */
class PasskeyLoginServiceSpy extends \Users_PasskeyLoginService_Model
{
    /** @var list<array{string, string|false, string}> */
    public array $calls = [];

    public ?int $result = null;

    public function __construct()
    {
    }

    public function authenticate(string $credentialJson, string|false $challenge, string $rpId): ?int
    {
        $this->calls[] = [$credentialJson, $challenge, $rpId];

        return $this->result;
    }
}

class PasskeyLoginTestAction extends \Users_PasskeyLogin_Action
{
    public bool $enabled = false;

    public PasskeyLoginServiceSpy $spy;

    public function __construct()
    {
        $this->spy = new PasskeyLoginServiceSpy();
    }

    public function exposeResolveRedirect(\Vtiger_Request $request): ?string
    {
        return $this->resolveRedirect($request);
    }

    public function exposeAuthenticatedUserId(): ?int
    {
        return $this->authenticatedUserId;
    }

    protected function isPasskeyOnlyLoginEnabled(): bool
    {
        return $this->enabled;
    }

    protected function createService(): \Users_PasskeyLoginService_Model
    {
        return $this->spy;
    }
}

class PasskeyLoginTest extends TestCase
{
    private const ERROR_URL = 'index.php?module=Users&parent=Settings&view=Login&error=login';

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_SERVER['SERVER_NAME'] = 'crm.example.com';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    public function test_フラグOFFならサービスを呼ばずerror_loginへ(): void
    {
        $_SESSION['challenge'] = base64_encode('challenge-bytes');
        $action = new PasskeyLoginTestAction();

        $redirect = $action->exposeResolveRedirect($this->request(['credential' => '{"id":"x"}']));

        self::assertSame(self::ERROR_URL, $redirect);
        self::assertSame([], $action->spy->calls);
        // フラグ OFF でもチャレンジは使い捨てにする
        self::assertArrayNotHasKey('challenge', $_SESSION);
    }

    public function test_credentialが無ければサービスを呼ばずerror_loginへ(): void
    {
        $_SESSION['challenge'] = base64_encode('challenge-bytes');
        $action = new PasskeyLoginTestAction();
        $action->enabled = true;

        self::assertSame(self::ERROR_URL, $action->exposeResolveRedirect($this->request([])));
        self::assertSame(self::ERROR_URL, $action->exposeResolveRedirect($this->request(['credential' => ''])));
        self::assertSame([], $action->spy->calls);
    }

    public function test_認証失敗ならerror_loginへ(): void
    {
        $_SESSION['challenge'] = base64_encode('challenge-bytes');
        $action = new PasskeyLoginTestAction();
        $action->enabled = true;
        $json = '{"id":"a+b/c==","response":{"userHandle":"<x>&"}}';

        $redirect = $action->exposeResolveRedirect($this->request(['credential' => $json]));

        self::assertSame(self::ERROR_URL, $redirect);
        // credential の JSON は手を加えずに渡し、チャレンジはセッションから取り出して消す
        self::assertSame([[$json, 'challenge-bytes', 'crm.example.com']], $action->spy->calls);
        self::assertArrayNotHasKey('challenge', $_SESSION);
    }

    public function test_認証成功ならリダイレクトせずユーザーIDを残す(): void
    {
        $_SESSION['challenge'] = base64_encode('challenge-bytes');
        $action = new PasskeyLoginTestAction();
        $action->enabled = true;
        $action->spy->result = 5;

        self::assertNull($action->exposeResolveRedirect($this->request(['credential' => '{"id":"x"}'])));
        self::assertSame(5, $action->exposeAuthenticatedUserId());
    }

    /**
     * @param array<string, string> $values
     */
    private function request(array $values): \Vtiger_Request
    {
        return new \Vtiger_Request($values, $values);
    }
}
