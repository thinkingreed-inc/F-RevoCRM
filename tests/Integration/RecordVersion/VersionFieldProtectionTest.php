<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use RecordVersion_Config_Model;
use Tests\Support\RecordVersionTestCase;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';

/**
 * 「版」項目は改版の処理以外から変えられない・作成時は版 1（仕様 動作定義 No.20〜24、設定 4-2）
 */
final class VersionFieldProtectionTest extends RecordVersionTestCase
{
    public function test_版管理はいのモジュールで作成すると版1(): void
    {
        $this->enableVersioning();
        $recordId = $this->createPotential('作成');
        $this->assertSame(1, $this->currentVersion($recordId));
    }

    public function test_版管理いいえのモジュールで作成すると版0のまま(): void
    {
        $this->enableVersioning();
        $this->disableVersioning();
        $recordId = $this->createPotential('いいえで作成');
        $this->assertSame(0, $this->currentVersion($recordId));
    }

    public function test_作成時に版の値を送っても版1から始める_複製(): void
    {
        $this->enableVersioning();
        $recordId = $this->createPotential('複製', [RecordVersion_Config_Model::FIELD_NAME => 5]);
        $this->assertSame(1, $this->currentVersion($recordId), '複製元の版番号は引き継がない');
    }

    public function test_保存で版の値を送っても変わらず更新履歴にも残らない(): void
    {
        $this->enableVersioning();
        $recordId = $this->createPotential('上書き');

        $record = Vtiger_Record_Model::getInstanceById($recordId, self::MODULE);
        $record->set(RecordVersion_Config_Model::FIELD_NAME, 99);
        $record->set('description', '版を書き換えようとする');
        $record->set('mode', 'edit');
        $record->save();

        $this->assertSame(1, $this->currentVersion($recordId), 'エラーにせず元の値のまま保存する');
        $this->assertSame(0, $this->versionChangeLogCount($recordId), '版が変わったとは記録しない');
        $saved = Vtiger_Record_Model::getInstanceById($recordId, self::MODULE);
        $this->assertSame('版を書き換えようとする', decode_html((string) $saved->get('description')), 'ほかの項目は保存される');
    }
}
