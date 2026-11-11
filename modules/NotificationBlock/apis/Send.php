<?php

/**
 * 送信ダイアログの「送信」（JSON）
 *
 * POST record=..&comment=..
 *   応答：{"result": 結果の種類, "sent": 送ろうとして送信履歴に記録したか, "message": 画面に出すメッセージ,
 *          "notify_type": "success"|"error"|"info"}
 *
 * 送れない状態（宛先 0 人・編集権限なし・ごみ箱・通知ブロックなし・送信中）でも、
 * 画面がメッセージを出してダイアログを閉じられるよう、エラーにはせずに結果として返す。
 */
class NotificationBlock_Send_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * メールを送り送信履歴を書くので、POST と CSRF トークンも確かめる
     */
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    /**
     * 権限（レコードの編集権限）は NotificationBlock_Notifier_Model::send() で確かめる
     *
     * @return array<int, array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [];
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $result = NotificationBlock_Notifier_Model::send(NotificationBlock_Util_Helper::toInt($request->get('record')), [
            'method' => NotificationBlock_History_Model::METHOD_MANUAL,
            'comment' => NotificationBlock_Util_Helper::toStr($request->getRaw('comment')),
        ]);
        return $this->sendSuccess([
            'result' => $result['code'],
            'sent' => $result['success'],
            'message' => $result['message'],
            'notify_type' => NotificationBlock_Flash_Model::typeForCode($result['code']),
        ]);
    }
}
