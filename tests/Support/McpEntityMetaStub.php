<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

// PHPUnit テスト環境用: Mcp_CrmTools::getEntityMeta() が返す webservice メタ（EntityMeta）の代替。
// 実在判定 exists() と実体モジュール判定 getObjectEntityName() だけをメモリ上の表で評価する。

$root = dirname(__DIR__, 2);

require_once $root . '/include/Webservices/EntityMeta.php';

/**
 * 1 モジュール分の webservice メタ。
 * $records は「このモジュールとして実在する id」の一覧、$entityNames は crmid => 実体モジュール名
 * （getObjectEntityName() が vtiger_crmentity と活動タイプから返す値を模す）。
 */
class McpEntityMetaStub extends \EntityMeta
{
    /** @var int[] */
    private array $records;

    /** @var array<int, string> */
    private array $entityNames;

    private string $module;

    private int $entityId;

    /** @var callable|null exists() / getObjectEntityName() に渡された crmid の記録先 */
    private $onLookup;

    /**
     * @param int[] $records
     * @param array<int, string> $entityNames
     */
    public function __construct(string $module, int $entityId, array $records, array $entityNames, ?callable $onLookup = null)
    {
        // 親は webservice オブジェクトと DB を要求するため呼ばない
        $this->module = $module;
        $this->entityId = $entityId;
        $this->records = $records;
        $this->entityNames = $entityNames;
        $this->onLookup = $onLookup;
    }

    public function exists($recordId)
    {
        $this->record((int) $recordId);
        return in_array((int) $recordId, $this->records, true);
    }

    public function getObjectEntityName($webserviceId)
    {
        $crmid = (int) (vtws_getIdComponents($webserviceId)[1] ?? 0);
        $this->record($crmid);
        return $this->entityNames[$crmid] ?? null;
    }

    public function getEntityName()
    {
        return $this->module;
    }

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function hasPermission($operation, $webserviceId)
    {
        return true;
    }

    public function hasAssignPrivilege($ownerWebserviceId)
    {
        return true;
    }

    public function hasDeleteAccess()
    {
        return true;
    }

    public function hasAccess()
    {
        return true;
    }

    public function hasReadAccess()
    {
        return true;
    }

    public function hasCreateAccess()
    {
        return true;
    }

    public function hasWriteAccess()
    {
        return true;
    }

    public function getNameFields()
    {
        return '';
    }

    public function getName($webserviceId)
    {
        return '';
    }

    public function isModuleEntity()
    {
        return true;
    }

    private function record(int $crmid): void
    {
        if ($this->onLookup !== null) {
            ($this->onLookup)($crmid);
        }
    }
}
