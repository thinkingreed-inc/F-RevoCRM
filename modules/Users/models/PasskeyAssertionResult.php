<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Passkey の assertion 検証の結果
 *
 * $userId は Passkey の持ち主を特定できた時点で入る（検証に失敗した場合も入る）。
 * 失敗回数を持ち主に数えるために使う。$reason はログ用の短い理由。
 */
class Users_PasskeyAssertionResult_Model
{
    public function __construct(
        public readonly bool $verified,
        public readonly ?int $userId,
        public readonly string $reason
    ) {
    }
}
