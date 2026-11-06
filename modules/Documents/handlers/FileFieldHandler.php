<?php

/**
 * ファイルアップロード項目（uitype 998）に入ったドキュメントの確認と関連付け
 *
 * 保存前: 項目の値が一時ファイル（Documents_FieldFileDraft）なら、ドキュメントにして
 *         値をそのドキュメントIDに置き換える。項目に既存のドキュメントがあれば、
 *         そのドキュメントのファイルを差し替えて版を足す（編集できなければ新しく作る）。
 *         ファイルが変わったときだけ版が増え、手書きの途中の内容は版に残らない。
 *         値がドキュメントIDで変わったときは、実行ユーザーが参照できるか確かめる。
 *         参照できないドキュメントの ID を送り込んで、他人のファイルを
 *         自分のレコードから見られるようにするのを防ぐ。
 *         手書きのドキュメントは、保存後にファイル名を「項目名_レコードID_項目ID_保存日時.png」にする
 *         （新しいレコードは保存するまで ID が無いため、保存後に行う）。
 * 保存後: 項目に入ったドキュメントをレコードに関連付ける（関連ドキュメントに出す）。
 *         差し替え前のドキュメントは関連付けを残す（履歴として辿れるようにする）。
 */
require_once 'modules/Documents/utils/FieldFile.php';
require_once 'modules/Documents/utils/FieldFileDraft.php';

/**
 * @phpstan-import-type FileFieldInfo from Documents_FieldFile
 */
class FileFieldHandler extends VTEventHandler
{
    /**
     * 保存前に作った・差し替えた手書きのドキュメント（保存後にファイル名を付ける）
     *
     * イベントごとにハンドラが作り直されるため、保存前と保存後で同じ VTEntityData を鍵にして渡す。
     *
     * @var array<int, list<array{notesId: int, fieldInfo: FileFieldInfo}>>
     */
    private static array $handwritingDocuments = [];

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
            $this->saveDrafts($entityData, $fields, $moduleName);
            $this->assertAttachableDocuments($entityData, $fields, $moduleName);
        } elseif ($eventName === 'vtiger.entity.aftersave') {
            $this->nameHandwritingDocuments($entityData, $moduleName);
            $this->linkDocuments($entityData, $fields);
        }
    }

    /**
     * 一時ファイルの入った項目をドキュメントにし、値をドキュメントIDに置き換える
     *
     * @param array<string, FileFieldInfo> $fields
     * @throws Exception 実行ユーザーがこの項目に置いた一時ファイルでない（期限切れを含む）場合
     */
    private function saveDrafts(VTEntityData $entityData, array $fields, string $moduleName): void
    {
        /** @var mixed $isNew VTEntityData の phpdoc は戻り値の型を表していないため mixed として受ける */
        $isNew = $entityData->isNew();
        $recordId = $isNew ? 0 : Documents_FieldFile::normalizeDocumentId($entityData->getId());
        $userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
        foreach ($fields as $fieldName => $fieldInfo) {
            $value = $this->getEntityValue($entityData, $fieldName);
            if (!Documents_FieldFileDraft::isDraftValue($value)) {
                continue;
            }
            $draft = Documents_FieldFileDraft::get($value, $userId, $fieldInfo['fieldid']);
            if ($draft === null) {
                throw new Exception(vtranslate('LBL_FILE_FIELD_DRAFT_INVALID', 'Documents'));
            }
            $file = Documents_FieldFileDraft::toFileEntry($draft);
            $storedId = Documents_FieldFile::getStoredDocumentId($recordId, $fieldInfo, $moduleName);
            if (Documents_FieldFile::canReplaceDocument($storedId)) {
                Documents_FieldFile::replaceDocumentFile($storedId, $file);
                $notesId = $storedId;
            } else {
                $notesId = Documents_FieldFile::createDocument($file, $fieldInfo);
            }
            $entityData->set($fieldName, $notesId);
            Documents_FieldFileDraft::delete($value);
            if ($fieldInfo['handwriting']) {
                self::$handwritingDocuments[spl_object_id($entityData)][] = ['notesId' => $notesId, 'fieldInfo' => $fieldInfo];
            }
        }
    }

    /**
     * 保存前に作った・差し替えた手書きのドキュメントに、レコードIDを含むファイル名を付ける
     */
    private function nameHandwritingDocuments(VTEntityData $entityData, string $moduleName): void
    {
        $key = spl_object_id($entityData);
        $documents = self::$handwritingDocuments[$key] ?? [];
        unset(self::$handwritingDocuments[$key]);
        if (empty($documents)) {
            return;
        }
        $recordId = Documents_FieldFile::normalizeDocumentId($entityData->getId());
        // ファイル名の日時は実行ユーザーのタイムゾーンで付ける（画面に出る日時と合わせる）
        $timeZone = (string) Users_Record_Model::getCurrentUserModel()->get('time_zone');
        if ($timeZone === '' || !in_array($timeZone, timezone_identifiers_list(), true)) {
            $timeZone = date_default_timezone_get();
        }
        $savedAt = (new DateTime('now', new DateTimeZone($timeZone)))->format('Y-m-d H:i:s');
        foreach ($documents as $document) {
            $fieldInfo = $document['fieldInfo'];
            Documents_FieldFile::renameDocumentFile($document['notesId'], Documents_FieldFile::buildHandwritingFileName(
                (string) vtranslate($fieldInfo['fieldlabel'], $moduleName),
                $recordId,
                $fieldInfo['fieldid'],
                $savedAt
            ));
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
