<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

// PHPUnit テスト環境用: ModTracker_Record_Model を単体でロードする。
// 通常は Vtiger_Loader が継承元を解決するが、テスト bootstrap ではローダーを起動しない。
//
// 継承元の Vtiger_Record_Model 本体は Vtiger_Module_Model 経由で vtiger_tab などの
// マスタデータを要求する。更新履歴の取得順とページングの検証には不要なので、
// getUpdates() が使う範囲（値の保持と setParent 経由の親レコード生成）だけを持つスタブを置く。

$root = dirname(__DIR__, 2);

require_once $root . '/includes/runtime/Globals.php';
require_once $root . '/includes/runtime/BaseModel.php';
require_once $root . '/modules/Vtiger/models/Paging.php';

if (!class_exists('Vtiger_Util_Helper')) {
    /**
     * 本体は modules/Vtiger/helpers/Util.php。
     * ModTracker_Record_Model::setParent() から呼ばれる存在チェックだけを差し替える。
     * 削除済み（1）を返して、DB 参照を伴わない getCleanInstance() 側の経路に倒す。
     */
    class Vtiger_Util_Helper
    {
        public static function checkRecordExistance(int|string $recordId): int
        {
            return 1;
        }
    }
}

if (!class_exists('Vtiger_Record_Model')) {
    /**
     * 本体は modules/Vtiger/models/Record.php。
     * 親レコードの生成だけができれば getUpdates() は完走する。
     */
    class Vtiger_Record_Model extends Vtiger_Base_Model
    {
        public int|string|null $id = null;

        // ModTracker_Record_Model::setParent() が代入する。本体側は宣言が無く
        // PHP 8.2 以降は動的プロパティの Deprecation になるが、その対応は本テストの対象外のため
        // ここで宣言して検証結果にノイズを持ち込まないようにする。
        public ?self $parent = null;

        public function setId(int|string $id): self
        {
            $this->id = $id;

            return $this;
        }

        public function getId(): int|string|null
        {
            return $this->id;
        }

        public static function getCleanInstance(string $moduleName): self
        {
            return new self();
        }

        public static function getInstanceById(int|string $recordId, ?string $moduleName = null): self
        {
            $instance = new self();
            $instance->setId($recordId);

            return $instance;
        }
    }
}

require_once $root . '/modules/ModTracker/models/Record.php';
