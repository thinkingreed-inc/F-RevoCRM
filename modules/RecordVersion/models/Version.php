<?php

/**
 * 過去の版 1 件（vtiger_recordversion の 1 行）
 */
class RecordVersion_Version_Model
{
    private int $id;
    private int $recordId;
    private string $moduleName;
    private int $versionNo;
    private string $reason;
    private int $revisedById;
    private string $revisedAt;
    private ?string $snapshotJson;

    /** @var array<string, mixed>|null */
    private ?array $snapshot = null;

    /**
     * @param array<string, mixed> $row vtiger_recordversion の 1 行（snapshot は無くてもよい）
     */
    public function __construct(array $row)
    {
        $this->id = RecordVersion_Util_Helper::toInt($row['versionid'] ?? 0);
        $this->recordId = RecordVersion_Util_Helper::toInt($row['crmid'] ?? 0);
        $this->moduleName = RecordVersion_Util_Helper::toStr($row['setype'] ?? '');
        $this->versionNo = RecordVersion_Util_Helper::toInt($row['version_no'] ?? 0);
        $this->reason = RecordVersion_Util_Helper::toStr($row['reason'] ?? '');
        $this->revisedById = RecordVersion_Util_Helper::toInt($row['revised_by'] ?? 0);
        $this->revisedAt = RecordVersion_Util_Helper::toStr($row['revised_at'] ?? '');
        $this->snapshotJson = array_key_exists('snapshot', $row) ? RecordVersion_Util_Helper::toStr($row['snapshot']) : null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getVersionNo(): int
    {
        return $this->versionNo;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getRevisedById(): int
    {
        return $this->revisedById;
    }

    /**
     * 改版した日時（DB の値。表示するときは getRevisedAtDisplay() を使う）
     */
    public function getRevisedAt(): string
    {
        return $this->revisedAt;
    }

    /**
     * 見ている利用者の書式・タイムゾーンで出した改版日時
     */
    public function getRevisedAtDisplay(): string
    {
        return RecordVersion_Util_Helper::toStr(Vtiger_Datetime_UIType::getDisplayDateTimeValue($this->revisedAt));
    }

    /**
     * 改版者の名前（改版した時点の名前。ユーザーを削除・改名しても当時の名前で出す）
     */
    public function getRevisedByName(): string
    {
        $name = RecordVersion_Util_Helper::toStr($this->getSnapshot()['revised_by_name'] ?? '');
        if ($name !== '') {
            return $name;
        }
        return RecordVersion_Util_Helper::toStr(getUserFullName($this->revisedById));
    }

    /**
     * 保存した内容（JSON を配列にしたもの）
     *
     * @return array<string, mixed>
     */
    public function getSnapshot(): array
    {
        if ($this->snapshot === null) {
            if ($this->snapshotJson === null) {
                $this->snapshotJson = self::loadSnapshotJson($this->id);
            }
            $this->snapshot = RecordVersion_Util_Helper::toArray(json_decode($this->snapshotJson, true));
        }
        return $this->snapshot;
    }

    /**
     * レコードの過去の版を新しい順に返す（内容の JSON は必要になるまで読まない）
     *
     * @return self[]
     */
    public static function getAllForRecord(int $recordId): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT versionid, crmid, setype, version_no, reason, revised_by, revised_at
             FROM ' . RecordVersion_Config_Model::VERSIONS_TABLE . ' WHERE crmid = ? ORDER BY version_no DESC',
            [$recordId]
        );
        $versions = [];
        foreach (RecordVersion_Util_Helper::fetchRawRows($result) as $row) {
            $versions[] = new self($row);
        }
        return $versions;
    }

    public static function getByNumber(int $recordId, int $versionNo): ?self
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT * FROM ' . RecordVersion_Config_Model::VERSIONS_TABLE . ' WHERE crmid = ? AND version_no = ?',
            [$recordId, $versionNo]
        );
        $rows = RecordVersion_Util_Helper::fetchRawRows($result);
        return empty($rows) ? null : new self($rows[0]);
    }

    /**
     * 隣の過去の版の番号（無ければ null）
     *
     * @return array{0: int|null, 1: int|null} [前の版, 次の版]
     */
    public static function getAdjacentVersionNos(int $recordId, int $versionNo): array
    {
        $db = PearDatabase::getInstance();
        $table = RecordVersion_Config_Model::VERSIONS_TABLE;
        $prev = RecordVersion_Util_Helper::fetchRawRows(
            $db->pquery("SELECT MAX(version_no) AS no FROM {$table} WHERE crmid = ? AND version_no < ?", [$recordId, $versionNo])
        );
        $next = RecordVersion_Util_Helper::fetchRawRows(
            $db->pquery("SELECT MIN(version_no) AS no FROM {$table} WHERE crmid = ? AND version_no > ?", [$recordId, $versionNo])
        );
        $prevNo = $prev[0]['no'] ?? null;
        $nextNo = $next[0]['no'] ?? null;
        return [
            $prevNo === null ? null : RecordVersion_Util_Helper::toInt($prevNo),
            $nextNo === null ? null : RecordVersion_Util_Helper::toInt($nextNo),
        ];
    }

    /**
     * 過去の版の内容を JSON にする
     *
     * @param array<string, mixed> $snapshot
     */
    public static function encodeSnapshot(array $snapshot): string
    {
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false) {
            throw new Exception('Could not encode the snapshot');
        }
        return $json;
    }

    /**
     * 過去の版を 1 件保存する（改版の処理のトランザクションの中で呼ぶ）
     *
     * @param string $json encodeSnapshot() で JSON にした内容
     */
    public static function insert(int $recordId, string $moduleName, int $versionNo, string $reason, int $revisedBy, string $revisedAt, string $json): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'INSERT INTO ' . RecordVersion_Config_Model::VERSIONS_TABLE
            . ' (crmid, setype, version_no, reason, revised_by, revised_at, snapshot) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$recordId, $moduleName, $versionNo, $reason, $revisedBy, $revisedAt, $json]
        );
    }

    /**
     * ごみ箱から完全に削除したレコードの過去の版を消す
     *
     * @param array<int|string> $recordIds
     */
    public static function deleteByRecordIds(array $recordIds): void
    {
        $ids = array_values(array_filter(array_map([RecordVersion_Util_Helper::class, 'toInt'], $recordIds)));
        if (empty($ids)) {
            return;
        }
        $db = PearDatabase::getInstance();
        $db->pquery(
            'DELETE FROM ' . RecordVersion_Config_Model::VERSIONS_TABLE . ' WHERE crmid IN (' . generateQuestionMarks($ids) . ')',
            $ids
        );
    }

    private static function loadSnapshotJson(int $versionId): string
    {
        $db = PearDatabase::getInstance();
        $rows = RecordVersion_Util_Helper::fetchRawRows($db->pquery(
            'SELECT snapshot FROM ' . RecordVersion_Config_Model::VERSIONS_TABLE . ' WHERE versionid = ?',
            [$versionId]
        ));
        return empty($rows) ? '' : RecordVersion_Util_Helper::toStr($rows[0]['snapshot'] ?? '');
    }
}
