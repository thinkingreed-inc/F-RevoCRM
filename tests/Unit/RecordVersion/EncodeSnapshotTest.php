<?php

declare(strict_types=1);

namespace Tests\Unit\RecordVersion;

use JsonException;
use PHPUnit\Framework\TestCase;
use RecordVersion_Version_Model;

require_once dirname(__DIR__, 3) . '/modules/RecordVersion/helpers/Util.php';
require_once dirname(__DIR__, 3) . '/modules/RecordVersion/models/Version.php';

/**
 * 過去の版の内容を JSON にする（確認事項 Q35）
 */
final class EncodeSnapshotTest extends TestCase
{
    public function test_日本語はそのまま残す(): void
    {
        $this->assertSame('{"label":"版"}', RecordVersion_Version_Model::encodeSnapshot(['label' => '版']));
    }

    public function test_不正なUTF8の文字だけを置き換えてほかの値は残す(): void
    {
        $json = RecordVersion_Version_Model::encodeSnapshot(['value' => "ab\xB1cd", 'other' => 'ok']);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame("ab\u{FFFD}cd", $decoded['value'], '値が null にならず、不正な文字だけが置き換わる');
        $this->assertSame('ok', $decoded['other']);
    }

    public function test_JSONにできない値があれば例外にする(): void
    {
        $this->expectException(JsonException::class);
        RecordVersion_Version_Model::encodeSnapshot(['value' => NAN]);
    }
}
