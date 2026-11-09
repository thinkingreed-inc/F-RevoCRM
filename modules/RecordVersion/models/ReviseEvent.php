<?php

/**
 * 「改版の前」「改版のあと」のイベントで、ハンドラに渡すデータ
 *
 * ほかの機能（アドオン）は、VTEventsManager で次のイベントにハンドラを登録する。
 * - recordversion.beforerevise：止めたいときは setStopReason() に理由（画面に出すメッセージ）を入れる
 * - recordversion.afterrevise：改版のあとの処理。ここで失敗しても改版は取り消さない（ログに残す）
 */
class RecordVersion_ReviseEvent_Model
{
    private int $recordId;
    private string $moduleName;
    private int $currentVersionNo;
    private int $newVersionNo;
    private string $reason;
    private int $userId;
    private string $stopReason = '';

    public function __construct(int $recordId, string $moduleName, int $currentVersionNo, int $newVersionNo, string $reason, int $userId)
    {
        $this->recordId = $recordId;
        $this->moduleName = $moduleName;
        $this->currentVersionNo = $currentVersionNo;
        $this->newVersionNo = $newVersionNo;
        $this->reason = $reason;
        $this->userId = $userId;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * 改版する前の版番号（過去の版として保存する版）
     */
    public function getCurrentVersionNo(): int
    {
        return $this->currentVersionNo;
    }

    /**
     * 改版したあとの版番号
     */
    public function getNewVersionNo(): int
    {
        return $this->newVersionNo;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * 改版者のユーザー ID
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * 改版を止める（「改版の前」のイベントでだけ意味がある）
     */
    public function setStopReason(string $message): void
    {
        $this->stopReason = $message;
    }

    public function getStopReason(): string
    {
        return $this->stopReason;
    }

    public function isStopped(): bool
    {
        return trim($this->stopReason) !== '';
    }
}
