<?php

/**
 * 保存したあとに移った先の画面で、作成時の送信の結果を受け取る（JSON。1 回だけ返して消す）
 *
 * GET
 *   応答：{"message": メッセージ（無ければ空）, "type": "success"|"error"|"info"}
 */
class NotificationBlock_Flash_Api extends Vtiger_Api_Controller
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
        $flash = NotificationBlock_Flash_Model::take();
        return $this->sendSuccess([
            'message' => $flash === null ? '' : $flash['message'],
            'type' => $flash === null ? '' : $flash['type'],
        ]);
    }
}
