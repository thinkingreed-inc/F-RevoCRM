<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

use Webauthn\Counter\CounterChecker;
use Webauthn\CredentialRecord;

/**
 * 署名カウンタの巻き戻りを拒否せず、警告ログだけ残す
 *
 * 同期型 Passkey や端末ごとの扱いの違いで、正規ユーザーが締め出されるのを避けるため。
 * カウンタが両方 0 の場合はライブラリ側（CheckCounter）がここを呼ばない。
 */
class Users_PasskeyCounterChecker_Model implements CounterChecker
{
    public function check(CredentialRecord $credentialRecord, int $currentCounter): void
    {
        if ($currentCounter > $credentialRecord->counter) {
            return;
        }
        global $log;
        if ($log instanceof Logger) {
            $log->warn(sprintf(
                'Passkey signature counter did not increase (stored=%d, received=%d, userHandle=%s)',
                $credentialRecord->counter,
                $currentCounter,
                $credentialRecord->userHandle
            ));
        }
    }
}
