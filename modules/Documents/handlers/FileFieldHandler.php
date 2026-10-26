<?php

/**
 * ファイルアップロード項目（uitype 998）に入ったドキュメントの確認と関連付け
 *
 * 保存前: 項目の値が変わったときに、入れようとしているドキュメントを
 *         実行ユーザーが参照できるか確かめる。参照できないドキュメントの ID を
 *         送り込んで、他人のファイルを自分のレコードから見られるようにするのを防ぐ。
 * 保存後: 項目に入ったドキュメントをレコードに関連付ける（関連ドキュメントに出す）。
 *         差し替え前のドキュメントは関連付けを残す（履歴として辿れるようにする）。
 */
require_once 'modules/Documents/utils/FieldFile.php';

/**
 * @phpstan-import-type FileFieldInfo from Documents_FieldFile
 */
class FileFieldHandler extends VTEventHandler
{
    /**
     * @param mixed $eventName vtiger.entity.beforesave / vtiger.entity.aftersave
     * @param mixed $entityData VTEntityData
     */
    public function handleEvent($eventName, $entityData): void
    {
        if (!$entityData instanceof VTEntityData) {
            return;
        }
        $moduleName = (string) $entityData->getModuleName();
        $fields = Documents_FieldFile::getFileFields($moduleName);
        if (empty($fields)) {
            return;
        }

        if ($eventName === 'vtiger.entity.beforesave') {
            $this->assertAttachableDocuments($entityData, $fields, $moduleName);
        } elseif ($eventName === 'vtiger.entity.aftersave') {
            $this->linkDocuments($entityData, $fields);
        }
    }

    /**
     * 値が変わった項目のドキュメントを参照できるか確かめる
     *
     * @param array<string, FileFieldInfo> $fields
     * @throws Exception 参照できないドキュメントが入っている場合
     */
    private function assertAttachableDocuments(VTEntityData $entityData, array $fields, string $moduleName): void
    {
        /** @var mixed $isNew VTEntityData の phpdoc は戻り値の型を表していないため mixed として受ける */
        $isNew = $entityData->isNew();
        $recordId = $isNew ? 0 : Documents_FieldFile::normalizeDocumentId($entityData->getId());
        foreach ($fields as $fieldName => $fieldInfo) {
            $notesId = Documents_FieldFile::normalizeDocumentId($this->getEntityValue($entityData, $fieldName));
            if ($notesId <= 0) {
                continue;
            }
            // 変わっていない値は確認しない（フォルダの権限が後から変わっても保存できるようにする）
            if ($recordId > 0 && Documents_FieldFile::getStoredDocumentId($recordId, $fieldInfo, $moduleName) === $notesId) {
                continue;
            }
            if (!Documents_FieldFile::canAttachDocument($notesId)) {
                throw new Exception(vtranslate('LBL_FILE_FIELD_DOCUMENT_DENIED', 'Documents'));
            }
        }
    }

    /**
     * 項目に入ったドキュメントをレコードに関連付ける
     *
     * @param array<string, FileFieldInfo> $fields
     */
    private function linkDocuments(VTEntityData $entityData, array $fields): void
    {
        $recordId = Documents_FieldFile::normalizeDocumentId($entityData->getId());
        foreach (array_keys($fields) as $fieldName) {
            $notesId = Documents_FieldFile::normalizeDocumentId($this->getEntityValue($entityData, $fieldName));
            if ($notesId > 0) {
                Documents_FieldFile::linkDocument($recordId, $notesId);
            }
        }
    }

    /**
     * 保存する値（column_fields）から項目の値を取り出す
     *
     * column_fields は配列のことも TrackableObject（ArrayAccess）のこともある。
     */
    private function getEntityValue(VTEntityData $entityData, string $fieldName): mixed
    {
        /** @var mixed $data */
        $data = $entityData->getData();
        if (is_array($data) || $data instanceof ArrayAccess) {
            return isset($data[$fieldName]) ? $data[$fieldName] : null;
        }
        return null;
    }
}
