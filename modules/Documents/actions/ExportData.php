<?php

/**
 * ドキュメントのエクスポート
 *
 * 共通のエクスポート処理はフォルダ権限を見ないため、クエリに条件を足す
 * 入口（Documents_Module_Model::getExportQuery）を通るようにする。
 */
class Documents_ExportData_Action extends Vtiger_ExportData_Action
{
    /**
     * モジュール側でクエリに手を入れるモジュールにドキュメントを加える
     *
     * @return array<int,string>
     */
    public function getAdditionalQueryModules()
    {
        return array_merge(parent::getAdditionalQueryModules(), ['Documents']);
    }
}
