<?php

declare(strict_types=1);

namespace Tests\Unit\RecordVersion;

use PHPUnit\Framework\TestCase;
use RecordVersion_ReviseEvent_Model;

require_once dirname(__DIR__, 3) . '/modules/RecordVersion/models/ReviseEvent.php';

/**
 * 「改版の前」「改版のあと」のイベントで渡すデータ（仕様 動作定義 No.25）
 */
final class ReviseEventTest extends TestCase
{
    public function test_止める理由を入れなければ止めない(): void
    {
        $event = new RecordVersion_ReviseEvent_Model(10, 'Quotes', 2, 3, '値引きの見直し', 5);
        $this->assertFalse($event->isStopped());
        $this->assertSame(10, $event->getRecordId());
        $this->assertSame('Quotes', $event->getModuleName());
        $this->assertSame(2, $event->getCurrentVersionNo());
        $this->assertSame(3, $event->getNewVersionNo());
        $this->assertSame('値引きの見直し', $event->getReason());
        $this->assertSame(5, $event->getUserId());
    }

    public function test_止める理由を入れると止め理由をそのまま返す(): void
    {
        $event = new RecordVersion_ReviseEvent_Model(10, 'Quotes', 2, 3, '理由', 5);
        $event->setStopReason('手続き中は改版できません');
        $this->assertTrue($event->isStopped());
        $this->assertSame('手続き中は改版できません', $event->getStopReason());
    }

    public function test_空白だけの理由では止めない(): void
    {
        $event = new RecordVersion_ReviseEvent_Model(10, 'Quotes', 2, 3, '理由', 5);
        $event->setStopReason('  ');
        $this->assertFalse($event->isStopped());
    }
}
