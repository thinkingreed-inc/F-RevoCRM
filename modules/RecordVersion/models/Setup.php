<?php

require_once 'vtlib/Vtiger/Module.php';

/**
 * 版管理（#1964）の「はい／いいえ」の切り替えと、版に含める関連の候補
 *
 * - はい：「版」項目が無ければ追加し、版が未設定（0）のレコードを版 1 にし、詳細画面のボタンを登録する
 * - いいえ：詳細画面のボタンの登録を外す（版番号・過去の版・「版」項目はそのまま残す）
 */
class RecordVersion_Setup_Model
{
    public const LINK_TYPE = 'DETAILVIEWBASIC';
    /** ボタンの名前（共通の言語ファイル languages/<言語>/Vtiger.php で翻訳する） */
    public const LINK_REVISE = 'LBL_RECORD_VERSION_REVISE';
    public const LINK_HISTORY = 'LBL_RECORD_VERSION_HISTORY';

    /** 「改版」ボタン：押したときに版管理の JavaScript を読み込んでダイアログを開く */
    public const REVISE_URL = "javascript:jQuery.getScript('layouts/v7/modules/RecordVersion/resources/RecordVersion.js')"
        . ".done(function(){RecordVersion_Js.openReviseDialog('\$MODULE\$','\$RECORD\$');});";

    /** 「版履歴」ボタン：版履歴の画面を開く */
    public const HISTORY_URL = 'index.php?module=RecordVersion&view=History&record=$RECORD$';

    private const LINK_HANDLER_PATH = 'modules/RecordVersion/helpers/LinkVisibility.php';
    private const LINK_HANDLER_CLASS = 'RecordVersion_LinkVisibility_Helper';

    /**
     * 版管理の対象にできるモジュール（エンティティ・有効・対象外でない）
     *
     * @return array<int, string> tabid => モジュール名（表示名の順）
     */
    public static function getSupportedModules(): array
    {
        $db = PearDatabase::getInstance();
        $excluded = RecordVersion_Config_Model::EXCLUDED_MODULES;
        $result = $db->pquery(
            'SELECT tabid, name FROM vtiger_tab WHERE isentitytype = 1 AND presence = 0
             AND name NOT IN (' . generateQuestionMarks($excluded) . ')',
            $excluded
        );
        $modules = [];
        foreach (RecordVersion_Util_Helper::fetchRows($result) as $row) {
            $modules[RecordVersion_Util_Helper::toInt($row['tabid'] ?? 0)] = RecordVersion_Util_Helper::toStr($row['name'] ?? '');
        }
        uasort($modules, function (string $a, string $b): int {
            return strcmp(
                RecordVersion_Util_Helper::toStr(vtranslate($a, $a)),
                RecordVersion_Util_Helper::toStr(vtranslate($b, $b))
            );
        });
        return $modules;
    }

    public static function isSupportedTab(int $tabId): bool
    {
        return array_key_exists($tabId, self::getSupportedModules());
    }

    /**
     * 版に含める関連の候補（関連タブの「1対多と多対多の関連」のうち、レコードの一覧を出すもの。非表示の関連も含む）
     *
     * @return array<int, array{relation_id: int, label: string, related_module: string}>
     */
    public static function getRelationCandidates(string $moduleName): array
    {
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if (!$moduleModel) {
            return [];
        }
        $candidates = [];
        foreach (Vtiger_Relation_Model::getAllRelations($moduleModel, false, true) as $relation) {
            $relatedModule = RecordVersion_Util_Helper::toStr($relation->get('relatedModuleName'));
            $functionName = RecordVersion_Util_Helper::toStr($relation->get('name'));
            if (!self::isListRelation($relatedModule, $functionName)) {
                continue;
            }
            $relationId = RecordVersion_Util_Helper::toInt($relation->getId());
            $candidates[$relationId] = [
                'relation_id' => $relationId,
                'label' => RecordVersion_Util_Helper::toStr($relation->get('label')),
                'related_module' => $relatedModule,
            ];
        }
        return $candidates;
    }

    /**
     * レコードの一覧を出す関連か（メール・コメント・活動・ユーザー、履歴・ガントチャートなどを除く）
     */
    public static function isListRelation(string $relatedModule, string $functionName): bool
    {
        if ($relatedModule === '' || in_array($relatedModule, RecordVersion_Config_Model::EXCLUDED_RELATED_MODULES, true)) {
            return false;
        }
        if (in_array($functionName, RecordVersion_Config_Model::NON_LIST_RELATION_FUNCTIONS, true)) {
            return false;
        }
        $relatedModel = Vtiger_Module_Model::getInstance($relatedModule);
        return $relatedModel && $relatedModel->isEntityModule();
    }

    /**
     * 版管理「はい」にする
     */
    public static function enable(int $tabId): void
    {
        $moduleName = RecordVersion_Util_Helper::toStr(getTabModuleName($tabId));
        if ($moduleName === '' || !RecordVersion_Config_Model::isSupportedModule($moduleName)) {
            throw new Exception('Unsupported module: ' . $moduleName);
        }
        self::ensureVersionField($moduleName);

        $db = PearDatabase::getInstance();
        // 版が未設定（0）のレコードだけ 1 にする。すでに版があるレコード（いいえ→はいに戻したとき）はそのまま
        $db->pquery(
            'UPDATE ' . RecordVersion_Config_Model::FIELD_TABLE . ' SET ' . RecordVersion_Config_Model::FIELD_COLUMN . ' = 1
             WHERE setype = ? AND ' . RecordVersion_Config_Model::FIELD_COLUMN . ' = 0',
            [$moduleName]
        );

        $handler = ['path' => self::LINK_HANDLER_PATH, 'class' => self::LINK_HANDLER_CLASS, 'method' => 'canRevise'];
        RecordVersion_Link_Model::add($tabId, self::LINK_TYPE, self::LINK_REVISE, self::REVISE_URL, 0, $handler);
        $handler['method'] = 'canViewHistory';
        RecordVersion_Link_Model::add($tabId, self::LINK_TYPE, self::LINK_HISTORY, self::HISTORY_URL, 0, $handler);

        RecordVersion_Config_Model::saveEnabled($tabId, true);
    }

    /**
     * 版管理「いいえ」にする（過去の版のデータ・版番号・「版」項目は消さない）
     */
    public static function disable(int $tabId): void
    {
        RecordVersion_Link_Model::remove($tabId, self::LINK_TYPE, self::LINK_REVISE);
        RecordVersion_Link_Model::remove($tabId, self::LINK_TYPE, self::LINK_HISTORY);
        RecordVersion_Config_Model::saveEnabled($tabId, false);
    }

    /**
     * 「版」項目が無ければ、モジュールの最初のブロックの最後に追加する
     *
     * 値は全モジュール共通の vtiger_crmentity.version に持つ（モジュールのテーブルは変えない）。
     * 標準項目（generatedtype=1）として登録するので、項目設定から削除はできない（移動・表示名の変更はできる）。
     */
    public static function ensureVersionField(string $moduleName): void
    {
        $tabId = RecordVersion_Util_Helper::toInt(getTabid($moduleName));
        if (RecordVersion_Config_Model::getVersionFieldId($tabId) > 0) {
            return;
        }
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        if (!$moduleInstance) {
            throw new Exception('Module not found: ' . $moduleName);
        }
        $blocks = Vtiger_Block::getAllForModule($moduleInstance);
        if (empty($blocks)) {
            throw new Exception('No block found: ' . $moduleName);
        }
        $firstBlock = reset($blocks);

        $field = new Vtiger_Field();
        $field->name = RecordVersion_Config_Model::FIELD_NAME;
        $field->label = RecordVersion_Config_Model::FIELD_LABEL;
        $field->table = RecordVersion_Config_Model::FIELD_TABLE;
        $field->column = RecordVersion_Config_Model::FIELD_COLUMN;
        // 列は既にある（vtiger_crmentity.version）ため、AddColumn は何もしない
        $field->columntype = 'INT(19)';
        $field->uitype = 7;
        $field->typeofdata = 'I~O';
        // 2：詳細画面などには出すが、作成・編集画面には出さない（読み取り専用）
        $field->displaytype = 2;
        $field->generatedtype = 1;
        $field->presence = 2;
        $field->masseditable = 0;
        $field->quickcreate = 1;
        $firstBlock->addField($field);

        RecordVersion_Config_Model::clearCache();
    }
}
