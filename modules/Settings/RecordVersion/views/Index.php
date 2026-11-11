<?php

/**
 * システム設定 → モジュール管理 → 版管理
 *
 * 版管理の対象にできるモジュールを一覧で出し、「版管理」（はい／いいえ）と
 * 「版に含める関連モジュール」を設定する（設定できるのは管理者だけ）。
 */
class Settings_RecordVersion_Index_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request): void
    {
        $qualifiedModuleName = $request->getModule(false);
        $rows = [];
        foreach (RecordVersion_Setup_Model::getSupportedModules() as $tabId => $moduleName) {
            $rows[] = self::buildRow($tabId, $moduleName);
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('ROWS', $rows);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('Index.tpl', $qualifiedModuleName);
    }

    /**
     * 一覧の 1 行（モジュール・版管理・版に含める関連モジュール）
     *
     * @return array{tabid: int, module: string, label: string, enabled: bool, relations: string[], relations_text: string}
     */
    public static function buildRow(int $tabId, string $moduleName): array
    {
        $candidates = RecordVersion_Setup_Model::getRelationCandidates($moduleName);
        $relationLabels = [];
        foreach (RecordVersion_Config_Model::getIncludedRelationIds($tabId) as $relationId) {
            if (isset($candidates[$relationId])) {
                $candidate = $candidates[$relationId];
                $relationLabels[] = RecordVersion_Util_Helper::toStr(vtranslate($candidate['label'], $candidate['related_module']));
            }
        }
        return [
            'tabid' => $tabId,
            'module' => $moduleName,
            'label' => RecordVersion_Util_Helper::toStr(vtranslate($moduleName, $moduleName)),
            'enabled' => RecordVersion_Config_Model::isEnabledTab($tabId),
            'relations' => $relationLabels,
            'relations_text' => empty($relationLabels)
                ? RecordVersion_Util_Helper::toStr(vtranslate('LBL_NONE', 'Settings:RecordVersion'))
                : implode(', ', $relationLabels),
        ];
    }
}
