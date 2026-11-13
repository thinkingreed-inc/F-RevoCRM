<?php

/**
 * 保存したあとに移った先の画面で、作成時の送信の結果を 1 回だけ出す
 *
 * 作成画面の保存は、保存のあとに別の画面へ移る（関連リストから追加したときは親レコードの詳細画面など）。
 * 結果をログイン中の利用者のセッションに残し、全画面で読み込む JavaScript が移った先で受け取って出す。
 * 毎回問い合わせなくて済むよう、結果があるときだけ印のクッキーを付ける。
 */
class NotificationBlock_Flash_Model
{
    public const SESSION_KEY = 'notificationblock_flash';
    public const COOKIE_NAME = 'notificationblock_flash';

    public const TYPE_SUCCESS = 'success';
    public const TYPE_ERROR = 'error';
    public const TYPE_INFO = 'info';

    public static function set(string $message, string $type): void
    {
        if ($message === '') {
            return;
        }
        $_SESSION[self::SESSION_KEY] = ['message' => $message, 'type' => $type];
        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, '1', ['path' => '/', 'samesite' => 'Lax']);
        }
    }

    /**
     * 残っている結果を取り出して消す
     *
     * @return array{message: string, type: string}|null
     */
    public static function take(): ?array
    {
        $flash = $_SESSION[self::SESSION_KEY] ?? null;
        unset($_SESSION[self::SESSION_KEY]);
        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
        }
        if (!is_array($flash)) {
            return null;
        }
        return [
            'message' => NotificationBlock_Util_Helper::toStr($flash['message'] ?? ''),
            'type' => NotificationBlock_Util_Helper::toStr($flash['type'] ?? self::TYPE_INFO),
        ];
    }

    /**
     * 送信の結果の種類から、メッセージの見た目（成功・失敗）を決める
     */
    public static function typeForCode(string $code): string
    {
        switch ($code) {
            case NotificationBlock_Notifier_Model::CODE_SENT:
                return self::TYPE_SUCCESS;
            case NotificationBlock_Notifier_Model::CODE_NO_RECIPIENTS:
                return self::TYPE_INFO;
        }
        return self::TYPE_ERROR;
    }
}
