<?php

/**
 * 通知先の候補を探す（JSON。作成・編集画面と詳細画面の直接編集の入力欄から）
 *
 * GET type=role|group|user&q=絞り込む文字&exclude=選んだ ID（カンマ区切り）
 *   成功：{"items": [{"type", "id", "label", "text"}], "more": まだ候補があるか}
 *
 * 担当者の割り当てのように、役割・共有設定で候補を絞らない（仕様 概要 1-3 No.2）。
 * 一度に返すのは 50 件まで（仕様 動作定義 No.3）。
 */
class NotificationBlock_SearchTargets_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [];
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $type = NotificationBlock_Util_Helper::toStr($request->get('type'));
        if (!in_array($type, NotificationBlock_Target_Model::TYPES, true)) {
            $this->sendError('Unknown type', 400);
        }
        $query = trim(NotificationBlock_Util_Helper::toStr($request->getRaw('q')));
        $exclude = array_values(array_filter(
            array_map('trim', explode(',', NotificationBlock_Util_Helper::toStr($request->get('exclude')))),
            function (string $id): bool {
                return $id !== '';
            }
        ));
        $found = NotificationBlock_Target_Model::searchCandidates($type, $query, $exclude);
        $items = [];
        foreach ($found['items'] as $item) {
            $target = $item + ['disabled' => false];
            $items[] = $item + ['text' => NotificationBlock_Display_Helper::formatTarget($target)];
        }
        return $this->sendSuccess(['items' => $items, 'more' => $found['more']]);
    }
}
