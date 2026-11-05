<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Emails\Models;

use Emails_Record_Model;
use PHPUnit\Framework\TestCase;

$emailsRecordTestRoot = dirname(__DIR__, 4);
require_once $emailsRecordTestRoot . '/includes/runtime/BaseModel.php';
require_once $emailsRecordTestRoot . '/modules/Vtiger/models/Record.php';
require_once $emailsRecordTestRoot . '/modules/Emails/models/Record.php';

/**
 * メール送信時のテキスト版 (AltBody) 本文の組み立て (#1892)
 */
class RecordPlainTextBodyTest extends TestCase
{
    public function testJapaneseBodyIsKeptAsUtf8(): void
    {
        $plainBody = Emails_Record_Model::buildPlainTextBody('<p>テスト送信メールです。</p>', '');

        $this->assertSame("\nテスト送信メールです。", $plainBody);
        $this->assertTrue(mb_check_encoding($plainBody, 'UTF-8'));
    }

    public function testLineBreakTagsAreConvertedToNewlines(): void
    {
        $plainBody = Emails_Record_Model::buildPlainTextBody('一行目<br>二行目<BR />三行目<p>四行目</p>', '');

        $this->assertSame("一行目\n二行目\n三行目\n四行目", $plainBody);
    }

    public function testEntitiesAreDecoded(): void
    {
        $plainBody = Emails_Record_Model::buildPlainTextBody('A &amp; B &quot;C&quot; &#039;D&#039;', '');

        $this->assertSame('A & B "C" \'D\'', $plainBody);
    }

    /**
     * &nbsp; は HTML 版と同じく U+00A0 になる (&nbsp; という文字列を残さない)
     */
    public function testNbspIsDecodedToNoBreakSpace(): void
    {
        $plainBody = Emails_Record_Model::buildPlainTextBody('A&nbsp;B', '');

        $this->assertSame("A\u{00A0}B", $plainBody);
    }

    public function testSignatureIsAppendedAsPlainText(): void
    {
        $plainBody = Emails_Record_Model::buildPlainTextBody('本文', '<p>山田 太郎<br />営業部 &amp; 企画部</p>');

        $this->assertSame("本文\n\n\n山田 太郎\n営業部 & 企画部", $plainBody);
    }

    /**
     * 署名はDBにエスケープされた状態で入っているため、二重エスケープでも1回分だけ戻す (HTML 版の decode_html と同じ)
     */
    public function testEscapedSignatureIsDecoded(): void
    {
        $plainBody = Emails_Record_Model::buildPlainTextBody('本文', '&lt;p&gt;山田 太郎&lt;br /&gt;営業部&lt;/p&gt;');

        $this->assertSame("本文\n\n\n山田 太郎\n営業部", $plainBody);
    }

    public function testNoSignatureIsAppendedWhenSignatureIsEmpty(): void
    {
        $plainBody = Emails_Record_Model::buildPlainTextBody('本文', '');

        $this->assertSame('本文', $plainBody);
    }
}
