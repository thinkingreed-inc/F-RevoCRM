<?php

/**
 * 版管理の結合テストで使う「改版の前」「改版のあと」のイベントハンドラ
 */
require_once 'include/events/VTEventHandler.inc';

/**
 * 改版を止める（ほかの機能が「手続き中は改版できない」などの条件を足す場合の例）
 */
class RecordVersionTestStopHandler extends VTEventHandler
{
    public const MESSAGE = 'SPECTEST 手続き中のため改版できません';

    /**
     * @param string $eventName
     */
    public function handleEvent($eventName, mixed $data): void
    {
        if ($data instanceof RecordVersion_ReviseEvent_Model) {
            $data->setStopReason(self::MESSAGE);
        }
    }
}

/**
 * 改版のあとの処理で失敗する
 */
class RecordVersionTestFailingAfterHandler extends VTEventHandler
{
    /**
     * @param string $eventName
     */
    public function handleEvent($eventName, mixed $data): void
    {
        throw new Exception('SPECTEST afterrevise failure');
    }
}
