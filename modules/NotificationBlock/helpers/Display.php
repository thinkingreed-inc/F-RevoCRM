<?php

/**
 * 通知ブロックを画面に出すための処理（詳細・概要・作成・編集画面のテンプレートから呼ぶ）
 *
 * 詳細・作成・編集画面のブロックは、項目のあるブロックだけが並ぶ（modules/Vtiger/models/RecordStructure.php）。
 * 項目を置かない通知ブロックは、テンプレートの中で「通知ブロックの位置」に来たときに出す（確認事項 Q1）。
 */
class NotificationBlock_Display_Helper
{
    /** 並んでいるブロックの最後に出す */
    public const POSITION_END = '__END__';

    /** 見積・受注・請求書・発注の品目明細のブロック（いつもすべてのブロックの下に別に出るので、位置の判定に使わない） */
    private const ITEM_DETAILS_BLOCK = 'LBL_ITEM_DETAILS';

    /**
     * 通知ブロックを、どのブロックの前に出すか（ブロックの名前。最後なら POSITION_END、出さないなら空）
     *
     * @param mixed $recordStructure ブロックの名前 => 項目の一覧（項目の無いブロックは画面に出ないので数えない）
     */
    public static function getPlacement(string $moduleName, mixed $recordStructure): string
    {
        $blockId = NotificationBlock_Config_Model::getBlockId($moduleName);
        if ($blockId <= 0) {
            return '';
        }
        $rendered = [];
        foreach (NotificationBlock_Util_Helper::toArray($recordStructure) as $label => $fields) {
            if ($label === self::ITEM_DETAILS_BLOCK && in_array($moduleName, getInventoryModules(), true)) {
                continue;
            }
            if (is_array($fields) && count($fields) > 0) {
                $rendered[(string) $label] = true;
            }
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT blockid, blocklabel FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence, blockid',
            [getTabid($moduleName)]
        );
        $passed = false;
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            if (NotificationBlock_Util_Helper::toInt($row['blockid'] ?? 0) === $blockId) {
                $passed = true;
                continue;
            }
            $label = NotificationBlock_Util_Helper::toStr($row['blocklabel'] ?? '');
            if ($passed && isset($rendered[$label])) {
                return $label;
            }
        }
        return self::POSITION_END;
    }

    /**
     * 詳細画面・概要画面の通知ブロックに出すもの
     *
     * @return array<string, mixed>
     */
    public static function getDetailData(string $moduleName, mixed $record): array
    {
        $recordId = NotificationBlock_Util_Helper::toInt($record);
        $canEdit = (bool) Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId);
        return [
            'record_id' => $recordId,
            'module_name' => $moduleName,
            'block_id' => NotificationBlock_Config_Model::getBlockId($moduleName),
            'is_hidden' => self::isBlockHidden($moduleName),
            'targets' => NotificationBlock_Target_Model::getTargets($recordId),
            'histories' => NotificationBlock_History_Model::getRecent($recordId),
            'can_edit' => $canEdit,
            'is_sending' => NotificationBlock_History_Model::isSending($recordId),
            'script_url' => self::getScriptUrl(),
        ];
    }

    /**
     * 作成・編集画面（複製を含む）の通知ブロックに出すもの
     *
     * - 作成：空（関連リストからの追加・クイック作成の「すべての項目へ」も同じ）
     * - 編集：登録済みの通知先（「（無効）」のユーザーも出す）
     * - 複製：複製元の有効な通知先だけ（仕様 動作定義 No.12）
     *
     * @return array<string, mixed>
     */
    public static function getEditData(string $moduleName, mixed $record, mixed $isDuplicate): array
    {
        // テンプレートから渡す値（URL の record など）なので、数値にしてから使う
        $recordId = NotificationBlock_Util_Helper::toInt($record);
        $isDuplicate = !empty($isDuplicate);
        $targets = [];
        if ($recordId > 0 && Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
            $targets = $isDuplicate
                ? NotificationBlock_Target_Model::getActiveTargets($recordId)
                : NotificationBlock_Target_Model::getTargets($recordId);
        }
        return [
            'module_name' => $moduleName,
            'targets' => $targets,
            'targets_json' => self::targetsJson($targets),
            'script_url' => self::getScriptUrl(),
        ];
    }

    /**
     * 通知先の表示（種類のラベル付き。例：[役割] 営業部長）
     *
     * @param array{type: string, id: string, label: string, disabled: bool} $target
     */
    public static function formatTarget(array $target): string
    {
        $module = NotificationBlock_Config_Model::MODULE_NAME;
        $text = '[' . NotificationBlock_Util_Helper::toStr(vtranslate('LBL_TARGET_TYPE_' . $target['type'], $module)) . '] ' . $target['label'];
        if ($target['disabled']) {
            $text .= NotificationBlock_Util_Helper::toStr(vtranslate('LBL_DISABLED_SUFFIX', $module));
        }
        return $text;
    }

    /**
     * 通知先を画面の JavaScript に渡す形にする
     *
     * @param array<int, array{type: string, id: string, label: string, disabled: bool}> $targets
     */
    public static function targetsJson(array $targets): string
    {
        $items = [];
        foreach ($targets as $target) {
            $items[] = [
                'type' => $target['type'],
                'id' => $target['id'],
                'label' => $target['label'],
                'disabled' => $target['disabled'],
                'text' => self::formatTarget($target),
            ];
        }
        return (string) json_encode($items, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * 画面の JavaScript に渡す文言（ログイン中の利用者の言語）
     */
    public static function messagesJson(): string
    {
        $keys = [
            'LBL_TARGET_TYPE_role', 'LBL_TARGET_TYPE_group', 'LBL_TARGET_TYPE_user', 'LBL_SEARCH_TARGETS',
            'LBL_NO_CANDIDATES', 'LBL_MORE_CANDIDATES', 'LBL_REMOVE_TARGET', 'LBL_ADD_TARGETS', 'LBL_SAVE', 'LBL_CANCEL',
            'LBL_TARGETS_SAVED', 'LBL_TARGETS_SAVE_FAILED', 'LBL_SEND_TIMEOUT', 'LBL_SEND_ERROR', 'LBL_NO_TARGETS',
            'LBL_COMMENT_TOO_LONG',
        ];
        $messages = [];
        foreach ($keys as $key) {
            $messages[$key] = NotificationBlock_Util_Helper::toStr(vtranslate($key, NotificationBlock_Config_Model::MODULE_NAME));
        }
        return (string) json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * 画面の JavaScript の URL
     *
     * 全画面で読み込むよう登録してあるが、Web サーバーの置き方によって読み込めない場合に備え、
     * 通知ブロックのテンプレートからも読み込む（読み込み済みなら何もしない）。
     */
    public static function getScriptUrl(): string
    {
        return 'layouts/v7/modules/NotificationBlock/resources/NotificationBlock.js';
    }

    /**
     * 項目設定の「ブロックを折りたたむ」
     */
    private static function isBlockHidden(string $moduleName): bool
    {
        $blockId = NotificationBlock_Config_Model::getBlockId($moduleName);
        if ($blockId <= 0) {
            return false;
        }
        $block = Vtiger_Block_Model::getInstance($blockId);
        return $block instanceof Vtiger_Block_Model && (bool) $block->isHidden();
    }
}
