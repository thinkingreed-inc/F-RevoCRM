<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Users\Actions;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 4);

require_once $root . '/includes/runtime/Controller.php';
require_once $root . '/libraries/HTTP_Session2/HTTP/Session2.php';
require_once $root . '/includes/http/Session.php';
require_once $root . '/config.php';
require_once $root . '/modules/Users/actions/Logout.php';

if (!function_exists('vimport')) {
    function vimport(string $qualifiedName): void
    {
    }
}

class LogoutTestAction extends \Users_Logout_Action
{
    public function exposeGetLogoutURL(): ?string
    {
        return $this->getLogoutURL();
    }
}

class LogoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    private function makeAction(): LogoutTestAction
    {
        return new LogoutTestAction();
    }

    public function test_getLogoutURL_returns_session_url_when_set(): void
    {
        $_SESSION['LOGOUT_URL'] = 'https://idp.example.com/logout';
        $action = $this->makeAction();
        $this->assertSame('https://idp.example.com/logout', $action->exposeGetLogoutURL());
    }

    public function test_getLogoutURL_returns_empty_when_session_url_not_set(): void
    {
        unset($_SESSION['LOGOUT_URL']);
        $action = $this->makeAction();
        // VtigerConfig::getOD() は常に '' を返す
        $this->assertSame('', $action->exposeGetLogoutURL());
    }

    public function test_getLogoutURL_returns_empty_when_session_url_is_blank(): void
    {
        $_SESSION['LOGOUT_URL'] = '';
        $action = $this->makeAction();
        $this->assertSame('', $action->exposeGetLogoutURL());
    }
}
