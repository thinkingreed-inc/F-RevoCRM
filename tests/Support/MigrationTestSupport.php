<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

require_once dirname(__DIR__, 2) . '/setup/migration/FRMigrationClass.php';

/**
 * マイグレーション実行時に Vtiger_Cache へ何が残っているかを記録するだけのマイグレーション。
 *
 * 実行前にキャッシュへ入れた値が process() から見えるかどうかを確かめるために使う。
 */
class FRTestMigrationCacheProbe extends FRMigrationClass
{
    /** process() 実行時点で名前空間付きキャッシュから取れた値 */
    public mixed $seenNamespacedValue = 'NOT_RUN';

    /** process() 実行時点で専用セッター側のキャッシュから取れた値 */
    public mixed $seenModuleName = 'NOT_RUN';

    public function process(): void
    {
        $this->seenNamespacedValue = Vtiger_Cache::get('module', 'FRTestMigrationModule');
        $this->seenModuleName = Vtiger_Cache::getInstance()->getModuleName(987654);
    }
}
